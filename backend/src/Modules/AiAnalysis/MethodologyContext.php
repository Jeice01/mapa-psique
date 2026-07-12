<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use RuntimeException;

final class MethodologyContext
{
    public const VERSION = 'mapa-psique-protocolo-v1';

    public static function load(): string
    {
        $path = dirname(__DIR__, 3) . '/resources/knowledge/mapa-psique-protocolo-v1.md';
        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            throw new RuntimeException('Contexto metodológico do Mapa da Psiquê não está disponível.');
        }

        return trim($content);
    }

    /** @param array<string,mixed>|null $map */
    public static function promptBlock(?array $map = null): string
    {
        $block = "\n\n<metodologia versão=\"" . self::VERSION . "\">\n"
            . self::load()
            . "\n</metodologia>";

        if ($map !== null) {
            try {
                $excerpts = KnowledgeRetriever::relevantExcerpts($map);
            } catch (\Throwable $exception) {
                error_log(sprintf(
                    'ai_knowledge_retrieval_failed type=%s message=%s',
                    $exception::class,
                    $exception->getMessage()
                ));
                $excerpts = '';
            }
            if ($excerpts !== '') {
                $block .= "\n\n<fontes_relevantes>\n"
                    . "Os trechos tipo exemplo_clinico ilustram possibilidades e não podem ser copiados como conclusão.\n\n"
                    . $excerpts
                    . "\n</fontes_relevantes>";
            }
        }

        return $block;
    }
}
