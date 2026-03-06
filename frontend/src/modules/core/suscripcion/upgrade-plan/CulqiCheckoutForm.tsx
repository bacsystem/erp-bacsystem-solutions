'use client';

import { useEffect, useState } from 'react';
import { CreditCard, Loader2 } from 'lucide-react';
import { useUpgradePlan } from './use-upgrade-plan';

declare global {
  interface Window {
    Culqi?: any;
    culqi?: () => void;
  }
}

interface CulqiCheckoutFormProps {
  readonly planId: string;
  readonly montoProrrateo: number;
  readonly onSuccess: () => void;
  readonly onError: (msg: string) => void;
}

export function CulqiCheckoutForm({ planId, montoProrrateo, onSuccess, onError }: CulqiCheckoutFormProps) {
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

  const culqi = globalThis as unknown as Window;

  const handlePay = () => {
    if (!culqi.Culqi) {
      onError('Culqi no está disponible. Recarga la página.');
      return;
    }

    culqi.Culqi.publicKey = process.env.NEXT_PUBLIC_CULQI_PUBLIC_KEY ?? '';
    culqi.Culqi.settings({
      title: 'OperaAI',
      currency: 'PEN',
      description: 'Upgrade de plan',
      amount: Math.round(montoProrrateo * 100),
    });

    culqi.culqi = () => {
      if (culqi.Culqi.token) {
        upgrade(
          { plan_id: planId, culqi_token: culqi.Culqi.token.id },
          { onSuccess, onError: (e: unknown) => onError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Error en el pago') }
        );
      } else if (culqi.Culqi.error) {
        onError(culqi.Culqi.error.user_message ?? 'Error en el pago');
      }
    };

    culqi.Culqi.open();
  };

  return (
    <div className="space-y-4">
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div className="flex items-center gap-2 text-blue-600 text-xs font-medium mb-2">
          <CreditCard className="w-3.5 h-3.5" />
          Monto a pagar hoy (prorrateo)
        </div>
        <p className="text-3xl font-extrabold text-blue-700">S/. {montoProrrateo.toFixed(2)}</p>
        <p className="text-xs text-gray-500 mt-1">Calculado por los días restantes de tu período actual.</p>
      </div>
      <button
        type="button"
        onClick={handlePay}
        disabled={!loaded || isPending}
        className="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        {isPending ? (
          <><Loader2 className="w-4 h-4 animate-spin" /> Procesando...</>
        ) : (
          <><CreditCard className="w-4 h-4" /> Pagar con tarjeta</>
        )}
      </button>
    </div>
  );
}
