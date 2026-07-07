import { useEffect, useState } from "react";
import { acceptConsent, getActiveConsent, type ConsentTerm } from "../../shared/api/httpClient";

type Props = {
  onAccepted: () => void;
};

export function ConsentPage({ onAccepted }: Props) {
  const [term, setTerm] = useState<ConsentTerm | null>(null);
  const [accepted, setAccepted] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getActiveConsent()
      .then(setTerm)
      .catch(() => setError("Nao foi possivel carregar o termo ativo."));
  }, []);

  async function handleAccept() {
    if (!accepted || !term) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await acceptConsent(term.id);
      onAccepted();
    } catch {
      setError("Nao foi possivel registrar o aceite.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h1 className="text-2xl font-semibold text-slate-950">Termo de consentimento</h1>
      {term ? (
        <div className="mt-5 space-y-4">
          <div>
            <h2 className="font-semibold text-slate-900">{term.title}</h2>
            <p className="mt-1 text-sm text-slate-500">Versao {term.version}</p>
          </div>
          <p className="max-h-72 overflow-auto rounded-md border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
            {term.content}
          </p>
          <label className="flex gap-3 text-sm text-slate-700">
            <input
              className="mt-1 h-4 w-4"
              type="checkbox"
              checked={accepted}
              onChange={(event) => setAccepted(event.target.checked)}
            />
            Li e aceito o termo de consentimento para uso do Gerador do Mapa da Psiquê.
          </label>
          {error ? <p className="text-sm text-red-700">{error}</p> : null}
          <button
            className="rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-70"
            disabled={!accepted || loading}
            onClick={handleAccept}
            type="button"
          >
            {loading ? "Registrando..." : "Aceitar termo"}
          </button>
        </div>
      ) : (
        <p className="mt-5 text-sm text-slate-600">{error ?? "Carregando termo..."}</p>
      )}
    </section>
  );
}
