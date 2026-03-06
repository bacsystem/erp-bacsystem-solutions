'use client'

import { useAuthStore } from '@/shared/stores/auth.store'
import { useTerminarImpersonacion } from './use-impersonacion'

interface Props {
  empresaId: string
  empresaNombre?: string
}

export default function ImpersonacionBanner({ empresaId, empresaNombre }: Props) {
  const { user } = useAuthStore()
  const { mutate, isPending } = useTerminarImpersonacion()

  // Only show if the current token has 'impersonated' ability
  // This is a client-side check — the backend enforces authorization
  if (!user) return null

  return (
    <div className="bg-red-900/80 border-b border-red-700 px-4 py-2 flex items-center justify-between sticky top-0 z-50">
      <p className="text-red-200 text-sm font-medium">
        Estás viendo la cuenta de <strong>{empresaNombre ?? 'una empresa'}</strong> como superadmin
      </p>
      <button
        onClick={() => mutate(empresaId)}
        disabled={isPending}
        className="px-3 py-1 bg-red-700 hover:bg-red-600 disabled:opacity-50 text-white text-xs font-medium rounded"
      >
        {isPending ? 'Saliendo...' : 'Salir'}
      </button>
    </div>
  )
}
