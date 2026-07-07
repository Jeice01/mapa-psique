import { FormEvent, useEffect, useState } from "react";
import type { Patient } from "../../shared/api/httpClient";

type Props = {
  patient?: Patient | null;
  onSubmit: (payload: Partial<Patient>) => Promise<void>;
  onCancel?: () => void;
};

export function PatientForm({ patient, onSubmit, onCancel }: Props) {
  const [name, setName] = useState("");
  const [internalCode, setInternalCode] = useState("");
  const [age, setAge] = useState("");
  const [notes, setNotes] = useState("");
  const [status, setStatus] = useState("active");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setName(patient?.name ?? "");
    setInternalCode(patient?.internal_code ?? "");
    setAge(patient?.age === null || patient?.age === undefined ? "" : String(patient.age));
    setNotes(patient?.notes ?? "");
    setStatus(patient?.status ?? "active");
  }, [patient]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);

    try {
      await onSubmit({
        name,
        internal_code: internalCode || null,
        age: age === "" ? null : Number(age),
        notes,
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
          Nome
          <input className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={name} onChange={(event) => setName(event.target.value)} required />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Código interno
          <input className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={internalCode} onChange={(event) => setInternalCode(event.target.value)} />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Idade
          <input className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" min="0" max="120" type="number" value={age} onChange={(event) => setAge(event.target.value)} />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Status
          <select className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="active">active</option>
            <option value="inactive">inactive</option>
            <option value="archived">archived</option>
          </select>
        </label>
      </div>
      <label className="mt-3 block text-sm font-medium text-slate-700">
        Observações
        <textarea className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2" value={notes} onChange={(event) => setNotes(event.target.value)} />
      </label>
      <div className="mt-4 flex gap-2">
        <button className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white" disabled={loading} type="submit">
          {patient ? "Salvar paciente" : "Criar paciente"}
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
