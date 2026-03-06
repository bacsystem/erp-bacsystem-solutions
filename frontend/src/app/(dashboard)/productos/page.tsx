'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Download, Grid3X3, List, Plus, Upload } from 'lucide-react'
import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import { useProductos, useDesactivarProducto, useActivarProducto } from '@/modules/core/producto/listar-productos/use-productos'
import { ProductosFiltros } from '@/modules/core/producto/listar-productos/ProductosFiltros'
import { ProductosTable } from '@/modules/core/producto/listar-productos/ProductosTable'
import { ProductosGrid } from '@/modules/core/producto/listar-productos/ProductosGrid'
import { useCategorias } from '@/modules/core/producto/categorias/use-categorias'
import { productosApi } from '@/modules/core/producto/shared/productos.api'
import type { ProductosFilters } from '@/modules/core/producto/shared/producto.types'

export default function ProductosPage() {
  const [view, setView] = useState<'table' | 'grid'>('table')
  const [filters, setFilters] = useState<ProductosFilters>({ page: 1, per_page: 10 })

  const { data, isLoading } = useProductos(filters)
  const { data: categorias = [] } = useCategorias()
  const { mutate: desactivar } = useDesactivarProducto()
  const { mutate: activar }    = useActivarProducto()

  const handleExport = async (formato: string) => {
    const res = await productosApi.exportarExcel({ formato })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const a   = document.createElement('a')
    a.href    = url
    a.download = `productos.${formato}`
    a.click()
    window.URL.revokeObjectURL(url)
  }

  const productos = data?.data ?? []
  const meta      = data?.meta

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto">
          <div className="max-w-7xl mx-auto px-6 py-6 space-y-4">

            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-xl font-bold text-gray-900">Productos</h1>
                {meta && (
                  <p className="text-sm text-gray-500 mt-0.5">{meta.total} productos en total</p>
                )}
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleExport('xlsx')}
                  className="flex items-center gap-1.5 px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50"
                >
                  <Download className="w-4 h-4" />
                  Exportar
                </button>
                <Link
                  href="/productos/importar"
                  className="flex items-center gap-1.5 px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50"
                >
                  <Upload className="w-4 h-4" />
                  Importar
                </Link>
                <Link
                  href="/productos/nuevo"
                  className="flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                  <Plus className="w-4 h-4" />
                  Nuevo producto
                </Link>
              </div>
            </div>

            {/* Filters */}
            <ProductosFiltros
              filters={filters}
              categorias={categorias}
              onChange={setFilters}
            />

            {/* View toggle */}
            <div className="flex items-center justify-between">
              <div />
              <div className="flex rounded-lg border border-gray-200 overflow-hidden">
                <button
                  onClick={() => setView('table')}
                  className={`p-2 ${view === 'table' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:bg-gray-50'}`}
                >
                  <List className="w-4 h-4" />
                </button>
                <button
                  onClick={() => setView('grid')}
                  className={`p-2 ${view === 'grid' ? 'bg-blue-50 text-blue-600' : 'text-gray-400 hover:bg-gray-50'}`}
                >
                  <Grid3X3 className="w-4 h-4" />
                </button>
              </div>
            </div>

            {/* Products */}
            <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
              {view === 'table' ? (
                <ProductosTable
                  productos={productos}
                  loading={isLoading}
                  onDesactivar={desactivar}
                  onActivar={activar}
                />
              ) : (
                <div className="p-4">
                  <ProductosGrid productos={productos} loading={isLoading} />
                </div>
              )}
            </div>

            {/* Pagination */}
            {meta && meta.total > 0 && (() => {
              const perPage     = filters.per_page ?? 10
              const totalPages  = Math.ceil(meta.total / perPage)
              const currentPage = filters.page ?? 1
              const from        = (currentPage - 1) * perPage + 1
              const to          = Math.min(currentPage * perPage, meta.total)

              // page numbers with ellipsis
              const pages: (number | '...')[] = []
              if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) pages.push(i)
              } else {
                pages.push(1)
                if (currentPage > 3) pages.push('...')
                for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) pages.push(i)
                if (currentPage < totalPages - 2) pages.push('...')
                pages.push(totalPages)
              }

              const goTo = (p: number) => setFilters((f) => ({ ...f, page: p }))

              return (
                <div className="flex items-center justify-between text-sm">
                  {/* Left: info + per_page */}
                  <div className="flex items-center gap-3 text-gray-500">
                    <span>Mostrando {from}–{to} de {meta.total} productos</span>
                    <select
                      value={perPage}
                      onChange={(e) => setFilters((f) => ({ ...f, page: 1, per_page: Number(e.target.value) }))}
                      className="px-2 py-1 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    >
                      {[10, 20, 50].map((n) => (
                        <option key={n} value={n}>{n} / pág.</option>
                      ))}
                    </select>
                  </div>

                  {/* Right: page buttons */}
                  <div className="flex items-center gap-1">
                    <button
                      disabled={currentPage <= 1}
                      onClick={() => goTo(currentPage - 1)}
                      className="px-3 py-1.5 border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors"
                    >
                      ‹
                    </button>
                    {pages.map((p, i) =>
                      p === '...' ? (
                        <span key={`ellipsis-${i}`} className="px-2 text-gray-400">…</span>
                      ) : (
                        <button
                          key={p}
                          onClick={() => goTo(p)}
                          className={`min-w-[2rem] px-2 py-1.5 border rounded-lg transition-colors ${
                            p === currentPage
                              ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold'
                              : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                          }`}
                        >
                          {p}
                        </button>
                      )
                    )}
                    <button
                      disabled={currentPage >= totalPages}
                      onClick={() => goTo(currentPage + 1)}
                      className="px-3 py-1.5 border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors"
                    >
                      ›
                    </button>
                  </div>
                </div>
              )
            })()}
          </div>
        </div>
      </main>
    </div>
  )
}
