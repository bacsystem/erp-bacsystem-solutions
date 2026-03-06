'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export interface SuscripcionPlan {
  id: string;
  nombre: string;
  nombre_display: string;
  precio_mensual: number;
  max_usuarios: number | null;
  modulos: string[];
}

export interface SuscripcionData {
  id: string;
  plan: SuscripcionPlan;
  estado: string;
  fecha_inicio: string;
  fecha_vencimiento: string;
  fecha_proximo_cobro: string | null;
  dias_restantes: number;
  datos_pago: {
    tiene_tarjeta: boolean;
    card_last4: string | null;
    card_brand: string | null;
  };
  // Convenience accessors used in some components
  plan_nombre: string;
  plan_modulos: string[];
}

const fetchSuscripcion = async (): Promise<SuscripcionData> => {
  const res = await api.get<{ data: SuscripcionData }>('/suscripcion');
  const d = res.data.data;
  return {
    ...d,
    plan_nombre: d.plan?.nombre_display ?? d.plan?.nombre ?? '',
    plan_modulos: d.plan?.modulos ?? [],
  };
};

export function useSuscripcion() {
  return useQuery({ queryKey: ['suscripcion'], queryFn: fetchSuscripcion });
}
