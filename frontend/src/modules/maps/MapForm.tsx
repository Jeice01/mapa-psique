import { FormEvent, useEffect, useState } from "react";
import { listPatients, type MapDraft, type Patient } from "../../shared/api/httpClient";

type Props = {
  map?: MapDraft | null;
  onSubmit: (payload: Partial<MapDraft>) => Promise<void>;
  onCancel?: () => void;
};

export function MapForm({ map, onSubmit, onCancel }: Props) {
  const [title, setTitle] = useState("");
  const [patientId, setPatientId] = useState("");
  const [reason, setReason] = useState("");
  const [status, setStatus] = useState("draft");
  const [patients, setPatients] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setTitle(map?.title ?? "");
    setPatientId(map?.patient_id ?? "");
    setReason(map?.reason ?? "");
    setStatus(map?.status ?? "draft");
  }, [map]);

  useEffect(() => {
    listPatients({ per_page: "50" })
      .then((response) => setPatients(response.data))
      .catch(() => setPatients([]));
  }, []);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);

    try {
      await onSubmit({
        title,
        patient_id: patientId || null,
        reason,
        status,
      });
    } finally {
      setLoading(false);
    }
  }

  return (
    <form className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" onSubmit={handleSubmit}>
      <div className="grid gap-3 sm:grid-cols-2">
        <label className="text-sm font-medium text-slate-700">
          Título
          <input className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={title} onChange={(event) => setTitle(event.target.value)} required />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Paciente
          <select className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={patientId} onChange={(event) => setPatientId(event.target.value)}>
            <option value="">Sem paciente vinculado</option>
            {patients.map((patient) => (
              <option key={patient.id} value={patient.id}>{patient.name}</option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium text-slate-700">
          Status
          <select className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="draft">draft</option>
            <option value="ready_for_analysis">ready_for_analysis</option>
            <option value="analyzed">analyzed</option>
            <option value="archived">archived</option>
          </select>
        </label>
      </div>
      <label className="mt-3 block text-sm font-medium text-slate-700">
        Motivo resumido
        <textarea className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2" value={reason} onChange={(event) => setReason(event.target.value)} />
      </label>
      <div className="mt-4 flex gap-2">
        <button className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white" disabled={loading} type="submit">
          {map ? "Salvar mapa" : "Criar rascunho"}
        </button>
        {onCancel ? (
          <button className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" onClick={onCancel} type="button">
            Cancelar
          </button>
        ) : null}
      </div>
    </form>
  );
}
