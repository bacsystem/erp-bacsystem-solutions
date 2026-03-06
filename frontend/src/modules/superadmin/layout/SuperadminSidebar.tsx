'use client'

import { useState } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { BarChart2, Building2, CreditCard, ScrollText, LogOut } from 'lucide-react'
import { useSuperadminAuthStore } from '@/modules/superadmin/auth/superadmin-auth.store'
import { superadminApi } from '@/modules/superadmin/auth/superadmin.api'

const NAV = [
  { href: '/superadmin/dashboard', label: 'Dashboard', icon: BarChart2 },
  { href: '/superadmin/empresas', label: 'Empresas', icon: Building2 },
  { href: '/superadmin/planes', label: 'Planes', icon: CreditCard },
  { href: '/superadmin/logs', label: 'Logs', icon: ScrollText },
]

export default function SuperadminSidebar() {
  const pathname = usePathname()
  const router = useRouter()
  const { superadmin, logout } = useSuperadminAuthStore()
  const [confirmLogout, setConfirmLogout] = useState(false)
  const [loggingOut, setLoggingOut] = useState(false)

  async function handleLogout() {
    if (!confirmLogout) {
      setConfirmLogout(true)
      return
    }
    setLoggingOut(true)
    try {
      await superadminApi.post('/auth/logout')
    } finally {
      logout()
      router.push('/superadmin/login')
    }
  }

  return (
    <aside className="w-64 bg-gray-950 border-r border-gray-800 flex flex-col h-screen">
      <div className="p-5 border-b border-gray-800">
        <p className="text-white font-bold text-lg">OperaAI</p>
        <p className="text-gray-500 text-xs">Panel Superadmin</p>
      </div>

      <nav className="flex-1 p-4 space-y-1">
        {NAV.map(({ href, label, icon: Icon }) => {
          const isActive = pathname.startsWith(href)
          return (
            <Link
              key={href}
              href={href}
              className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                isActive
                  ? 'bg-indigo-600 text-white'
                  : 'text-gray-400 hover:text-white hover:bg-gray-800'
              }`}
            >
              <Icon size={18} />
              {label}
            </Link>
          )
        })}
      </nav>

      <div className="p-4 border-t border-gray-800">
        {superadmin && (
          <div className="mb-3 px-1">
            <p className="text-white text-sm font-medium truncate">{superadmin.nombre}</p>
            <p className="text-gray-500 text-xs truncate">{superadmin.email}</p>
          </div>
        )}

        {confirmLogout ? (
          <div className="space-y-2">
            <p className="text-yellow-400 text-xs px-1">¿Confirmar cierre de sesión?</p>
            <div className="flex gap-2">
              <button
                onClick={handleLogout}
                disabled={loggingOut}
                className="flex-1 py-1.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs font-medium rounded-lg"
              >
                {loggingOut ? 'Cerrando...' : 'Sí, salir'}
              </button>
              <button
                onClick={() => setConfirmLogout(false)}
                className="flex-1 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs font-medium rounded-lg"
              >
                Cancelar
              </button>
            </div>
          </div>
        ) : (
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg text-sm transition-colors"
          >
            <LogOut size={16} />
            Cerrar sesión
          </button>
        )}
      </div>
    </aside>
  )
}
