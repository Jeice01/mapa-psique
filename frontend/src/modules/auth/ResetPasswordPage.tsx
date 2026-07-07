import { FormEvent, useState } from "react";
import { ApiError, resetPassword } from "../../shared/api/httpClient";

type Props = {
  token: string;
  onBackToLogin: () => void;
};

export function ResetPasswordPage({ token, onBackToLogin }: Props) {
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage(null);
    setError(null);

    if (password !== confirmPassword) {
      setError("As senhas precisam ser iguais.");
      return;
    }

    if (password.length < 8 || !/[A-Za-z]/.test(password) || !/\d/.test(password)) {
      setError("A senha precisa ter pelo menos 8 caracteres, incluindo letras e numeros.");
      return;
    }

    setLoading(true);

    try {
      const response = await resetPassword({ token, password });
      window.history.replaceState({}, document.title, window.location.pathname);
      setMessage(response.message);
      setPassword("");
      setConfirmPassword("");
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : "Nao foi possivel redefinir a senha.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h1 className="text-2xl font-semibold text-slate-950">Criar nova senha</h1>
      <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
        <label className="block text-sm font-medium text-slate-700">
          Nova senha
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
        </label>
        <label className="block text-sm font-medium text-slate-700">
          Confirmar nova senha
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            type="password"
            value={confirmPassword}
            onChange={(event) => setConfirmPassword(event.target.value)}
            required
          />
        </label>
        <p className="text-sm text-slate-500">A senha precisa ter pelo menos 8 caracteres, incluindo letras e numeros.</p>
        {message ? <p className="text-sm text-brand-700">{message}</p> : null}
        {error ? <p className="text-sm text-red-700">{error}</p> : null}
        <button className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white" disabled={loading} type="submit">
          {loading ? "Salvando..." : "Salvar nova senha"}
        </button>
      </form>
      <button className="mt-4 text-sm font-medium text-brand-700" onClick={onBackToLogin} type="button">
        Voltar para login
      </button>
    </section>
  );
}
