import { useMutation } from '@tanstack/react-query'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'
import { useAuthStore } from '@/shared/stores/auth.store'

export function useImpersonar() {
  const { setAccessToken } = useAuthStore()

  return useMutation({
    mutationFn: async (empresaId: string) => {
      const res = await superadminApi.post(`/empresas/${empresaId}/impersonar`)
      return res.data.data
    },
    onSuccess: (data) => {
      setAccessToken(data.token)
      window.open('/dashboard', '_blank')
    },
  })
}

export function useTerminarImpersonacion() {
  const { logout } = useAuthStore()

  return useMutation({
    mutationFn: async (empresaId: string) => {
      const res = await superadminApi.delete(`/empresas/${empresaId}/impersonar`)
      return res.data
    },
    onSuccess: () => {
      logout()
    },
  })
}
