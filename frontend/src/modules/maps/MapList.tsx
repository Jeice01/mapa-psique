import { useCallback, useEffect, useState } from "react";
import {
  archiveMap,
  createMap,
  getMap,
  listMaps,
  updateMap,
  type MapDraft,
} from "../../shared/api/httpClient";
import { MapDetails } from "./MapDetails";
import { MapForm } from "./MapForm";
import { formatMapStatus } from "./mapStatus";

export function MapList() {
  const [maps, setMaps] = useState<MapDraft[]>([]);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("");
  const [editing, setEditing] = useState<MapDraft | null>(null);
  const [details, setDetails] = useState<MapDraft | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await listMaps({ q: query, status });
      setMaps(response.data);
    } catch {
      setError("Nao foi possivel carregar mapas.");
    } finally {
      setLoading(false);
    }
  }, [query, status]);

  useEffect(() => {
    void load();
  }, [load]);

  async function handleSubmit(payload: Partial<MapDraft>) {
    try {
      if (editing) {
        await updateMap(editing.id, payload);
      } else {
        await createMap(payload);
      }

      setEditing(null);
      setShowForm(false);
      await load();
    } catch {
      setError("Nao foi possivel salvar o mapa.");
    }
  }

  async function handleEdit(map: MapDraft) {
    try {
      setEditing(await getMap(map.id));
      setShowForm(true);
    } catch {
      setError("Nao foi possivel abrir o mapa.");
    }
  }

  async function handleDetails(map: MapDraft) {
    try {
      setDetails(await getMap(map.id));
    } catch {
      setError("Nao foi possivel carregar os detalhes.");
    }
  }

  async function handleDetailsSave(payload: Partial<MapDraft>) {
    if (!details) {
      return;
    }

    const updatedMap = await updateMap(details.id, payload);
    setDetails(updatedMap);
    setMaps((current) => current.map((map) => (map.id === updatedMap.id ? { ...map, ...updatedMap } : map)));
  }

  async function handleArchive(id: string) {
    await archiveMap(id).catch(() => setError("Nao foi possivel arquivar o mapa."));
    await load();
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-col gap-3 lg:flex-row">
        <input className="flex-1 rounded-md border border-slate-300 px-3 py-2" placeholder="Buscar por título" value={query} onChange={(event) => setQuery(event.target.value)} />
        <select className="rounded-md border border-slate-300 px-3 py-2" value={status} onChange={(event) => setStatus(event.target.value)}>
          <option value="">Todos os status</option>
          <option value="draft">{formatMapStatus("draft")}</option>
          <option value="ready_for_analysis">{formatMapStatus("ready_for_analysis")}</option>
          <option value="analyzed">{formatMapStatus("analyzed")}</option>
          <option value="archived">{formatMapStatus("archived")}</option>
        </select>
        <button className="rounded-md border border-slate-300 px-4 py-2" onClick={load} type="button">Filtrar</button>
        <button className="rounded-md bg-brand-600 px-4 py-2 font-medium text-white" onClick={() => { setEditing(null); setShowForm(true); }} type="button">Novo mapa</button>
      </div>
      {showForm ? <MapForm map={editing} onCancel={() => setShowForm(false)} onSubmit={handleSubmit} /> : null}
      {details ? <MapDetails map={details} onClose={() => setDetails(null)} onSave={handleDetailsSave} /> : null}
      {error ? <p className="text-sm text-red-700">{error}</p> : null}
      {loading ? <p className="text-sm text-slate-500">Carregando mapas...</p> : null}
      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
        {maps.map((map) => (
          <div className="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-0 lg:flex-row lg:items-center lg:justify-between" key={map.id}>
            <div>
              <p className="font-medium text-slate-950">{map.title}</p>
              <p className="text-sm text-slate-500">{map.patient_name ?? "Sem paciente"} · {formatMapStatus(map.status)}</p>
            </div>
            <div className="flex flex-wrap gap-2">
              <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={() => void handleDetails(map)} type="button">Detalhes</button>
              <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={() => void handleEdit(map)} type="button">Editar</button>
              <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={() => void handleArchive(map.id)} type="button">Arquivar</button>
            </div>
          </div>
        ))}
        {maps.length === 0 && !loading ? <p className="p-4 text-sm text-slate-500">Nenhum mapa encontrado.</p> : null}
      </div>
    </section>
  );
}
