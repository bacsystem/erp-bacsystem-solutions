'use client'

import { useState } from 'react'
import { useUpdatePlan, type Plan } from './use-planes'

const ALL_MODULES = ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia']

interface Props {
  plan: Plan
  onClose: () => void
}

export default function EditPlanModal({ plan, onClose }: Props) {
  const { mutateAsync, isPending } = useUpdatePlan()
  const [precio, setPrecio] = useState(plan.precio_mensual.toString())
  const [modulos, setModulos] = useState<string[]>(plan.modulos ?? [])

  function toggleModulo(mod: string) {
    setModulos((prev) => prev.includes(mod) ? prev.filter((m) => m !== mod) : [...prev, mod])
  }

  async function handleSubmit() {
    await mutateAsync({
      planId: plan.id,
      data: { precio_mensual: parseFloat(precio), modulos },
    })
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="bg-gray-900 border border-gray-700 rounded-xl p-6 w-full max-w-md">
        <h3 className="text-white font-bold text-lg mb-4">Editar plan: {plan.nombre_display}</h3>

        <div className="space-y-4">
          <div>
            <label className="block text-sm text-gray-400 mb-1">Precio mensual (S/)</label>
            <input
              type="number"
              value={precio}
              onChange={(e) => setPrecio(e.target.value)}
              step="0.01"
              min="0.01"
              className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div>
            <label className="block text-sm text-gray-400 mb-2">Módulos incluidos</label>
            <div className="space-y-2">
              {ALL_MODULES.map((mod) => (
                <label key={mod} className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={modulos.includes(mod)}
                    onChange={() => toggleModulo(mod)}
                    className="rounded border-gray-600 bg-gray-700 text-indigo-500"
                  />
                  <span className="text-white text-sm capitalize">{mod}</span>
                </label>
              ))}
            </div>
          </div>
        </div>

        <div className="flex gap-3 justify-end mt-5">
          <button onClick={onClose} className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg">
            Cancelar
          </button>
          <button
            onClick={handleSubmit}
            disabled={isPending}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg"
          >
            {isPending ? 'Guardando...' : 'Guardar cambios'}
          </button>
        </div>
      </div>
    </div>
  )
}
