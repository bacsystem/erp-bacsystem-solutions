'use client'

import { useState } from 'react'
import { usePlanes, type Plan } from './use-planes'
import EditPlanModal from './EditPlanModal'

export default function PlanesManager() {
  const { data: planes, isLoading } = usePlanes()
  const [editingPlan, setEditingPlan] = useState<Plan | null>(null)

  if (isLoading) return <div className="text-gray-400 text-center py-12">Cargando planes...</div>

  return (
    <div className="space-y-4">
      <div className="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-700">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Plan</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Precio mensual</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Módulos</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Tenants activos</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">MRR</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium"></th>
            </tr>
          </thead>
          <tbody>
            {(planes ?? []).map((plan) => (
              <tr key={plan.id} className="border-b border-gray-700/50">
                <td className="px-4 py-3 text-white font-medium">{plan.nombre_display}</td>
                <td className="px-4 py-3 text-gray-300">S/ {Number(plan.precio_mensual).toFixed(2)}</td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-1">
                    {(plan.modulos ?? []).map((m) => (
                      <span key={m} className="px-1.5 py-0.5 bg-gray-700 text-gray-300 text-xs rounded">{m}</span>
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3 text-gray-300">{plan.tenants_activos}</td>
                <td className="px-4 py-3 text-gray-300">S/ {Number(plan.mrr_plan ?? 0).toFixed(2)}</td>
                <td className="px-4 py-3">
                  <button
                    onClick={() => setEditingPlan(plan)}
                    className="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-xs rounded-lg"
                  >
                    Editar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {editingPlan && (
        <EditPlanModal plan={editingPlan} onClose={() => setEditingPlan(null)} />
      )}
    </div>
  )
}
