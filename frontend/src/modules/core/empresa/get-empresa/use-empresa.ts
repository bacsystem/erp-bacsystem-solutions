'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export interface Empresa {
  id: string;
  razon_social: string;
  nombre_comercial: string | null;
  ruc: string;
  direccion: string | null;
  ubigeo: string | null;
  regimen_tributario: string | null;
  logo_url: string | null;
}

const fetchEmpresa = () =>
  api.get<{ data: Empresa }>('/empresa').then((r) => r.data.data);

export function useEmpresa() {
  return useQuery({ queryKey: ['empresa'], queryFn: fetchEmpresa });
}
