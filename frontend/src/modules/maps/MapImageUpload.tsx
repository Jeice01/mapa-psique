import { useCallback, useEffect, useRef, useState } from "react";
import { ApiError, generateMapCanvas, getMapImageBlob, MapCanvasData, uploadMapImage } from "../../shared/api/httpClient";

type Props = {
  mapId: string;
  hasMapImage: boolean;
  onCanvasGenerated: (canvas: MapCanvasData) => void;
};

export function MapImageUpload({ mapId, hasMapImage, onCanvasGenerated }: Props) {
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const objectUrlRef = useRef<string | null>(null);
  const generatingRef = useRef(false);

  // Carregar imagem existente ao montar
  useEffect(() => {
    if (!hasMapImage) return;

    getMapImageBlob(mapId)
      .then((blob) => {
        const url = URL.createObjectURL(blob);
        objectUrlRef.current = url;
        setImageUrl(url);
      })
      .catch(() => {
        // Imagem ainda não existe — ignora
      });

    return () => {
      if (objectUrlRef.current) {
        URL.revokeObjectURL(objectUrlRef.current);
      }
    };
  }, [mapId, hasMapImage]);

  const handleFileChange = useCallback(
    async (e: React.ChangeEvent<HTMLInputElement>) => {
      const file = e.target.files?.[0];
      if (!file) return;

      setError(null);
      setSuccess(null);
      setUploading(true);

      try {
        // Preview local imediato
        const localUrl = URL.createObjectURL(file);
        if (objectUrlRef.current) URL.revokeObjectURL(objectUrlRef.current);
        objectUrlRef.current = localUrl;
        setImageUrl(localUrl);

        await uploadMapImage(mapId, file);
        setSuccess("Imagem enviada com sucesso.");
      } catch (err) {
        const msg = err instanceof ApiError ? err.message : "Erro ao enviar a imagem.";
        setError(msg);
        setImageUrl(null);
      } finally {
        setUploading(false);
        if (fileInputRef.current) fileInputRef.current.value = "";
      }
    },
    [mapId]
  );

  const handleGenerateCanvas = useCallback(async () => {
    if (generatingRef.current) return;
    generatingRef.current = true;
    setError(null);
    setSuccess(null);
    setGenerating(true);

    try {
      const canvas = await generateMapCanvas(mapId);
      onCanvasGenerated(canvas);
      setSuccess("Canvas preenchido pela IA. Revise os campos antes de salvar.");
    } catch (err) {
      const msg = err instanceof ApiError ? err.message : "Erro ao gerar o canvas.";
      setError(msg);
    } finally {
      setGenerating(false);
      generatingRef.current = false;
    }
  }, [mapId, onCanvasGenerated]);

  return (
    <section className="border border-gray-200 rounded-lg p-5 bg-white space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold text-gray-800">Imagem do Mapa da Psiquê</h3>
          <p className="text-xs text-gray-500 mt-0.5">
            Faça upload da foto do mapa físico para que a IA leia e preencha o canvas automaticamente.
          </p>
        </div>

        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={uploading}
          className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {uploading ? (
            <>
              <span className="animate-spin inline-block w-3 h-3 border border-current border-t-transparent rounded-full" />
              Enviando…
            </>
          ) : (
            <>
              <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              {imageUrl ? "Substituir imagem" : "Carregar imagem"}
            </>
          )}
        </button>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          className="hidden"
          onChange={handleFileChange}
        />
      </div>

      {/* Preview da imagem */}
      {imageUrl && (
        <div className="relative rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
          <img
            src={imageUrl}
            alt="Mapa da Psiquê"
            className="w-full max-h-72 object-contain"
          />
        </div>
      )}

      {/* Mensagens */}
      {error && (
        <p className="text-xs text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
          {error}
        </p>
      )}
      {success && (
        <p className="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-3 py-2">
          {success}
        </p>
      )}

      {/* Botão Gerar Mapa */}
      {imageUrl && (
        <button
          type="button"
          onClick={handleGenerateCanvas}
          disabled={generating || uploading}
          className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {generating ? (
            <>
              <span className="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
              A IA está lendo o mapa… pode levar até 30 segundos
            </>
          ) : (
            <>
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
              Gerar Mapa — Preencher canvas com IA
            </>
          )}
        </button>
      )}

      {!imageUrl && (
        <p className="text-xs text-gray-400 text-center">
          Faça o upload da foto do mapa para habilitar o preenchimento automático.
        </p>
      )}
    </section>
  );
}
