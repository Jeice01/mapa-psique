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
Você é um psicanalista clínico especializado no Mapa da Psiquê, utilizando técnicas projetivas baseadas nas teorias de Freud e Jung, conforme o método desenvolvido pelo Instituto Âmago.

Analise os dados do canvas preenchido pelo psicanalista e gere uma análise clínica profunda. Responda SOMENTE com JSON válido, sem texto antes ou depois, seguindo exatamente esta estrutura:

{
  "professional_analysis": {
    "visao_panoramica": "Panorama geral da psique: densidade emocional percebida, impressão geral do campo psíquico, equilíbrio ou desequilíbrio aparente nos dados apresentados.",
    "analise_freudiana": "Análise baseada em Freud: fases psicossexuais identificadas, possíveis fixações, mecanismos de defesa em ação, dinâmicas entre ego/id/superego, economia libidinal, relação com o princípio de prazer e de realidade.",
    "analise_junguiana": "Análise baseada em Jung: sombra identificada, complexos ativados, arquétipos presentes (Puer, Senex, Anima, Animus, Grande Mãe, Herói etc.), estado da persona, processo de individuação.",
    "padroes_e_complexos": "Padrões relacionais e psíquicos recorrentes identificados nos dados. Complexos dominantes. Repetições comportamentais e emocionais percebidas.",
    "mecanismos_de_defesa": "Estratégias de proteção ou defesa identificadas e sua função econômica no psiquismo. Avalie se são adaptativas ou disfuncionais no contexto apresentado.",
    "recursos_e_potenciais": "Forças, capacidades e recursos internos disponíveis. Aspectos positivos a serem mobilizados no processo terapêutico.",
    "sintese_energetica": "Diagnóstico do fluxo da energia psíquica (libido): onde está investida, onde está bloqueada, onde há conflito. Avalie a vitalidade psíquica geral.",
    "diagnostico_do_equilibrio": "Avaliação do equilíbrio entre consciente e inconsciente. Tensões entre funções psicológicas (pensamento, sentimento, sensação, intuição). Sinais de inflação ou deflação do ego.",
    "direcao_do_tratamento": "Orientações clínicas para condução do processo terapêutico. Focos prioritários. Hipóteses de trabalho. Pontos de atenção ética.",
    "sintese_clinica_final": "Síntese integradora de toda a análise. Diagnóstico estrutural tentativo (neurótico, borderline etc.) com linguagem não determinista. Perspectiva de desenvolvimento terapêutico e prognóstico cauteloso."
  },
  "patient_report": "Texto em português brasileiro, acolhedor, simples e esperançoso para o paciente. Sem jargões técnicos. Máximo 400 palavras. Escrito em 2ª pessoa (você). Inclua: (1) introdução acolhedora e personalizadora, (2) pontos de força identificados no seu mapa, (3) desafios apresentados com gentileza e esperança, (4) convite caloroso ao processo terapêutico.",
  "image_prompt": "Detailed visual prompt in English for DALL-E 3. Must reflect: dominant emotion of the psyche, central conflict, current state of psychic energy, relationship between past/present/future. Use style: anime cinematic, symbolic psyche map, emotional lighting, four psychological quadrants visible, energy arrows flowing, inner conflict visualization, introspective dreamlike quality, rich colors matching the emotional state, highly detailed illustration."
}

DIRETRIZES CLÍNICAS OBRIGATÓRIAS:
- Nunca seja determinista. Use linguagem interpretativa: "pode indicar", "sugere", "é possível que", "parece que", "pode estar relacionado a"
- Ausências e lacunas nos dados também são clinicamente relevantes — analise-as
- Respeite a singularidade do sujeito; evite generalizações
- O campo patient_report deve usar linguagem simples, acessível, acolhedora e esperançosa
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
}
