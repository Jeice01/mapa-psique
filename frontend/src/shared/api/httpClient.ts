const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? "/api").replace(/\/$/, "");

export type HealthStatus = {
  status: string;
  service: string;
};

export type User = {
  id: string;
  name: string;
  email: string;
  role: string;
  user_status: string;
};

export type AuthResponse = {
  status: string;
  user: User;
  requires_consent: boolean;
};

export type ConsentTerm = {
  id: string;
  version: string;
  title: string;
  content: string;
};

export type DashboardSummary = {
  patients_count: number;
  maps_count: number;
  draft_maps_count: number;
  analyzed_maps_count: number;
};

export type Patient = {
  id: string;
  name: string;
  internal_code: string | null;
  age: number | null;
  notes?: string | null;
  status: string;
  created_at?: string;
};

export type MapCanvasData = {
  main_demand: string;
  current_context: string;
  emotional_history: string;
  recurring_patterns: string;
  core_beliefs: string;
  defense_strategies: string;
  internal_resources: string;
  reflective_hypotheses: string;
  next_steps: string;
};

export type MapDraft = {
  id: string;
  title: string;
  patient_id: string | null;
  patient_name?: string | null;
  patient_status?: string | null;
  reason?: string | null;
  status: string;
  canvas_json?: MapCanvasData | string | null;
  map_image_path?: string | null;
  created_at?: string;
};

export type MapCanvasVersion = {
  id: string;
  map_id: string;
  user_id?: string | null;
  version_number: number;
  summary?: string | null;
  created_at: string;
};

export type MapCanvasVersionDetails = MapCanvasVersion & {
  canvas_data: unknown;
};

export type RestoreMapCanvasVersionResult = {
  map_id: string;
  restored_version_id: string;
  restored_version_number: number;
  backup_version_id: string;
  backup_version_number: number;
};

export type ExportMapPdfResult = {
  blob: Blob;
  filename: string;
};

export type AiProfessionalAnalysis = {
  visao_panoramica: string;
  analise_freudiana: string;
  analise_junguiana: string;
  padroes_e_complexos: string;
  mecanismos_de_defesa: string;
  recursos_e_potenciais: string;
  sintese_energetica: string;
  diagnostico_do_equilibrio: string;
  direcao_do_tratamento: string;
  sintese_clinica_final: string;
};

export type AiAnalysis = {
  id: string;
  map_id: string;
  professional_analysis: AiProfessionalAnalysis | null;
  patient_report: string | null;
  image_path: string | null;
  image_prompt: string | null;
  model_text: string | null;
  model_image: string | null;
  status: "pending" | "processing" | "completed" | "failed";
  error_message: string | null;
  generated_at: string | null;
  created_at: string;
};

type Pagination = {
  page: number;
  per_page: number;
  total: number;
};

type RegisterPayload = {
  name: string;
  email: string;
  password: string;
  role: "profissional";
};

type LoginPayload = {
  email: string;
  password: string;
};

type ForgotPasswordPayload = {
  email: string;
};

type ResetPasswordPayload = {
  token: string;
  password: string;
};

type ApiErrorPayload = {
  message?: string;
  error?: string;
};

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number
  ) {
    super(message);
  }
}

export async function getHealthStatus(): Promise<HealthStatus> {
  return request<HealthStatus>("/health");
}

export async function getCsrfToken(): Promise<string> {
  const response = await request<{ csrf_token: string }>("/csrf-token");

  return response.csrf_token;
}

export async function register(payload: RegisterPayload): Promise<{ status: string; user: User }> {
  const csrfToken = await getCsrfToken();

  return request<{ status: string; user: User }>("/auth/register", {
    method: "POST",
    csrfToken,
    body: payload,
  });
}

export async function login(payload: LoginPayload): Promise<AuthResponse> {
  const csrfToken = await getCsrfToken();

  return request<AuthResponse>("/auth/login", {
    method: "POST",
    csrfToken,
    body: payload,
  });
}

export async function requestPasswordReset(payload: ForgotPasswordPayload): Promise<{ status: string; message: string }> {
  const csrfToken = await getCsrfToken();

  return request<{ status: string; message: string }>("/auth/forgot-password", {
    method: "POST",
    csrfToken,
    body: payload,
  });
}

export async function resetPassword(payload: ResetPasswordPayload): Promise<{ status: string; message: string }> {
  const csrfToken = await getCsrfToken();

  return request<{ status: string; message: string }>("/auth/reset-password", {
    method: "POST",
    csrfToken,
    body: payload,
  });
}

export async function logout(): Promise<void> {
  const csrfToken = await getCsrfToken();

  await request<{ status: string }>("/auth/logout", {
    method: "POST",
    csrfToken,
  });
}

export async function me(): Promise<AuthResponse> {
  return request<AuthResponse>("/auth/me");
}

export async function getActiveConsent(): Promise<ConsentTerm> {
  const response = await request<{ consent_term: ConsentTerm }>("/consents/active");

  return response.consent_term;
}

export async function acceptConsent(consentTermId?: string): Promise<void> {
  const csrfToken = await getCsrfToken();

  await request<{ status: string }>("/consents/accept", {
    method: "POST",
    csrfToken,
    body: consentTermId ? { consent_term_id: consentTermId } : {},
  });
}

export async function getDashboardSummary(): Promise<DashboardSummary> {
  const response = await request<{ summary: DashboardSummary }>("/dashboard/summary");

  return response.summary;
}

export async function listPatients(params: Record<string, string> = {}): Promise<{ data: Patient[]; pagination: Pagination }> {
  return request(`/patients${toQuery(params)}`);
}

export async function createPatient(payload: Partial<Patient>): Promise<Patient> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ patient: Patient }>("/patients", {
    method: "POST",
    csrfToken,
    body: payload,
  });

  return response.patient;
}

export async function getPatient(id: string): Promise<Patient> {
  const response = await request<{ patient: Patient }>(`/patients/${encodeURIComponent(id)}`);

  return response.patient;
}

export async function updatePatient(id: string, payload: Partial<Patient>): Promise<Patient> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ patient: Patient }>(`/patients/${encodeURIComponent(id)}`, {
    method: "PUT",
    csrfToken,
    body: payload,
  });

  return response.patient;
}

export async function archivePatient(id: string): Promise<void> {
  const csrfToken = await getCsrfToken();

  await request<{ status: string }>(`/patients/${encodeURIComponent(id)}`, {
    method: "DELETE",
    csrfToken,
  });
}

export async function restorePatient(id: string): Promise<void> {
  const csrfToken = await getCsrfToken();

  await request<{ status: string }>(
    `/patients/${encodeURIComponent(id)}/restore`,
    {
      method: "POST",
      csrfToken,
    },
  );
}

export async function listMaps(params: Record<string, string> = {}): Promise<{ data: MapDraft[]; pagination: Pagination }> {
  return request(`/maps${toQuery(params)}`);
}

export async function createMap(payload: Partial<MapDraft>): Promise<MapDraft> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ map: MapDraft }>("/maps", {
    method: "POST",
    csrfToken,
    body: payload,
  });

  return response.map;
}

export async function getMap(id: string): Promise<MapDraft> {
  const response = await request<{ map: MapDraft }>(`/maps/${encodeURIComponent(id)}`);

  return response.map;
}

export async function updateMap(id: string, payload: Partial<MapDraft>): Promise<MapDraft> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ map: MapDraft }>(`/maps/${encodeURIComponent(id)}`, {
    method: "PUT",
    csrfToken,
    body: payload,
  });

  return response.map;
}

export async function listMapCanvasVersions(id: string): Promise<MapCanvasVersion[]> {
  const response = await request<{ success: boolean; data: MapCanvasVersion[] }>(`/maps/${encodeURIComponent(id)}/canvas-versions`);

  return response.data;
}

export async function getMapCanvasVersion(mapId: string, versionId: string): Promise<MapCanvasVersionDetails> {
  const response = await request<{ success: boolean; data: MapCanvasVersionDetails }>(
    `/maps/${encodeURIComponent(mapId)}/canvas-versions/${encodeURIComponent(versionId)}`
  );

  return response.data;
}

export async function restoreMapCanvasVersion(mapId: string, versionId: string): Promise<RestoreMapCanvasVersionResult> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ success: boolean; message: string; data: RestoreMapCanvasVersionResult }>(
    `/maps/${encodeURIComponent(mapId)}/canvas-versions/${encodeURIComponent(versionId)}/restore`,
    {
      method: "POST",
      csrfToken,
    }
  );

  return response.data;
}

export async function exportMapPdf(mapId: string): Promise<ExportMapPdfResult> {
  const response = await fetch(`${apiBaseUrl}/maps/${encodeURIComponent(mapId)}/export/pdf`, {
    method: "GET",
    credentials: "include",
    headers: {
      Accept: "application/pdf",
    },
  });

  if (!response.ok) {
    let message = "Não foi possível exportar o PDF agora.";

    try {
      const data = (await response.json()) as ApiErrorPayload;
      message = data.message ?? data.error ?? message;
    } catch {
      // PDF endpoint may not return JSON on failure.
    }

    throw new ApiError(message, response.status);
  }

  const blob = await response.blob();
  const disposition = response.headers.get("Content-Disposition");
  const filename = getFilenameFromContentDisposition(disposition) ?? `mapa-psique-${mapId}.pdf`;

  return { blob, filename };
}

export async function createMapFromPatient(
  patientId: string,
  file: File,
  mapNotes: string
): Promise<{ map_id: string; patient_id: string; patient_name: string }> {
  const csrfToken = await getCsrfToken();
  const formData = new FormData();
  formData.append("map_image", file);
  if (mapNotes.trim()) {
    formData.append("map_notes", mapNotes.trim());
  }

  const response = await fetch(
    `${apiBaseUrl}/patients/${encodeURIComponent(patientId)}/create-map`,
    {
      method: "POST",
      credentials: "include",
      headers: {
        Accept: "application/json",
        "X-CSRF-Token": csrfToken,
      },
      body: formData,
    }
  );

  const data = (await response.json().catch(() => ({}))) as {
    success?: boolean;
    data?: { map_id: string; patient_id: string; patient_name: string };
    message?: string;
    error?: string;
  };

  if (!response.ok) {
    throw new ApiError(data.message ?? data.error ?? "Erro ao criar mapa.", response.status);
  }

  return data.data!;
}

export async function uploadMapImage(mapId: string, file: File): Promise<{ image_path: string }> {
  const csrfToken = await getCsrfToken();
  const formData = new FormData();
  formData.append("map_image", file);

  const response = await fetch(`${apiBaseUrl}/maps/${encodeURIComponent(mapId)}/image`, {
    method: "POST",
    credentials: "include",
    headers: {
      Accept: "application/json",
      "X-CSRF-Token": csrfToken,
    },
    body: formData,
  });

  const data = (await response.json().catch(() => ({}))) as { image_path?: string; message?: string; error?: string };

  if (!response.ok) {
    throw new ApiError(data.message ?? data.error ?? "Erro ao enviar imagem.", response.status);
  }

  return { image_path: data.image_path ?? "" };
}

export async function getMapImageBlob(mapId: string): Promise<Blob> {
  const response = await fetch(`${apiBaseUrl}/maps/${encodeURIComponent(mapId)}/image`, {
    method: "GET",
    credentials: "include",
    headers: { Accept: "image/*" },
  });

  if (!response.ok) {
    throw new ApiError("Imagem do mapa não encontrada.", response.status);
  }

  return response.blob();
}

export async function generateMapCanvas(mapId: string): Promise<MapCanvasData> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ success: boolean; data: MapCanvasData }>(
    `/maps/${encodeURIComponent(mapId)}/generate-canvas`,
    { method: "POST", csrfToken }
  );
  return response.data;
}

export async function getMapAiAnalysis(mapId: string): Promise<AiAnalysis | null> {
  const response = await request<{ success: boolean; data: AiAnalysis | null }>(
    `/maps/${encodeURIComponent(mapId)}/analysis`
  );

  return response.data;
}

export async function generateMapAiAnalysis(mapId: string): Promise<AiAnalysis> {
  const csrfToken = await getCsrfToken();
  const response = await request<{ success: boolean; message: string; data: AiAnalysis }>(
    `/maps/${encodeURIComponent(mapId)}/analysis`,
    {
      method: "POST",
      csrfToken,
    }
  );

  return response.data;
}

export async function getMapAiAnalysisImageBlob(mapId: string): Promise<Blob> {
  const response = await fetch(`${apiBaseUrl}/maps/${encodeURIComponent(mapId)}/analysis/image`, {
    method: "GET",
    credentials: "include",
    headers: { Accept: "image/png" },
  });

  if (!response.ok) {
    throw new ApiError("Não foi possível carregar a imagem do infográfico.", response.status);
  }

  return response.blob();
}

export async function archiveMap(id: string): Promise<void> {
  const csrfToken = await getCsrfToken();

  await request<{ status: string }>(`/maps/${encodeURIComponent(id)}`, {
    method: "DELETE",
    csrfToken,
  });
}

async function request<T>(
  path: string,
  options: {
    method?: "GET" | "POST" | "PUT" | "DELETE";
    csrfToken?: string;
    body?: unknown;
  } = {}
): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    method: options.method ?? "GET",
    credentials: "include",
    headers: {
      Accept: "application/json",
      ...(options.body === undefined ? {} : { "Content-Type": "application/json" }),
      ...(options.csrfToken ? { "X-CSRF-Token": options.csrfToken } : {}),
    },
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });

  const data = (await response.json().catch(() => ({}))) as ApiErrorPayload;

  if (!response.ok) {
    throw new ApiError(data.message ?? data.error ?? "Request failed", response.status);
  }

  return data as T;
}

function toQuery(params: Record<string, string>): string {
  const query = new URLSearchParams();

  for (const [key, value] of Object.entries(params)) {
    if (value !== "") {
      query.set(key, value);
    }
  }

  const text = query.toString();

  return text === "" ? "" : `?${text}`;
}

function getFilenameFromContentDisposition(disposition: string | null): string | null {
  if (!disposition) {
    return null;
  }

  const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);

  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1].replace(/["]/g, ""));
  }

  const filenameMatch = disposition.match(/filename="?([^"]+)"?/i);

  return filenameMatch?.[1] ?? null;
}

export async function exportMapCanvasVersionPdf(
  mapId: string,
  versionId: string
): Promise<ExportMapPdfResult> {
  const response = await fetch(
    `${apiBaseUrl}/maps/${encodeURIComponent(mapId)}/canvas-versions/${encodeURIComponent(versionId)}/export/pdf`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/pdf",
      },
    }
  );

  if (!response.ok) {
    let message = "Não foi possível exportar o PDF desta versão agora.";

    try {
      const data = (await response.json()) as ApiErrorPayload;
      message = data.message ?? data.error ?? message;
    } catch {
      // PDF endpoint may not return JSON on failure.
    }

    throw new ApiError(message, response.status);
  }

  const blob = await response.blob();
  const disposition = response.headers.get("Content-Disposition") ?? "";
  const filenameMatch = disposition.match(/filename="?([^"]+)"?/i);

  return {
    blob,
    filename: filenameMatch?.[1] ?? "mapa-psique-versao-historica.pdf",
  };
}
