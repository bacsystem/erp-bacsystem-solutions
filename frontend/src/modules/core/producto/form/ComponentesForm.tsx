'use client'

import { useState } from 'react'
import { Plus, Search, Trash2 } from 'lucide-react'
import { useProductos } from '../listar-productos/use-productos'
import type { ProductoComponente, Producto } from '../shared/producto.types'

interface ComponenteInput {
  componente_id: string
  cantidad: number
  producto?: Producto
}

interface Props {
  value: ComponenteInput[]
  onChange: (v: ComponenteInput[]) => void
  currentProductoId?: string
}

export function ComponentesForm({ value, onChange, currentProductoId }: Props) {
  const [q, setQ] = useState('')
  const { data } = useProductos({ q, tipo: 'simple', per_page: 10 })
  const resultados = (data?.data ?? []).filter(
    (p) => p.id !== currentProductoId && !value.find((c) => c.componente_id === p.id),
  )

  const add = (producto: Producto) => {
    onChange([...value, { componente_id: producto.id, cantidad: 1, producto }])
    setQ('')
  }

  const remove = (id: string) => onChange(value.filter((c) => c.componente_id !== id))

  const updateCantidad = (id: string, cantidad: number) =>
    onChange(value.map((c) => (c.componente_id === id ? { ...c, cantidad } : c)))

  return (
    <div className="space-y-3">
      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          type="text"
          placeholder="Buscar productos para agregar..."
          value={q}
          onChange={(e) => setQ(e.target.value)}
          className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        {q && resultados.length > 0 && (
          <div className="absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
            {resultados.map((p) => (
              <button
                key={p.id}
                onClick={() => add(p)}
                className="w-full flex items-center justify-between px-3 py-2 text-sm hover:bg-gray-50 text-left"
              >
                <span className="text-gray-900">{p.nombre}</span>
                <span className="text-gray-400 font-mono text-xs">{p.sku}</span>
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Component list */}
      {value.length > 0 && (
        <div className="space-y-2">
          {value.map((c) => (
            <div key={c.componente_id} className="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate">{c.producto?.nombre ?? c.componente_id}</p>
                <p className="text-xs text-gray-400 font-mono">{c.producto?.sku}</p>
              </div>
              <div className="flex items-center gap-1">
                <span className="text-xs text-gray-500">Cant:</span>
                <input
                  type="number"
                  min="0.001"
                  step="0.001"
                  value={c.cantidad}
                  onChange={(e) => updateCantidad(c.componente_id, Number(e.target.value))}
                  className="w-20 text-sm border border-gray-200 rounded px-2 py-0.5 text-center"
                />
              </div>
              <button
                onClick={() => remove(c.componente_id)}
                className="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          ))}
        </div>
      )}

      {value.length === 0 && (
        <p className="text-xs text-gray-400 text-center py-4">
          Sin componentes. Agrega productos al kit.
        </p>
      )}
    </div>
  )
}
