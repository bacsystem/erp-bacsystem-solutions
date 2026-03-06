'use client';

import Link from 'next/link';
import { useAuthStore } from '@/shared/stores/auth.store';

export function SuscripcionBanner() {
  const { user } = useAuthStore();
  const estado = user?.suscripcion?.estado;

  if (!estado || (estado !== 'vencida' && estado !== 'cancelada')) {
    return null;
  }

  return (
    <div className={`px-4 py-2 text-sm flex items-center justify-between ${
      estado === 'cancelada' ? 'bg-red-600 text-white' : 'bg-amber-500 text-white'
    }`}>
      {estado === 'cancelada' ? (
        <>
          <span>Tu suscripción ha sido cancelada. Reactiva tu cuenta para continuar.</span>
          <Link href="/configuracion/plan" className="ml-4 underline font-medium">
            Reactivar ahora
          </Link>
        </>
      ) : (
        <>
          <span>Tu suscripción ha vencido. Renueva tu plan para continuar.</span>
          <Link href="/configuracion/plan" className="ml-4 underline font-medium">
            Renovar ahora
          </Link>
        </>
      )}
    </div>
  );
}
