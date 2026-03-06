'use client';

import { useEffect, useState } from 'react';
import { CreditCard, Loader2 } from 'lucide-react';
import { useUpgradePlan } from './use-upgrade-plan';

interface CulqiInstance {
  publicKey: string;
  settings: (opts: Record<string, unknown>) => void;
  open: () => void;
  close: () => void;
  token?: { id: string };
  error?: { user_message?: string };
}

declare global {
  interface Window {
    Culqi?: CulqiInstance;
    culqi?: () => void;
  }
}

interface CulqiCheckoutFormProps {
  readonly planId: string;
  readonly montoProrrateo: number;
  readonly esRenovacion?: boolean;
  readonly onSuccess: () => void;
  readonly onError: (msg: string) => void;
}

function getApiMsg(e: unknown) {
  return (e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Error en el pago';
}

export function CulqiCheckoutForm({ planId, montoProrrateo, esRenovacion = false, onSuccess, onError }: CulqiCheckoutFormProps) {
  const { mutate: upgrade, isPending } = useUpgradePlan();
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://checkout.culqi.com/js/v4';
    script.async = true;
    script.onload = () => setLoaded(true);
    document.head.appendChild(script);
    return () => { script.remove(); };
  }, []);

  const handlePay = () => {
    const culqi = globalThis as unknown as Window;
    if (!culqi.Culqi) { onError('Culqi no está disponible. Recarga la página.'); return; }

    const publicKey = process.env.NEXT_PUBLIC_CULQI_PUBLIC_KEY ?? '';
    if (!publicKey) { onError('Clave pública de Culqi no configurada.'); return; }

    culqi.Culqi.publicKey = publicKey;
    culqi.Culqi.settings({
      title: 'OperaAI',
      currency: 'PEN',
      description: esRenovacion ? 'Renovación de plan' : 'Upgrade de plan',
      amount: Math.round(Number(montoProrrateo) * 100),
    });

    const instance = culqi.Culqi;
    culqi.culqi = () => {
      if (instance?.token) {
        upgrade({ plan_id: planId, culqi_token: instance.token.id }, { onSuccess, onError: (e) => onError(getApiMsg(e)) });
      } else if (instance?.error) {
        onError(instance.error.user_message ?? 'Error en el pago');
      }
    };
    culqi.Culqi.open();
  };

  return (
    <div className="space-y-4">
      {/* Monto */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <p className="text-xs text-blue-600 font-medium mb-1">
          {esRenovacion ? 'Monto a pagar (renovación mensual)' : 'Monto a pagar hoy (prorrateo)'}
        </p>
        <p className="text-3xl font-extrabold text-blue-700">S/. {Number(montoProrrateo).toFixed(2)}</p>
        <p className="text-xs text-gray-500 mt-1">
          {esRenovacion
            ? 'Precio mensual del plan. Se renovará por 30 días.'
            : 'Diferencia proporcional por los días restantes de tu período actual.'}
        </p>
      </div>

      <button
        onClick={handlePay}
        disabled={!loaded || isPending}
        className="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 text-sm font-medium transition disabled:opacity-50 flex items-center justify-center gap-2"
      >
        {isPending
          ? <><Loader2 className="w-4 h-4 animate-spin" /> Procesando...</>
          : <><CreditCard className="w-4 h-4" /> Pagar</>}
      </button>
    </div>
  );
}
