import axios, { type AxiosError } from 'axios'
import { useAuthStore } from '@/shared/stores/auth.store'

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true, // envía httpOnly cookies automáticamente
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Inyectar access token en cada request
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().accessToken
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Cola para requests pendientes durante refresh
let isRefreshing = false
let refreshQueue: Array<(token: string) => void> = []

// Manejar 401 → refresh automático
api.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const original = error.config as typeof error.config & { _retry?: boolean }

    if (error.response?.status === 401 && original && !original._retry) {
      if (isRefreshing) {
        return new Promise((resolve) => {
          refreshQueue.push((token) => {
            if (original.headers) {
              original.headers.Authorization = `Bearer ${token}`
            }
            resolve(api(original))
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
        refreshQueue.forEach((cb) => cb(newToken))
        refreshQueue = []
        if (original.headers) {
          original.headers.Authorization = `Bearer ${newToken}`
        }
        return api(original)
      } catch {
        useAuthStore.getState().logout()
        if (typeof window !== 'undefined') {
          window.location.href = '/login'
        }
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
