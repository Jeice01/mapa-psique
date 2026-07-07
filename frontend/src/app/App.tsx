import { useEffect, useState } from "react";
import { ForgotPasswordPage } from "../modules/auth/ForgotPasswordPage";
import { LoginPage } from "../modules/auth/LoginPage";
import { RegisterPage } from "../modules/auth/RegisterPage";
import { ResetPasswordPage } from "../modules/auth/ResetPasswordPage";
import { ConsentPage } from "../modules/consents/ConsentPage";
import { ProtectedHomePage } from "../modules/protected/ProtectedHomePage";
import { me, type User } from "../shared/api/httpClient";

type View = "login" | "register" | "forgot-password" | "reset-password" | "consent" | "protected";

export function App() {
  const initialResetToken = new URLSearchParams(window.location.search).get("reset_token") ?? "";
  const [view, setView] = useState<View>(initialResetToken === "" ? "login" : "reset-password");
  const [resetToken, setResetToken] = useState(initialResetToken);
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (resetToken !== "") {
      setLoading(false);
      return;
    }

    me()
      .then((response) => {
        setUser(response.user);
        setView(response.requires_consent ? "consent" : "protected");
      })
      .catch(() => {
        setUser(null);
        setView("login");
      })
      .finally(() => setLoading(false));
  }, [resetToken]);

  function resetSession() {
    setUser(null);
    setView("login");
  }

  return (
    <main className="min-h-screen bg-slate-50 px-6 py-10 text-slate-900">
      <div className="mx-auto mb-8 w-full max-w-2xl">
        <p className="text-xs font-bold uppercase tracking-widest text-brand-600">Gerador do Mapa da Psiquê</p>
        <p className="mt-2 text-sm text-slate-600">Autenticacao, sessao segura e consentimento inicial.</p>
      </div>

      {loading ? <p className="mx-auto max-w-2xl text-sm text-slate-600">Verificando sessao...</p> : null}

      {!loading && view === "login" ? (
        <LoginPage
          onLogin={(response) => {
            setUser(response.user);
            setView(response.requires_consent ? "consent" : "protected");
          }}
          onRegisterClick={() => setView("register")}
          onForgotPasswordClick={() => setView("forgot-password")}
        />
      ) : null}

      {!loading && view === "register" ? <RegisterPage onBackToLogin={() => setView("login")} /> : null}

      {!loading && view === "forgot-password" ? <ForgotPasswordPage onBackToLogin={() => setView("login")} /> : null}

      {!loading && view === "reset-password" ? (
        <ResetPasswordPage
          token={resetToken}
          onBackToLogin={() => {
            setResetToken("");
            setView("login");
          }}
        />
      ) : null}

      {!loading && view === "consent" && user ? <ConsentPage onAccepted={() => setView("protected")} /> : null}

      {!loading && view === "protected" && user ? <ProtectedHomePage user={user} onLogout={resetSession} /> : null}
    </main>
  );
}
