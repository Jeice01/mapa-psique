import type { MapArrowReading, MapElementReading, StructuredMapReading } from "../../shared/api/httpClient";

type Props = {
  reading: StructuredMapReading;
  onChange: (reading: StructuredMapReading) => void;
};

const quadrants = [
  ["emocional", "Emocional"],
  ["espiritual", "Espiritual"],
  ["passado", "Passado"],
  ["presente_fisico", "Presente/Físico"],
] as const;

export function StructuredMapReview({ reading, onChange }: Props) {
  const reviewed = reading.review.status === "reviewed";

  function updateElement(index: number, patch: Partial<MapElementReading>) {
    onChange({ ...reading, elements: reading.elements.map((item, itemIndex) => itemIndex === index ? { ...item, ...patch } : item) });
  }

  function updateArrow(index: number, patch: Partial<MapArrowReading>) {
    onChange({ ...reading, arrows: reading.arrows.map((arrow, arrowIndex) => arrowIndex === index ? { ...arrow, ...patch } : arrow) });
  }

  function setReviewed(value: boolean) {
    onChange({
      ...reading,
      review: {
        ...reading.review,
        status: value ? "reviewed" : "pending",
        reviewed_at: value ? new Date().toISOString() : null,
      },
    });
  }

  return (
    <section className="mt-5 rounded-lg border border-violet-200 bg-violet-50/50 p-5">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h4 className="text-base font-semibold text-violet-950">Revisão humana da leitura do mapa</h4>
          <p className="mt-1 max-w-3xl text-sm text-violet-800">
            Confira o que a IA identificou na imagem. Corrija elementos, quadrantes e setas e registre observações antes de liberar o relatório clínico.
          </p>
        </div>
        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${reviewed ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-800"}`}>
          {reviewed ? "Leitura revisada" : "Revisão pendente"}
        </span>
      </div>

      <label className="mt-4 block text-sm font-medium text-slate-800">
        Resumo objetivo da composição
        <textarea className="mt-2 min-h-24 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={reading.summary} onChange={(event) => onChange({ ...reading, summary: event.target.value })} />
      </label>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        <label className="block text-sm font-medium text-slate-800">
          Posição do EU
          <input className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={reading.self_position.position} onChange={(event) => onChange({ ...reading, self_position: { ...reading.self_position, position: event.target.value } })} />
        </label>
        <label className="block text-sm font-medium text-slate-800">
          Observações sobre o EU
          <input className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={reading.self_position.notes} onChange={(event) => onChange({ ...reading, self_position: { ...reading.self_position, notes: event.target.value } })} />
        </label>
      </div>

      <div className="mt-5">
        <h5 className="text-sm font-semibold text-slate-950">Quatro quadrantes</h5>
        <div className="mt-3 grid gap-3 lg:grid-cols-2">
          {quadrants.map(([key, label]) => (
            <label className="block text-sm font-medium text-slate-700" key={key}>
              {label}
              <textarea className="mt-1 min-h-20 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={reading.quadrants[key]} onChange={(event) => onChange({ ...reading, quadrants: { ...reading.quadrants, [key]: event.target.value } })} />
            </label>
          ))}
        </div>
      </div>

      <div className="mt-5">
        <div className="flex items-center justify-between gap-3">
          <h5 className="text-sm font-semibold text-slate-950">Pessoas, lugares e situações</h5>
          <button className="rounded-md border border-violet-300 bg-white px-3 py-1.5 text-xs font-medium text-violet-800" onClick={() => onChange({ ...reading, elements: [...reading.elements, emptyElement(reading.elements.length + 1)] })} type="button">Adicionar elemento</button>
        </div>
        <div className="mt-3 space-y-3">
          {reading.elements.map((item, index) => (
            <div className="rounded-md border border-slate-200 bg-white p-3" key={item.id || `item-${index}`}>
              <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
                <input aria-label={`Nome do elemento ${index + 1}`} className="rounded-md border border-slate-300 px-2 py-2 text-sm xl:col-span-2" placeholder="Nome ou descrição" value={item.label} onChange={(event) => updateElement(index, { label: event.target.value })} />
                <Select value={item.type} onChange={(value) => updateElement(index, { type: value as MapElementReading["type"] })} options={[["pessoa", "Pessoa"], ["lugar", "Lugar"], ["situacao", "Situação"]]} />
                <Select value={item.signal} onChange={(value) => updateElement(index, { signal: value as MapElementReading["signal"] })} options={[["positivo", "Positivo"], ["negativo", "Negativo"], ["ambivalente", "Ambivalente"], ["neutro", "Neutro"]]} />
                <Select value={item.quadrant} onChange={(value) => updateElement(index, { quadrant: value as MapElementReading["quadrant"] })} options={[...quadrants, ["centro", "Centro"], ["fora", "Fora"]]} />
              </div>
              <div className="mt-2 grid gap-2 md:grid-cols-[180px_1fr_auto]">
                <Select value={item.distance_from_self} onChange={(value) => updateElement(index, { distance_from_self: value as MapElementReading["distance_from_self"] })} options={[["proximo", "Próximo do EU"], ["medio", "Distância média"], ["longe", "Longe do EU"], ["fora", "Fora do círculo"]]} />
                <input className="rounded-md border border-slate-300 px-2 py-2 text-sm" placeholder="Observações/correções" value={item.notes} onChange={(event) => updateElement(index, { notes: event.target.value })} />
                <button className="rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700" onClick={() => onChange({ ...reading, elements: reading.elements.filter((_, itemIndex) => itemIndex !== index) })} type="button">Remover</button>
              </div>
              <label className="mt-2 flex items-center gap-2 text-xs text-slate-600"><input checked={item.is_outside_circle} onChange={(event) => updateElement(index, { is_outside_circle: event.target.checked })} type="checkbox" /> Fora do círculo · confiança da leitura: {Math.round(item.confidence * 100)}%</label>
            </div>
          ))}
          {reading.elements.length === 0 ? <p className="text-sm text-slate-600">Nenhum elemento foi identificado. Adicione ou descreva manualmente.</p> : null}
        </div>
      </div>

      <div className="mt-5">
        <h5 className="text-sm font-semibold text-slate-950">Setas PS, PR e F</h5>
        <div className="mt-3 grid gap-3 lg:grid-cols-3">
          {reading.arrows.map((arrow, index) => (
            <div className="rounded-md border border-slate-200 bg-white p-3" key={`${arrow.arrow_type}-${index}`}>
              <p className="font-semibold text-slate-900">Seta {arrow.arrow_type}</p>
              <div className="mt-2 space-y-2">
                <Select value={arrow.quadrant} onChange={(value) => updateArrow(index, { quadrant: value as MapArrowReading["quadrant"] })} options={[...quadrants, ["centro", "Centro"], ["fora", "Fora"]]} />
                <Select value={arrow.size} onChange={(value) => updateArrow(index, { size: value as MapArrowReading["size"] })} options={[["pequena", "Pequena"], ["media", "Média"], ["grande", "Grande"]]} />
                <input className="w-full rounded-md border border-slate-300 px-2 py-2 text-sm" placeholder="Relação com o EU" value={arrow.relation_to_self} onChange={(event) => updateArrow(index, { relation_to_self: event.target.value })} />
                <textarea className="min-h-16 w-full rounded-md border border-slate-300 px-2 py-2 text-sm" placeholder="Observações" value={arrow.notes} onChange={(event) => updateArrow(index, { notes: event.target.value })} />
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="mt-5 grid gap-4 lg:grid-cols-2">
        <ListEditor label="Ausências observadas" values={reading.absences} onChange={(values) => onChange({ ...reading, absences: values })} />
        <ListEditor label="Dúvidas ou trechos ilegíveis" values={reading.uncertainties} onChange={(values) => onChange({ ...reading, uncertainties: values })} />
      </div>

      <label className="mt-4 block text-sm font-medium text-slate-800">
        Observações e correções do terapeuta
        <textarea className="mt-2 min-h-24 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={reading.review.professional_notes} onChange={(event) => onChange({ ...reading, review: { ...reading.review, professional_notes: event.target.value } })} />
      </label>

      <label className="mt-4 flex items-start gap-3 rounded-md border border-violet-200 bg-white p-4 text-sm text-slate-800">
        <input checked={reviewed} className="mt-1 h-4 w-4" onChange={(event) => setReviewed(event.target.checked)} type="checkbox" />
        <span><strong>Confirmo que revisei a leitura da imagem.</strong><span className="mt-1 block text-xs text-slate-600">A análise completa só será liberada depois de salvar o canvas com esta confirmação.</span></span>
      </label>
    </section>
  );
}

function Select({ value, options, onChange }: { value: string; options: ReadonlyArray<readonly [string, string]>; onChange: (value: string) => void }) {
  return <select className="w-full rounded-md border border-slate-300 bg-white px-2 py-2 text-sm" value={value} onChange={(event) => onChange(event.target.value)}>{options.map(([optionValue, label]) => <option key={optionValue} value={optionValue}>{label}</option>)}</select>;
}

function ListEditor({ label, values, onChange }: { label: string; values: string[]; onChange: (values: string[]) => void }) {
  return <label className="block text-sm font-medium text-slate-800">{label}<span className="mt-1 block text-xs font-normal text-slate-500">Use uma linha para cada item.</span><textarea className="mt-2 min-h-28 w-full rounded-md border border-slate-300 bg-white px-3 py-2" value={values.join("\n")} onChange={(event) => onChange(event.target.value.split("\n").map((value) => value.trim()).filter(Boolean))} /></label>;
}

function emptyElement(sequence: number): MapElementReading {
  return { id: `manual-${Date.now()}-${sequence}`, type: "pessoa", label: "", signal: "neutro", quadrant: "centro", distance_from_self: "medio", is_outside_circle: false, notes: "", confidence: 1, x: null, y: null };
}
