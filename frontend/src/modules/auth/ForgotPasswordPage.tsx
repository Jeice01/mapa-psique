import { FormEvent, useState } from "react";
import { requestPasswordReset } from "../../shared/api/httpClient";

type Props = {
  onBackToLogin: () => void;
};

export function ForgotPasswordPage({ onBackToLogin }: Props) {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage(null);
    setError(null);
    setLoading(true);

    try {
      const response = await requestPasswordReset({ email });
      setMessage(response.message);
    } catch {
      setError("Nao foi possivel solicitar a redefinicao de senha.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h1 className="text-2xl font-semibold text-slate-950">Redefinir senha</h1>
      <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
        <label className="block text-sm font-medium text-slate-700">
          E-mail
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />
        </label>
        {message ? <p className="text-sm text-brand-700">{message}</p> : null}
        {error ? <p className="text-sm text-red-700">{error}</p> : null}
        <button className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white" disabled={loading} type="submit">
          {loading ? "Enviando..." : "Enviar link"}
        </button>
      </form>
      <button className="mt-4 text-sm font-medium text-brand-700" onClick={onBackToLogin} type="button">
        Voltar para login
      </button>
    </section>
  );
}
