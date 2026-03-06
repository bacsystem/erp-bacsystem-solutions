'use client';

import { Suspense, useState } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useMutation } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import { api } from '@/shared/lib/api';
import { useAuthStore } from '@/shared/stores/auth.store';
import type { UserPayload, ApiError } from '@/shared/types';

interface ActivarPayload {
  token: string;
  nombre: string;
  password: string;
  password_confirmation: string;
}

function ActivarForm() {
  const params = useSearchParams();
  const token = params.get('token') ?? '';
  const router = useRouter();
  const { setAccessToken, setUser } = useAuthStore();

  const [nombre, setNombre] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');

  const { mutate, isPending } = useMutation({
    mutationFn: (data: ActivarPayload) =>
      api.post<{ data: { access_token: string; user: UserPayload } }>('/auth/activar-cuenta', data).then((r) => r.data.data),
    onSuccess: (result) => {
      setAccessToken(result.access_token);
      setUser(result.user);
      router.push('/dashboard');
    },
    onError: (err: Error) => setError((err as AxiosError<ApiError>)?.response?.data?.errors?.token?.[0] ?? (err as AxiosError<ApiError>)?.response?.data?.message ?? 'Error al activar cuenta'),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    mutate({ token, nombre, password, password_confirmation: confirm });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1">Tu nombre completo</label>
        <input value={nombre} onChange={(e) => setNombre(e.target.value)} required className="w-full border rounded-md px-3 py-2 text-sm" />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">Contraseña</label>
        <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" required minLength={8} className="w-full border rounded-md px-3 py-2 text-sm" />
      </div>
      <div>
        <label className="block text-sm font-medium mb-1">Confirmar contraseña</label>
        <input value={confirm} onChange={(e) => setConfirm(e.target.value)} type="password" required className="w-full border rounded-md px-3 py-2 text-sm" />
      </div>
      {error && <p className="text-red-500 text-sm bg-red-50 border border-red-200 rounded p-2">{error}</p>}
      <button type="submit" disabled={isPending} className="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium disabled:opacity-50">
        {isPending ? 'Activando...' : 'Activar mi cuenta'}
      </button>
    </form>
  );
}

export default function ActivarPage() {
  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded-xl shadow-sm border w-full max-w-md">
        <h1 className="text-2xl font-bold mb-2">Activa tu cuenta</h1>
        <p className="text-gray-500 text-sm mb-6">Crea tu contraseña para comenzar.</p>
        <Suspense fallback={<p>Cargando...</p>}>
          <ActivarForm />
        </Suspense>
      </div>
    </main>
  );
}
