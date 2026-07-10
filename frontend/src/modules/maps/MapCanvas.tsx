import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from "react";
import { AiAnalysisSection } from "./AiAnalysisSection";
import { MapImageUpload } from "./MapImageUpload";
import {
  exportMapCanvasVersionPdf,
  exportMapPdf,
  getMap,
  getMapCanvasVersion,
  listMapCanvasVersions,
  restoreMapCanvasVersion,
} from "../../shared/api/httpClient";
import type {
  MapCanvasData,
  MapCanvasVersion,
  MapCanvasVersionDetails,
  MapDraft,
  RestoreMapCanvasVersionResult,
} from "../../shared/api/httpClient";

type Props = {
  map: MapDraft;
  onSave: (payload: Partial<MapDraft>) => Promise<void>;
};

type VersionFilter = "all" | "backup" | "manual";

const emptyCanvas: MapCanvasData = {
  main_demand: "",
  current_context: "",
  emotional_history: "",
  recurring_patterns: "",
  core_beliefs: "",
  defense_strategies: "",
  internal_resources: "",
  reflective_hypotheses: "",
  next_steps: "",
};

const fields: Array<{ key: keyof MapCanvasData; label: string; help: string; placeholder: string }> = [
  {
    key: "main_demand",
    label: "Queixa ou demanda principal",
    help: "Registre a demanda central trazida para este mapa.",
    placeholder: "Ex.: dificuldade recorrente em vínculos, decisão importante, sensação persistente...",
  },
  {
    key: "current_context",
    label: "Contexto de vida atual",
    help: "Situe momento de vida, relações, trabalho, rotina e pressões atuais.",
    placeholder: "Descreva elementos atuais que ajudam a compreender o mapa.",
  },
  {
    key: "emotional_history",
    label: "História emocional relevante",
    help: "Inclua eventos, fases ou experiências com impacto emocional percebido.",
    placeholder: "Registre apenas o necessário para orientar a leitura reflexiva.",
  },
  {
    key: "recurring_patterns",
    label: "Padrões recorrentes",
    help: "Observe repetições de comportamento, escolha, reação ou relação.",
    placeholder: "Ex.: evita confronto, assume excesso de responsabilidade, repete papéis...",
  },
  {
    key: "core_beliefs",
    label: "Crenças centrais",
    help: "Aponte crenças ou narrativas internas que aparecem no processo.",
    placeholder: "Ex.: preciso dar conta, não posso falhar, não sou ouvido...",
  },
  {
    key: "defense_strategies",
    label: "Estratégias de proteção ou defesa",
    help: "Nomeie formas de autoproteção sem julgamento clínico fechado.",
    placeholder: "Ex.: controle, retraimento, racionalização, agradar para evitar conflito...",
  },
  {
    key: "internal_resources",
    label: "Potenciais e recursos internos",
    help: "Registre forças, apoios, capacidades e recursos já disponíveis.",
    placeholder: "Ex.: rede de apoio, criatividade, espiritualidade, disciplina, capacidade de reflexão...",
  },
  {
    key: "reflective_hypotheses",
    label: "Hipóteses reflexivas",
    help: "Anote hipóteses abertas para investigação futura.",
    placeholder: "Use linguagem provisória: parece, pode indicar, talvez esteja relacionado...",
  },
  {
    key: "next_steps",
    label: "Próximos passos",
    help: "Defina encaminhamentos, observações ou pontos para retomar.",
    placeholder: "Ex.: aprofundar tema X, observar reação Y, validar percepção com paciente...",
  },
];

const historicalPreviewFields: Array<{ key: keyof MapCanvasData; label: string }> = [
  { key: "main_demand", label: "Demanda principal" },
  { key: "current_context", label: "Contexto atual" },
  { key: "emotional_history", label: "História emocional" },
  { key: "recurring_patterns", label: "Padrões recorrentes" },
  { key: "core_beliefs", label: "Crenças centrais" },
  { key: "defense_strategies", label: "Estratégias de defesa" },
  { key: "internal_resources", label: "Recursos internos" },
  { key: "reflective_hypotheses", label: "Hipóteses reflexivas" },
  { key: "next_steps", label: "Próximos passos" },
];

export function MapCanvas({ map, onSave }: Props) {
  const [canvas, setCanvas] = useState<MapCanvasData>(() => normalizeCanvas(map.canvas_json));
  const [savedCanvas, setSavedCanvas] = useState<MapCanvasData>(() => normalizeCanvas(map.canvas_json));
  const [versions, setVersions] = useState<MapCanvasVersion[]>([]);
  const [versionFilter, setVersionFilter] = useState<VersionFilter>("all");
  const [versionsLoading, setVersionsLoading] = useState(false);
  const [versionsError, setVersionsError] = useState<string | null>(null);
  const [selectedVersion, setSelectedVersion] = useState<MapCanvasVersionDetails | null>(null);
  const [versionDetailsLoadingId, setVersionDetailsLoadingId] = useState<string | null>(null);
  const [versionDetailsError, setVersionDetailsError] = useState<string | null>(null);
  const [restoreLoading, setRestoreLoading] = useState(false);
  const [restoreError, setRestoreError] = useState<string | null>(null);
  const [restoreResult, setRestoreResult] = useState<RestoreMapCanvasVersionResult | null>(null);
  const restoreInFlightRef = useRef(false);
  const [saving, setSaving] = useState(false);
  const [saveState, setSaveState] = useState<"idle" | "saved" | "error">("idle");
  const [exportingPdf, setExportingPdf] = useState(false);
  const [exportPdfMessage, setExportPdfMessage] = useState<string | null>(null);
  const [exportPdfError, setExportPdfError] = useState<string | null>(null);
  const [exportingVersionPdfId, setExportingVersionPdfId] = useState<string | null>(null);
  const [exportVersionPdfError, setExportVersionPdfError] = useState<string | null>(null);
  const [hasMapImage, setHasMapImage] = useState<boolean>(!!map.map_image_path);
  const isDirty = serializeCanvas(canvas) !== serializeCanvas(savedCanvas);

  const handleCanvasGeneratedByAi = useCallback((aiCanvas: MapCanvasData) => {
    setCanvas(aiCanvas);
    setHasMapImage(true);
  }, []);

  const canvasHasContent = useMemo(() => {
    return Object.values(savedCanvas).some((v) => typeof v === "string" && v.trim() !== "");
  }, [savedCanvas]);

  const filteredVersions = versions.filter((version) => {
    if (versionFilter === "backup") {
      return isAutomaticBackup(version.summary);
    }

    if (versionFilter === "manual") {
      return !isAutomaticBackup(version.summary);
    }

    return true;
  });

  const loadVersions = useCallback(async () => {
    setVersionsLoading(true);
    setVersionsError(null);

    try {
      setVersions(await listMapCanvasVersions(map.id));
    } catch {
      setVersionsError("Não foi possível carregar o histórico agora.");
    } finally {
      setVersionsLoading(false);
    }
  }, [map.id]);

  useEffect(() => {
    const normalizedCanvas = normalizeCanvas(map.canvas_json);

    setCanvas(normalizedCanvas);
    setSavedCanvas(normalizedCanvas);
    setSaveState("idle");
    setSelectedVersion(null);
    setVersionDetailsError(null);
    setRestoreError(null);
    setRestoreResult(null);
    setExportPdfMessage(null);
    setExportPdfError(null);
    setExportVersionPdfError(null);
    setExportingVersionPdfId(null);
    setVersionFilter("all");
  }, [map.canvas_json, map.id]);

  useEffect(() => {
    void loadVersions();
  }, [loadVersions]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setSaveState("idle");

    try {
      await onSave({ canvas_json: canvas });
      setSavedCanvas(canvas);
      setSaveState("saved");
      await loadVersions();
    } catch {
      setSaveState("error");
    } finally {
      setSaving(false);
    }
  }

  function updateField(key: keyof MapCanvasData, value: string) {
    setCanvas((current) => ({ ...current, [key]: value }));
  }

  async function handleViewVersion(versionId: string) {
    setVersionDetailsLoadingId(versionId);
    setVersionDetailsError(null);
    setExportVersionPdfError(null);

    try {
      setSelectedVersion(await getMapCanvasVersion(map.id, versionId));
      setRestoreError(null);
      setRestoreResult(null);
    } catch {
      setVersionDetailsError("Não foi possível carregar os detalhes desta versão.");
    } finally {
      setVersionDetailsLoadingId(null);
    }
  }

  async function handleRestoreVersion(versionId: string) {
    if (restoreInFlightRef.current || restoreLoading || !selectedVersion || selectedVersion.id !== versionId) {
      return;
    }

    restoreInFlightRef.current = true;
    setRestoreLoading(true);
    setRestoreError(null);
    setRestoreResult(null);

    try {
      const result = await restoreMapCanvasVersion(map.id, versionId);
      setRestoreResult(result);

      try {
        const restoredMap = await getMap(map.id);
        const restoredCanvas = normalizeCanvas(restoredMap.canvas_json);

        setCanvas(restoredCanvas);
        setSavedCanvas(restoredCanvas);
        setSaveState("saved");
        await loadVersions();
      } catch {
        setRestoreError("Versão restaurada, mas não foi possível recarregar o canvas atualizado. Reabra o mapa para conferir.");
      }
    } catch {
      setRestoreError("Não foi possível restaurar esta versão. Nenhuma alteração foi aplicada.");
    } finally {
      restoreInFlightRef.current = false;
      setRestoreLoading(false);
    }
  }

  const handleExportPdf = useCallback(async () => {
    setExportingPdf(true);
    setExportPdfError(null);
    setExportPdfMessage(null);

    try {
      const { blob, filename } = await exportMapPdf(map.id);
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement("a");

      link.href = objectUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(objectUrl);

      setExportPdfMessage("PDF gerado com sucesso.");
    } catch {
      setExportPdfError("Não foi possível exportar o PDF agora.");
    } finally {
      setExportingPdf(false);
    }
  }, [map.id]);

  const handleExportVersionPdf = useCallback(
    async (versionId: string) => {
      setExportingVersionPdfId(versionId);
      setExportVersionPdfError(null);
      setExportPdfMessage(null);

      try {
        const { blob, filename } = await exportMapCanvasVersionPdf(map.id, versionId);
        const objectUrl = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = objectUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(objectUrl);

        setExportPdfMessage("PDF da versão histórica gerado com sucesso.");
      } catch {
        setExportVersionPdfError("Não foi possível exportar o PDF desta versão agora.");
      } finally {
        setExportingVersionPdfId(null);
      }
    },
    [map.id]
  );

  return (
    <form className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-5" onSubmit={handleSubmit}>
      <div className="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-950">Canvas do Mapa da Psiquê</h3>
          <p className="mt-1 max-w-2xl text-sm text-slate-600">Registro reflexivo estruturado para organizar a leitura inicial do mapa.</p>
        </div>

        <div className="flex flex-col items-start gap-2 sm:items-end">
          <span className={statusClassName(isDirty, saveState)}>{saving ? "Salvando..." : statusText(isDirty, saveState)}</span>

          <div className="flex flex-wrap gap-2 sm:justify-end">
            <button
              className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
              disabled={exportingPdf}
              onClick={() => void handleExportPdf()}
              type="button"
            >
              {exportingPdf ? "Gerando PDF..." : "Exportar PDF"}
            </button>

            <button
              className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
              disabled={saving || !isDirty}
              type="submit"
            >
              {saving ? "Salvando..." : "Salvar canvas"}
            </button>
          </div>
        </div>
      </div>

      {exportPdfMessage ? (
        <p className="mt-3 rounded-md border border-brand-200 bg-brand-50 p-3 text-sm font-medium text-brand-800">{exportPdfMessage}</p>
      ) : null}

      {exportPdfError ? (
        <p className="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">{exportPdfError}</p>
      ) : null}

      <div className="mt-4">
        <MapImageUpload
          mapId={map.id}
          hasMapImage={hasMapImage}
          onCanvasGenerated={handleCanvasGeneratedByAi}
        />
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        {fields.map((field) => (
          <label className="block text-sm font-medium text-slate-700" key={field.key}>
            {field.label}
            <span className="mt-1 block text-xs font-normal leading-5 text-slate-500">{field.help}</span>
            <textarea
              className="mt-2 min-h-32 w-full resize-y rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
              placeholder={field.placeholder}
              value={canvas[field.key]}
              onChange={(event) => updateField(field.key, event.target.value)}
            />
          </label>
        ))}
      </div>

      <AiAnalysisSection canvasHasContent={canvasHasContent} mapId={map.id} patientName={map.patient_name ?? undefined} />

      <section className="mt-5 border-t border-slate-200 pt-4">
        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h4 className="text-sm font-semibold text-slate-950">Histórico do canvas</h4>
            {versions.length > 0 ? (
              <p className="mt-1 text-xs text-slate-500">
                Exibindo {filteredVersions.length} de {versions.length} versões.
              </p>
            ) : null}
          </div>

          {versionsLoading ? <span className="text-xs text-slate-500">Carregando histórico do canvas...</span> : null}
        </div>

        {versions.length > 0 ? (
          <div className="mt-3 flex flex-wrap gap-2">
            <button
              className={versionFilterButtonClassName(versionFilter === "all")}
              onClick={() => setVersionFilter("all")}
              type="button"
            >
              Todos
            </button>

            <button
              className={versionFilterButtonClassName(versionFilter === "backup")}
              onClick={() => setVersionFilter("backup")}
              type="button"
            >
              Backups automáticos
            </button>

            <button
              className={versionFilterButtonClassName(versionFilter === "manual")}
              onClick={() => setVersionFilter("manual")}
              type="button"
            >
              Snapshots manuais
            </button>
          </div>
        ) : null}

        {versionsError ? <p className="mt-2 text-sm text-red-700">{versionsError}</p> : null}

        {!versionsLoading && versions.length === 0 && !versionsError ? (
          <p className="mt-2 text-sm text-slate-500">Nenhuma versão salva ainda. Ao salvar o canvas, o histórico será criado automaticamente.</p>
        ) : null}

        {!versionsLoading && versions.length > 0 && filteredVersions.length === 0 && !versionsError ? (
          <p className="mt-3 rounded-md border border-slate-200 bg-white p-3 text-sm text-slate-600">
            Nenhuma versão encontrada para este filtro.
          </p>
        ) : null}

        {filteredVersions.length > 0 ? (
          <div className="mt-3 overflow-hidden rounded-md border border-slate-200 bg-white">
            {filteredVersions.map((version) => {
              const isSelected = selectedVersion?.id === version.id;

              return (
                <div
                  className={`flex flex-col gap-3 border-b px-3 py-3 last:border-0 sm:flex-row sm:items-center sm:justify-between ${
                    isSelected ? "border-brand-100 bg-brand-50" : "border-slate-100"
                  }`}
                  key={version.id}
                >
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="text-sm font-semibold text-slate-900">Versão {version.version_number}</p>
                      <span className={versionKindClassName(version.summary)}>{versionKindLabel(version.summary)}</span>
                      {isSelected ? <span className="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-brand-700">Em prévia</span> : null}
                    </div>
                    <p className="mt-1 text-xs text-slate-600">{version.summary ?? versionKindHelp(version.summary)}</p>
                    <p className="mt-1 text-xs text-slate-500">{versionKindHelp(version.summary)}</p>
                    <p className="mt-1 text-xs text-slate-500">Criada em {formatDate(version.created_at)}</p>
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <button
                      className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                      disabled={versionDetailsLoadingId === version.id}
                      onClick={() => void handleViewVersion(version.id)}
                      type="button"
                    >
                      {versionDetailsLoadingId === version.id ? "Carregando prévia..." : "Ver detalhes"}
                    </button>
                    <button
                      className="rounded-md border border-brand-300 bg-white px-3 py-1.5 text-xs font-medium text-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
                      disabled={exportingVersionPdfId === version.id || restoreLoading}
                      onClick={() => void handleExportVersionPdf(version.id)}
                      type="button"
                    >
                      {exportingVersionPdfId === version.id ? "Gerando PDF..." : "Exportar PDF"}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : null}

        {versionDetailsLoadingId ? <p className="mt-3 text-sm text-slate-500">Carregando prévia da versão...</p> : null}
        {versionDetailsError ? <p className="mt-3 text-sm text-red-700">{versionDetailsError}</p> : null}

        {selectedVersion ? (
          <HistoricalVersionPreview
            exportError={exportVersionPdfError}
            exportLoading={exportingVersionPdfId === selectedVersion.id}
            restoreError={restoreError}
            restoreLoading={restoreLoading}
            restoreResult={restoreResult}
            version={selectedVersion}
            onClose={() => setSelectedVersion(null)}
            onExportPdf={() => void handleExportVersionPdf(selectedVersion.id)}
            onRestore={() => void handleRestoreVersion(selectedVersion.id)}
          />
        ) : null}
      </section>
    </form>
  );
}

function HistoricalVersionPreview({
  exportError,
  exportLoading,
  restoreError,
  restoreLoading,
  restoreResult,
  version,
  onClose,
  onExportPdf,
  onRestore,
}: {
  exportError: string | null;
  exportLoading: boolean;
  restoreError: string | null;
  restoreLoading: boolean;
  restoreResult: RestoreMapCanvasVersionResult | null;
  version: MapCanvasVersionDetails;
  onClose: () => void;
  onExportPdf: () => void;
  onRestore: () => void;
}) {
  const preview = buildHistoricalPreview(version.canvas_data);
  const historicalCanvas = preview.canvas;
  const [showRestoreConfirmation, setShowRestoreConfirmation] = useState(false);
  const [restoreConfirmationText, setRestoreConfirmationText] = useState("");
  const canConfirmRestore = restoreConfirmationText === "RESTAURAR";

  useEffect(() => {
    if (restoreResult) {
      setShowRestoreConfirmation(false);
      setRestoreConfirmationText("");
    }
  }, [restoreResult]);

  useEffect(() => {
    setShowRestoreConfirmation(false);
    setRestoreConfirmationText("");
  }, [version.id]);

  return (
    <aside className="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4" aria-label="Prévia da versão histórica">
      <div className="flex flex-col gap-3 border-b border-amber-200 pb-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-amber-800">Prévia da versão histórica</p>
          <h4 className="mt-1 text-base font-semibold text-slate-950">Versão {version.version_number}</h4>
          <p className="mt-1 text-sm text-slate-700">{version.summary ?? versionKindHelp(version.summary)}</p>
          <p className="mt-1 text-xs text-slate-600">{versionKindHelp(version.summary)}</p>
          <div className="mt-2 space-y-1 text-xs font-medium text-amber-900">
            <p>Visualização somente leitura.</p>
            <p>Esta prévia não altera o canvas atual.</p>
            <p>Use Restaurar esta versão apenas se desejar substituir o canvas atual.</p>
          </div>
        </div>

        <div className="flex flex-col items-start gap-2 sm:items-end">
          <time className="text-xs text-slate-600">Criada em {formatDate(version.created_at)}</time>
          <span className={versionKindClassName(version.summary)}>{versionKindLabel(version.summary)}</span>

          <div className="flex flex-wrap justify-start gap-2 sm:justify-end">
            <button
              className="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-800 disabled:cursor-not-allowed disabled:opacity-60"
              disabled={restoreLoading}
              onClick={() => setShowRestoreConfirmation(true)}
              type="button"
            >
              Restaurar esta versão
            </button>

            <button
              className="rounded-md border border-brand-300 bg-white px-3 py-1.5 text-xs font-medium text-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
              disabled={exportLoading || restoreLoading}
              onClick={onExportPdf}
              type="button"
            >
              {exportLoading ? "Gerando PDF..." : "Exportar PDF"}
            </button>

            <button
              className="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-900"
              disabled={restoreLoading}
              onClick={onClose}
              type="button"
            >
              Fechar prévia
            </button>
          </div>
        </div>
      </div>

      {showRestoreConfirmation ? (
        <div className="mt-4 rounded-md border border-red-200 bg-white p-3">
          <h5 className="text-sm font-semibold text-red-900">Confirmar restauração da versão</h5>
          <div className="mt-2 space-y-2 text-sm leading-6 text-slate-700">
            <p>O canvas atual será substituído pelo conteúdo desta versão histórica.</p>
            <p>Antes da restauração, o sistema criará automaticamente um backup do canvas atual.</p>
            <p>Você poderá encontrar esse backup no histórico.</p>
          </div>

          <label className="mt-3 block text-sm font-medium text-slate-800">
            Digite RESTAURAR para habilitar a confirmação.
            <input
              className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 outline-none focus:border-red-500"
              disabled={restoreLoading}
              onChange={(event) => setRestoreConfirmationText(event.target.value)}
              placeholder="RESTAURAR"
              value={restoreConfirmationText}
            />
          </label>

          {restoreLoading ? <p className="mt-3 text-sm font-medium text-red-800">Restaurando versão e criando backup automático...</p> : null}

          <div className="mt-3 flex flex-wrap gap-2">
            <button
              className="rounded-md bg-red-700 px-3 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
              disabled={restoreLoading || !canConfirmRestore}
              onClick={onRestore}
              type="button"
            >
              {restoreLoading ? "Restaurando..." : "Confirmar restauração"}
            </button>

            <button
              className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
              disabled={restoreLoading}
              onClick={() => {
                setShowRestoreConfirmation(false);
                setRestoreConfirmationText("");
              }}
              type="button"
            >
              Cancelar
            </button>
          </div>
        </div>
      ) : null}

      {restoreResult ? (
        <p className="mt-4 rounded-md border border-brand-200 bg-brand-50 p-3 text-sm font-medium text-brand-800">
          Versão restaurada com sucesso. Backup automático criado como versão {restoreResult.backup_version_number}.
        </p>
      ) : null}

      {restoreError ? <p className="mt-4 rounded-md border border-red-200 bg-white p-3 text-sm font-medium text-red-700">{restoreError}</p> : null}

      {exportError ? <p className="mt-4 rounded-md border border-red-200 bg-white p-3 text-sm font-medium text-red-700">{exportError}</p> : null}

      {historicalCanvas ? (
        <div className="mt-4 grid gap-3 lg:grid-cols-2">
          {historicalPreviewFields.map((field) => (
            <section className="rounded-md border border-amber-100 bg-white p-3" key={field.key}>
              <h5 className="text-sm font-semibold text-slate-900">{field.label}</h5>
              <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                {historicalCanvas[field.key].trim() === "" ? "Não preenchido" : historicalCanvas[field.key]}
              </p>
            </section>
          ))}
        </div>
      ) : (
        <div className="mt-4 rounded-md border border-amber-100 bg-white p-3">
          <p className="text-sm text-slate-700">Dados da versão carregados, mas não foi possível renderizar todos os campos.</p>
          {preview.technicalPreview ? (
            <details className="mt-3">
              <summary className="cursor-pointer text-xs font-medium text-slate-600">Prévia técnica limitada</summary>
              <pre className="mt-2 max-h-48 overflow-auto rounded-md bg-slate-950 p-3 text-xs leading-5 text-slate-100">{preview.technicalPreview}</pre>
            </details>
          ) : null}
        </div>
      )}
    </aside>
  );
}

function normalizeCanvas(value: MapDraft["canvas_json"]): MapCanvasData {
  if (value === null || value === undefined) {
    return { ...emptyCanvas };
  }

  if (typeof value === "string") {
    try {
      const parsed = JSON.parse(value) as unknown;

      return normalizeCanvas(parsed as MapDraft["canvas_json"]);
    } catch {
      return { ...emptyCanvas };
    }
  }

  if (typeof value === "object" && !Array.isArray(value)) {
    return Object.fromEntries(
      Object.keys(emptyCanvas).map((key) => {
        const typedKey = key as keyof MapCanvasData;
        const fieldValue = value[typedKey];

        return [typedKey, typeof fieldValue === "string" ? fieldValue : ""];
      })
    ) as MapCanvasData;
  }

  return { ...emptyCanvas };
}

function serializeCanvas(canvas: MapCanvasData): string {
  return JSON.stringify(canvas);
}

function formatDate(value: string): string {
  if (value.trim() === "") {
    return "-";
  }

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

function isAutomaticBackup(summary?: string | null): boolean {
  const normalizedSummary = (summary ?? "").toLowerCase();

  return normalizedSummary.includes("backup") || normalizedSummary.includes("restauracao") || normalizedSummary.includes("restauração");
}

function versionKindLabel(summary?: string | null): string {
  return isAutomaticBackup(summary) ? "Backup automático" : "Snapshot manual";
}

function versionKindHelp(summary?: string | null): string {
  return isAutomaticBackup(summary)
    ? "Criado automaticamente antes de uma restauração."
    : "Criado ao salvar alterações no canvas.";
}

function versionKindClassName(summary?: string | null): string {
  const baseClassName = "rounded-full px-2 py-0.5 text-xs font-semibold";

  if (isAutomaticBackup(summary)) {
    return `${baseClassName} bg-red-50 text-red-700`;
  }

  return `${baseClassName} bg-brand-50 text-brand-700`;
}

function versionFilterButtonClassName(active: boolean): string {
  const baseClassName = "rounded-md border px-3 py-1.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-60";

  if (active) {
    return `${baseClassName} border-brand-600 bg-brand-600 text-white`;
  }

  return `${baseClassName} border-slate-300 bg-white text-slate-700 hover:border-brand-300 hover:text-brand-800`;
}

function buildHistoricalPreview(value: unknown): { canvas: MapCanvasData | null; technicalPreview: string | null } {
  if (typeof value === "string") {
    try {
      return buildHistoricalPreview(JSON.parse(value) as unknown);
    } catch {
      return { canvas: null, technicalPreview: limitTechnicalPreview(value) };
    }
  }

  if (value !== null && typeof value === "object" && !Array.isArray(value)) {
    return { canvas: normalizeCanvas(value as MapDraft["canvas_json"]), technicalPreview: null };
  }

  return { canvas: null, technicalPreview: limitTechnicalPreview(value) };
}

function limitTechnicalPreview(value: unknown): string | null {
  const maxPreviewLength = 2000;

  try {
    const formatted = JSON.stringify(value, null, 2);

    if (formatted === undefined) {
      return null;
    }

    if (formatted.length > maxPreviewLength) {
      return `${formatted.slice(0, maxPreviewLength)}\n\n...previsualizacao limitada.`;
    }

    return formatted;
  } catch {
    return null;
  }
}

function statusText(isDirty: boolean, saveState: "idle" | "saved" | "error"): string {
  if (isDirty) {
    return "Alterações não salvas";
  }

  if (saveState === "saved") {
    return "Canvas salvo";
  }

  if (saveState === "error") {
    return "Erro ao salvar";
  }

  return "Sem alterações";
}

function statusClassName(isDirty: boolean, saveState: "idle" | "saved" | "error"): string {
  const baseClassName = "rounded-full px-3 py-1 text-xs font-medium";

  if (isDirty) {
    return `${baseClassName} bg-amber-100 text-amber-800`;
  }

  if (saveState === "saved") {
    return `${baseClassName} bg-brand-100 text-brand-800`;
  }

  if (saveState === "error") {
    return `${baseClassName} bg-red-100 text-red-800`;
  }

  return `${baseClassName} bg-slate-100 text-slate-600`;
}