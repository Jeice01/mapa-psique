<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

final class KnowledgeRetriever
{
    private const MAX_EXCERPTS = 6;
    private const MAX_EXCERPT_LENGTH = 1200;
    private const MAX_TOTAL_LENGTH = 6500;

    /** @param array<string,mixed> $map */
    public static function relevantExcerpts(array $map): string
    {
        $query = self::queryFromMap($map);
        if ($query === []) {
            return '';
        }

        $ranked = [];
        foreach (self::chunks() as $chunk) {
            $normalized = self::normalize($chunk['text']);
            $score = 0;
            foreach ($query as $term => $weight) {
                $occurrences = substr_count($normalized, $term);
                if ($occurrences > 0) {
                    $score += min($occurrences, 4) * $weight;
                }
            }

            if ($score > 0) {
                $ranked[] = $chunk + ['score' => $score];
            }
        }

        usort($ranked, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return $left['index'] <=> $right['index'];
            }
            return $right['score'] <=> $left['score'];
        });

        $selected = [];
        $total = 0;
        foreach ($ranked as $chunk) {
            $text = trim((string) $chunk['text']);
            if (strlen($text) > self::MAX_EXCERPT_LENGTH) {
                preg_match('/^.{0,' . self::MAX_EXCERPT_LENGTH . '}/us', $text, $match);
                $text = rtrim((string) ($match[0] ?? '')) . ' [...]';
            }
            if ($total + strlen($text) > self::MAX_TOTAL_LENGTH) {
                continue;
            }

            $selected[] = sprintf(
                "[Fonte: material-didatico-2026 | tipo: %s | trecho: %d]\n%s",
                $chunk['type'],
                $chunk['index'],
                $text
            );
            $total += strlen($text);
            if (count($selected) >= self::MAX_EXCERPTS) {
                break;
            }
        }

        return implode("\n\n", $selected);
    }

    /** @param array<string,mixed> $map @return array<string,int> */
    private static function queryFromMap(array $map): array
    {
        $raw = $map['canvas_json'] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $canvas = is_array($decoded) ? $decoded : [];
        } else {
            $canvas = is_array($raw) ? $raw : [];
        }

        $terms = [];
        $add = static function (string $text, int $weight = 1) use (&$terms): void {
            foreach (preg_split('/[^\p{L}\p{N}]+/u', self::normalize($text)) ?: [] as $term) {
                if (strlen($term) < 4 || in_array($term, self::stopWords(), true)) {
                    continue;
                }
                $terms[$term] = max($terms[$term] ?? 0, $weight);
            }
        };

        foreach (['main_demand', 'current_context', 'emotional_history', 'recurring_patterns', 'core_beliefs', 'defense_strategies', 'internal_resources', 'reflective_hypotheses', 'next_steps'] as $field) {
            $add((string) ($canvas[$field] ?? ''), 1);
        }

        $reading = is_array($canvas['structured_reading'] ?? null) ? $canvas['structured_reading'] : [];
        $add((string) ($reading['summary'] ?? ''), 2);
        $add((string) (($reading['review']['professional_notes'] ?? '')), 3);

        foreach (is_array($reading['quadrants'] ?? null) ? $reading['quadrants'] : [] as $quadrant => $description) {
            if (trim((string) $description) !== '') {
                $add(str_replace('_', ' ', (string) $quadrant), 4);
                $add((string) $description, 2);
            }
        }

        foreach (is_array($reading['elements'] ?? null) ? $reading['elements'] : [] as $element) {
            if (!is_array($element)) {
                continue;
            }
            $add((string) ($element['label'] ?? ''), 3);
            $add((string) ($element['quadrant'] ?? ''), 3);
            $add((string) ($element['notes'] ?? ''), 2);
        }

        foreach (is_array($reading['arrows'] ?? null) ? $reading['arrows'] : [] as $arrow) {
            if (!is_array($arrow)) {
                continue;
            }
            $type = (string) ($arrow['arrow_type'] ?? '');
            $names = ['F' => 'seta futuro', 'PR' => 'seta presente', 'PS' => 'seta passado'];
            $add($names[$type] ?? 'seta', 5);
            $add((string) ($arrow['quadrant'] ?? ''), 4);
            $add((string) ($arrow['notes'] ?? ''), 2);
        }

        foreach (is_array($reading['absences'] ?? null) ? $reading['absences'] : [] as $absence) {
            $add('ausencia ' . (is_scalar($absence) ? (string) $absence : ''), 4);
        }

        return $terms;
    }

    /** @return list<array{index:int,type:string,text:string}> */
    private static function chunks(): array
    {
        $path = dirname(__DIR__, 3) . '/resources/knowledge/sources/material-didatico-2026.md';
        $source = file_get_contents($path);
        if ($source === false || trim($source) === '') {
            return [];
        }

        $analysisStart = strpos($source, 'ANÁLISE DOS MAPAS', 20000);
        $parts = preg_split('/(?:\R\s*){2,}|Mapa da Psiquê 2026[^\r\n]*\R/u', $source) ?: [];
        $chunks = [];
        $offset = 0;
        foreach ($parts as $part) {
            $text = trim($part);
            if (strlen($text) < 120) {
                $offset += strlen($part);
                continue;
            }
            $position = strpos($source, $text, $offset);
            if ($position === false) {
                $position = $offset;
            }
            $chunks[] = [
                'index' => count($chunks) + 1,
                'type' => $analysisStart !== false && $position >= $analysisStart ? 'exemplo_clinico' : 'referencia_teorica',
                'text' => $text,
            ];
            $offset = $position + strlen($text);
        }

        return $chunks;
    }

    private static function normalize(string $text): string
    {
        $text = strtr($text, [
            'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a', 'Ä' => 'a',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Õ' => 'o', 'Ô' => 'o', 'Ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ç' => 'c',
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);
        return strtolower($text);
    }

    /** @return list<string> */
    private static function stopWords(): array
    {
        return ['para', 'como', 'com', 'uma', 'esse', 'essa', 'isso', 'mais', 'pode', 'mapa', 'sobre', 'entre', 'onde', 'pela', 'pelo', 'muito', 'esta', 'este'];
    }
}
