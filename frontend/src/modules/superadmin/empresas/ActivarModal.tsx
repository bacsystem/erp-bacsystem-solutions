'use client'

import { useActivarEmpresa } from './use-empresas'

interface Props {
  empresaId: string
  onClose: () => void
}

export default function ActivarModal({ empresaId, onClose }: Props) {
  const { mutateAsync, isPending, error } = useActivarEmpresa()

  async function handleActivar() {
    await mutateAsync(empresaId)
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="bg-gray-900 border border-gray-700 rounded-xl p-6 w-full max-w-sm">
        <h3 className="text-white font-bold text-lg mb-2">Reactivar empresa</h3>
        <p className="text-gray-400 text-sm mb-4">
          Esta acción reactivará la suscripción y enviará un email al owner de la empresa notificando la reactivación.
        </p>
        {error && (
          <p className="text-red-400 text-sm mb-3">Error al reactivar. Intenta nuevamente.</p>
        )}
        <div className="flex gap-3 justify-end">
          <button onClick={onClose} className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg">
            Cancelar
          </button>
          <button
            onClick={handleActivar}
            disabled={isPending}
            className="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg flex items-center gap-2"
          >
            {isPending ? (
              <><span className="animate-spin">⟳</span> Reactivando...</>
            ) : (
              'Reactivar empresa'
            )}
          </button>
        </div>
      </div>
    </div>
  )
}
