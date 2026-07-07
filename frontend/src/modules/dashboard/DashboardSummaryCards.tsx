import type { DashboardSummary } from "../../shared/api/httpClient";

type Props = {
  summary: DashboardSummary | null;
};

export function DashboardSummaryCards({ summary }: Props) {
  const cards = [
    ["Pacientes", summary?.patients_count ?? 0],
    ["Mapas", summary?.maps_count ?? 0],
    ["Rascunhos", summary?.draft_maps_count ?? 0],
    ["Analisados", summary?.analyzed_maps_count ?? 0],
  ];

  return (
    <div className="grid gap-3 sm:grid-cols-4">
      {cards.map(([label, value]) => (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" key={label}>
          <p className="text-sm text-slate-500">{label}</p>
          <p className="mt-2 text-2xl font-semibold text-slate-950">{value}</p>
        </div>
      ))}
    </div>
  );
}
