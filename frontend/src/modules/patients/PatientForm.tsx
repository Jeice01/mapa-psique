import { FormEvent, useEffect, useState } from "react";
import type { Patient } from "../../shared/api/httpClient";

type Props = {
  patient?: Patient | null;
  onSubmit: (payload: Partial<Patient>) => Promise<void>;
  onCancel?: () => void;
};

type FormErrors = {
  name?: string;
  age?: string;
  status?: string;
};

const allowedStatuses = ["active", "inactive", "archived"] as const;

export function PatientForm({ patient, onSubmit, onCancel }: Props) {
  const [name, setName] = useState("");
  const [internalCode, setInternalCode] = useState("");
  const [age, setAge] = useState("");
  const [notes, setNotes] = useState("");
  const [status, setStatus] = useState("active");
  const [errors, setErrors] = useState<FormErrors>({});
  const [loading, setLoading] = useState(false);

  const isEditing = patient !== null && patient !== undefined;

  useEffect(() => {
    setName(patient?.name ?? "");
    setInternalCode(patient?.internal_code ?? "");
    setAge(
      patient?.age === null || patient?.age === undefined
        ? ""
        : String(patient.age),
    );
    setNotes(patient?.notes ?? "");
    setStatus(patient?.status ?? "active");
    setErrors({});
  }, [patient]);

  function validateForm() {
    const nextErrors: FormErrors = {};
    const normalizedName = name.trim();

    if (!normalizedName) {
      nextErrors.name = "Informe o nome do paciente.";
    }

    if (age !== "") {
      const parsedAge = Number(age);

      if (
        !Number.isInteger(parsedAge) ||
        parsedAge < 0 ||
        parsedAge > 120
      ) {
        nextErrors.age = "Informe uma idade válida entre 0 e 120 anos.";
      }
    }

    if (
      !allowedStatuses.includes(
        status as (typeof allowedStatuses)[number],
      )
    ) {
      nextErrors.status = "Selecione um status válido.";
    }

    setErrors(nextErrors);

    return Object.keys(nextErrors).length === 0;
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (loading || !validateForm()) {
      return;
    }

    setLoading(true);

    try {
      await onSubmit({
        name: name.trim(),
        internal_code: internalCode.trim() || null,
        age: age === "" ? null : Number(age),
        notes: notes.trim(),
        status,
      });
    } finally {
      setLoading(false);
    }
  }

  return (
    <form
      className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
      onSubmit={handleSubmit}
      noValidate
    >
      <div className="mb-4">
        <h2 className="text-lg font-semibold text-slate-950">
          {isEditing ? "Editar paciente" : "Novo paciente"}
        </h2>

        <p className="mt-1 text-sm text-slate-500">
          {isEditing
            ? "Atualize os dados cadastrais do paciente."
            : "Preencha os dados para cadastrar um novo paciente."}
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <label className="text-sm font-medium text-slate-700">
          Nome completo
          <input
            aria-describedby={errors.name ? "patient-name-error" : undefined}
            aria-invalid={Boolean(errors.name)}
            autoFocus
            className={`mt-1 w-full rounded-md border px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 ${
              errors.name
                ? "border-red-400"
                : "border-slate-300"
            }`}
            disabled={loading}
            maxLength={100}
            value={name}
            onChange={(event) => {
              setName(event.target.value);

              if (errors.name) {
                setErrors((current) => ({
                  ...current,
                  name: undefined,
                }));
              }
            }}
          />

          {errors.name ? (
            <span
              className="mt-1 block text-xs font-normal text-red-700"
              id="patient-name-error"
              role="alert"
            >
              {errors.name}
            </span>
          ) : null}
        </label>

        <label className="text-sm font-medium text-slate-700">
          Código interno
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100"
            disabled={loading}
            maxLength={5000}
            value={internalCode}
            onChange={(event) => setInternalCode(event.target.value)}
          />

          <span className="mt-1 block text-xs font-normal text-slate-500">
            {notes.length} de 5.000 caracteres
            
          </span>
        </label>

        <label className="text-sm font-medium text-slate-700">
          Idade em anos
          <input
            aria-describedby={errors.age ? "patient-age-error" : undefined}
            aria-invalid={Boolean(errors.age)}
            className={`mt-1 w-full rounded-md border px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 ${
              errors.age
                ? "border-red-400"
                : "border-slate-300"
            }`}
            disabled={loading}
            inputMode="numeric"
            min="0"
            max="120"
            step="1"
            type="number"
            value={age}
            onChange={(event) => {
              setAge(event.target.value);

              if (errors.age) {
                setErrors((current) => ({
                  ...current,
                  age: undefined,
                }));
              }
            }}
          />

          {errors.age ? (
            <span
              className="mt-1 block text-xs font-normal text-red-700"
              id="patient-age-error"
              role="alert"
            >
              {errors.age}
            </span>
          ) : (
            <span className="mt-1 block text-xs font-normal text-slate-500">
              Informe a idade em anos completos.
            </span>
          )}
        </label>

        <label className="text-sm font-medium text-slate-700">
          Status do acompanhamento
          <select
            aria-describedby={
              errors.status ? "patient-status-error" : undefined
            }
            aria-invalid={Boolean(errors.status)}
            className={`mt-1 w-full rounded-md border px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 ${
              errors.status
                ? "border-red-400"
                : "border-slate-300"
            }`}
            disabled={loading}
            value={status}
            onChange={(event) => {
              setStatus(event.target.value);

              if (errors.status) {
                setErrors((current) => ({
                  ...current,
                  status: undefined,
                }));
              }
            }}
          >
            <option value="active">Ativo</option>
            <option value="inactive">Inativo</option>
            <option value="archived">Arquivado</option>
          </select>

          {errors.status ? (
            <span
              className="mt-1 block text-xs font-normal text-red-700"
              id="patient-status-error"
              role="alert"
            >
              {errors.status}
            </span>
          ) : null}
        </label>
      </div>

      <label className="mt-4 block text-sm font-medium text-slate-700">
        Observações
        <textarea
          className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100"
          disabled={loading}
          value={notes}
          onChange={(event) => setNotes(event.target.value)}
        />

        <span className="mt-1 block text-xs font-normal text-slate-500">
          Registre apenas informações adicionais necessárias para o acompanhamento.
        </span>
      </label>

      <div className="mt-4 flex flex-wrap gap-2">
        <button
          className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
          disabled={loading}
          type="submit"
        >
          {loading
            ? isEditing
              ? "Salvando..."
              : "Cadastrando..."
            : isEditing
              ? "Salvar alterações"
              : "Cadastrar paciente"}
        </button>

        {onCancel ? (
          <button
            className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={loading}
            onClick={onCancel}
            type="button"
          >
            Cancelar
          </button>
        ) : null}
      </div>
    </form>
  );
}