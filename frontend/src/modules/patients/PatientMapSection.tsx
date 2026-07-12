import { useCallback, useRef, useState } from "react";
import {
  ApiError,
  createMapFromPatient,
  generateMapCanvas,
  updateMap,
  type MapCanvasData,
} from "../../shared/api/httpClient";

type Props = {
  patientId: string;
  patientName: string;
  onMapCreated?: (mapId: string) => void;
};

type Phase =
  | "idle"
  | "saving"
  | "generating"
  | "guided"
  | "done";

type CanvasTextField = Exclude<keyof MapCanvasData, "schema_version" | "structured_reading">;

const CANVAS_FIELDS: Array<{ key: CanvasTextField; label: string; hint: string }> = [
  {
    key: "main_demand",
    label: "Demanda Principal",
    hint: "Qual é o problema ou queixa central que trouxe o paciente?",
  },
  {
    key: "current_context",
    label: "Contexto de Vida Atual",
    hint: "Como está a vida do paciente hoje (trabalho, relacionamentos, rotina)?",
  },
  {
    key: "emotional_history",
    label: "História Emocional Relevante",
    hint: "Que experiências passadas marcantes o paciente trouxe ao mapa?",
  },
  {
    key: "recurring_patterns",
    label: "Padrões Recorrentes",
    hint: "Que comportamentos ou situações se repetem na vida do paciente?",
  },
  {
    key: "core_beliefs",
    label: "Crenças Centrais",
    hint: "O que o paciente acredita sobre si mesmo, os outros e o mundo?",
  },
  {
    key: "defense_strategies",
    label: "Estratégias de Proteção ou Defesa",
    hint: "Como o paciente se protege emocionalmente? Que mecanismos usa?",
  },
  {
    key: "internal_resources",
    label: "Potenciais e Recursos Internos",
    hint: "Quais forças, talentos ou apoios o paciente tem disponíveis?",
  },
  {
    key: "reflective_hypotheses",
    label: "Hipóteses Reflexivas",
    hint: "Que hipóteses clínicas emergem da observação do mapa?",
  },
  {
    key: "next_steps",
    label: "Próximos Passos",
    hint: "Que direções terapêuticas ou tarefas fazem sentido a partir daqui?",
  },
];

const emptyCanvas = (): MapCanvasData => ({
  main_demand: "",
  current_context: "",
  emotional_history: "",
  recurring_patterns: "",
  core_beliefs: "",
  defense_strategies: "",
  internal_resources: "",
  reflective_hypotheses: "",
  next_steps: "",
});

export function PatientMapSection({ patientId, patientName, onMapCreated }: Props) {
  const [file, setFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [mapNotes, setMapNotes] = useState("");
  const [phase, setPhase] = useState<Phase>("idle");
  const [error, setError] = useState<string | null>(null);
  const [createdMapId, setCreatedMapId] = useState<string | null>(null);
  const [guidedCanvas, setGuidedCanvas] = useState<MapCanvasData>(emptyCanvas());
  const [savingGuided, setSavingGuided] = useState(false);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const previewUrlRef = useRef<string | null>(null);

  const handleFileChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const chosen = e.target.files?.[0] ?? null;
    if (!chosen) return;

    if (previewUrlRef.current) {
      URL.revokeObjectURL(previewUrlRef.current);
    }

    const isPdf = chosen.type === "application/pdf";
    const url = isPdf ? null : URL.createObjectURL(chosen);
    previewUrlRef.current = url;

    setFile(chosen);
    setPreviewUrl(url);
    setError(null);

    if (fileInputRef.current) fileInputRef.current.value = "";
  }, []);

  const handleCreate = useCallback(async () => {
    if (!file || phase !== "idle") return;

    setError(null);
    setPhase("saving");

    let mapId: string;
    try {
      const result = await createMapFromPatient(patientId, file, mapNotes);
      mapId = result.map_id;
      setCreatedMapId(mapId);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Erro ao criar o mapa.");
      setPhase("idle");
      return;
    }

    setPhase("generating");

    try {
      const canvas = await generateMapCanvas(mapId);
      // Salvar canvas no mapa
      await updateMap(mapId, { canvas_json: canvas as unknown as string });
      setPhase("done");
      onMapCreated?.(mapId);
    } catch (err) {
      const msg = err instanceof ApiError ? err.message : "A IA não conseguiu ler a imagem.";
      setError(msg + " Preencha os campos manualmente abaixo.");
      setPhase("guided");
    }
  }, [file, phase, patientId, mapNotes, onMapCreated]);

  const handleGuidedSubmit = useCallback(async () => {
    if (!createdMapId || savingGuided) return;
    setSavingGuided(true);
    setError(null);

    try {
      await updateMap(createdMapId, { canvas_json: guidedCanvas as unknown as string });
      setPhase("done");
      onMapCreated?.(createdMapId);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Erro ao salvar o canvas.");
    } finally {
      setSavingGuided(false);
    }
  }, [createdMapId, guidedCanvas, savingGuided, onMapCreated]);

  const busy = phase === "saving" || phase === "generating";

  return (
    <section className="rounded-lg border border-teal-200 bg-teal-50/40 p-5 space-y-5">
      <div>
        <h3 className="text-sm font-semibold text-teal-900">Mapa da Psiquê — Upload e Geração do Canvas</h3>
        <p className="mt-0.5 text-xs text-teal-700">
          Suba a foto do mapa que {patientName} desenhou. A IA lerá o mapa e preencherá o canvas automaticamente.
          Se não conseguir, você poderá preencher manualmente.
        </p>
      </div>

      {/* Upload */}
      {phase === "idle" || phase === "guided" ? (
        <div className="space-y-4">
          <div className="flex items-center gap-3 flex-wrap">
            <button
              type="button"
              onClick={() => fileInputRef.current?.click()}
              disabled={busy}
              className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md border border-teal-300 bg-white text-teal-700 hover:bg-teal-50 disabled:opacity-50 transition-colors"
            >
              <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              {file ? "Trocar arquivo" : "Selecionar imagem ou PDF"}
            </button>

            {file && (
              <span className="text-xs text-slate-600 truncate max-w-xs">
                {file.name} ({(file.size / 1024 / 1024).toFixed(1)} MB)
              </span>
            )}

            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
              className="hidden"
              onChange={handleFileChange}
            />
          </div>

          {/* Preview da imagem */}
          {previewUrl && (
            <div className="rounded-lg overflow-hidden border border-teal-200 bg-white max-h-60">
              <img src={previewUrl} alt="Prévia do mapa" className="w-full max-h-60 object-contain" />
            </div>
          )}

          {file?.type === "application/pdf" && !previewUrl && (
            <p className="text-xs text-teal-600 bg-white border border-teal-200 rounded px-3 py-2">
              PDF selecionado: <strong>{file.name}</strong>. A IA tentará converter e ler o mapa.
            </p>
          )}

          {/* Observações sobre o mapa */}
          {phase === "idle" && (
            <label className="block text-xs font-medium text-slate-700">
              Observações sobre este mapa
              <textarea
                className="mt-1 w-full min-h-20 rounded-md border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100"
                placeholder="Descreva o que chamou atenção no mapa, contexto da sessão, palavras do paciente etc."
                value={mapNotes}
                disabled={busy}
                onChange={(e) => setMapNotes(e.target.value)}
              />
              <span className="mt-1 block text-xs font-normal text-slate-400">
                Estas observações serão enviadas à IA para enriquecer a leitura do mapa.
              </span>
            </label>
          )}
        </div>
      ) : null}

      {/* Mensagem de erro */}
      {error && (
        <p className="text-xs text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
          {error}
        </p>
      )}

      {/* Botão principal — Criar e Gerar */}
      {phase === "idle" && (
        <button
          type="button"
          onClick={() => void handleCreate()}
          disabled={!file || busy}
          className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          {file ? "Salvar mapa e gerar canvas com IA" : "Selecione um arquivo para continuar"}
        </button>
      )}

      {/* Estados de carregamento */}
      {phase === "saving" && (
        <div className="flex items-center gap-2 text-sm text-teal-700">
          <span className="animate-spin inline-block w-4 h-4 border-2 border-teal-600 border-t-transparent rounded-full" />
          Salvando mapa e imagem…
        </div>
      )}

      {phase === "generating" && (
        <div className="flex items-center gap-2 text-sm text-teal-700">
          <span className="animate-spin inline-block w-4 h-4 border-2 border-teal-600 border-t-transparent rounded-full" />
          A IA está lendo o mapa… pode levar até 30 segundos
        </div>
      )}

      {/* Formulário guiado (fallback) */}
      {phase === "guided" && (
        <div className="space-y-4">
          <p className="text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2">
            A IA não conseguiu ler o mapa. Responda as perguntas abaixo para preencher o canvas manualmente.
          </p>

          {CANVAS_FIELDS.map(({ key, label, hint }) => (
            <label key={key} className="block text-xs font-medium text-slate-700">
              {label}
              <span className="block font-normal text-slate-400 mb-1">{hint}</span>
              <textarea
                className="mt-0.5 w-full min-h-16 rounded-md border border-slate-300 px-3 py-2 text-sm"
                value={guidedCanvas[key]}
                onChange={(e) =>
                  setGuidedCanvas((prev) => ({ ...prev, [key]: e.target.value }))
                }
              />
            </label>
          ))}

          <button
            type="button"
            onClick={() => void handleGuidedSubmit()}
            disabled={savingGuided}
            className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-slate-700 text-white text-sm font-semibold hover:bg-slate-800 disabled:opacity-50 transition-colors"
          >
            {savingGuided ? "Salvando canvas…" : "Salvar canvas preenchido manualmente"}
          </button>
        </div>
      )}

      {/* Sucesso */}
      {phase === "done" && (
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          <p className="font-semibold">Mapa criado com sucesso!</p>
          <p className="mt-1 text-xs text-emerald-700">
            Acesse a aba <strong>Mapas</strong> para ver o canvas e gerar a análise psicanalítica completa.
          </p>
        </div>
      )}
    </section>
  );
}
