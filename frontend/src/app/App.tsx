import { useEffect, useState } from "react";
import { LoginPage } from "../modules/auth/LoginPage";
import { RegisterPage } from "../modules/auth/RegisterPage";
import { ConsentPage } from "../modules/consents/ConsentPage";
import { ProtectedHomePage } from "../modules/protected/ProtectedHomePage";
import { me, type User } from "../shared/api/httpClient";

type View = "login" | "register" | "consent" | "protected";

export function App() {
  const [view, setView] = useState<View>("login");
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
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
  }, []);

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
        />
      ) : null}

      {!loading && view === "register" ? <RegisterPage onBackToLogin={() => setView("login")} /> : null}

      {!loading && view === "consent" && user ? <ConsentPage onAccepted={() => setView("protected")} /> : null}

      {!loading && view === "protected" && user ? <ProtectedHomePage user={user} onLogout={resetSession} /> : null}
    </main>
  );
}
