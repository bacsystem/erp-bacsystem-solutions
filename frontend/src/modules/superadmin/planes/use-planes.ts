import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'

export interface Plan {
  id: string
  nombre: string
  nombre_display: string
  precio_mensual: number
  modulos: string[]
  activo: boolean
  tenants_activos: number
  mrr_plan: number
}

export function usePlanes() {
  return useQuery<Plan[]>({
    queryKey: ['superadmin', 'planes'],
    queryFn: async () => {
      const res = await superadminApi.get('/planes')
      return res.data.data
    },
  })
}

export function useUpdatePlan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ planId, data }: { planId: string; data: { precio_mensual?: number; modulos?: string[] } }) =>
      superadminApi.put(`/planes/${planId}`, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['superadmin', 'planes'] }),
  })
}

export function useAplicarDescuento() {
  return useMutation({
    mutationFn: ({ empresaId, data }: { empresaId: string; data: { tipo: string; valor: number; motivo: string } }) =>
      superadminApi.post(`/empresas/${empresaId}/descuento`, data),
  })
}

export function useDesactivarDescuento() {
  return useMutation({
    mutationFn: ({ empresaId, descuentoId }: { empresaId: string; descuentoId: string }) =>
      superadminApi.delete(`/empresas/${empresaId}/descuento/${descuentoId}`),
  })
}
