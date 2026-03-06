'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Calendar,
  Check,
  CheckCircle2,
  CreditCard,
  Loader2,
  Package,
  Star,
  TrendingUp,
  Users,
  Zap,
} from 'lucide-react';
import { Sidebar } from '@/shared/components/Sidebar';
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner';
import { useSuscripcion } from '@/modules/core/suscripcion/get-suscripcion/use-suscripcion';
import { getPlanes } from '@/modules/core/auth/register/register.api';
import { useAuthStore } from '@/shared/stores/auth.store';
import { UpgradePlanModal } from '@/modules/core/suscripcion/upgrade-plan/UpgradePlanModal';
import type { Plan } from '@/modules/core/auth/register/register.api';

const estadoBadge: Record<string, { label: string; className: string }> = {
  activo: { label: 'Activo', className: 'bg-green-100 text-green-700' },
  trial: { label: 'Prueba gratuita', className: 'bg-blue-100 text-blue-700' },
  vencido: { label: 'Vencido', className: 'bg-red-100 text-red-700' },
  cancelado: { label: 'Cancelado', className: 'bg-gray-100 text-gray-600' },
};

function formatDate(dateStr: string | null) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function PlanPage() {
  const { user } = useAuthStore();
  const { data: suscripcion, isLoading } = useSuscripcion();
  const { data: planes = [] } = useQuery({ queryKey: ['planes'], queryFn: getPlanes });
  const [upgradeTarget, setUpgradeTarget] = useState<Plan | null>(null);

  const esOwner = user?.rol === 'owner';
  const currentPrecio = suscripcion?.plan?.precio_mensual ?? 0;
  const badge = estadoBadge[suscripcion?.estado ?? ''] ?? { label: suscripcion?.estado ?? '', className: 'bg-gray-100 text-gray-600' };
  const estaVencidaOCancelada = suscripcion?.estado === 'vencida' || suscripcion?.estado === 'cancelada';

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-5xl mx-auto p-8 space-y-8">

            {/* Page header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <Package className="w-4 h-4" />
                <span>Configuración</span>
                <span>/</span>
                <span className="text-gray-900 font-medium">Plan y Facturación</span>
              </div>
              <h1 className="text-2xl font-bold text-gray-900">Plan y Facturación</h1>
              <p className="text-sm text-gray-500 mt-1">Administra tu suscripción y método de pago</p>
            </div>

            {isLoading ? (
              <div className="flex items-center justify-center py-20">
                <Loader2 className="w-6 h-6 animate-spin text-blue-500" />
              </div>
            ) : suscripcion ? (
              <>
                {/* Current plan card */}
                <div className="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg shadow-blue-200">
                  <div className="flex items-start justify-between">
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <span className={`text-xs font-semibold px-2.5 py-0.5 rounded-full ${badge.className}`}>
                          {badge.label}
                        </span>
                      </div>
                      <h2 className="text-2xl font-bold mt-2">{suscripcion.plan_nombre}</h2>
                      <p className="text-blue-100 text-sm mt-0.5">
                        S/. {suscripcion.plan?.precio_mensual ?? '—'} / mes
                      </p>
                    </div>
                    <div className="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                      <Zap className="w-6 h-6 text-white" />
                    </div>
                  </div>

                  <div className="mt-6 grid grid-cols-3 gap-4">
                    <div className="bg-white/10 rounded-xl p-3">
                      <div className="flex items-center gap-1.5 text-blue-100 text-xs mb-1">
                        <Calendar className="w-3.5 h-3.5" />
                        Días restantes
                      </div>
                      <p className="text-xl font-bold">{suscripcion.dias_restantes}</p>
                    </div>
                    <div className="bg-white/10 rounded-xl p-3">
                      <div className="flex items-center gap-1.5 text-blue-100 text-xs mb-1">
                        <Calendar className="w-3.5 h-3.5" />
                        Próximo cobro
                      </div>
                      <p className="text-sm font-semibold">{formatDate(suscripcion.fecha_proximo_cobro)}</p>
                    </div>
                    <div className="bg-white/10 rounded-xl p-3">
                      <div className="flex items-center gap-1.5 text-blue-100 text-xs mb-1">
                        <CreditCard className="w-3.5 h-3.5" />
                        Método de pago
                      </div>
                      {suscripcion.datos_pago.tiene_tarjeta ? (
                        <p className="text-sm font-semibold">
                          {suscripcion.datos_pago.card_brand} ····{suscripcion.datos_pago.card_last4}
                        </p>
                      ) : (
                        <p className="text-sm text-blue-200">Sin tarjeta</p>
                      )}
                    </div>
                  </div>

                  {/* Current plan modules */}
                  {suscripcion.plan?.modulos?.length > 0 && (
                    <div className="mt-5 flex flex-wrap gap-2">
                      {suscripcion.plan.modulos.map((m) => (
                        <span key={m} className="inline-flex items-center gap-1 bg-white/15 text-white text-xs font-medium px-2.5 py-1 rounded-full capitalize">
                          <CheckCircle2 className="w-3 h-3" /> {m}
                        </span>
                      ))}
                      <span className="inline-flex items-center gap-1 bg-white/15 text-white text-xs font-medium px-2.5 py-1 rounded-full">
                        <Users className="w-3 h-3" />
                        {suscripcion.plan.max_usuarios === null ? 'Usuarios ilimitados' : `Hasta ${suscripcion.plan.max_usuarios} usuarios`}
                      </span>
                    </div>
                  )}
                </div>

                {/* Plans comparison */}
                <div>
                  <div className="flex items-center justify-between mb-4">
                    <div>
                      <h2 className="text-lg font-bold text-gray-900">Planes disponibles</h2>
                      <p className="text-sm text-gray-500">Cambia de plan en cualquier momento</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {planes.map((plan) => {
                      const esPlanActual = plan.nombre === suscripcion.plan?.nombre || plan.id === suscripcion.plan?.id;
                      const esUpgrade = !esPlanActual && plan.precio_mensual > currentPrecio;
                      const esDowngrade = !esPlanActual && plan.precio_mensual < currentPrecio;
                      const puedeRenovar = estaVencidaOCancelada;

                      return (
                        <div
                          key={plan.id}
                          className={`relative bg-white rounded-2xl border-2 p-5 flex flex-col transition-shadow ${
                            esPlanActual
                              ? 'border-blue-600 shadow-md shadow-blue-100'
                              : plan.recomendado
                              ? 'border-blue-300 shadow-sm'
                              : 'border-gray-200'
                          }`}
                        >
                          {/* Popular badge */}
                          {plan.recomendado && !esPlanActual && (
                            <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                              <span className="bg-blue-600 text-white text-xs font-semibold px-3 py-0.5 rounded-full inline-flex items-center gap-1">
                                <Star className="w-3 h-3 fill-current" /> Más popular
                              </span>
                            </div>
                          )}
                          {esPlanActual && (
                            <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                              <span className="bg-green-600 text-white text-xs font-semibold px-3 py-0.5 rounded-full inline-flex items-center gap-1">
                                <Check className="w-3 h-3" strokeWidth={3} /> Tu plan actual
                              </span>
                            </div>
                          )}

                          <div className="mt-2">
                            <h3 className="font-bold text-gray-900 text-base">{plan.nombre_display}</h3>
                            <p className="mt-2">
                              <span className="text-3xl font-extrabold text-gray-900">S/.{plan.precio_mensual}</span>
                              <span className="text-sm text-gray-500 ml-1">/mes</span>
                            </p>
                          </div>

                          <ul className="mt-4 space-y-2 flex-1">
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

                          {esOwner && (
                            <div className="mt-5">
                              {puedeRenovar ? (
                                <button
                                  onClick={() => setUpgradeTarget(plan)}
                                  className="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 text-sm font-medium transition flex items-center justify-center gap-2"
                                >
                                  <TrendingUp className="w-4 h-4" /> {esPlanActual ? 'Renovar plan' : 'Activar este plan'}
                                </button>
                              ) : esPlanActual ? (
                                <div className="w-full bg-green-50 text-green-700 rounded-xl py-2.5 text-sm font-medium text-center border border-green-200">
                                  Plan activo
                                </div>
                              ) : esUpgrade ? (
                                <button
                                  onClick={() => setUpgradeTarget(plan)}
                                  className="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 text-sm font-medium transition flex items-center justify-center gap-2"
                                >
                                  <TrendingUp className="w-4 h-4" /> Actualizar plan
                                </button>
                              ) : esDowngrade ? (
                                <button
                                  onClick={() => setUpgradeTarget(plan)}
                                  className="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl py-2.5 text-sm font-medium transition flex items-center justify-center gap-2"
                                >
                                  Cambiar a este plan
                                </button>
                              ) : null}
                            </div>
                          )}

                          {!esOwner && (
                            <p className="mt-4 text-xs text-gray-400 text-center">Solo el propietario puede cambiar el plan</p>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </div>
              </>
            ) : null}
          </div>
        </div>
      </main>

      {upgradeTarget && suscripcion && (
        <UpgradePlanModal
          plan={upgradeTarget}
          suscripcion={suscripcion}
          onClose={() => setUpgradeTarget(null)}
        />
      )}

    </div>
  );
}
