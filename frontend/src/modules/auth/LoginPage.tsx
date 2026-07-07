import { FormEvent, useState } from "react";
import { login, type AuthResponse } from "../../shared/api/httpClient";

type Props = {
  onLogin: (response: AuthResponse) => void;
  onRegisterClick: () => void;
  onForgotPasswordClick: () => void;
};

export function LoginPage({ onLogin, onRegisterClick, onForgotPasswordClick }: Props) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);

    try {
      onLogin(await login({ email, password }));
    } catch {
      setError("Nao foi possivel entrar. Confira os dados e tente novamente.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h1 className="text-2xl font-semibold text-slate-950">Entrar</h1>
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
        <label className="block text-sm font-medium text-slate-700">
          Senha
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
        </label>
        {error ? <p className="text-sm text-red-700">{error}</p> : null}
        <button
          className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-70"
          disabled={loading}
          type="submit"
        >
          {loading ? "Entrando..." : "Entrar"}
        </button>
      </form>
      <button className="mt-4 text-sm font-medium text-brand-700" onClick={onRegisterClick} type="button">
        Criar cadastro
      </button>
      <button className="ml-4 mt-4 text-sm font-medium text-slate-700" onClick={onForgotPasswordClick} type="button">
        Esqueci minha senha
      </button>
    </section>
  );
}
