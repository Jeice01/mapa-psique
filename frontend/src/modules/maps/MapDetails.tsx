import type { MapDraft } from "../../shared/api/httpClient";

type Props = {
  map: MapDraft;
  onClose: () => void;
};

export function MapDetails({ map, onClose }: Props) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h2 className="text-xl font-semibold text-slate-950">{map.title}</h2>
          <p className="mt-1 text-sm text-slate-500">Status: {map.status}</p>
        </div>
        <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={onClose} type="button">Fechar</button>
      </div>
      <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <dt className="font-medium text-slate-500">Paciente</dt>
          <dd className="mt-1 text-slate-950">{map.patient_name ?? map.patient_id ?? "Não vinculado"}</dd>
        </div>
        <div>
          <dt className="font-medium text-slate-500">Criado em</dt>
          <dd className="mt-1 text-slate-950">{map.created_at ?? "-"}</dd>
        </div>
      </dl>
      <div className="mt-4 rounded-md border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
        Canvas será implementado na próxima etapa.
      </div>
    </section>
  );
}
