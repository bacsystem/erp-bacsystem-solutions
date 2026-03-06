'use client';

import { useState } from 'react';
import { ArrowRight, CheckCircle2, TrendingUp, X } from 'lucide-react';
import type { Plan } from '@/modules/core/auth/register/register.api';
import type { SuscripcionData } from '../get-suscripcion/use-suscripcion';
import { CulqiCheckoutForm } from './CulqiCheckoutForm';

interface UpgradePlanModalProps {
  readonly plan: Plan;
  readonly suscripcion: SuscripcionData;
  readonly onClose: () => void;
}

export function UpgradePlanModal({ plan, suscripcion, onClose }: UpgradePlanModalProps) {
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const diasRestantes   = Number(suscripcion.dias_restantes) || 0;
  const precioActual    = Number(suscripcion.plan?.precio_mensual) || 0;
  const precioNuevo     = Number(plan.precio_mensual) || 0;
  const esRenovacion    = diasRestantes === 0 || precioNuevo <= precioActual;
  const montoProrrateo  = esRenovacion
    ? precioNuevo
    : ((precioNuevo - precioActual) / 30) * diasRestantes;

  if (success) {
    return (
      <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div className="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 text-center shadow-xl">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle2 className="w-8 h-8 text-green-600" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">¡Plan actualizado!</h2>
          <p className="text-gray-500 text-sm mb-6">
            Ya tienes acceso a todos los módulos del plan {plan.nombre_display}.
          </p>
          <button
            onClick={onClose}
            className="w-full bg-blue-600 text-white rounded-xl py-2.5 text-sm font-medium hover:bg-blue-700 transition"
          >
            Continuar
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
            <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
              <TrendingUp className="w-4 h-4 text-blue-600" />
            </div>
            <div>
              <h2 className="text-base font-bold text-gray-900">Actualizar plan</h2>
              <p className="text-xs text-gray-500">Acceso inmediato al nuevo plan</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6 space-y-5">
          {/* Plan comparison */}
          <div className="flex items-center gap-3">
            <div className="flex-1 bg-gray-50 border border-gray-200 rounded-xl p-3 text-center">
              <p className="text-xs text-gray-500 mb-1">Plan actual</p>
              <p className="font-semibold text-gray-800 text-sm">{suscripcion.plan_nombre}</p>
              <p className="text-xs text-gray-500">S/.{suscripcion.plan?.precio_mensual}/mes</p>
            </div>
            <ArrowRight className="w-5 h-5 text-blue-500 flex-shrink-0" />
            <div className="flex-1 bg-blue-50 border-2 border-blue-500 rounded-xl p-3 text-center">
              <p className="text-xs text-blue-500 mb-1">Plan nuevo</p>
              <p className="font-bold text-blue-800 text-sm">{plan.nombre_display}</p>
              <p className="text-xs text-blue-600">S/.{plan.precio_mensual}/mes</p>
            </div>
          </div>

          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3">
              <p className="text-red-600 text-sm">{error}</p>
            </div>
          )}

          <CulqiCheckoutForm
            planId={plan.id}
            montoProrrateo={montoProrrateo}
            esRenovacion={esRenovacion}
            onSuccess={() => setSuccess(true)}
            onError={setError}
          />
        </div>
      </div>
    </div>
  );
}
