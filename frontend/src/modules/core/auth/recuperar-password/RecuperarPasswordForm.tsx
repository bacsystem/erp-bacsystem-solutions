'use client';

import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { recuperarPasswordApi } from './recuperar-password.api';

export function RecuperarPasswordForm() {
  const [email, setEmail] = useState('');
  const [sent, setSent] = useState(false);

  const { mutate, isPending } = useMutation({
    mutationFn: recuperarPasswordApi,
    onSuccess: () => setSent(true),
    onError: () => setSent(true), // Siempre mostrar mensaje genérico
  });

  if (sent) {
    return (
      <div className="text-center space-y-2">
        <p className="text-green-700 bg-green-50 border border-green-200 rounded p-3 text-sm">
          Si ese email existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña en los próximos minutos.
        </p>
      </div>
    );
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        mutate(email);
      }}
      className="space-y-4"
    >
      <div>
        <label htmlFor="recuperar-email" className="block text-sm font-medium mb-1">Email</label>
        <input
          id="recuperar-email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          className="w-full border rounded-md px-3 py-2 text-sm"
          placeholder="tu@empresa.com"
        />
      </div>
      <button
        type="submit"
        disabled={isPending}
        className="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium disabled:opacity-50"
      >
        {isPending ? 'Enviando...' : 'Enviar enlace de recuperación'}
      </button>
    </form>
  );
}
