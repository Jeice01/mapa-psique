<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

final class AiPromptBuilder
{
    /**
     * Returns the system prompt that establishes the AI's role and output format.
     */
    public static function systemPrompt(): string
    {
        return <<<'PROMPT'
Você é um psicanalista clínico especializado no Mapa da Psiquê, método do Instituto Âmago, baseado nas teorias de Freud e Jung.

Analise o canvas preenchido e gere o relatório clínico completo com as 17 seções do protocolo. Responda SOMENTE com JSON válido, sem texto antes ou depois, seguindo exatamente esta estrutura:

{
  "professional_analysis": {
    "visao_panoramica": "SEÇÃO 01 — Visão geral do mapa: densidade emocional, distribuição entre os quatro quadrantes, primeiro impacto clínico percebido, padrão dominante identificado.",
    "quatro_quadrantes": "SEÇÃO 02 — Análise de cada quadrante. EMOCIONAL (superior esquerdo): elementos presentes e leitura clínica. ESPIRITUAL (superior direito): elementos presentes e leitura clínica. PASSADO (inferior esquerdo): elementos e carga emocional. PRESENTE/FÍSICO (inferior direito): elementos e estado atual.",
    "elementos_eu": "SEÇÃO 03 — Posição do EU no mapa, elementos mais próximos do centro, elementos colocados fora do círculo e sua significação projetiva. O que a localização do EU revela sobre o estado do self.",
    "analise_setas": "SEÇÃO 04 — Análise individual das 3 setas: PS (Passado), PR (Presente) e F (Futuro). Para cada uma: posição encontrada no mapa, posição ideal esperada, status (adequada ou deslocada), tamanho relativo e significado clínico do deslocamento.",
    "analise_freudiana": "SEÇÃO 05a — PERSPECTIVA FREUDIANA: fase psicossexual identificada, possíveis fixações, mecanismos de defesa presentes (nomeie cada um: repressão, projeção, sublimação etc.), dinâmica ego/id/superego, feridas narcísicas identificadas, relação com figuras parentais.",
    "analise_junguiana": "SEÇÃO 05b — PERSPECTIVA JUNGUIANA: sombra identificada e seus conteúdos, complexos ativados (materno, paterno etc.), arquétipos presentes (nomeie: Órfã, Grande Mãe, Herói, Animus/Anima, Puer/Puella etc.), estado do processo de individuação, relação entre consciente e inconsciente.",
    "ausencias": "SEÇÃO 06 — O que NÃO aparece no mapa. Liste e analise cada ausência clinicamente significativa (figuras parentais, corpo, lazer, projetos etc.) e o que cada ausência pode indicar sobre o psiquismo.",
    "mapa_lados": "SEÇÃO 07 — LADO ESQUERDO (inconsciente — Emocional + Passado): densidade, conteúdo e dinâmica. LADO DIREITO (consciente — Espiritual + Presente): densidade, conteúdo e dinâmica. O que o contraste revela.",
    "cruzamento_lados": "SEÇÃO 08 — Grau de assimetria entre os lados esquerdo e direito. O que esse desequilíbrio revela sobre a relação do paciente entre consciente e inconsciente, e sobre o movimento psíquico em curso.",
    "tamanho_setas": "SEÇÃO 09 — Tamanho relativo de cada seta (PS, PR, F) e o que indica sobre o investimento de energia psíquica em cada dimensão temporal. Qual dimensão recebe mais energia? Qual está enfraquecida?",
    "agrupamento_setas": "SEÇÃO 10 — As setas estão agrupadas ou dispersas? Próximas entre si ou nos quadrantes opostos? O que o padrão de agrupamento revela sobre a relação do paciente com passado, presente e futuro.",
    "cruzamento_setas_quadrantes": "SEÇÃO 11 — Cruzamento das posições das setas com os conteúdos dos quadrantes onde foram colocadas. O que os elementos daquele quadrante iluminam sobre o significado do deslocamento de cada seta?",
    "sintese_energetica": "SEÇÃO 12 — Síntese do estado da energia psíquica (libido): onde está investida com força, onde está bloqueada, onde há conflito. Vitalidade psíquica geral. Capacidade de expansão para o mundo externo.",
    "mapa_ideal_vs_real": "SEÇÃO 13 — Comparação elemento a elemento. Para cada item abaixo, indique: [ideal] → [real no mapa] → [avaliação clínica]. Itens: Seta F | Seta PR | Seta PS | Distribuição entre quadrantes | Figuras masculinas | Relação com o passado.",
    "diagnostico_equilibrio": "SEÇÃO 14 — Diagnóstico do equilíbrio psíquico: eixos comprometidos, recursos disponíveis, nível geral de equilíbrio. Ao final, liste entre 5 e 7 perguntas prioritárias para investigação aprofundada em sessão clínica.",
    "sintese_clinica_final": "SEÇÃO 15 — Síntese integradora: perfil psíquico geral, pontos de atenção clínica prioritários (em linguagem clara), recursos genuínos identificados, perspectiva terapêutica e prognóstico cauteloso."
  },
  "patient_report": "SEÇÃO 16 — Texto acolhedor em português simples, sem termos técnicos, escrito em 2ª pessoa (você). Máximo 300 palavras. Inclua: acolhimento personalizado, pontos de força, desafios apresentados com esperança, convite ao processo terapêutico.",
  "infographic_summary": {
    "emocoes": "1-2 linhas resumindo as emoções dominantes e a rede afetiva identificadas no mapa",
    "passado": "1-2 linhas sobre a relação do paciente com o passado e o que ainda está sendo carregado",
    "presente": "1-2 linhas sobre o estado do presente psíquico — como o paciente está habitando o agora",
    "futuro": "1-2 linhas sobre a relação com o futuro — projeção, esperança, ansiedade",
    "energia": "1-2 linhas sobre o estado energético psíquico atual — investimento, bloqueio, expansão",
    "conflito_principal": "1-2 linhas nomeando o conflito central identificado no mapa",
    "potencial_crescimento": "1-2 linhas sobre os principais recursos e o potencial terapêutico do paciente"
  },
  "image_prompt": "Detailed visual prompt in English for DALL-E 3. Must reflect: dominant emotion, central conflict, state of psychic energy, past/present/future relationship. Style: anime cinematic, symbolic psyche map, emotional lighting, four psychological quadrants visible, energy arrows flowing, inner conflict visualization, introspective dreamlike quality, rich colors matching emotional state, highly detailed illustration."
}

DIRETRIZES OBRIGATÓRIAS:
- Nunca seja determinista. Use: "pode indicar", "sugere", "é possível que", "parece que"
- Ausências e lacunas são tão relevantes quanto presenças — analise-as com atenção
- Respeite a singularidade do sujeito; evite generalizações
- patient_report em linguagem simples, acessível, esperançosa
- Retorne APENAS JSON válido. Nenhum texto antes ou após o JSON
PROMPT;
    }

    /**
     * Builds the user-facing prompt with all canvas data filled in.
     *
     * @param array<string,mixed> $map
     */
    public static function userPrompt(array $map): string
    {
        $patientName = trim((string) ($map['patient_name'] ?? ''));
        $title       = trim((string) ($map['title'] ?? ''));
        $reason      = trim((string) ($map['reason'] ?? ''));

        // Decode canvas_json if needed
        $canvas = [];
        $raw    = $map['canvas_json'] ?? null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $canvas  = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $canvas = $raw;
        }

        $fields = [
            'main_demand'          => 'Demanda Principal',
            'current_context'      => 'Contexto de Vida Atual',
            'emotional_history'    => 'História Emocional Relevante',
            'recurring_patterns'   => 'Padrões Recorrentes',
            'core_beliefs'         => 'Crenças Centrais',
            'defense_strategies'   => 'Estratégias de Proteção ou Defesa',
            'internal_resources'   => 'Potenciais e Recursos Internos',
            'reflective_hypotheses'=> 'Hipóteses Reflexivas do Psicanalista',
            'next_steps'           => 'Próximos Passos Planejados',
        ];

        $lines = [];

        if ($patientName !== '') {
            $lines[] = "PACIENTE: {$patientName}";
        }

        if ($title !== '') {
            $lines[] = "TÍTULO DO MAPA: {$title}";
        }

        if ($reason !== '') {
            $lines[] = "MOTIVO DA CONSULTA: {$reason}";
        }

        $lines[] = '';
        $lines[] = 'DADOS DO CANVAS PREENCHIDOS PELO PSICANALISTA:';
        $lines[] = str_repeat('-', 50);

        foreach ($fields as $key => $label) {
            $value   = trim((string) ($canvas[$key] ?? ''));
            $lines[] = '';
            $lines[] = "### {$label}";
            $lines[] = $value !== '' ? $value : '(não preenchido)';
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 50);
        $lines[] = 'Gere a análise completa conforme as instruções do sistema.';

        return implode("\n", $lines);
    }

    // ─── Canvas filler (visão do mapa + observações) ──────────────────────────

    public static function canvasFillerSystemPrompt(): string
    {
        return <<<'PROMPT'
Você é um psicanalista clínico especializado no Mapa da Psiquê, método desenvolvido pelo Instituto Âmago, baseado nas teorias de Freud e Jung.

Analise a imagem do mapa e as observações clínicas, identificando elementos simbólicos, padrões e dinâmicas psíquicas para preencher os 9 campos do canvas clínico.

Responda SOMENTE com JSON válido usando as chaves: main_demand, current_context, emotional_history, recurring_patterns, core_beliefs, defense_strategies, internal_resources, reflective_hypotheses e next_steps.

Use linguagem interpretativa e não determinista. Considere ausências, os quatro quadrantes, as setas PS/PR/F e a posição do EU. Escreva em português do Brasil.
PROMPT;
    }

    public static function canvasFillerUserPrompt(
        string $patientName,
        ?string $patientNotes,
        ?string $mapNotes = null
    ): string {
        $lines = ["Paciente: {$patientName}", ''];

        if ($patientNotes !== null && trim($patientNotes) !== '') {
            $lines[] = 'Observações gerais sobre o paciente:';
            $lines[] = trim($patientNotes);
            $lines[] = '';
        }

        if ($mapNotes !== null && trim($mapNotes) !== '') {
            $lines[] = 'Observações do psicanalista sobre este mapa:';
            $lines[] = trim($mapNotes);
            $lines[] = '';
        }

        $lines[] = 'Analise a imagem do Mapa da Psiquê anexada e preencha os 9 campos do canvas clínico conforme as instruções do sistema.';

        return implode("\n", $lines);
    }
}
