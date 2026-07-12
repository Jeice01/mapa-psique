<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

final class StructuredReading
{
    /** @param array<string,mixed> $reading */
    public static function isReviewed(array $reading): bool
    {
        $review = $reading['review'] ?? null;

        return is_array($review) && ($review['status'] ?? '') === 'reviewed';
    }

    /** @param array<string,mixed> $reading @return array<string,mixed> */
    public static function normalizeExtraction(array $reading): array
    {
        $review = is_array($reading['review'] ?? null) ? $reading['review'] : [];
        $rawSelf = is_array($reading['self_position'] ?? null) ? $reading['self_position'] : [];
        $rawQuadrants = is_array($reading['quadrants'] ?? null) ? $reading['quadrants'] : [];
        $elements = [];

        foreach (is_array($reading['elements'] ?? null) ? $reading['elements'] : [] as $index => $element) {
            if (!is_array($element)) {
                continue;
            }

            $elements[] = [
                'id' => trim((string) ($element['id'] ?? 'item-' . ((int) $index + 1))),
                'type' => self::allowed((string) ($element['type'] ?? ''), ['pessoa', 'lugar', 'situacao'], 'situacao'),
                'label' => trim((string) ($element['label'] ?? '')),
                'signal' => self::allowed((string) ($element['signal'] ?? ''), ['positivo', 'negativo', 'ambivalente', 'neutro'], 'neutro'),
                'quadrant' => self::quadrant((string) ($element['quadrant'] ?? '')),
                'distance_from_self' => self::allowed((string) ($element['distance_from_self'] ?? ''), ['proximo', 'medio', 'longe', 'fora'], 'medio'),
                'is_outside_circle' => (bool) ($element['is_outside_circle'] ?? false),
                'notes' => trim((string) ($element['notes'] ?? '')),
                'confidence' => self::confidence($element['confidence'] ?? 0),
                'x' => self::coordinate($element['x'] ?? null),
                'y' => self::coordinate($element['y'] ?? null),
            ];
        }

        $arrowsByType = [];
        foreach (is_array($reading['arrows'] ?? null) ? $reading['arrows'] : [] as $arrow) {
            if (!is_array($arrow)) {
                continue;
            }
            $type = self::allowed((string) ($arrow['arrow_type'] ?? ''), ['PS', 'PR', 'F'], '');
            if ($type === '') {
                continue;
            }
            $arrowsByType[$type] = [
                'arrow_type' => $type,
                'quadrant' => self::quadrant((string) ($arrow['quadrant'] ?? '')),
                'size' => self::allowed((string) ($arrow['size'] ?? ''), ['pequena', 'media', 'grande'], 'media'),
                'relation_to_self' => trim((string) ($arrow['relation_to_self'] ?? '')),
                'is_outside_circle' => (bool) ($arrow['is_outside_circle'] ?? false),
                'notes' => trim((string) ($arrow['notes'] ?? '')),
                'confidence' => self::confidence($arrow['confidence'] ?? 0),
                'x' => self::coordinate($arrow['x'] ?? null),
                'y' => self::coordinate($arrow['y'] ?? null),
            ];
        }

        $arrows = [];
        foreach (['PS', 'PR', 'F'] as $type) {
            $arrows[] = $arrowsByType[$type] ?? [
                'arrow_type' => $type, 'quadrant' => 'centro', 'size' => 'media',
                'relation_to_self' => '', 'is_outside_circle' => false, 'notes' => 'Não identificada com segurança.',
                'confidence' => 0.0, 'x' => null, 'y' => null,
            ];
        }

        return [
            'summary' => trim((string) ($reading['summary'] ?? '')),
            'self_position' => [
                'quadrant' => self::quadrant((string) ($rawSelf['quadrant'] ?? 'centro')),
                'position' => trim((string) ($rawSelf['position'] ?? '')),
                'notes' => trim((string) ($rawSelf['notes'] ?? '')),
                'confidence' => self::confidence($rawSelf['confidence'] ?? 0),
                'x' => self::coordinate($rawSelf['x'] ?? null),
                'y' => self::coordinate($rawSelf['y'] ?? null),
            ],
            'quadrants' => [
                'emocional' => trim((string) ($rawQuadrants['emocional'] ?? '')),
                'espiritual' => trim((string) ($rawQuadrants['espiritual'] ?? '')),
                'passado' => trim((string) ($rawQuadrants['passado'] ?? '')),
                'presente_fisico' => trim((string) ($rawQuadrants['presente_fisico'] ?? '')),
            ],
            'elements' => $elements,
            'arrows' => $arrows,
            'absences' => is_array($reading['absences'] ?? null) ? array_values($reading['absences']) : [],
            'uncertainties' => is_array($reading['uncertainties'] ?? null) ? array_values($reading['uncertainties']) : [],
            'review' => [
                'status' => 'pending',
                'professional_notes' => trim((string) ($review['professional_notes'] ?? '')),
                'reviewed_at' => null,
            ],
        ];
    }

    /** @param list<string> $allowed */
    private static function allowed(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function quadrant(string $value): string
    {
        return self::allowed($value, ['emocional', 'espiritual', 'passado', 'presente_fisico', 'centro', 'fora'], 'centro');
    }

    private static function confidence(mixed $value): float
    {
        return max(0.0, min(1.0, (float) $value));
    }

    private static function coordinate(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        return max(0.0, min(1.0, (float) $value));
    }
}
