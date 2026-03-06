import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'

export interface EmpresaRow {
  id: string
  razon_social: string
  nombre_comercial: string
  ruc: string
  plan: string
  estado: string
  mrr: number
  fecha_registro: string
}

export interface EmpresaFilters {
  q?: string
  estado?: string
  plan?: string
  page?: number
}

export function useEmpresas(filters: EmpresaFilters = {}) {
  return useQuery({
    queryKey: ['superadmin', 'empresas', filters],
    queryFn: async () => {
      const res = await superadminApi.get('/empresas', { params: filters })
      return res.data
    },
  })
}

export function useEmpresaDetalle(id: string) {
  return useQuery({
    queryKey: ['superadmin', 'empresa', id],
    queryFn: async () => {
      const res = await superadminApi.get(`/empresas/${id}`)
      return res.data.data
    },
    enabled: !!id,
  })
}

export function useSuspenderEmpresa() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (empresaId: string) => superadminApi.post(`/empresas/${empresaId}/suspender`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['superadmin', 'empresas'] })
      qc.invalidateQueries({ queryKey: ['superadmin', 'empresa'] })
    },
  })
}

export function useActivarEmpresa() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (empresaId: string) => superadminApi.post(`/empresas/${empresaId}/activar`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['superadmin', 'empresas'] })
      qc.invalidateQueries({ queryKey: ['superadmin', 'empresa'] })
    },
  })
}
