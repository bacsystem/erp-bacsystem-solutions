'use client';

import { useState } from 'react';
import { AlertTriangle, CalendarClock, Check, Loader2, TrendingDown, X } from 'lucide-react';
import type { Plan } from '@/modules/core/auth/register/register.api';
import { useDowngradePlan } from './use-downgrade-plan';

interface DowngradePlanModalProps {
  readonly plan: Plan;
  readonly onClose: () => void;
}

export function DowngradePlanModal({ plan, onClose }: DowngradePlanModalProps) {
  const { mutate: downgrade, isPending, error } = useDowngradePlan();
  const [confirmed, setConfirmed] = useState(false);

  const apiError = (error as { response?: { data?: { message?: string } } })?.response?.data?.message;

  if (confirmed) {
    return (
      <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div className="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-xl">
          <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CalendarClock className="w-8 h-8 text-blue-600" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">Cambio programado</h2>
          <p className="text-gray-500 text-sm mb-6">
            Tu plan cambiará a <strong>{plan.nombre_display}</strong> al inicio del próximo período de facturación.
          </p>
          <button
            onClick={onClose}
            className="w-full bg-blue-600 text-white rounded-xl py-2.5 text-sm font-medium hover:bg-blue-700 transition"
          >
            Entendido
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
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
              <TrendingDown className="w-4 h-4 text-amber-600" />
            </div>
            <div>
              <h2 className="text-base font-bold text-gray-900">Cambiar a {plan.nombre_display}</h2>
              <p className="text-xs text-gray-500">Efectivo al inicio del próximo período</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6 space-y-4">
          {/* Plan features */}
          <div className="bg-gray-50 rounded-xl p-4">
            <p className="text-sm font-semibold text-gray-700 mb-2">Incluido en {plan.nombre_display}:</p>
            <ul className="space-y-1.5">
              {plan.modulos.map((m) => (
                <li key={m} className="flex items-center gap-2 text-sm text-gray-700 capitalize">
                  <Check className="w-4 h-4 text-green-500 flex-shrink-0" strokeWidth={2.5} />
                  {m}
                </li>
              ))}
            </ul>
          </div>

          {/* Warning */}
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
            <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
            <div className="text-sm text-amber-800">
              <p className="font-semibold mb-1">Perderás acceso a módulos superiores</p>
              <p className="text-xs">Los módulos no incluidos en este plan dejarán de estar disponibles al inicio del próximo período.</p>
            </div>
          </div>

          {apiError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3">
              <p className="text-red-600 text-sm">{apiError}</p>
            </div>
          )}

          <div className="flex gap-3 pt-1">
            <button
              onClick={onClose}
              className="flex-1 border border-gray-300 rounded-xl py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Cancelar
            </button>
            <button
              onClick={() => downgrade(plan.id, { onSuccess: () => setConfirmed(true) })}
              disabled={isPending}
              className="flex-1 bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-2.5 text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {isPending ? (
                <><Loader2 className="w-4 h-4 animate-spin" /> Programando...</>
              ) : (
                'Confirmar cambio'
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
