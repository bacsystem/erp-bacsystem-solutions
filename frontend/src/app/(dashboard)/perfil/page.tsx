'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import type { AxiosError } from 'axios';
import { Loader2, UserCircle } from 'lucide-react';
import { Sidebar } from '@/shared/components/Sidebar';
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner';
import { useProfile, useUpdateProfile } from '@/modules/core/usuario/get-profile/use-profile';
import { useAuthStore } from '@/shared/stores/auth.store';
import type { ApiError } from '@/shared/types';

export default function PerfilPage() {
  const { data: profile, isLoading } = useProfile();
  const { mutate: updateProfile, isPending, error } = useUpdateProfile();
  const { logout } = useAuthStore();
  const router = useRouter();

  const [nombre, setNombre] = useState('');
  const [passwordActual, setPasswordActual] = useState('');
  const [passwordNuevo, setPasswordNuevo] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [nombreSuccess, setNombreSuccess] = useState(false);

  const apiError = (error as AxiosError<ApiError>)?.response?.data?.errors?.password_actual?.[0]
    ?? (error as AxiosError<ApiError>)?.response?.data?.message;

  const handleNombre = (e: React.SubmitEvent<HTMLFormElement>) => {
    e.preventDefault();
    updateProfile({ nombre }, {
      onSuccess: () => {
        setNombreSuccess(true);
        setTimeout(() => setNombreSuccess(false), 3000);
      },
    });
  };

  const handlePassword = (e: React.SubmitEvent<HTMLFormElement>) => {
    e.preventDefault();
    updateProfile(
      { password_actual: passwordActual, password: passwordNuevo, password_confirmation: passwordConfirm },
      {
        onSuccess: () => {
          logout();
          router.push('/login');
        },
      }
    );
  };

  if (isLoading) return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50 flex items-center justify-center">
          <Loader2 className="w-6 h-6 animate-spin text-blue-500" />
        </div>
      </main>
    </div>
  );

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-4xl mx-auto p-8 space-y-8">

            {/* Page header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <UserCircle className="w-4 h-4" />
                <span className="text-gray-900 font-medium">Mi perfil</span>
              </div>
              <h1 className="text-2xl font-bold text-gray-900">Mi perfil</h1>
              <p className="text-sm text-gray-500 mt-1">Administra tu información personal y acceso</p>
            </div>

            {/* Info section */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4 text-sm">
              <h2 className="font-semibold text-gray-900">Información de cuenta</h2>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <p className="text-gray-500 text-xs mb-0.5">Email</p>
                  <p className="font-medium text-gray-900">{profile?.email}</p>
                  <p className="text-xs text-gray-400 mt-0.5">No puede modificarse.</p>
                </div>
                <div>
                  <p className="text-gray-500 text-xs mb-0.5">Rol</p>
                  <p className="font-medium text-gray-900 capitalize">{profile?.rol}</p>
                </div>
                <div>
                  <p className="text-gray-500 text-xs mb-0.5">Empresa</p>
                  <p className="font-medium text-gray-900">{profile?.empresa?.razon_social}</p>
                </div>
              </div>
            </div>

            {/* Nombre form */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6">
              <h2 className="font-semibold text-gray-900 mb-4">Nombre</h2>
              <form onSubmit={handleNombre} className="flex gap-2">
                <input
                  value={nombre || profile?.nombre || ''}
                  onChange={(e) => setNombre(e.target.value)}
                  className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Tu nombre completo"
                />
                <button
                  type="submit"
                  disabled={isPending}
                  className="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50 transition-colors"
                >
                  {isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Guardar'}
                </button>
              </form>
              {nombreSuccess && <p className="text-green-600 text-xs mt-2">Nombre actualizado.</p>}
            </div>

            {/* Password form */}
            <div className="bg-white rounded-2xl border border-gray-200 p-6">
              <h2 className="font-semibold text-gray-900 mb-1">Cambiar contraseña</h2>
              <p className="text-xs text-gray-500 mb-5">Al cambiar tu contraseña se cerrarán todas tus sesiones activas.</p>
              <form onSubmit={handlePassword} className="space-y-4">
                <div>
                  <label htmlFor="perfil-password-actual" className="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                  <input id="perfil-password-actual" value={passwordActual} onChange={(e) => setPasswordActual(e.target.value)} type="password" required className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                  <label htmlFor="perfil-password-nuevo" className="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                  <input id="perfil-password-nuevo" value={passwordNuevo} onChange={(e) => setPasswordNuevo(e.target.value)} type="password" required minLength={8} className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                  <label htmlFor="perfil-password-confirm" className="block text-sm font-medium text-gray-700 mb-1">Confirmar nueva contraseña</label>
                  <input id="perfil-password-confirm" value={passwordConfirm} onChange={(e) => setPasswordConfirm(e.target.value)} type="password" required className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                {apiError && <p className="text-red-500 text-sm">{apiError}</p>}
                <button
                  type="submit"
                  disabled={isPending}
                  className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50 transition-colors flex items-center gap-2"
                >
                  {isPending && <Loader2 className="w-4 h-4 animate-spin" />}
                  {isPending ? 'Actualizando...' : 'Cambiar contraseña'}
                </button>
              </form>
            </div>

          </div>
        </div>
      </main>
    </div>
  );
}
