import axios from 'axios'
import { useSuperadminAuthStore } from './superadmin-auth.store'

export const superadminApi = axios.create({
  baseURL: `${process.env.NEXT_PUBLIC_API_URL?.replace('/api', '')}/superadmin/api`,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

superadminApi.interceptors.request.use((config) => {
  const token = useSuperadminAuthStore.getState().accessToken
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export function getSuperadminApiError(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? 'Ocurrió un error inesperado'
  }
  return 'Ocurrió un error inesperado'
}
