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

function getStatusLabel(status: string) {
  const labels: Record<string, string> = {
    active: "Ativo",
    inactive: "Inativo",
    archived: "Arquivado",
  };

  return labels[status] ?? status;
}

function getStatusClasses(status: string) {
  const classes: Record<string, string> = {
    active: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
    inactive: "bg-amber-50 text-amber-700 ring-amber-600/20",
    archived: "bg-slate-100 text-slate-600 ring-slate-500/20",
  };

  return classes[status] ?? "bg-slate-100 text-slate-600 ring-slate-500/20";
}

function formatCreatedAt(createdAt?: string) {
  if (!createdAt) {
    return null;
  }

  const date = new Date(createdAt);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return new Intl.DateTimeFormat("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

export function PatientList() {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [query, setQuery] = useState("");
  const [appliedQuery, setAppliedQuery] = useState("");
  const [editing, setEditing] = useState<Patient | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
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
      setError(
        "Não foi possível carregar os pacientes. Tente novamente em alguns instantes.",
      );
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
    const isEditing = editing !== null;

    setError(null);
    setSuccess(null);

    try {
      if (editing) {
        await updatePatient(editing.id, payload);
      } else {
        await createPatient(payload);
      }

      setEditing(null);
      setShowForm(false);
      await load(appliedQuery);

      setSuccess(
        isEditing
          ? "Paciente atualizado com sucesso."
          : "Paciente cadastrado com sucesso.",
      );
    } catch {
      setError(
        isEditing
          ? "Não foi possível atualizar o paciente. Revise os dados e tente novamente."
          : "Não foi possível cadastrar o paciente. Revise os dados e tente novamente.",
      );
    }
  }

  async function handleEdit(patient: Patient) {
    setError(null);
    setSuccess(null);

    try {
      setEditing(await getPatient(patient.id));
      setShowForm(true);
    } catch {
      setError(
        "Não foi possível abrir os dados do paciente. Tente novamente em alguns instantes.",
      );
    }
  }

  async function handleArchive(patient: Patient) {
    setError(null);
    setSuccess(null);

    try {
      await archivePatient(patient.id);
      await load(appliedQuery);
      setSuccess(`Paciente “${patient.name}” arquivado com sucesso.`);
    } catch {
      setError(
        "Não foi possível arquivar o paciente. Tente novamente em alguns instantes.",
      );
    }
  }

  function handleSearch() {
    if (loading) {
      return;
    }

    setSuccess(null);
    void load(query);
  }

  function handleClearSearch() {
    if (loading) {
      return;
    }

    setQuery("");
    setSuccess(null);
    void load("");
  }

  function handleNewPatient() {
    setEditing(null);
    setError(null);
    setSuccess(null);
    setShowForm(true);
  }

  function handleCancelForm() {
    setEditing(null);
    setShowForm(false);
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
          onClick={handleNewPatient}
          type="button"
        >
          Novo paciente
        </button>
      </div>

      {showForm ? (
        <PatientForm
          patient={editing}
          onCancel={handleCancelForm}
          onSubmit={handleSubmit}
        />
      ) : null}

      {success ? (
        <div
          className="flex items-start justify-between gap-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
          role="status"
        >
          <span>{success}</span>

          <button
            aria-label="Fechar mensagem de sucesso"
            className="font-medium text-emerald-700 hover:text-emerald-900"
            onClick={() => setSuccess(null)}
            type="button"
          >
            Fechar
          </button>
        </div>
      ) : null}

      {error ? (
        <div
          className="flex items-start justify-between gap-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          <span>{error}</span>

          <button
            aria-label="Fechar mensagem de erro"
            className="font-medium text-red-700 hover:text-red-900"
            onClick={() => setError(null)}
            type="button"
          >
            Fechar
          </button>
        </div>
      ) : null}

      {loading ? (
        <p className="text-sm text-slate-500" role="status">
          Carregando pacientes...
        </p>
      ) : null}

      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
        {patients.map((patient) => {
          const createdAt = formatCreatedAt(patient.created_at);

          return (
            <div
              className="flex flex-col gap-4 border-b border-slate-100 p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between"
              key={patient.id}
            >
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <p className="truncate font-semibold text-slate-950">
                    {patient.name}
                  </p>

                  <span
                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusClasses(
                      patient.status,
                    )}`}
                  >
                    {getStatusLabel(patient.status)}
                  </span>
                </div>

                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                  <span>
                    Código:{" "}
                    <strong className="font-medium text-slate-700">
                      {patient.internal_code ?? "Não informado"}
                    </strong>
                  </span>

                  <span>
                    Idade:{" "}
                    <strong className="font-medium text-slate-700">
                      {patient.age !== null
                        ? `${patient.age} ${patient.age === 1 ? "ano" : "anos"}`
                        : "Não informada"}
                    </strong>
                  </span>

                  {createdAt ? (
                    <span>
                      Cadastrado em:{" "}
                      <strong className="font-medium text-slate-700">
                        {createdAt}
                      </strong>
                    </span>
                  ) : null}
                </div>
              </div>

              <div className="flex flex-wrap gap-2">
                <button
                  className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                  onClick={() => void handleEdit(patient)}
                  type="button"
                >
                  Editar
                </button>

                <button
                  className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                  onClick={() => void handleArchive(patient)}
                  type="button"
                >
                  Arquivar
                </button>
              </div>
            </div>
          );
        })}

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
