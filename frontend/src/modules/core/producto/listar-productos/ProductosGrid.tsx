'use client'

import Link from 'next/link'
import { Package } from 'lucide-react'
import type { Producto } from '../shared/producto.types'

interface Props {
  productos: Producto[]
  loading?: boolean
}

export function ProductosGrid({ productos, loading }: Props) {
  if (loading) {
    return (
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="bg-white border border-gray-100 rounded-xl overflow-hidden animate-pulse">
            <div className="h-40 bg-gray-100" />
            <div className="p-3 space-y-2">
              <div className="h-4 bg-gray-100 rounded w-3/4" />
              <div className="h-3 bg-gray-100 rounded w-1/2" />
              <div className="h-4 bg-gray-100 rounded w-1/3" />
            </div>
          </div>
        ))}
      </div>
    )
  }

  if (!productos.length) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-gray-400">
        <Package className="w-12 h-12 mb-3 opacity-30" />
        <p className="text-sm">No se encontraron productos</p>
      </div>
    )
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      {productos.map((p) => (
        <Link
          key={p.id}
          href={`/productos/${p.id}`}
          className="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md hover:border-blue-200 transition-all group"
        >
          {p.imagenes?.[0] ? (
            <img
              src={p.imagenes[0].url}
              alt={p.nombre}
              className="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-200"
            />
          ) : (
            <div className="w-full h-40 bg-gray-50 flex items-center justify-center">
              <Package className="w-10 h-10 text-gray-300" />
            </div>
          )}
          <div className="p-3">
            <p className="text-sm font-medium text-gray-900 truncate">{p.nombre}</p>
            <p className="text-xs text-gray-400 mt-0.5 font-mono">{p.sku}</p>
            <p className="text-sm font-semibold text-blue-600 mt-2">S/ {p.precio_venta.toFixed(2)}</p>
            {!p.activo && (
              <span className="text-xs bg-red-50 text-red-500 px-1.5 py-0.5 rounded-full mt-1 inline-block">
                Inactivo
              </span>
            )}
          </div>
        </Link>
      ))}
    </div>
  )
}
