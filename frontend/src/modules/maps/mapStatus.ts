export const mapStatusLabels: Record<string, string> = {
  draft: "Rascunho",
  ready_for_analysis: "Pronto para análise",
  analyzed: "Analisado",
  archived: "Arquivado",
};

export function formatMapStatus(status: string): string {
  return mapStatusLabels[status] ?? status;
}
