'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { AtSign, BarChart2, Check, Loader2, Send, Shield, User, X } from 'lucide-react';
import { api } from '@/shared/lib/api';
import { inviteUsuarioSchema, type InviteUsuarioData } from './invite-usuario.schema';

const ROLES = [
  {
    value: 'admin',
    label: 'Administrador',
    desc: 'Acceso completo excepto facturación y plan',
    Icon: Shield,
  },
  {
    value: 'empleado',
    label: 'Empleado',
    desc: 'Acceso operativo — ventas, clientes y reportes',
    Icon: User,
  },
  {
    value: 'contador',
    label: 'Contador',
    desc: 'Solo lectura contable — libros, reportes y SUNAT',
    Icon: BarChart2,
  },
];

interface InviteUsuarioModalProps {
  readonly onClose: () => void;
}

export function InviteUsuarioModal({ onClose }: InviteUsuarioModalProps) {
  const queryClient = useQueryClient();
  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<InviteUsuarioData>({ resolver: zodResolver(inviteUsuarioSchema) });

  const rolSelected = watch('rol');

  const { mutate, isPending, error, isSuccess } = useMutation({
    mutationFn: (data: InviteUsuarioData) => api.post('/usuarios/invitar', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['usuarios'] }),
  });

  const apiError =
    (error as { response?: { data?: { errors?: { email?: string[] }; message?: string } } })
      ?.response?.data?.errors?.email?.[0] ??
    (error as { response?: { data?: { message?: string } } })?.response?.data?.message;

  if (isSuccess) {
    return (
      <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div className="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-xl">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check className="w-8 h-8 text-green-600" strokeWidth={2.5} />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">¡Invitación enviada!</h2>
          <p className="text-gray-500 text-sm mb-6">El usuario recibirá un email con el enlace de activación.</p>
          <button
            onClick={onClose}
            className="w-full bg-blue-600 text-white rounded-xl py-2.5 text-sm font-medium hover:bg-blue-700 transition"
          >
            Cerrar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div>
            <h2 className="text-lg font-bold text-gray-900">Invitar usuario</h2>
            <p className="text-xs text-gray-500 mt-0.5">Recibirá un email con acceso a tu empresa</p>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <form onSubmit={handleSubmit((d) => mutate(d))} className="p-6 space-y-5">
          {/* Email */}
          <div>
            <label htmlFor="invite-email" className="block text-sm font-semibold text-gray-700 mb-1.5">
              Email del usuario
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                <AtSign className="w-4 h-4 text-gray-400" />
              </div>
              <input
                id="invite-email"
                {...register('email')}
                type="email"
                placeholder="usuario@empresa.com"
                className="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              />
            </div>
            {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
          </div>

          {/* Rol */}
          <div>
            <p className="block text-sm font-semibold text-gray-700 mb-2">Rol <span className="text-red-500">*</span></p>
            <div className="space-y-2">
              {ROLES.map((r) => {
                const isSelected = rolSelected === r.value;
                return (
                  <button
                    key={r.value}
                    type="button"
                    onClick={() => setValue('rol', r.value as 'admin' | 'empleado' | 'contador')}
                    className={`flex items-center gap-4 w-full border-2 rounded-xl px-4 py-3 text-left transition-all ${
                      isSelected
                        ? 'border-blue-600 bg-blue-50'
                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                    }`}
                  >
                    <div className={`w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 ${
                      isSelected ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500'
                    }`}>
                      <r.Icon className="w-4 h-4" />
                    </div>
                    <div className="flex-1">
                      <p className={`text-sm font-semibold ${isSelected ? 'text-blue-700' : 'text-gray-800'}`}>
                        {r.label}
                      </p>
                      <p className="text-xs text-gray-500 mt-0.5">{r.desc}</p>
                    </div>
                    <div className={`w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition ${
                      isSelected ? 'border-blue-600' : 'border-gray-300'
                    }`}>
                      {isSelected && <div className="w-2 h-2 rounded-full bg-blue-600" />}
                    </div>
                  </button>
                );
              })}
            </div>
            <input type="hidden" {...register('rol')} />
            {errors.rol && <p className="text-red-500 text-xs mt-1">{errors.rol.message}</p>}
          </div>

          {apiError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3">
              <p className="text-red-600 text-sm">{apiError}</p>
            </div>
          )}

          {/* Actions */}
          <div className="flex gap-3 pt-1">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 border border-gray-300 rounded-xl py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="flex-1 bg-blue-600 text-white rounded-xl py-2.5 text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {isPending ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Enviando...
                </>
              ) : (
                <>
                  <Send className="w-4 h-4" />
                  Enviar invitación
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

