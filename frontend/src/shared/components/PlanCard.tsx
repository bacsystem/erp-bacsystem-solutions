'use client';

import { Check, Star } from 'lucide-react';
import type { Plan } from '@/modules/core/auth/register/register.api';

interface PlanCardProps {
  readonly plan: Plan;
  readonly selected: boolean;
  readonly onSelect: (id: string) => void;
}

export function PlanCard({ plan, selected, onSelect }: PlanCardProps) {
  return (
    <button
      type="button"
      onClick={() => onSelect(plan.id)}
      className={`relative border-2 rounded-xl p-5 text-left transition-all w-full flex flex-col ${
        selected
          ? 'border-blue-600 bg-blue-50 shadow-md shadow-blue-100'
          : 'border-gray-200 hover:border-blue-300 hover:shadow-sm'
      } ${
        plan.recomendado ? 'ring-2 ring-blue-600 ring-offset-1' : ''
      }`}
    >
      {/* Recommended badge */}
      {plan.recomendado && (
        <div className="absolute -top-3 left-1/2 -translate-x-1/2">
          <span className="bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap inline-flex items-center gap-1">
            <Star className="w-3 h-3 fill-current" /> Más popular
          </span>
        </div>
      )}

      {/* Selected check */}
      {selected && (
        <div className="absolute top-3 right-3 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
          <Check className="w-3.5 h-3.5 text-white" strokeWidth={3} />
        </div>
      )}

      <div className={`mt-${plan.recomendado ? '4' : '0'}`}>
        <span className="font-bold text-gray-900 text-base">{plan.nombre_display}</span>
      </div>

      <p className="mt-3 mb-4">
        <span className="text-3xl font-extrabold text-gray-900">S/.{plan.precio_mensual}</span>
        <span className="text-sm font-normal text-gray-500 ml-1">/mes</span>
      </p>

      <ul className="space-y-1.5 flex-1">
        <li className="flex items-center gap-2 text-sm text-gray-700">
          <Check className="w-4 h-4 text-blue-500 flex-shrink-0" strokeWidth={2.5} />
          {plan.max_usuarios === null ? 'Usuarios ilimitados' : `Hasta ${plan.max_usuarios} usuarios`}
        </li>
        {plan.modulos.map((m) => (
          <li key={m} className="flex items-center gap-2 text-sm text-gray-700 capitalize">
            <Check className="w-4 h-4 text-blue-500 flex-shrink-0" strokeWidth={2.5} />
            {m}
          </li>
        ))}
      </ul>

      <div className={`mt-4 text-center text-sm font-medium py-2 rounded-lg transition ${
        selected ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-blue-50'
      }`}>
        {selected ? 'Plan seleccionado' : 'Seleccionar'}
      </div>
    </button>
  );
}
