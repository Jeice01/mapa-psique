import { useCallback, useEffect, useRef, useState } from "react";
import {
  generateMapAiAnalysis,
  getMapAiAnalysis,
  getMapAiAnalysisImageBlob,
} from "../../shared/api/httpClient";
import type { AiAnalysis, AiInfographicSummary, AiProfessionalAnalysis } from "../../shared/api/httpClient";

type Props = {
  mapId: string;
  canvasHasContent: boolean;
  readingReviewed: boolean;
  patientName?: string;
};

type SingleSection = { type: "single"; num: string; key: string; label: string; highlight?: boolean };
type DualSection = {
  type: "dual";
  num: string;
  label: string;
  partA: { key: string; label: string; headColor: string; bgBorder: string };
  partB: { key: string; label: string; headColor: string; bgBorder: string };
};
type SectionDef = SingleSection | DualSection;

const SECTIONS: SectionDef[] = [
  { type: "single", num: "01", key: "visao_panoramica",            label: "Visão Panorâmica do Mapa" },
  { type: "single", num: "02", key: "quatro_quadrantes",           label: "Os Quatro Quadrantes" },
  { type: "single", num: "03", key: "elementos_eu",                label: "Elementos Próximos e Fora do EU" },
  { type: "single", num: "04", key: "analise_setas",               label: "Análise das Setas (PS, PR, F)" },
  {
    type: "dual",
    num: "05",
    label: "Análise Psicanalítica — Freud & Jung",
    partA: { key: "analise_freudiana", label: "Perspectiva Freudiana",  headColor: "text-blue-700",   bgBorder: "bg-blue-50 border-blue-200" },
    partB: { key: "analise_junguiana", label: "Perspectiva Junguiana",  headColor: "text-violet-700", bgBorder: "bg-violet-50 border-violet-200" },
  },
  { type: "single", num: "06", key: "ausencias",                   label: "Ausências Significativas" },
  { type: "single", num: "07", key: "mapa_lados",                  label: "Mapa dos Lados — Esquerdo × Direito" },
  { type: "single", num: "08", key: "cruzamento_lados",            label: "Cruzamento dos Lados — Assimetria" },
  { type: "single", num: "09", key: "tamanho_setas",               label: "Tamanho das Setas" },
  { type: "single", num: "10", key: "agrupamento_setas",           label: "Agrupamento das Setas" },
  { type: "single", num: "11", key: "cruzamento_setas_quadrantes", label: "Cruzamento das Setas com Quadrantes" },
  { type: "single", num: "12", key: "sintese_energetica",          label: "Síntese Energética" },
  { type: "single", num: "13", key: "mapa_ideal_vs_real",          label: "Mapa Ideal vs. Real" },
  { type: "single", num: "14", key: "diagnostico_equilibrio",      label: "Diagnóstico do Equilíbrio" },
  { type: "single", num: "15", key: "sintese_clinica_final",       label: "Síntese Clínica Final", highlight: true },
];

const INFOGRAPHIC_ROWS: { key: keyof AiInfographicSummary; label: string }[] = [
  { key: "emocoes",              label: "Emoções" },
  { key: "passado",              label: "Passado" },
  { key: "presente",             label: "Presente" },
  { key: "futuro",               label: "Futuro" },
  { key: "energia",              label: "Energia" },
  { key: "conflito_principal",   label: "Conflito principal" },
  { key: "potencial_crescimento", label: "Potencial de crescimento" },
];

function getText(pa: AiProfessionalAnalysis, key: string): string {
  return ((pa as Record<string, unknown>)[key] as string | undefined) ?? "";
}

export function AiAnalysisSection({ mapId, canvasHasContent, readingReviewed, patientName }: Props) {
  const [analysis, setAnalysis]             = useState<AiAnalysis | null>(null);
  const [loading, setLoading]               = useState(false);
  const [loadError, setLoadError]           = useState<string | null>(null);
  const [generating, setGenerating]         = useState(false);
  const [generateError, setGenerateError]   = useState<string | null>(null);
  const [openSections, setOpenSections]     = useState<Set<string>>(new Set(["01"]));
  const [activeTab, setActiveTab]           = useState<"professional" | "patient">("professional");
  const [imageObjectUrl, setImageObjectUrl] = useState<string | null>(null);
  const [imageLoading, setImageLoading]     = useState(false);
  const [imageError, setImageError]         = useState<string | null>(null);
  const generateInFlight                    = useRef(false);

  useEffect(() => {
    let cancelled = false;
    async function load() {
      setLoading(true);
      setLoadError(null);
      try {
        const data = await getMapAiAnalysis(mapId);
        if (!cancelled) setAnalysis(data);
      } catch {
        if (!cancelled) setLoadError("Não foi possível carregar a análise existente.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    void load();
    return () => { cancelled = true; };
  }, [mapId]);

  useEffect(() => {
    if (imageObjectUrl) { URL.revokeObjectURL(imageObjectUrl); setImageObjectUrl(null); }
    if (!analysis?.image_path) { setImageError(null); return; }
    let cancelled = false;
    setImageLoading(true);
    setImageError(null);
    getMapAiAnalysisImageBlob(mapId)
      .then((blob) => { if (!cancelled) setImageObjectUrl(URL.createObjectURL(blob)); })
      .catch(() => { if (!cancelled) setImageError("Não foi possível carregar o infográfico."); })
      .finally(() => { if (!cancelled) setImageLoading(false); });
    return () => { cancelled = true; };
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
      setOpenSections(new Set(["01"]));
      setActiveTab("professional");
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : "Não foi possível gerar a análise agora.";
      setGenerateError(message);
    } finally {
      generateInFlight.current = false;
      setGenerating(false);
    }
  }, [mapId, generating]);

  function toggleSection(num: string) {
    setOpenSections((prev) => {
      const next = new Set(prev);
      if (next.has(num)) next.delete(num); else next.add(num);
      return next;
    });
  }

  function expandAll()  { setOpenSections(new Set(SECTIONS.map((s) => s.num))); }
  function collapseAll(){ setOpenSections(new Set()); }

  const hasAnalysis = analysis?.status === "completed" && analysis.professional_analysis !== null;
  const hasFailed   = analysis?.status === "failed";
  const pa          = analysis?.professional_analysis ?? null;
  const infographic = pa?.infographic_summary ?? null;

  return (
    <section className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-5">
      {/* Controles superiores */}
      <div className="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-950">Análise por Inteligência Artificial</h3>
          <p className="mt-1 text-sm text-slate-500">17 seções · Protocolo Mapa da Psiquê · Freud + Jung</p>
        </div>
        <div className="flex flex-col items-start gap-2 sm:items-end">
          {analysis?.model_text ? (
            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
              {analysis.model_text}
            </span>
          ) : null}
          <button
            className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
            disabled={generating || !canvasHasContent || !readingReviewed}
            onClick={() => void handleGenerate()}
            type="button"
            title={!canvasHasContent ? "Preencha ao menos um campo do canvas antes de gerar a análise." : !readingReviewed ? "Revise, confirme e salve a leitura da imagem antes de gerar a análise." : undefined}
          >
            {generating ? "Gerando análise..." : hasAnalysis ? "Regenerar análise" : "Gerar análise com IA"}
          </button>
        </div>
      </div>

      {/* Faixa de informações do paciente */}
      {hasAnalysis && (patientName || analysis?.generated_at) ? (
        <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-1 rounded-md border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm">
          {patientName ? (
            <span>
              <span className="font-semibold text-indigo-700">Paciente:</span>{" "}
              <span className="text-slate-800">{patientName}</span>
            </span>
          ) : null}
          {analysis?.generated_at ? (
            <span>
              <span className="font-semibold text-indigo-700">Gerado em:</span>{" "}
              <span className="text-slate-800">{formatDate(analysis.generated_at)}</span>
            </span>
          ) : null}
          {analysis?.model_text ? (
            <span>
              <span className="font-semibold text-indigo-700">Modelo:</span>{" "}
              <span className="text-slate-800">{analysis.model_text}</span>
            </span>
          ) : null}
          <span className="ml-auto text-xs italic text-slate-400">
            Análise interpretativa — não diagnóstica
          </span>
        </div>
      ) : null}

      {/* Estados vazios / loading / erro */}
      {!loading && !hasAnalysis && !hasFailed && !generating ? (
        <div className="mt-5 rounded-md border border-dashed border-slate-300 bg-white p-6 text-center">
          <p className="text-sm font-medium text-slate-700">Nenhuma análise gerada ainda.</p>
          <p className="mt-1 text-sm text-slate-500">
            {canvasHasContent && readingReviewed
              ? "Clique em \"Gerar análise com IA\" para criar o relatório clínico completo (17 seções)."
              : canvasHasContent
                ? "Revise, confirme e salve a leitura estruturada da imagem antes de gerar o relatório."
              : "Preencha ao menos um campo do canvas e salve antes de gerar a análise."}
          </p>
        </div>
      ) : null}

      {loading ? <p className="mt-4 text-sm text-slate-500">Carregando análise...</p> : null}

      {generating ? (
        <div className="mt-4 rounded-md border border-brand-200 bg-brand-50 p-4">
          <p className="text-sm font-medium text-brand-800">
            A IA está gerando o relatório clínico completo (17 seções)...
          </p>
          <p className="mt-1 text-xs text-brand-700">
            Isso pode levar até 2 minutos. Por favor, aguarde.
          </p>
        </div>
      ) : null}

      {loadError ? (
        <p className="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{loadError}</p>
      ) : null}

      {generateError ? (
        <p className="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{generateError}</p>
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

      {/* Conteúdo principal */}
      {hasAnalysis && pa ? (
        <div className="mt-5">
          {/* Abas */}
          <div className="flex gap-1 rounded-lg border border-slate-200 bg-white p-1">
            <button
              className={tabCls(activeTab === "professional")}
              onClick={() => setActiveTab("professional")}
              type="button"
            >
              Análise profissional — Seções 01 a 15
            </button>
            <button
              className={tabCls(activeTab === "patient")}
              onClick={() => setActiveTab("patient")}
              type="button"
            >
              Relatório do paciente — Seções 16 e 17
            </button>
          </div>

          {/* Aba: análise profissional */}
          {activeTab === "professional" ? (
            <div className="mt-4">
              <div className="mb-3 flex gap-2">
                <button
                  className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  onClick={expandAll}
                  type="button"
                >
                  Expandir tudo
                </button>
                <button
                  className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  onClick={collapseAll}
                  type="button"
                >
                  Recolher tudo
                </button>
              </div>

              <div className="flex flex-col gap-2">
                {SECTIONS.map((section) => {
                  const isOpen      = openSections.has(section.num);
                  const isHighlight = section.type === "single" && section.highlight === true;

                  return (
                    <div
                      key={section.num}
                      className={`overflow-hidden rounded-md border bg-white ${
                        isHighlight ? "border-indigo-300" : "border-slate-200"
                      }`}
                    >
                      {/* Cabeçalho da seção */}
                      <button
                        className={`flex w-full items-center justify-between px-4 py-3 text-left transition-colors ${
                          isHighlight ? "bg-indigo-50 hover:bg-indigo-100" : "hover:bg-slate-50"
                        }`}
                        onClick={() => toggleSection(section.num)}
                        type="button"
                      >
                        <div className="flex items-center gap-3 min-w-0">
                          <span className="shrink-0 rounded px-2 py-0.5 text-xs font-bold tracking-wider bg-indigo-100 text-indigo-700">
                            SEÇÃO {section.num}
                          </span>
                          <span className={`text-sm font-semibold truncate ${isHighlight ? "text-indigo-900" : "text-slate-800"}`}>
                            {section.label}
                          </span>
                        </div>
                        <span className="ml-3 shrink-0 text-xs text-slate-400">{isOpen ? "▲" : "▼"}</span>
                      </button>

                      {/* Conteúdo */}
                      {isOpen ? (
                        <div className="border-t border-slate-100 px-5 py-4">
                          {section.type === "single" ? (
                            <p
                              className={`whitespace-pre-wrap text-sm leading-7 ${
                                isHighlight ? "font-medium text-indigo-900" : "text-slate-700"
                              }`}
                            >
                              {getText(pa, section.key) !== "" ? (
                                getText(pa, section.key)
                              ) : (
                                <span className="italic text-slate-400">
                                  Não gerado — regenere a análise para preencher esta seção.
                                </span>
                              )}
                            </p>
                          ) : (
                            /* Seção dupla: Freud + Jung */
                            <div className="flex flex-col gap-4 lg:flex-row">
                              <div className={`flex-1 rounded-md border p-4 ${section.partA.bgBorder}`}>
                                <p className={`mb-2 text-xs font-bold uppercase tracking-widest ${section.partA.headColor}`}>
                                  {section.partA.label}
                                </p>
                                <p className="whitespace-pre-wrap text-sm leading-7 text-slate-700">
                                  {getText(pa, section.partA.key) !== "" ? (
                                    getText(pa, section.partA.key)
                                  ) : (
                                    <span className="italic text-slate-400">Não gerado.</span>
                                  )}
                                </p>
                              </div>
                              <div className={`flex-1 rounded-md border p-4 ${section.partB.bgBorder}`}>
                                <p className={`mb-2 text-xs font-bold uppercase tracking-widest ${section.partB.headColor}`}>
                                  {section.partB.label}
                                </p>
                                <p className="whitespace-pre-wrap text-sm leading-7 text-slate-700">
                                  {getText(pa, section.partB.key) !== "" ? (
                                    getText(pa, section.partB.key)
                                  ) : (
                                    <span className="italic text-slate-400">Não gerado.</span>
                                  )}
                                </p>
                              </div>
                            </div>
                          )}
                        </div>
                      ) : null}
                    </div>
                  );
                })}
              </div>
            </div>
          ) : null}

          {/* Aba: relatório do paciente */}
          {activeTab === "patient" ? (
            <div className="mt-4 flex flex-col gap-5">
              {/* Seção 16 — Versão para o paciente */}
              <div className="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div className="flex items-center gap-3 border-b border-slate-100 px-5 py-3">
                  <span className="shrink-0 rounded px-2 py-0.5 text-xs font-bold tracking-wider bg-indigo-100 text-indigo-700">
                    SEÇÃO 16
                  </span>
                  <span className="text-sm font-semibold text-slate-800">Versão para o Paciente</span>
                  <span className="ml-auto rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700">
                    Linguagem acessível
                  </span>
                </div>
                <div className="px-5 py-5">
                  {analysis?.patient_report ? (
                    <p className="whitespace-pre-wrap text-sm italic leading-7 text-slate-700">
                      {analysis.patient_report}
                    </p>
                  ) : (
                    <p className="text-sm text-slate-500">Relatório do paciente não disponível.</p>
                  )}
                </div>
              </div>

              {/* Seção 17 — Infográfico / resumo visual */}
              {infographic ? (
                <div className="overflow-hidden rounded-md border border-slate-200 bg-white">
                  <div className="flex items-center gap-3 border-b border-slate-100 px-5 py-3">
                    <span className="shrink-0 rounded px-2 py-0.5 text-xs font-bold tracking-wider bg-indigo-100 text-indigo-700">
                      SEÇÃO 17
                    </span>
                    <span className="text-sm font-semibold text-slate-800">Infográfico — Resumo Visual</span>
                  </div>
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <tbody>
                        {INFOGRAPHIC_ROWS.map((row, idx) => (
                          <tr key={row.key} className={idx % 2 === 0 ? "bg-slate-50" : "bg-white"}>
                            <td className="w-48 shrink-0 px-5 py-3 font-semibold text-indigo-800">
                              {row.label}
                            </td>
                            <td className="px-5 py-3 leading-6 text-slate-700">
                              {infographic[row.key] ?? "—"}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              ) : null}

              {/* Infográfico visual gerado por IA (DALL-E) */}
              {analysis?.image_path || imageLoading || imageError ? (
                <div className="rounded-md border border-purple-200 bg-purple-50 p-5">
                  <div className="mb-3 flex items-center justify-between">
                    <h4 className="text-sm font-semibold text-purple-900">Infográfico visual — gerado por IA</h4>
                    <span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                      {analysis?.model_image ?? "DALL-E"}
                    </span>
                  </div>
                  {imageLoading ? (
                    <p className="text-sm text-slate-500">Carregando imagem...</p>
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
                  ) : null}
                </div>
              ) : null}
            </div>
          ) : null}
        </div>
      ) : null}
    </section>
  );
}

function tabCls(active: boolean): string {
  const base = "flex-1 rounded-md px-3 py-2 text-xs font-medium transition-colors";
  return active ? `${base} bg-brand-600 text-white` : `${base} text-slate-600 hover:text-slate-900`;
}

function formatDate(value: string): string {
  const d = new Date(value.includes("T") ? value : value.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return value;
  return new Intl.DateTimeFormat("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d);
}
