'use client';

import { useRouter, useSearchParams } from 'next/navigation';
import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { resetPasswordApi } from './recuperar-password.api';

export function ResetPasswordForm() {
  const router = useRouter();
  const params = useSearchParams();
  const token = params.get('token') ?? '';
  const email = params.get('email') ?? '';

  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [apiError, setApiError] = useState('');

  const { mutate, isPending } = useMutation({
    mutationFn: resetPasswordApi,
    onSuccess: () => router.push('/login?reset=1'),
    onError: (err: { response?: { data?: { message?: string } } }) => setApiError(err?.response?.data?.message ?? 'Error al restablecer contraseña'),
  });

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setApiError('');
    mutate({ token, email, password, password_confirmation: confirm });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label htmlFor="reset-password" className="block text-sm font-medium mb-1">Nueva contraseña</label>
        <input
          id="reset-password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          minLength={8}
          className="w-full border rounded-md px-3 py-2 text-sm"
        />
      </div>
      <div>
        <label htmlFor="reset-confirm" className="block text-sm font-medium mb-1">Confirmar contraseña</label>
        <input
          id="reset-confirm"
          type="password"
          value={confirm}
          onChange={(e) => setConfirm(e.target.value)}
          required
          className="w-full border rounded-md px-3 py-2 text-sm"
        />
      </div>
      {apiError && (
        <p className="text-red-500 text-sm bg-red-50 border border-red-200 rounded p-2">{apiError}</p>
      )}
      <button
        type="submit"
        disabled={isPending}
        className="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium disabled:opacity-50"
      >
        {isPending ? 'Restableciendo...' : 'Restablecer contraseña'}
      </button>
    </form>
  );
}
