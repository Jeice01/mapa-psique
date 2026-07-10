import { useCallback, useEffect, useRef, useState } from "react";
import {
  generateMapAiAnalysis,
  getMapAiAnalysis,
  getMapAiAnalysisImageBlob,
} from "../../shared/api/httpClient";
import type { AiAnalysis, AiProfessionalAnalysis } from "../../shared/api/httpClient";

type Props = {
  mapId: string;
  canvasHasContent: boolean;
};

type AccordionSection = {
  key: keyof AiProfessionalAnalysis;
  label: string;
  color: string;
};

const SECTIONS: AccordionSection[] = [
  { key: "visao_panoramica",        label: "Visão Panorâmica",            color: "indigo" },
  { key: "analise_freudiana",       label: "Análise Freudiana",           color: "violet" },
  { key: "analise_junguiana",       label: "Análise Junguiana",           color: "purple" },
  { key: "padroes_e_complexos",     label: "Padrões e Complexos",         color: "fuchsia" },
  { key: "mecanismos_de_defesa",    label: "Mecanismos de Defesa",        color: "pink" },
  { key: "recursos_e_potenciais",   label: "Recursos e Potenciais",       color: "emerald" },
  { key: "sintese_energetica",      label: "Síntese Energética",          color: "amber" },
  { key: "diagnostico_do_equilibrio", label: "Diagnóstico do Equilíbrio", color: "orange" },
  { key: "direcao_do_tratamento",   label: "Direção do Tratamento",       color: "sky" },
  { key: "sintese_clinica_final",   label: "Síntese Clínica Final",       color: "teal" },
];

const COLOR_CLASSES: Record<string, { badge: string; border: string; headerBg: string; headerText: string }> = {
  indigo:  { badge: "bg-indigo-100 text-indigo-800",   border: "border-indigo-200",  headerBg: "bg-indigo-50",  headerText: "text-indigo-900" },
  violet:  { badge: "bg-violet-100 text-violet-800",   border: "border-violet-200",  headerBg: "bg-violet-50",  headerText: "text-violet-900" },
  purple:  { badge: "bg-purple-100 text-purple-800",   border: "border-purple-200",  headerBg: "bg-purple-50",  headerText: "text-purple-900" },
  fuchsia: { badge: "bg-fuchsia-100 text-fuchsia-800", border: "border-fuchsia-200", headerBg: "bg-fuchsia-50", headerText: "text-fuchsia-900" },
  pink:    { badge: "bg-pink-100 text-pink-800",       border: "border-pink-200",    headerBg: "bg-pink-50",    headerText: "text-pink-900" },
  emerald: { badge: "bg-emerald-100 text-emerald-800", border: "border-emerald-200", headerBg: "bg-emerald-50", headerText: "text-emerald-900" },
  amber:   { badge: "bg-amber-100 text-amber-800",     border: "border-amber-200",   headerBg: "bg-amber-50",   headerText: "text-amber-900" },
  orange:  { badge: "bg-orange-100 text-orange-800",   border: "border-orange-200",  headerBg: "bg-orange-50",  headerText: "text-orange-900" },
  sky:     { badge: "bg-sky-100 text-sky-800",         border: "border-sky-200",     headerBg: "bg-sky-50",     headerText: "text-sky-900" },
  teal:    { badge: "bg-teal-100 text-teal-800",       border: "border-teal-200",    headerBg: "bg-teal-50",    headerText: "text-teal-900" },
};

export function AiAnalysisSection({ mapId, canvasHasContent }: Props) {
  const [analysis, setAnalysis]           = useState<AiAnalysis | null>(null);
  const [loading, setLoading]             = useState(false);
  const [loadError, setLoadError]         = useState<string | null>(null);
  const [generating, setGenerating]       = useState(false);
  const [generateError, setGenerateError] = useState<string | null>(null);
  const [openSections, setOpenSections]   = useState<Set<string>>(new Set(["visao_panoramica"]));
  const [activeTab, setActiveTab]         = useState<"professional" | "patient">("professional");
  const [imageObjectUrl, setImageObjectUrl] = useState<string | null>(null);
  const [imageLoading, setImageLoading]   = useState(false);
  const [imageError, setImageError]       = useState<string | null>(null);
  const generateInFlight                  = useRef(false);

  // Load existing analysis on mount
  useEffect(() => {
    let cancelled = false;

    async function load() {
      setLoading(true);
      setLoadError(null);

      try {
        const data = await getMapAiAnalysis(mapId);

        if (!cancelled) {
          setAnalysis(data);
        }
      } catch {
        if (!cancelled) {
          setLoadError("Não foi possível carregar a análise existente.");
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [mapId]);

  // Load image when analysis has one
  useEffect(() => {
    if (imageObjectUrl) {
      URL.revokeObjectURL(imageObjectUrl);
      setImageObjectUrl(null);
    }

    if (!analysis?.image_path) {
      setImageError(null);
      return;
    }

    let cancelled = false;
    setImageLoading(true);
    setImageError(null);

    getMapAiAnalysisImageBlob(mapId)
      .then((blob) => {
        if (!cancelled) {
          setImageObjectUrl(URL.createObjectURL(blob));
        }
      })
      .catch(() => {
        if (!cancelled) {
          setImageError("Não foi possível carregar o infográfico.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setImageLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [mapId, analysis?.image_path]);

  const handleGenerate = useCallback(async () => {
    if (generateInFlight.current || generating) return;

    generateInFlight.current = true;
    setGenerating(true);
    setGenerateError(null);

    try {
      const data = await generateMapAiAnalysis(mapId);
      setAnalysis(data);
      setOpenSections(new Set(["visao_panoramica"]));
      setActiveTab("professional");
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : "Não foi possível gerar a análise agora.";
      setGenerateError(message);
    } finally {
      generateInFlight.current = false;
      setGenerating(false);
    }
  }, [mapId, generating]);

  function toggleSection(key: string) {
    setOpenSections((prev) => {
      const next = new Set(prev);

      if (next.has(key)) {
        next.delete(key);
      } else {
        next.add(key);
      }

      return next;
    });
  }

  function expandAll() {
    setOpenSections(new Set(SECTIONS.map((s) => s.key)));
  }

  function collapseAll() {
    setOpenSections(new Set());
  }

  const hasAnalysis = analysis?.status === "completed" && analysis.professional_analysis !== null;
  const hasFailed   = analysis?.status === "failed";

  return (
    <section className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-5">
      {/* Header */}
      <div className="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-950">Análise por Inteligência Artificial</h3>
          <p className="mt-1 max-w-2xl text-sm text-slate-600">
            Gera uma análise psicanalítica profunda (Freud + Jung) e um relatório simplificado com infográfico para o paciente.
          </p>
        </div>

        <div className="flex flex-col items-start gap-2 sm:items-end">
          {analysis?.generated_at ? (
            <span className="text-xs text-slate-500">
              Gerada em {formatDate(analysis.generated_at)}
            </span>
          ) : null}

          {analysis?.model_text ? (
            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
              {analysis.model_text}
            </span>
          ) : null}

          <button
            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
            disabled={generating || !canvasHasContent}
            onClick={() => void handleGenerate()}
            type="button"
            title={!canvasHasContent ? "Preencha ao menos um campo do canvas antes de gerar a análise." : undefined}
          >
            {generating
              ? "Gerando análise..."
              : hasAnalysis
              ? "Regenerar análise"
              : "Gerar análise com IA"}
          </button>
        </div>
      </div>

      {/* Empty state */}
      {!loading && !hasAnalysis && !hasFailed && !generating ? (
        <div className="mt-5 rounded-md border border-dashed border-slate-300 bg-white p-6 text-center">
          <p className="text-sm font-medium text-slate-700">Nenhuma análise gerada ainda.</p>
          <p className="mt-1 text-sm text-slate-500">
            {canvasHasContent
              ? "Clique em \"Gerar análise com IA\" para criar a análise psicanalítica deste mapa."
              : "Preencha ao menos um campo do canvas e salve antes de gerar a análise."}
          </p>
        </div>
      ) : null}

      {/* Loading state */}
      {loading ? (
        <p className="mt-4 text-sm text-slate-500">Carregando análise...</p>
      ) : null}

      {/* Generation in progress */}
      {generating ? (
        <div className="mt-4 rounded-md border border-brand-200 bg-brand-50 p-4">
          <p className="text-sm font-medium text-brand-800">
            A IA está analisando o mapa... Isso pode levar até 1 minuto.
          </p>
          <p className="mt-1 text-xs text-brand-700">
            O modelo está lendo os campos do canvas, consultando as teorias de Freud e Jung e gerando o infográfico.
          </p>
        </div>
      ) : null}

      {/* Error states */}
      {loadError ? (
        <p className="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
          {loadError}
        </p>
      ) : null}

      {generateError ? (
        <p className="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
          {generateError}
        </p>
      ) : null}

      {hasFailed && !generateError ? (
        <div className="mt-4 rounded-md border border-red-200 bg-red-50 p-4">
          <p className="text-sm font-medium text-red-800">A última geração falhou.</p>
          {analysis?.error_message ? (
            <p className="mt-1 text-xs text-red-700">{analysis.error_message}</p>
          ) : null}
          <p className="mt-2 text-sm text-red-700">Clique em "Regenerar análise" para tentar novamente.</p>
        </div>
      ) : null}

      {/* Analysis content */}
      {hasAnalysis ? (
        <div className="mt-5">
          {/* Tabs */}
          <div className="flex gap-1 rounded-lg border border-slate-200 bg-white p-1">
            <button
              className={tabClassName(activeTab === "professional")}
              onClick={() => setActiveTab("professional")}
              type="button"
            >
              Análise profissional
            </button>
            <button
              className={tabClassName(activeTab === "patient")}
              onClick={() => setActiveTab("patient")}
              type="button"
            >
              Relatório do paciente
            </button>
          </div>

          {/* Professional analysis tab */}
          {activeTab === "professional" ? (
            <div className="mt-4">
              <div className="mb-3 flex flex-wrap gap-2">
                <button
                  className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700"
                  onClick={expandAll}
                  type="button"
                >
                  Expandir tudo
                </button>
                <button
                  className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700"
                  onClick={collapseAll}
                  type="button"
                >
                  Recolher tudo
                </button>
              </div>

              <div className="flex flex-col gap-2">
                {SECTIONS.map((section, index) => {
                  const colors  = COLOR_CLASSES[section.color] ?? COLOR_CLASSES["indigo"]!;
                  const isOpen  = openSections.has(section.key);
                  const content = analysis!.professional_analysis?.[section.key] ?? "";

                  return (
                    <div
                      className={`overflow-hidden rounded-md border ${colors.border} bg-white`}
                      key={section.key}
                    >
                      <button
                        className={`flex w-full items-center justify-between px-4 py-3 text-left ${colors.headerBg}`}
                        onClick={() => toggleSection(section.key)}
                        type="button"
                      >
                        <div className="flex items-center gap-2">
                          <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${colors.badge}`}>
                            {String(index + 1).padStart(2, "0")}
                          </span>
                          <span className={`text-sm font-semibold ${colors.headerText}`}>
                            {section.label}
                          </span>
                        </div>
                        <span className="text-slate-400">{isOpen ? "▲" : "▼"}</span>
                      </button>

                      {isOpen ? (
                        <div className="px-4 py-3">
                          <p className="whitespace-pre-wrap text-sm leading-7 text-slate-700">
                            {content || "Não gerado."}
                          </p>
                        </div>
                      ) : null}
                    </div>
                  );
                })}
              </div>
            </div>
          ) : null}

          {/* Patient report tab */}
          {activeTab === "patient" ? (
            <div className="mt-4 flex flex-col gap-4">
              {/* Text report */}
              <div className="rounded-md border border-teal-200 bg-teal-50 p-5">
                <div className="mb-3 flex items-center justify-between">
                  <h4 className="text-sm font-semibold text-teal-900">Relatório para o paciente</h4>
                  <span className="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">
                    Linguagem acessível
                  </span>
                </div>

                {analysis?.patient_report ? (
                  <p className="whitespace-pre-wrap text-sm leading-7 text-slate-800">
                    {analysis.patient_report}
                  </p>
                ) : (
                  <p className="text-sm text-slate-600">Relatório do paciente não disponível.</p>
                )}
              </div>

              {/* Infographic image */}
              <div className="rounded-md border border-purple-200 bg-purple-50 p-5">
                <div className="mb-3 flex items-center justify-between">
                  <h4 className="text-sm font-semibold text-purple-900">Infográfico da psique</h4>
                  <span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                    Gerado por IA
                  </span>
                </div>

                {imageLoading ? (
                  <p className="text-sm text-slate-500">Carregando infográfico...</p>
                ) : imageError ? (
                  <p className="text-sm text-red-700">{imageError}</p>
                ) : imageObjectUrl ? (
                  <div>
                    <img
                      alt="Infográfico da psique gerado por IA"
                      className="w-full max-w-xl rounded-lg shadow-md"
                      src={imageObjectUrl}
                    />
                    {analysis?.image_prompt ? (
                      <details className="mt-3">
                        <summary className="cursor-pointer text-xs font-medium text-slate-500">
                          Ver prompt visual utilizado
                        </summary>
                        <p className="mt-2 text-xs leading-5 text-slate-600">{analysis.image_prompt}</p>
                      </details>
                    ) : null}
                  </div>
                ) : analysis?.image_path ? null : (
                  <p className="text-sm text-slate-600">
                    Infográfico não gerado nesta análise.{" "}
                    {analysis?.model_image ? null : "Configure OPENAI_API_KEY no servidor para habilitar a geração de imagens."}
                  </p>
                )}
              </div>
            </div>
          ) : null}
        </div>
      ) : null}
    </section>
  );
}

function tabClassName(active: boolean): string {
  const base = "flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors";

  if (active) {
    return `${base} bg-brand-600 text-white`;
  }

  return `${base} text-slate-600 hover:text-slate-900`;
}

function formatDate(value: string): string {
  const normalizedValue = value.includes("T") ? value : value.replace(" ", "T");
  const date = new Date(normalizedValue);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}
