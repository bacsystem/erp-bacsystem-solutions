'use client'

import Link from 'next/link'
import { Package, PencilLine, Power } from 'lucide-react'
import type { Producto } from '../shared/producto.types'

interface Props {
  productos: Producto[]
  loading?: boolean
  onDesactivar: (id: string) => void
  onActivar: (id: string) => void
}

const IGV_BADGE: Record<string, string> = {
  gravado:    'bg-blue-50 text-blue-700',
  exonerado:  'bg-green-50 text-green-700',
  inafecto:   'bg-gray-50 text-gray-600',
}

function SkeletonRow() {
  return (
    <tr className="border-t border-gray-100 animate-pulse">
      {Array.from({ length: 7 }).map((_, i) => (
        <td key={i} className="px-4 py-3">
          <div className="h-4 bg-gray-100 rounded w-full" />
        </td>
      ))}
    </tr>
  )
}

export function ProductosTable({ productos, loading, onDesactivar, onActivar }: Props) {
  if (loading) {
    return (
      <table className="w-full text-sm">
        <thead className="bg-gray-50">
          <tr>
            {['Producto', 'SKU', 'Categoría', 'Tipo', 'Precio', 'IGV', 'Acciones'].map((h) => (
              <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {Array.from({ length: 5 }).map((_, i) => <SkeletonRow key={i} />)}
        </tbody>
      </table>
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
    <table className="w-full text-sm">
      <thead className="bg-gray-50 border-b border-gray-100">
        <tr>
          {['Producto', 'SKU', 'Categoría', 'Tipo', 'Precio', 'IGV', 'Acciones'].map((h) => (
            <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {productos.map((p) => (
          <tr key={p.id} className="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td className="px-4 py-3">
              <div className="flex items-center gap-3">
                {p.imagenes?.[0] ? (
                  <img src={p.imagenes[0].url} alt={p.nombre} className="w-8 h-8 rounded object-cover" />
                ) : (
                  <div className="w-8 h-8 rounded bg-gray-100 flex items-center justify-center">
                    <Package className="w-4 h-4 text-gray-400" />
                  </div>
                )}
                <div>
                  <Link href={`/productos/${p.id}`} className="font-medium text-gray-900 hover:text-blue-600 transition-colors">
                    {p.nombre}
                  </Link>
                  {!p.activo && (
                    <span className="ml-2 text-xs bg-red-50 text-red-500 px-1.5 py-0.5 rounded-full">Inactivo</span>
                  )}
                </div>
              </div>
            </td>
            <td className="px-4 py-3 text-gray-500 font-mono text-xs">{p.sku}</td>
            <td className="px-4 py-3 text-gray-600">{p.categoria?.nombre ?? '-'}</td>
            <td className="px-4 py-3">
              <span className="capitalize text-gray-600">{p.tipo}</span>
            </td>
            <td className="px-4 py-3 text-gray-900 font-medium">
              S/ {p.precio_venta.toFixed(2)}
            </td>
            <td className="px-4 py-3">
              <span className={`text-xs px-2 py-0.5 rounded-full font-medium capitalize ${IGV_BADGE[p.igv_tipo] ?? ''}`}>
                {p.igv_tipo}
              </span>
            </td>
            <td className="px-4 py-3">
              <div className="flex items-center gap-1">
                <Link
                  href={`/productos/${p.id}`}
                  className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Editar"
                >
                  <PencilLine className="w-4 h-4" />
                </Link>
                {p.activo ? (
                  <button
                    onClick={() => onDesactivar(p.id)}
                    className="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                    title="Desactivar"
                  >
                    <Power className="w-4 h-4" />
                  </button>
                ) : (
                  <button
                    onClick={() => onActivar(p.id)}
                    className="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                    title="Activar"
                  >
                    <Power className="w-4 h-4" />
                  </button>
                )}
              </div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}
