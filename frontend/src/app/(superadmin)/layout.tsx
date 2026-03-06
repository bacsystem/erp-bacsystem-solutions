'use client'

import { useEffect } from 'react'
import { useRouter, usePathname } from 'next/navigation'
import SuperadminSidebar from '@/modules/superadmin/layout/SuperadminSidebar'
import { useSuperadminAuthStore } from '@/modules/superadmin/auth/superadmin-auth.store'

export default function SuperadminLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname()
  const router = useRouter()
  const { accessToken, _hasHydrated } = useSuperadminAuthStore()
  const isLoginPage = pathname === '/superadmin/login'

  useEffect(() => {
    if (!_hasHydrated) return
    if (!isLoginPage && !accessToken) {
      router.push('/superadmin/login')
    }
    if (isLoginPage && accessToken) {
      router.push('/superadmin/dashboard')
    }
  }, [accessToken, isLoginPage, router, _hasHydrated])

  if (!_hasHydrated) return null

  if (isLoginPage) {
    return <>{children}</>
  }

  if (!accessToken) {
    return null
  }

  return (
    <div className="flex h-screen bg-gray-900">
      <SuperadminSidebar />
      <main className="flex-1 overflow-auto p-6">{children}</main>
    </div>
  )
}
