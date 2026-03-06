import { useQuery } from '@tanstack/react-query'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'

export interface MrrHistorico {
  mes: string
  mrr: number
}

export interface DashboardData {
  mrr_total: number
  totales_por_estado: Record<string, number>
  nuevos_hoy: number
  nuevos_mes: number
  tasa_conversion: number
  churn: number
  mrr_historico: MrrHistorico[]
}

export function useGlobalDashboard() {
  return useQuery<DashboardData>({
    queryKey: ['superadmin', 'dashboard'],
    queryFn: async () => {
      const res = await superadminApi.get('/dashboard')
      return res.data.data
    },
  })
}
