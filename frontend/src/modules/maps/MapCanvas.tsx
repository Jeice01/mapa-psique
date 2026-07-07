import { FormEvent, useEffect, useState } from "react";
import type { MapCanvasData, MapDraft } from "../../shared/api/httpClient";

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

const fields: Array<{ key: keyof MapCanvasData; label: string }> = [
  { key: "main_demand", label: "Queixa ou demanda principal" },
  { key: "current_context", label: "Contexto de vida atual" },
  { key: "emotional_history", label: "História emocional relevante" },
  { key: "recurring_patterns", label: "Padrões recorrentes" },
  { key: "core_beliefs", label: "Crenças centrais" },
  { key: "defense_strategies", label: "Estratégias de proteção ou defesa" },
  { key: "internal_resources", label: "Potenciais e recursos internos" },
  { key: "reflective_hypotheses", label: "Hipóteses reflexivas" },
  { key: "next_steps", label: "Próximos passos" },
];

export function MapCanvas({ map, onSave }: Props) {
  const [canvas, setCanvas] = useState<MapCanvasData>(() => normalizeCanvas(map.canvas_json));
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setCanvas(normalizeCanvas(map.canvas_json));
    setMessage(null);
    setError(null);
  }, [map.canvas_json, map.id]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setMessage(null);
    setError(null);

    try {
      await onSave({ canvas_json: canvas });
      setMessage("Canvas salvo.");
    } catch {
      setError("Não foi possível salvar o canvas.");
    } finally {
      setSaving(false);
    }
  }

  function updateField(key: keyof MapCanvasData, value: string) {
    setCanvas((current) => ({ ...current, [key]: value }));
  }

  return (
    <form className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4" onSubmit={handleSubmit}>
      <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-base font-semibold text-slate-950">Canvas do Mapa da Psiquê</h3>
          <p className="mt-1 text-sm text-slate-600">Registro reflexivo estruturado do mapa.</p>
        </div>
        <button className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60" disabled={saving} type="submit">
          {saving ? "Salvando..." : "Salvar canvas"}
        </button>
      </div>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        {fields.map((field) => (
          <label className="block text-sm font-medium text-slate-700" key={field.key}>
            {field.label}
            <textarea
              className="mt-1 min-h-28 w-full resize-y rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
              value={canvas[field.key]}
              onChange={(event) => updateField(field.key, event.target.value)}
            />
          </label>
        ))}
      </div>

      {message ? <p className="mt-3 text-sm text-brand-700">{message}</p> : null}
      {error ? <p className="mt-3 text-sm text-red-700">{error}</p> : null}
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
    return {
      ...emptyCanvas,
      ...Object.fromEntries(
        Object.keys(emptyCanvas).map((key) => {
          const typedKey = key as keyof MapCanvasData;
          const fieldValue = value[typedKey];

          return [typedKey, typeof fieldValue === "string" ? fieldValue : ""];
        })
      ),
    };
  }

  return { ...emptyCanvas };
}
