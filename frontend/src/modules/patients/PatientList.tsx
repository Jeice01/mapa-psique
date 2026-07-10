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

type PatientStatusFilter = "" | "active" | "inactive" | "archived";

const statusFilters: Array<{
  value: PatientStatusFilter;
  label: string;
}> = [
  { value: "", label: "Todos" },
  { value: "active", label: "Ativos" },
  { value: "inactive", label: "Inativos" },
  { value: "archived", label: "Arquivados" },
];

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

function getEmptyStateTitle(
  appliedQuery: string,
  statusFilter: PatientStatusFilter,
) {
  if (appliedQuery) {
    return `Nenhum paciente encontrado para “${appliedQuery}”.`;
  }

  const titles: Record<PatientStatusFilter, string> = {
    "": "Nenhum paciente cadastrado.",
    active: "Nenhum paciente ativo encontrado.",
    inactive: "Nenhum paciente inativo encontrado.",
    archived: "Nenhum paciente arquivado encontrado.",
  };

  return titles[statusFilter];
}

function getEmptyStateDescription(
  appliedQuery: string,
  statusFilter: PatientStatusFilter,
) {
  if (appliedQuery) {
    return "Confira a escrita do nome ou do código e faça uma nova busca.";
  }

  const descriptions: Record<PatientStatusFilter, string> = {
    "": "Cadastre o primeiro paciente para começar.",
    active: "Não existem pacientes com acompanhamento ativo.",
    inactive: "Não existem pacientes com acompanhamento inativo.",
    archived: "Nenhum paciente foi arquivado até o momento.",
  };

  return descriptions[statusFilter];
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
  const [statusFilter, setStatusFilter] =
    useState<PatientStatusFilter>("");
  const [editing, setEditing] = useState<Patient | null>(null);
  const [patientToArchive, setPatientToArchive] =
    useState<Patient | null>(null);
  const [archivingId, setArchivingId] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const latestRequestRef = useRef(0);

  const load = useCallback(
    async (
      searchText = "",
      selectedStatus: PatientStatusFilter = "",
    ) => {
      const normalizedSearch = searchText.trim();
      const requestId = latestRequestRef.current + 1;
      const params: Record<string, string> = {};

      if (normalizedSearch) {
        params.q = normalizedSearch;
      }

      if (selectedStatus) {
        params.status = selectedStatus;
      }

      latestRequestRef.current = requestId;
      setLoading(true);
      setError(null);

      try {
        const response = await listPatients(params);

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
    },
    [],
  );

  useEffect(() => {
    void load("", "");
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
      await load(appliedQuery, statusFilter);

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

  function requestArchive(patient: Patient) {
    setError(null);
    setSuccess(null);
    setPatientToArchive(patient);
  }

  function cancelArchive() {
    if (archivingId) {
      return;
    }

    setPatientToArchive(null);
  }

  async function confirmArchive() {
    if (!patientToArchive || archivingId) {
      return;
    }

    const patient = patientToArchive;

    setError(null);
    setSuccess(null);
    setArchivingId(patient.id);

    try {
      await archivePatient(patient.id);
      setPatientToArchive(null);
      await load(appliedQuery, statusFilter);
      setSuccess(`Paciente “${patient.name}” arquivado com sucesso.`);
    } catch {
      setError(
        "Não foi possível arquivar o paciente. Tente novamente em alguns instantes.",
      );
    } finally {
      setArchivingId(null);
    }
  }

  function handleSearch() {
    if (loading) {
      return;
    }

    setSuccess(null);
    void load(query, statusFilter);
  }

  function handleClearSearch() {
    if (loading) {
      return;
    }

    setQuery("");
    setSuccess(null);
    void load("", statusFilter);
  }

  function handleStatusFilter(nextStatus: PatientStatusFilter) {
    if (loading || nextStatus === statusFilter) {
      return;
    }

    setStatusFilter(nextStatus);
    setSuccess(null);
    void load(query, nextStatus);
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

      <div
        aria-label="Filtrar pacientes por status"
        className="flex flex-wrap gap-2"
        role="group"
      >
        {statusFilters.map((filter) => {
          const isSelected = statusFilter === filter.value;

          return (
            <button
              aria-pressed={isSelected}
              className={`rounded-full border px-4 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60 ${
                isSelected
                  ? "border-brand-600 bg-brand-600 text-white"
                  : "border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
              }`}
              disabled={loading}
              key={filter.value || "all"}
              onClick={() => handleStatusFilter(filter.value)}
              type="button"
            >
              {filter.label}
            </button>
          );
        })}
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
          const isArchived = patient.status === "archived";
          const isArchiving = archivingId === patient.id;

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
                        ? `${patient.age} ${
                            patient.age === 1 ? "ano" : "anos"
                          }`
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

                {!isArchived ? (
                  <button
                    className="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={isArchiving}
                    onClick={() => requestArchive(patient)}
                    type="button"
                  >
                    {isArchiving ? "Arquivando..." : "Arquivar"}
                  </button>
                ) : null}
              </div>
            </div>
          );
        })}

        {patients.length === 0 && !loading && !error ? (
          <div className="px-6 py-10 text-center">
            <p className="font-medium text-slate-800">
              {getEmptyStateTitle(appliedQuery, statusFilter)}
            </p>

            <p className="mt-1 text-sm text-slate-500">
              {getEmptyStateDescription(appliedQuery, statusFilter)}
            </p>

            {appliedQuery ? (
              <button
                className="mt-4 rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                onClick={handleClearSearch}
                type="button"
              >
                Limpar busca
              </button>
            ) : null}
          </div>
        ) : null}
      </div>

      {patientToArchive ? (
        <div
          aria-labelledby="archive-patient-title"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
          role="dialog"
        >
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2
              className="text-lg font-semibold text-slate-950"
              id="archive-patient-title"
            >
              Arquivar paciente
            </h2>

            <p className="mt-3 text-sm text-slate-600">
              Deseja realmente arquivar o paciente{" "}
              <strong className="font-semibold text-slate-900">
                “{patientToArchive.name}”
              </strong>
              ?
            </p>

            <p className="mt-2 text-sm text-slate-500">
              O paciente deixará de aparecer entre os registros ativos, mas
              seus dados serão preservados no sistema.
            </p>

            <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <button
                className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                disabled={Boolean(archivingId)}
                onClick={cancelArchive}
                type="button"
              >
                Cancelar
              </button>

              <button
                className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                disabled={Boolean(archivingId)}
                onClick={() => void confirmArchive()}
                type="button"
              >
                {archivingId
                  ? "Arquivando..."
                  : "Confirmar arquivamento"}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </section>
  );
}