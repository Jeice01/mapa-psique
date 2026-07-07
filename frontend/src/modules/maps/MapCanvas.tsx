import { FormEvent, useCallback, useEffect, useState } from "react";
import { getMapCanvasVersion, listMapCanvasVersions } from "../../shared/api/httpClient";
import type { MapCanvasData, MapCanvasVersion, MapCanvasVersionDetails, MapDraft } from "../../shared/api/httpClient";

type Props = {
  map: MapDraft;
  onSave: (payload: Partial<MapDraft>) => Promise<void>;
};

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

export function MapCanvas({ map, onSave }: Props) {
  const [canvas, setCanvas] = useState<MapCanvasData>(() => normalizeCanvas(map.canvas_json));
  const [savedCanvas, setSavedCanvas] = useState<MapCanvasData>(() => normalizeCanvas(map.canvas_json));
  const [versions, setVersions] = useState<MapCanvasVersion[]>([]);
  const [versionsLoading, setVersionsLoading] = useState(false);
  const [versionsError, setVersionsError] = useState<string | null>(null);
  const [selectedVersion, setSelectedVersion] = useState<MapCanvasVersionDetails | null>(null);
  const [versionDetailsLoadingId, setVersionDetailsLoadingId] = useState<string | null>(null);
  const [versionDetailsError, setVersionDetailsError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [saveState, setSaveState] = useState<"idle" | "saved" | "error">("idle");
  const isDirty = serializeCanvas(canvas) !== serializeCanvas(savedCanvas);

  const loadVersions = useCallback(async () => {
    setVersionsLoading(true);
    setVersionsError(null);

    try {
      setVersions(await listMapCanvasVersions(map.id));
    } catch {
      setVersionsError("Nao foi possivel carregar o historico do canvas.");
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

    try {
      setSelectedVersion(await getMapCanvasVersion(map.id, versionId));
    } catch {
      setVersionDetailsError("Nao foi possivel carregar os detalhes da versao.");
    } finally {
      setVersionDetailsLoadingId(null);
    }
  }

  return (
    <form className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-5" onSubmit={handleSubmit}>
      <div className="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-950">Canvas do Mapa da Psiquê</h3>
          <p className="mt-1 max-w-2xl text-sm text-slate-600">Registro reflexivo estruturado para organizar a leitura inicial do mapa.</p>
        </div>
        <div className="flex flex-col items-start gap-2 sm:items-end">
          <span className={statusClassName(isDirty, saveState)}>
            {saving ? "Salvando..." : statusText(isDirty, saveState)}
          </span>
          <button className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60" disabled={saving || !isDirty} type="submit">
            {saving ? "Salvando..." : "Salvar canvas"}
          </button>
        </div>
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

      <section className="mt-5 border-t border-slate-200 pt-4">
        <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <h4 className="text-sm font-semibold text-slate-950">Histórico do canvas</h4>
          {versionsLoading ? <span className="text-xs text-slate-500">Carregando histórico...</span> : null}
        </div>
        {versionsError ? <p className="mt-2 text-sm text-red-700">{versionsError}</p> : null}
        {!versionsLoading && versions.length === 0 && !versionsError ? (
          <p className="mt-2 text-sm text-slate-500">Nenhuma versão salva ainda.</p>
        ) : null}
        {versions.length > 0 ? (
          <div className="mt-3 overflow-hidden rounded-md border border-slate-200 bg-white">
            {versions.map((version) => (
              <div className="flex flex-col gap-1 border-b border-slate-100 px-3 py-2 last:border-0 sm:flex-row sm:items-center sm:justify-between" key={version.id}>
                <div>
                  <p className="text-sm font-medium text-slate-900">Versão {version.version_number}</p>
                  <p className="text-xs text-slate-500">{version.summary ?? "Snapshot do canvas"}</p>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                  <time className="text-xs text-slate-500">{formatDate(version.created_at)}</time>
                  <button
                    className="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={versionDetailsLoadingId === version.id}
                    onClick={() => void handleViewVersion(version.id)}
                    type="button"
                  >
                    {versionDetailsLoadingId === version.id ? "Carregando..." : "Ver detalhes"}
                  </button>
                </div>
              </div>
            ))}
          </div>
        ) : null}
        {versionDetailsError ? <p className="mt-3 text-sm text-red-700">{versionDetailsError}</p> : null}
        {selectedVersion ? (
          <div className="mt-4 rounded-md border border-slate-200 bg-white p-3">
            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-sm font-semibold text-slate-950">Versão {selectedVersion.version_number}</p>
                <p className="text-xs text-slate-500">{selectedVersion.summary ?? "Snapshot do canvas"}</p>
              </div>
              <time className="text-xs text-slate-500">{formatDate(selectedVersion.created_at)}</time>
            </div>
            <pre className="mt-3 max-h-64 overflow-auto rounded-md bg-slate-950 p-3 text-xs leading-5 text-slate-100">
              {formatCanvasData(selectedVersion.canvas_data)}
            </pre>
          </div>
        ) : null}
      </section>
    </form>
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

  return value;
}

function formatCanvasData(value: unknown): string {
  const maxPreviewLength = 6000;

  try {
    const formatted = JSON.stringify(value, null, 2);

    if (formatted.length > maxPreviewLength) {
      return `${formatted.slice(0, maxPreviewLength)}\n\n...previsualizacao limitada.`;
    }

    return formatted;
  } catch {
    return "Dados da versao carregados com sucesso.";
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
