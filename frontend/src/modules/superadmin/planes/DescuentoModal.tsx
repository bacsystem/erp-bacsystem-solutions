'use client'

import { useState } from 'react'
import { useAplicarDescuento } from './use-planes'

interface Props {
  empresaId: string
  onClose: () => void
}

export default function DescuentoModal({ empresaId, onClose }: Props) {
  const { mutateAsync, isPending, error } = useAplicarDescuento()
  const [tipo, setTipo] = useState<'porcentaje' | 'monto_fijo'>('porcentaje')
  const [valor, setValor] = useState('')
  const [motivo, setMotivo] = useState('')

  async function handleSubmit() {
    await mutateAsync({
      empresaId,
      data: { tipo, valor: parseFloat(valor), motivo },
    })
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="bg-gray-900 border border-gray-700 rounded-xl p-6 w-full max-w-md">
        <h3 className="text-white font-bold text-lg mb-4">Aplicar descuento</h3>

        <div className="space-y-4">
          <div>
            <label className="block text-sm text-gray-400 mb-1">Tipo de descuento</label>
            <select
              value={tipo}
              onChange={(e) => setTipo(e.target.value as 'porcentaje' | 'monto_fijo')}
              className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500"
            >
              <option value="porcentaje">Porcentaje (%)</option>
              <option value="monto_fijo">Monto fijo (S/)</option>
            </select>
          </div>

          <div>
            <label className="block text-sm text-gray-400 mb-1">
              Valor {tipo === 'porcentaje' ? '(%)' : '(S/)'}
            </label>
            <input
              type="number"
              value={valor}
              onChange={(e) => setValor(e.target.value)}
              min="0.01"
              max={tipo === 'porcentaje' ? '100' : undefined}
              step="0.01"
              className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div>
            <label className="block text-sm text-gray-400 mb-1">Motivo</label>
            <input
              type="text"
              value={motivo}
              onChange={(e) => setMotivo(e.target.value)}
              placeholder="Ej: Descuento por fidelidad"
              className="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-indigo-500"
            />
          </div>

          {error && <p className="text-red-400 text-sm">Error al aplicar descuento.</p>}
        </div>

        <div className="flex gap-3 justify-end mt-5">
          <button onClick={onClose} className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg">
            Cancelar
          </button>
          <button
            onClick={handleSubmit}
            disabled={isPending || !valor || !motivo}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg"
          >
            {isPending ? 'Aplicando...' : 'Aplicar descuento'}
          </button>
        </div>
      </div>
    </div>
  )
}
