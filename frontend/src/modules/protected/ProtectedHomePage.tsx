import { useEffect, useState } from "react";
import { DashboardSummaryCards } from "../dashboard/DashboardSummaryCards";
import { MapList } from "../maps/MapList";
import { PatientList } from "../patients/PatientList";
import { getDashboardSummary, logout, type DashboardSummary, type User } from "../../shared/api/httpClient";

type Props = {
  user: User;
  onLogout: () => void;
};

type Tab = "patients" | "maps";

export function ProtectedHomePage({ user, onLogout }: Props) {
  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [tab, setTab] = useState<Tab>("patients");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getDashboardSummary()
      .then(setSummary)
      .catch(() => setError("Nao foi possivel carregar o resumo inicial."));
  }, []);

  async function handleLogout() {
    await logout().catch(() => undefined);
    onLogout();
  }

  return (
    <section className="mx-auto w-full max-w-6xl space-y-6">
      <div className="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <p className="text-xs font-bold uppercase tracking-widest text-brand-600">Dashboard inicial</p>
          <h1 className="mt-2 text-2xl font-semibold text-slate-950">{user.name}</h1>
          <p className="mt-1 text-sm text-slate-500">{user.email} · {user.role} · {user.user_status}</p>
        </div>
        <button className="rounded-md border border-slate-300 px-4 py-2 font-medium text-slate-700" onClick={handleLogout} type="button">
          Sair
        </button>
      </div>

      {error ? <p className="text-sm text-red-700">{error}</p> : null}
      <DashboardSummaryCards summary={summary} />

      <div className="flex gap-2">
        <button className={`rounded-md px-4 py-2 text-sm font-medium ${tab === "patients" ? "bg-brand-600 text-white" : "border border-slate-300 bg-white text-slate-700"}`} onClick={() => setTab("patients")} type="button">
          Pacientes
        </button>
        <button className={`rounded-md px-4 py-2 text-sm font-medium ${tab === "maps" ? "bg-brand-600 text-white" : "border border-slate-300 bg-white text-slate-700"}`} onClick={() => setTab("maps")} type="button">
          Mapas
        </button>
      </div>

      {tab === "patients" ? <PatientList /> : <MapList />}
    </section>
  );
}
