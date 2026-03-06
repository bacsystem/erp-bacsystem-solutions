'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import axios from 'axios'
import { api } from '@/shared/lib/api'
import { useAuthStore } from '@/shared/stores/auth.store'
import type { UserPayload } from '@/shared/types'

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const { accessToken, setAccessToken, setUser } = useAuthStore()
  const [ready, setReady] = useState(false)

  useEffect(() => {
    if (accessToken) {
      setReady(true)
      return
    }

    // Refrescar token con la cookie refresh_token
    axios
      .post<{ data: { access_token: string } }>(
        `${process.env.NEXT_PUBLIC_API_URL}/auth/refresh`,
        {},
        { withCredentials: true }
      )
      .then(({ data }) => {
        const token = data.data.access_token
        setAccessToken(token)

        // Cargar datos del usuario con el nuevo token
        return api
          .get<{ data: UserPayload }>('/me', {
            headers: { Authorization: `Bearer ${token}` },
          })
          .then(({ data: meData }) => {
            setUser(meData.data)
            setReady(true)
          })
      })
      .catch(() => {
        // Refresh token inválido o expirado → limpiar cookie y redirigir
        document.cookie = 'has_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
        router.replace('/login')
      })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  if (!ready) {
    return (
      <div className="flex h-screen items-center justify-center bg-gray-50">
        <div className="flex flex-col items-center gap-3">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent" />
          <p className="text-sm text-gray-500">Cargando sesión…</p>
        </div>
      </div>
    )
  }

  return <>{children}</>
}
