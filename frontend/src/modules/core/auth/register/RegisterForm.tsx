'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useQuery } from '@tanstack/react-query';
import { BarChart2, Building2, Check, ClipboardCheck, Package } from 'lucide-react';
import { PlanCard } from '@/shared/components/PlanCard';
import { getPlanes } from './register.api';
import { registerSchema, type RegisterFormData } from './register.schema';
import { useRegister } from './use-register';

const STEPS = [
  { label: 'Plan', Icon: Package },
  { label: 'Empresa', Icon: Building2 },
  { label: 'Cuenta', Icon: BarChart2 },
  { label: 'Confirmar', Icon: ClipboardCheck },
];

const INPUT_CLASS = 'w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
const BTN_SECONDARY = 'flex-1 border border-gray-300 rounded-lg py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
const BTN_PRIMARY = 'flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed';

const REGIMENES = [
  { value: 'RER', label: 'Nuevo RUS (RER)', desc: 'Régimen Especial de Renta — hasta S/. 525,000 de ingresos anuales' },
  { value: 'RMT', label: 'Régimen MYPE Tributario (RMT)', desc: 'Para MYPES con ingresos hasta 1,700 UIT — tasa progresiva 10% / 29.5%' },
  { value: 'RG', label: 'Régimen General (RG)', desc: 'Sin límite de ingresos — tasa del 29.5% sobre la renta neta' },
];

export function RegisterForm() {
  const [step, setStep] = useState(0);
  const { mutate: register, isPending, error } = useRegister();

  const { data: planes = [] } = useQuery({ queryKey: ['planes'], queryFn: getPlanes });

  const {
    register: field,
    handleSubmit,
    setValue,
    watch,
    trigger,
    formState: { errors },
  } = useForm<RegisterFormData>({ resolver: zodResolver(registerSchema) });

  const planId = watch('plan_id');
  const watchedData = watch();
  const apiError = (error as { response?: { data?: { message?: string } } })?.response?.data?.message;

  const onSubmit = (data: RegisterFormData) => register(data);

  const goToStep2 = async () => {
    const ok = await trigger(['empresa.razon_social', 'empresa.ruc', 'empresa.regimen_tributario']);
    if (ok) setStep(2);
  };

  const goToStep3 = async () => {
    const ok = await trigger(['owner.nombre', 'owner.email', 'owner.password', 'owner.password_confirmation']);
    if (ok) setStep(3);
  };

  // Errores por paso para mostrar en el resumen
  const step1Errors = errors.empresa?.razon_social || errors.empresa?.ruc || errors.empresa?.regimen_tributario;
  const step2Errors = errors.owner?.nombre || errors.owner?.email || errors.owner?.password || errors.owner?.password_confirmation;

  const selectedPlan = planes.find((p) => p.id === planId);
  const regimenSelected = watch('empresa.regimen_tributario');

  const stepCircleClass = (i: number) => {
    if (i < step) return 'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold bg-green-500 text-white';
    if (i === step) return 'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold bg-blue-600 text-white ring-4 ring-blue-100';
    return 'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold bg-gray-100 text-gray-400';
  };

  const stepLabelClass = (i: number) => {
    if (i === step) return 'text-xs mt-1 font-medium text-blue-600';
    if (i < step) return 'text-xs mt-1 font-medium text-green-600';
    return 'text-xs mt-1 font-medium text-gray-400';
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* Step indicator */}
      <div className="flex items-center gap-0">
        {STEPS.map((s, i) => (
          <div key={s.label} className="flex items-center flex-1">
            <div className="flex flex-col items-center flex-1">
              <div className={stepCircleClass(i)}>
                {i < step ? <Check size={14} strokeWidth={3} /> : i + 1}
              </div>
              <span className={stepLabelClass(i)}>{s.label}</span>
            </div>
            {i < STEPS.length - 1 && (
              <div className={`h-0.5 flex-1 mb-4 transition-all ${
                i < step ? 'bg-green-400' : 'bg-gray-200'
              }`} />
            )}
          </div>
        ))}
      </div>

      {/* Step 0: Plan selection */}
      {step === 0 && (
        <div className="space-y-4">
          <div className="text-center">
            <h2 className="text-xl font-bold text-gray-900">Elige tu plan</h2>
            <p className="text-sm text-gray-500 mt-1">Todos incluyen 30 días de prueba gratuita</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {planes.map((plan) => (
              <PlanCard
                key={plan.id}
                plan={plan}
                selected={planId === plan.id}
                onSelect={(id) => setValue('plan_id', id)}
              />
            ))}
          </div>
          {errors.plan_id && <p className="text-red-500 text-xs text-center">{errors.plan_id.message}</p>}
          <button
            type="button"
            onClick={() => planId && setStep(1)}
            className={BTN_PRIMARY + ' w-full'}
            disabled={!planId}
          >
            Continuar con {selectedPlan?.nombre_display ?? 'plan seleccionado'} →
          </button>
        </div>
      )}

      {/* Step 1: Empresa */}
      {step === 1 && (
        <div className="space-y-5">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Datos de la empresa</h2>
            <p className="text-sm text-gray-500 mt-1">Puedes completar más datos después en Configuración</p>
          </div>
          <div>
            <label htmlFor="razon_social" className="block text-sm font-semibold text-gray-700 mb-1.5">Razón Social <span className="text-red-500">*</span></label>
            <input id="razon_social" {...field('empresa.razon_social')} placeholder="Ej: Mi Empresa S.A.C." className={INPUT_CLASS} />
            {errors.empresa?.razon_social && <p className="text-red-500 text-xs mt-1">{errors.empresa.razon_social.message}</p>}
          </div>
          <div>
            <label htmlFor="ruc" className="block text-sm font-semibold text-gray-700 mb-1.5">RUC <span className="text-red-500">*</span></label>
            <input id="ruc" {...field('empresa.ruc')} maxLength={11} placeholder="20xxxxxxxxx" className={INPUT_CLASS} />
            {errors.empresa?.ruc && <p className="text-red-500 text-xs mt-1">{errors.empresa.ruc.message}</p>}
          </div>
          <div>
            <p className="block text-sm font-semibold text-gray-700 mb-2">Régimen Tributario <span className="text-red-500">*</span></p>
            <div className="grid grid-cols-1 gap-2">
              {REGIMENES.map((r) => {
                const isSelected = regimenSelected === r.value;
                return (
                  <button
                    key={r.value}
                    type="button"
                    onClick={() => setValue('empresa.regimen_tributario', r.value as 'RER' | 'RG' | 'RMT')}
                    className={`flex items-start gap-3 border-2 rounded-xl px-4 py-3 text-left transition-all ${
                      isSelected
                        ? 'border-blue-600 bg-blue-50'
                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                    }`}
                  >
                    <div className={`mt-0.5 w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition ${
                      isSelected ? 'border-blue-600' : 'border-gray-300'
                    }`}>
                      {isSelected && <div className="w-2.5 h-2.5 rounded-full bg-blue-600" />}
                    </div>
                    <div>
                      <span className={`text-sm font-semibold ${isSelected ? 'text-blue-700' : 'text-gray-800'}`}>
                        {r.label}
                      </span>
                      <p className="text-xs text-gray-500 mt-0.5">{r.desc}</p>
                    </div>
                  </button>
                );
              })}
            </div>
            <input type="hidden" {...field('empresa.regimen_tributario')} />
            {errors.empresa?.regimen_tributario && <p className="text-red-500 text-xs mt-1">{errors.empresa.regimen_tributario.message}</p>}
          </div>
          <div className="flex gap-3 pt-2">
            <button type="button" onClick={() => setStep(0)} className={BTN_SECONDARY}>← Atrás</button>
            <button type="button" onClick={goToStep2} className={BTN_PRIMARY}>Continuar →</button>
          </div>
        </div>
      )}

      {/* Step 2: Owner data */}
      {step === 2 && (
        <div className="space-y-5">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Tu cuenta de administrador</h2>
            <p className="text-sm text-gray-500 mt-1">Serás el owner con acceso total</p>
          </div>
          <div>
            <label htmlFor="nombre" className="block text-sm font-semibold text-gray-700 mb-1.5">Nombre completo <span className="text-red-500">*</span></label>
            <input id="nombre" {...field('owner.nombre')} placeholder="Juan Pérez" className={INPUT_CLASS} />
            {errors.owner?.nombre && <p className="text-red-500 text-xs mt-1">{errors.owner.nombre.message}</p>}
          </div>
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-gray-700 mb-1.5">Email <span className="text-red-500">*</span></label>
            <input id="email" {...field('owner.email')} type="email" placeholder="juan@empresa.com" className={INPUT_CLASS} />
            {errors.owner?.email && <p className="text-red-500 text-xs mt-1">{errors.owner.email.message}</p>}
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label htmlFor="password" className="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña <span className="text-red-500">*</span></label>
              <input id="password" {...field('owner.password')} type="password" placeholder="Mín. 8 caracteres" className={INPUT_CLASS} />
              {errors.owner?.password && <p className="text-red-500 text-xs mt-1">{errors.owner.password.message}</p>}
            </div>
            <div>
              <label htmlFor="password_confirmation" className="block text-sm font-semibold text-gray-700 mb-1.5">Confirmar <span className="text-red-500">*</span></label>
              <input id="password_confirmation" {...field('owner.password_confirmation')} type="password" placeholder="Repetir contraseña" className={INPUT_CLASS} />
              {errors.owner?.password_confirmation && <p className="text-red-500 text-xs mt-1">{errors.owner.password_confirmation.message}</p>}
            </div>
          </div>
          <div className="flex gap-3 pt-2">
            <button type="button" onClick={() => setStep(1)} className={BTN_SECONDARY}>← Atrás</button>
            <button type="button" onClick={goToStep3} className={BTN_PRIMARY}>Continuar →</button>
          </div>
        </div>
      )}

      {/* Step 3: Confirm */}
      {step === 3 && (
        <div className="space-y-5">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Resumen y confirmación</h2>
            <p className="text-sm text-gray-500 mt-1">Revisa los datos antes de crear tu cuenta</p>
          </div>
          <div className="bg-gray-50 rounded-xl p-5 space-y-3 border border-gray-200">
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Plan</span>
              <span className="font-semibold">{selectedPlan?.nombre_display} — S/.{selectedPlan?.precio_mensual}/mes</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Empresa</span>
              <span className="font-semibold">{watchedData.empresa?.razon_social}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">RUC</span>
              <span className="font-semibold">{watchedData.empresa?.ruc}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Administrador</span>
              <span className="font-semibold">{watchedData.owner?.nombre}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Email</span>
              <span className="font-semibold">{watchedData.owner?.email}</span>
            </div>
          </div>
          <div className="flex items-start gap-2 bg-blue-50 border border-blue-200 rounded-lg p-3">
            <span className="text-blue-500 mt-0.5">ℹ️</span>
            <p className="text-xs text-blue-700">Tu prueba gratuita de 30 días comienza hoy. No necesitas tarjeta de crédito.</p>
          </div>
          {(step1Errors || step2Errors) && (
            <div className="bg-red-50 border border-red-200 rounded-xl p-4 space-y-2">
              <p className="text-sm font-semibold text-red-700">⚠️ Hay campos incompletos o inválidos:</p>
              {step1Errors && (
                <button
                  type="button"
                  onClick={() => setStep(1)}
                  className="flex items-center gap-2 text-sm text-red-600 hover:text-red-800 hover:underline w-full text-left"
                >
                  → Paso 2 (Empresa):{' '}
                  {errors.empresa?.razon_social?.message || errors.empresa?.ruc?.message || errors.empresa?.regimen_tributario?.message}
                </button>
              )}
              {step2Errors && (
                <button
                  type="button"
                  onClick={() => setStep(2)}
                  className="flex items-center gap-2 text-sm text-red-600 hover:text-red-800 hover:underline w-full text-left"
                >
                  → Paso 3 (Cuenta):{' '}
                  {errors.owner?.nombre?.message || errors.owner?.email?.message || errors.owner?.password?.message || errors.owner?.password_confirmation?.message}
                </button>
              )}
            </div>
          )}
          {apiError && (
            <p className="text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg p-3">{apiError}</p>
          )}
          <div className="flex gap-3 pt-2">
            <button type="button" onClick={() => setStep(2)} className={BTN_SECONDARY}>← Atrás</button>
            <button type="submit" disabled={isPending} className={BTN_PRIMARY}>
              {isPending ? (
                <span className="flex items-center justify-center gap-2">⏳ Creando cuenta...</span>
              ) : '🚀 Crear cuenta gratis'}
            </button>
          </div>
        </div>
      )}
    </form>
  );
}
