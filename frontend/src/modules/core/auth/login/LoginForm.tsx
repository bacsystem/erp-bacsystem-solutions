'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import Link from 'next/link';
import { loginSchema, type LoginFormData } from './login.schema';
import { useLogin } from './use-login';

export function LoginForm() {
  const [showPassword, setShowPassword] = useState(false);
  const { mutate: login, isPending, error } = useLogin();

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormData>({ resolver: zodResolver(loginSchema) });

  const apiError = (error as any)?.response?.data?.message;

  return (
    <form onSubmit={handleSubmit((data) => login(data))} className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1">Email</label>
        <input
          {...register('email')}
          type="email"
          autoComplete="email"
          className="w-full border rounded-md px-3 py-2 text-sm"
          placeholder="tu@empresa.com"
        />
        {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Contraseña</label>
        <div className="relative">
          <input
            {...register('password')}
            type={showPassword ? 'text' : 'password'}
            autoComplete="current-password"
            className="w-full border rounded-md px-3 py-2 text-sm pr-10"
          />
          <button
            type="button"
            onClick={() => setShowPassword((v) => !v)}
            className="absolute right-3 top-2.5 text-xs text-gray-500"
          >
            {showPassword ? 'Ocultar' : 'Mostrar'}
          </button>
        </div>
        {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password.message}</p>}
      </div>

      {apiError && (
        <p className="text-red-500 text-sm bg-red-50 border border-red-200 rounded p-2">{apiError}</p>
      )}

      <button
        type="submit"
        disabled={isPending}
        className="w-full bg-blue-600 text-white rounded-md py-2 text-sm font-medium disabled:opacity-50"
      >
        {isPending ? 'Ingresando...' : 'Iniciar sesión'}
      </button>

      <p className="text-center text-sm text-gray-500">
        <Link href="/recuperar-password" className="text-blue-600 hover:underline">
          ¿Olvidaste tu contraseña?
        </Link>
      </p>
    </form>
  );
}
