import axios, { type AxiosError } from 'axios'
import { toast } from 'sonner'
import { useAuthStore } from '@/shared/stores/auth.store'

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Inyectar access token en cada request
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().accessToken
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Cola para requests pendientes durante refresh — con soporte de rechazo
type QueueItem = { resolve: (token: string) => void; reject: (err: unknown) => void }
let isRefreshing = false
let refreshQueue: QueueItem[] = []

function drainQueue(token: string) {
  refreshQueue.forEach(({ resolve }) => resolve(token))
  refreshQueue = []
}

function rejectQueue(err: unknown) {
  refreshQueue.forEach(({ reject }) => reject(err))
  refreshQueue = []
}

// Manejar 401 → refresh automático
api.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const original = error.config as typeof error.config & { _retry?: boolean }

    if (error.response?.status === 402) {
      const data = error.response.data as { message?: string; errors?: { redirect?: string } }
      const mensaje = data?.message ?? 'Tu suscripción ha vencido.'
      const redirect = data?.errors?.redirect ?? '/configuracion/plan'

      toast.warning(mensaje, {
        duration: 8000,
        action: {
          label: redirect === '/reactivar' ? 'Reactivar' : 'Ver planes',
          onClick: () => { window.location.href = redirect },
        },
      })

      return Promise.reject(error)
    }

    const isAuthEndpoint = original?.url?.includes('/auth/login') || original?.url?.includes('/auth/refresh')

    if (error.response?.status === 401 && original && !original._retry && !isAuthEndpoint) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          refreshQueue.push({
            resolve: (token) => {
              if (original.headers) original.headers.Authorization = `Bearer ${token}`
              resolve(api(original))
            },
            reject,
          })
        })
      }

      original._retry = true
      isRefreshing = true

      try {
        const { data } = await axios.post(
          `${process.env.NEXT_PUBLIC_API_URL}/auth/refresh`,
          {},
          { withCredentials: true }
        )
        const newToken = data.data.access_token
        useAuthStore.getState().setAccessToken(newToken)
        drainQueue(newToken)
        if (original.headers) original.headers.Authorization = `Bearer ${newToken}`
        return api(original)
      } catch (refreshError) {
        rejectQueue(refreshError)
        useAuthStore.getState().logout()
        if (typeof window !== 'undefined') {
          // Limpiar has_session para que el middleware no redirija de vuelta al dashboard
          document.cookie = 'has_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
          toast.error('Tu sesión ha expirado. Por favor, inicia sesión de nuevo.', { duration: 4000 })
          setTimeout(() => { window.location.href = '/login' }, 1000)
        }
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  }
)

export function getApiError(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? 'Ocurrió un error inesperado'
  }
  return 'Ocurrió un error inesperado'
}
