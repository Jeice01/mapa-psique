<?php

declare(strict_types=1);

namespace App\Modules\Maps;

final class MapPdfExporter
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN_X = 48.0;
    private const TOP_Y = 780.0;
    private const BOTTOM_Y = 72.0;
    private const LINE_HEIGHT = 14.0;

    private const CANVAS_FIELDS = [
        'main_demand' => 'Demanda principal',
        'current_context' => 'Contexto atual',
        'emotional_history' => 'História emocional',
        'recurring_patterns' => 'Padrões recorrentes',
        'core_beliefs' => 'Crenças centrais',
        'defense_strategies' => 'Estratégias de defesa',
        'internal_resources' => 'Recursos internos',
        'reflective_hypotheses' => 'Hipóteses reflexivas',
        'next_steps' => 'Próximos passos',
    ];

    /** @var list<list<string>> */
    private array $pages = [[]];

    private float $cursorY = self::TOP_Y;

    /**
     * @param array<string, mixed> $map
     */
    public function export(array $map): string
    {
        $this->pages = [[]];
        $this->cursorY = self::TOP_Y;

        $exportSubtitle = $this->optionalText($map['pdf_export_subtitle'] ?? null, 'Exportação do Mapa');

        $this->writeTitle('Mapa da Psiquê', 20);
        $this->writeLine($exportSubtitle, 13);
        $this->writeLine('Exportado em: ' . date('d/m/Y H:i'));
        $this->writeLine('Identificador do mapa: ' . $this->value($map['id'] ?? null), 9);

        foreach ($this->metadataLines($map['pdf_metadata'] ?? null) as $metadataLine) {
            $this->writeLine($metadataLine, 9);
        }

        $this->space(4);
        $this->addLine(self::MARGIN_X, $this->cursorY, self::PAGE_WIDTH - self::MARGIN_X, $this->cursorY);
        $this->space(12);

        $this->writeSection('Dados básicos do mapa');
        $this->writeKeyValue('Título', $this->value($map['title'] ?? null));
        $this->writeKeyValue('Paciente', $this->value($map['patient_name'] ?? null));
        $this->writeKeyValue('Data de criação', $this->formatDate($map['created_at'] ?? null));
        $this->writeKeyValue('Última atualização', $this->formatDate($map['updated_at'] ?? null));
        $this->space(8);

        $this->writeSection($this->optionalText($map['pdf_canvas_section_title'] ?? null, 'Canvas atual'));
        $canvas = $this->normalizeCanvas($map['canvas_json'] ?? null);

        foreach (self::CANVAS_FIELDS as $key => $label) {
            $this->writeField($label, $canvas[$key] ?? '');
        }

        return $this->buildPdf();
    }

    private function writeTitle(string $text, int $fontSize): void
    {
        $this->ensureSpace(self::LINE_HEIGHT + 4);
        $this->addText($text, self::MARGIN_X, $this->cursorY, $fontSize, true);
        $this->cursorY -= self::LINE_HEIGHT + 5;
    }

    private function writeSection(string $title): void
    {
        $this->ensureSpace(self::LINE_HEIGHT + 14);
        $this->space(4);
        $this->addText($title, self::MARGIN_X, $this->cursorY, 12, true);
        $this->cursorY -= self::LINE_HEIGHT;
        $this->addLine(self::MARGIN_X, $this->cursorY + 4, self::PAGE_WIDTH - self::MARGIN_X, $this->cursorY + 4);
        $this->space(8);
    }

    private function writeKeyValue(string $label, string $value): void
    {
        $this->writeLine($label . ': ' . $value, 10);
    }

    private function writeField(string $label, string $value): void
    {
        $this->ensureSpace(self::LINE_HEIGHT * 3);

        $this->addText('• ' . $label, self::MARGIN_X, $this->cursorY, 10, true);
        $this->cursorY -= self::LINE_HEIGHT + 2;

        $text = trim($value) === '' ? 'Não preenchido' : $value;
        $contentX = self::MARGIN_X + 14;
        $contentWidth = self::PAGE_WIDTH - self::MARGIN_X - $contentX;
        
        foreach ($this->wrapText($text, 10, $contentWidth) as $line) {
            $this->writeLine($line, 10, $contentX);
        }

        $this->space(8);
    }

    private function writeLine(string $text, int $fontSize = 10, float $x = self::MARGIN_X): void
    {
        foreach (preg_split('/\R/u', $text) ?: [$text] as $line) {
            $this->ensureSpace(self::LINE_HEIGHT);
            $this->addText($line === '' ? ' ' : $line, $x, $this->cursorY, $fontSize);
            $this->cursorY -= self::LINE_HEIGHT;
        }
    }

    private function space(float $height): void
    {
        $this->ensureSpace($height);
        $this->cursorY -= $height;
    }

    private function ensureSpace(float $height): void
    {
        if (($this->cursorY - $height) < self::BOTTOM_Y) {
            $this->pages[] = [];
            $this->cursorY = self::TOP_Y;

            $this->addText('Mapa da Psiquê — Relatório exportado', self::MARGIN_X, $this->cursorY, 10, true);
            $this->cursorY -= self::LINE_HEIGHT;
            $this->addLine(self::MARGIN_X, $this->cursorY + 4, self::PAGE_WIDTH - self::MARGIN_X, $this->cursorY + 4);
            $this->space(10);
        }
    }

    private function addText(string $text, float $x, float $y, int $fontSize, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->pages[array_key_last($this->pages)][] = sprintf(
            'BT /%s %d Tf %.2F %.2F Td %s Tj ET',
            $font,
            $fontSize,
            $x,
            $y,
            $this->pdfText($text)
        );
    }

    private function addLine(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->pages[array_key_last($this->pages)][] = sprintf(
            '0.75 w %.2F %.2F m %.2F %.2F l S',
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $fontSize, float $maxWidth): array
    {
        $lines = [];
        $paragraphs = preg_split('/\R/u', $text) ?: [$text];
        $maxChars = max(20, (int) floor($maxWidth / ($fontSize * 0.52)));

        foreach ($paragraphs as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph)) ?: [];
            $line = '';

            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }

                $candidate = $line === '' ? $word : $line . ' ' . $word;

                if ($this->textLength($candidate) <= $maxChars) {
                    $line = $candidate;
                    continue;
                }

                if ($line !== '') {
                    $lines[] = $line;
                }

                while ($this->textLength($word) > $maxChars) {
                    $lines[] = $this->textSlice($word, 0, $maxChars);
                    $word = $this->textSlice($word, $maxChars);
                }

                $line = $word;
            }

            $lines[] = $line === '' ? ' ' : $line;
        }

        return $lines;
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    private function normalizeCanvas(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($value)) {
            $value = [];
        }

        $canvas = [];

        foreach (self::CANVAS_FIELDS as $key => $_label) {
            $canvas[$key] = is_string($value[$key] ?? null) ? (string) $value[$key] : '';
        }

        return $canvas;
    }

    private function optionalText(mixed $value, string $fallback): string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? $fallback : $text;
    }

    /**
     * @return list<string>
     */
    private function metadataLines(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $lines = [];

        foreach ($value as $label => $content) {
            $labelText = trim((string) $label);
            $contentText = trim((string) ($content ?? ''));

            if ($labelText === '' || $contentText === '') {
                continue;
            }

            $lines[] = $labelText . ': ' . $contentText;
        }

        return $lines;
    }



    private function value(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? 'Não informado' : $text;
    }

    private function formatDate(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return 'Não informado';
        }

        $timestamp = strtotime($text);

        return $timestamp === false ? $text : date('d/m/Y H:i', $timestamp);
    }

    private function buildPdf(): string
    {
        $pageCount = count($this->pages);

        foreach ($this->pages as $index => $_commands) {
            $pageNumber = $index + 1;

            $this->pages[$index][] = sprintf(
                '0.50 w %.2F %.2F m %.2F %.2F l S',
                self::MARGIN_X,
                58.0,
                self::PAGE_WIDTH - self::MARGIN_X,
                58.0,
            );

            $this->pages[$index][] = sprintf(
                'BT /F1 8 Tf %.2F %.2F Td %s Tj ET',
                self::MARGIN_X,
                42.0,
                $this->pdfText('Gerado pelo Mapa da Psiquê. Documento confidencial. Uso restrito ao profissional autorizado.')
            );

            $this->pages[$index][] = sprintf(
               'BT /F1 8 Tf %.2F %.2F Td %s Tj ET',
                self::PAGE_WIDTH - 105,
                42.0,
                $this->pdfText(sprintf('Página %d de %d', $pageNumber, $pageCount))
            );
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $kids = [];
        $nextId = 5;

        foreach ($this->pages as $commands) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $stream = implode("\n", $commands);
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentId
            );
            $kids[] = "{$pageId} 0 R";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text) ?? $text;
        $encoded = function_exists('iconv') ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) : false;

        if ($encoded === false) {
            $encoded = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        $encoded = str_replace(
            ["\\", '(', ')', "\r", "\n"],
            ["\\\\", "\\(", "\\)", ' ', ' '],
            $encoded
        );

        return '(' . $encoded . ')';
    }

    private function textLength(string $text): int
    {
        preg_match_all('/./us', $text, $matches);

        return count($matches[0]);
    }

    private function textSlice(string $text, int $offset, ?int $length = null): string
    {
        preg_match_all('/./us', $text, $matches);
        $slice = array_slice($matches[0], $offset, $length);

        return implode('', $slice);
    }
}
