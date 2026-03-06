'use client'

import { useState } from 'react'
import { CheckCircle, Loader2, XCircle } from 'lucide-react'
import { productosApi } from '../shared/productos.api'

interface FilaPreview {
  fila: number
  datos: Record<string, string>
  errores: string[]
  valido: boolean
}

interface Props {
  importToken: string
  total: number
  validos: number
  errores: number
  filas: FilaPreview[]
  onConfirmed: (creados: number) => void
  onCancel: () => void
}

export function ImportarPreview({ importToken, total, validos, errores, filas, onConfirmed, onCancel }: Props) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const confirmar = async () => {
    setLoading(true)
    setError(null)
    try {
      const result = await productosApi.importarConfirmar(importToken)
      onConfirmed(result.creados)
    } catch {
      setError('Error al confirmar la importación.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-4">
      {/* Summary */}
      <div className="grid grid-cols-3 gap-3">
        <div className="bg-gray-50 rounded-lg p-3 text-center">
          <p className="text-2xl font-bold text-gray-900">{total}</p>
          <p className="text-xs text-gray-500 mt-0.5">Total filas</p>
        </div>
        <div className="bg-green-50 rounded-lg p-3 text-center">
          <p className="text-2xl font-bold text-green-600">{validos}</p>
          <p className="text-xs text-gray-500 mt-0.5">Válidos</p>
        </div>
        <div className="bg-red-50 rounded-lg p-3 text-center">
          <p className="text-2xl font-bold text-red-500">{errores}</p>
          <p className="text-xs text-gray-500 mt-0.5">Con errores</p>
        </div>
      </div>

      {/* Rows table */}
      <div className="border border-gray-100 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
        <table className="w-full text-xs">
          <thead className="bg-gray-50 sticky top-0">
            <tr>
              <th className="px-3 py-2 text-left text-gray-500 font-semibold w-16">Fila</th>
              <th className="px-3 py-2 text-left text-gray-500 font-semibold">Nombre</th>
              <th className="px-3 py-2 text-left text-gray-500 font-semibold">SKU</th>
              <th className="px-3 py-2 text-left text-gray-500 font-semibold">Estado</th>
            </tr>
          </thead>
          <tbody>
            {filas.map((f) => (
              <tr key={f.fila} className={`border-t border-gray-100 ${f.valido ? '' : 'bg-red-50'}`}>
                <td className="px-3 py-2 text-gray-500">{f.fila}</td>
                <td className="px-3 py-2 text-gray-900">{f.datos.nombre ?? '-'}</td>
                <td className="px-3 py-2 font-mono text-gray-600">{f.datos.sku ?? '-'}</td>
                <td className="px-3 py-2">
                  {f.valido ? (
                    <CheckCircle className="w-4 h-4 text-green-500" />
                  ) : (
                    <div className="flex items-start gap-1">
                      <XCircle className="w-4 h-4 text-red-400 shrink-0" />
                      <span className="text-red-500">{f.errores[0]}</span>
                    </div>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {error && <p className="text-sm text-red-500">{error}</p>}

      {/* Actions */}
      <div className="flex justify-end gap-2">
        <button
          onClick={onCancel}
          className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          Cancelar
        </button>
        <button
          onClick={confirmar}
          disabled={loading || validos === 0}
          className="flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60"
        >
          {loading && <Loader2 className="w-4 h-4 animate-spin" />}
          Confirmar importación ({validos} productos)
        </button>
      </div>
    </div>
  )
}
