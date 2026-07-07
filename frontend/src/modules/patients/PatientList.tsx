import { useCallback, useEffect, useState } from "react";
import {
  archivePatient,
  createPatient,
  getPatient,
  listPatients,
  updatePatient,
  type Patient,
} from "../../shared/api/httpClient";
import { PatientForm } from "./PatientForm";

export function PatientList() {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [query, setQuery] = useState("");
  const [editing, setEditing] = useState<Patient | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await listPatients({ q: query });
      setPatients(response.data);
    } catch {
      setError("Nao foi possivel carregar pacientes.");
    } finally {
      setLoading(false);
    }
  }, [query]);

  useEffect(() => {
    void load();
  }, [load]);

  async function handleSubmit(payload: Partial<Patient>) {
    try {
      if (editing) {
        await updatePatient(editing.id, payload);
      } else {
        await createPatient(payload);
      }

      setEditing(null);
      setShowForm(false);
      await load();
    } catch {
      setError("Nao foi possivel salvar o paciente.");
    }
  }

  async function handleEdit(patient: Patient) {
    try {
      setEditing(await getPatient(patient.id));
      setShowForm(true);
    } catch {
      setError("Nao foi possivel abrir o paciente.");
    }
  }

  async function handleArchive(id: string) {
    await archivePatient(id).catch(() => setError("Nao foi possivel arquivar o paciente."));
    await load();
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row">
        <input className="flex-1 rounded-md border border-slate-300 px-3 py-2" placeholder="Buscar por nome ou código" value={query} onChange={(event) => setQuery(event.target.value)} />
        <button className="rounded-md border border-slate-300 px-4 py-2" onClick={load} type="button">Buscar</button>
        <button className="rounded-md bg-brand-600 px-4 py-2 font-medium text-white" onClick={() => { setEditing(null); setShowForm(true); }} type="button">Novo paciente</button>
      </div>
      {showForm ? <PatientForm patient={editing} onCancel={() => setShowForm(false)} onSubmit={handleSubmit} /> : null}
      {error ? <p className="text-sm text-red-700">{error}</p> : null}
      {loading ? <p className="text-sm text-slate-500">Carregando pacientes...</p> : null}
      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
        {patients.map((patient) => (
          <div className="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between" key={patient.id}>
            <div>
              <p className="font-medium text-slate-950">{patient.name}</p>
              <p className="text-sm text-slate-500">{patient.internal_code ?? "Sem código"} · {patient.status}</p>
            </div>
            <div className="flex gap-2">
              <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={() => void handleEdit(patient)} type="button">Editar</button>
              <button className="rounded-md border border-slate-300 px-3 py-1 text-sm" onClick={() => void handleArchive(patient.id)} type="button">Arquivar</button>
            </div>
          </div>
        ))}
        {patients.length === 0 && !loading ? <p className="p-4 text-sm text-slate-500">Nenhum paciente encontrado.</p> : null}
      </div>
    </section>
  );
}
