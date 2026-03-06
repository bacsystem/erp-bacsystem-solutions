'use client';

import { useRef, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ImageUp, Loader2, RefreshCw } from 'lucide-react';
import { api } from '@/shared/lib/api';

function validateFile(file: File): string | null {
  if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) return 'Solo se permiten imágenes JPG o PNG.';
  if (file.size > 2 * 1024 * 1024) return 'El logo no puede superar los 2 MB.';
  return null;
}

export function LogoUpload({ currentLogo }: Readonly<{ currentLogo?: string | null }>) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [isDragging, setIsDragging] = useState(false);
  const queryClient = useQueryClient();

  const { mutate, isPending, isSuccess } = useMutation({
    mutationFn: (file: File) => {
      const form = new FormData();
      form.append('logo', file);
      return api.post('/empresa/logo', form, { headers: { 'Content-Type': 'multipart/form-data' } });
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['empresa'] }),
    onError: (err: { response?: { data?: { message?: string } } }) =>
      setError(err?.response?.data?.message ?? 'Error al subir logo'),
  });

  const processFile = (file: File) => {
    setError('');
    const validationError = validateFile(file);
    if (validationError) { setError(validationError); return; }
    const reader = new FileReader();
    reader.onload = (ev) => setPreview(ev.target?.result as string);
    reader.readAsDataURL(file);
    mutate(file);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) processFile(file);
  };

  const handleDrop = (e: React.DragEvent<HTMLElement>) => {
    e.preventDefault();
    setIsDragging(false);
    const file = e.dataTransfer.files?.[0];
    if (file) processFile(file);
  };

  const logoSrc = preview ?? currentLogo;

  return (
    <div className="space-y-4">
      <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Logo de la empresa</p>

      {logoSrc ? (
        <div className="relative group w-28">
          <img src={logoSrc} alt="Logo" className="h-28 w-28 object-contain rounded-xl border border-gray-200 bg-gray-50 p-2" />
          {isPending && (
            <div className="absolute inset-0 bg-white/70 rounded-xl flex items-center justify-center">
              <Loader2 className="w-5 h-5 animate-spin text-blue-600" />
            </div>
          )}
          {!isPending && (
            <button
              type="button"
              onClick={() => inputRef.current?.click()}
              className="absolute inset-0 bg-black/40 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
            >
              <RefreshCw className="w-5 h-5 text-white" />
            </button>
          )}
        </div>
      ) : (
        <button
          type="button"
          onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
          onDragLeave={() => setIsDragging(false)}
          onDrop={handleDrop}
          onClick={() => !isPending && inputRef.current?.click()}
          disabled={isPending}
          className={`flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-xl p-6 w-full cursor-pointer transition ${
            isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'
          } disabled:opacity-60 disabled:cursor-not-allowed`}
        >
          {isPending ? (
            <Loader2 className="w-7 h-7 animate-spin text-blue-500" />
          ) : (
            <ImageUp className="w-7 h-7 text-gray-400" />
          )}
          <p className="text-sm text-gray-500">
            {isPending ? 'Subiendo...' : 'Arrastra tu logo o haz clic'}
          </p>
        </button>
      )}

      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/jpg,image/png"
        className="hidden"
        onChange={handleChange}
      />

      {!logoSrc && !isPending && (
        <button
          type="button"
          onClick={() => inputRef.current?.click()}
          className="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 font-medium"
        >
          <ImageUp className="w-4 h-4" />
          Seleccionar archivo
        </button>
      )}

      {isSuccess && !error && (
        <p className="text-xs text-green-600 font-medium">Logo actualizado correctamente.</p>
      )}
      {error && <p className="text-red-500 text-xs">{error}</p>}
      <p className="text-xs text-gray-400">JPG o PNG, máximo 2 MB.</p>
    </div>
  );
}
