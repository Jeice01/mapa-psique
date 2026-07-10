import { useCallback, useEffect, useRef, useState } from "react";
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
  const [appliedQuery, setAppliedQuery] = useState("");
  const [editing, setEditing] = useState<Patient | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const latestRequestRef = useRef(0);

  const load = useCallback(async (searchText = "") => {
    const normalizedSearch = searchText.trim();
    const requestId = latestRequestRef.current + 1;

    latestRequestRef.current = requestId;
    setLoading(true);
    setError(null);

    try {
      const response = await listPatients({ q: normalizedSearch });

      if (requestId !== latestRequestRef.current) {
        return;
      }

      setPatients(response.data);
      setAppliedQuery(normalizedSearch);
      setError(null);
    } catch {
      if (requestId !== latestRequestRef.current) {
        return;
      }

      setPatients([]);
      setError("Não foi possível carregar pacientes.");
    } finally {
      if (requestId === latestRequestRef.current) {
        setLoading(false);
      }
    }
  }, []);

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
      await load(appliedQuery);
    } catch {
      setError("Não foi possível salvar o paciente.");
    }
  }

  async function handleEdit(patient: Patient) {
    try {
      setError(null);
      setEditing(await getPatient(patient.id));
      setShowForm(true);
    } catch {
      setError("Não foi possível abrir o paciente.");
    }
  }

  async function handleArchive(id: string) {
    setError(null);

    try {
      await archivePatient(id);
      await load(appliedQuery);
    } catch {
      setError("Não foi possível arquivar o paciente.");
    }
  }

  function handleSearch() {
    if (loading) {
      return;
    }

    void load(query);
  }

  function handleClearSearch() {
    if (loading) {
      return;
    }

    setQuery("");
    void load("");
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row">
        <input
          className="flex-1 rounded-md border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100"
          disabled={loading}
          placeholder="Buscar por nome ou código"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === "Enter") {
              handleSearch();
            }
          }}
        />

        <button
          className="rounded-md border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          disabled={loading}
          onClick={handleSearch}
          type="button"
        >
          {loading ? "Buscando..." : "Buscar"}
        </button>

        {query || appliedQuery ? (
          <button
            className="rounded-md border border-slate-300 px-4 py-2 text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={loading}
            onClick={handleClearSearch}
            type="button"
          >
            Limpar busca
          </button>
        ) : null}

        <button
          className="rounded-md bg-brand-600 px-4 py-2 font-medium text-white transition hover:bg-brand-700"
          onClick={() => {
            setEditing(null);
            setShowForm(true);
          }}
          type="button"
        >
          Novo paciente
        </button>
      </div>

      {showForm ? (
        <PatientForm
          patient={editing}
          onCancel={() => {
            setEditing(null);
            setShowForm(false);
          }}
          onSubmit={handleSubmit}
        />
      ) : null}

      {error ? (
        <div
          className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          {error}
        </div>
      ) : null}

      {loading ? (
        <p className="text-sm text-slate-500" role="status">
          Carregando pacientes...
        </p>
      ) : null}

      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
        {patients.map((patient) => (
          <div
            className="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between"
            key={patient.id}
          >
            <div>
              <p className="font-medium text-slate-950">{patient.name}</p>
              <p className="text-sm text-slate-500">
                {patient.internal_code ?? "Sem código"} · {patient.status}
              </p>
            </div>

            <div className="flex gap-2">
              <button
                className="rounded-md border border-slate-300 px-3 py-1 text-sm transition hover:bg-slate-50"
                onClick={() => void handleEdit(patient)}
                type="button"
              >
                Editar
              </button>

              <button
                className="rounded-md border border-slate-300 px-3 py-1 text-sm transition hover:bg-slate-50"
                onClick={() => void handleArchive(patient.id)}
                type="button"
              >
                Arquivar
              </button>
            </div>
          </div>
        ))}

        {patients.length === 0 && !loading && !error ? (
          <div className="px-6 py-10 text-center">
            <p className="font-medium text-slate-800">
              {appliedQuery
                ? `Nenhum paciente encontrado para “${appliedQuery}”.`
                : "Nenhum paciente cadastrado."}
            </p>

            <p className="mt-1 text-sm text-slate-500">
              {appliedQuery
                ? "Confira a escrita do nome ou do código e faça uma nova busca."
                : "Cadastre o primeiro paciente para começar."}
            </p>

            {appliedQuery ? (
              <button
                className="mt-4 rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                onClick={handleClearSearch}
                type="button"
              >
                Mostrar todos os pacientes
              </button>
            ) : null}
          </div>
        ) : null}
      </div>
    </section>
  );
}
