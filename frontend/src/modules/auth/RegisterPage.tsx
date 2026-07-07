import { FormEvent, useState } from "react";
import { ApiError, register } from "../../shared/api/httpClient";

type Props = {
  onBackToLogin: () => void;
};

export function RegisterPage({ onBackToLogin }: Props) {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setMessage(null);

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
      await register({ name, email, password, role: "profissional" });
      setMessage("Cadastro criado. Entre com seu e-mail e senha para continuar.");
      setName("");
      setEmail("");
      setPassword("");
      setConfirmPassword("");
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : "Nao foi possivel criar o cadastro.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h1 className="text-2xl font-semibold text-slate-950">Cadastro</h1>
      <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
        <label className="block text-sm font-medium text-slate-700">
          Nome
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />
        </label>
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
        <label className="block text-sm font-medium text-slate-700">
          Confirmar senha
          <input
            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-slate-950 outline-none focus:border-brand-600"
            type="password"
            value={confirmPassword}
            onChange={(event) => setConfirmPassword(event.target.value)}
            required
          />
        </label>
        <p className="text-sm text-slate-500">A senha precisa ter pelo menos 8 caracteres, incluindo letras e numeros.</p>
        <p className="text-sm text-slate-500">Perfil inicial: profissional.</p>
        {message ? <p className="text-sm text-brand-700">{message}</p> : null}
        {error ? <p className="text-sm text-red-700">{error}</p> : null}
        <button
          className="w-full rounded-md bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-70"
          disabled={loading}
          type="submit"
        >
          {loading ? "Criando..." : "Criar cadastro"}
        </button>
      </form>
      <button className="mt-4 text-sm font-medium text-brand-700" onClick={onBackToLogin} type="button">
        Voltar para login
      </button>
    </section>
  );
}
