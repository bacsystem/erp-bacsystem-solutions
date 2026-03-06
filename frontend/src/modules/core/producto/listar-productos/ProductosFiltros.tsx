'use client'

import { useCallback } from 'react'
import { Search, X } from 'lucide-react'
import type { Categoria, ProductosFilters } from '../shared/producto.types'

interface Props {
  filters: ProductosFilters
  categorias: Categoria[]
  onChange: (f: ProductosFilters) => void
}

const SEL = (active: boolean) =>
  `text-sm border rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition-colors ${
    active
      ? 'border-blue-400 bg-blue-50 text-blue-700 font-medium'
      : 'border-gray-200 text-gray-600 hover:border-gray-300'
  }`

export function ProductosFiltros({ filters, categorias, onChange }: Props) {
  const set = useCallback(
    (key: keyof ProductosFilters, value: unknown) =>
      onChange({ ...filters, [key]: value || undefined, page: 1 }),
    [filters, onChange],
  )

  const clear = () => onChange({ page: 1, per_page: filters.per_page })

  const activeFilters: { label: string; key: keyof ProductosFilters }[] = [
    filters.q            ? { label: `"${filters.q}"`, key: 'q' }                                                                   : null,
    filters.categoria_id ? { label: categorias.find((c) => c.id === filters.categoria_id)?.nombre ?? 'Categoría', key: 'categoria_id' } : null,
    filters.tipo         ? { label: filters.tipo.charAt(0).toUpperCase() + filters.tipo.slice(1), key: 'tipo' }                    : null,
    filters.estado       ? { label: filters.estado === 'activo' ? 'Activo' : 'Inactivo', key: 'estado' }                          : null,
    filters.precio_min   ? { label: `Desde S/ ${filters.precio_min}`, key: 'precio_min' }                                         : null,
    filters.precio_max   ? { label: `Hasta S/ ${filters.precio_max}`, key: 'precio_max' }                                         : null,
  ].filter(Boolean) as { label: string; key: keyof ProductosFilters }[]

  return (
    <div className="space-y-2">
      {/* Row 1: search + selects */}
      <div className="flex flex-wrap gap-2">
        {/* Search */}
        <div className="relative flex-1 min-w-48">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
          <input
            type="text"
            placeholder="Nombre, SKU o código de barras..."
            value={filters.q ?? ''}
            onChange={(e) => set('q', e.target.value)}
            className="w-full pl-9 pr-8 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder:text-gray-400"
          />
          {filters.q && (
            <button
              onClick={() => set('q', '')}
              className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          )}
        </div>

        {/* Categoría */}
        <select
          value={filters.categoria_id ?? ''}
          onChange={(e) => set('categoria_id', e.target.value)}
          className={SEL(!!filters.categoria_id)}
        >
          <option value="">Categoría</option>
          {categorias.map((c) => (
            <option key={c.id} value={c.id}>{c.nombre}</option>
          ))}
        </select>

        {/* Tipo */}
        <select
          value={filters.tipo ?? ''}
          onChange={(e) => set('tipo', e.target.value)}
          className={SEL(!!filters.tipo)}
        >
          <option value="">Tipo</option>
          <option value="simple">Simple</option>
          <option value="compuesto">Compuesto</option>
          <option value="servicio">Servicio</option>
        </select>

        {/* Estado */}
        <select
          value={filters.estado ?? ''}
          onChange={(e) => set('estado', e.target.value as 'activo' | 'inactivo' | '')}
          className={SEL(!!filters.estado)}
        >
          <option value="">Estado</option>
          <option value="activo">Activo</option>
          <option value="inactivo">Inactivo</option>
        </select>

        {/* Precio */}
        <div className={`flex items-center gap-1.5 border rounded-lg px-3 py-2 bg-white transition-colors ${filters.precio_min || filters.precio_max ? 'border-blue-400 bg-blue-50' : 'border-gray-200'}`}>
          <span className="text-xs text-gray-400">S/</span>
          <input
            type="number"
            placeholder="Mín"
            value={filters.precio_min ?? ''}
            onChange={(e) => set('precio_min', e.target.value ? Number(e.target.value) : undefined)}
            className="w-14 text-sm bg-transparent focus:outline-none placeholder:text-gray-400"
          />
          <span className="text-gray-300">–</span>
          <input
            type="number"
            placeholder="Máx"
            value={filters.precio_max ?? ''}
            onChange={(e) => set('precio_max', e.target.value ? Number(e.target.value) : undefined)}
            className="w-14 text-sm bg-transparent focus:outline-none placeholder:text-gray-400"
          />
        </div>
      </div>

      {/* Row 2: active filter chips */}
      {activeFilters.length > 0 && (
        <div className="flex flex-wrap items-center gap-1.5">
          <span className="text-xs text-gray-400 mr-1">Filtros activos:</span>
          {activeFilters.map(({ label, key }) => (
            <span
              key={key}
              className="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200"
            >
              {label}
              <button onClick={() => set(key, undefined)} className="hover:text-blue-900 ml-0.5">
                <X className="w-3 h-3" />
              </button>
            </span>
          ))}
          <button
            onClick={clear}
            className="text-xs text-gray-400 hover:text-red-500 underline underline-offset-2 ml-1 transition-colors"
          >
            Limpiar todo
          </button>
        </div>
      )}
    </div>
  )
}
