import { useQuery } from '@tanstack/react-query'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'

export interface LogFilters {
  empresa_id?: string
  accion?: string
  fecha_desde?: string
  fecha_hasta?: string
  page?: number
}

export function useLogs(filters: LogFilters = {}) {
  return useQuery({
    queryKey: ['superadmin', 'logs', filters],
    queryFn: async () => {
      const res = await superadminApi.get('/logs', { params: filters })
      return res.data
    },
  })
}

export async function exportLogs(filters: LogFilters = {}) {
  const res = await superadminApi.get('/logs/export', {
    params: filters,
    responseType: 'blob',
  })
  const url = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = url
  a.download = `logs-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
