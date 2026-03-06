'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useParams, useRouter } from 'next/navigation'
import { ArrowLeft, Clock, History, Tag } from 'lucide-react'
import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import { useProductoDetalle, useActualizarProducto } from '@/modules/core/producto/listar-productos/use-productos'
import { ProductoForm } from '@/modules/core/producto/form/ProductoForm'
import { ImagenesUpload } from '@/modules/core/producto/form/ImagenesUpload'

const TABS = ['Ver', 'Editar', 'Imágenes', 'Historial precios'] as const

export default function ProductoDetallePage() {
  const { id } = useParams<{ id: string }>()
  const router  = useRouter()
  const [tab, setTab] = useState<(typeof TABS)[number]>('Ver')

  const { data: producto, refetch, isLoading } = useProductoDetalle(id)
  const { mutateAsync: actualizar, isPending } = useActualizarProducto(id)

  if (isLoading) {
    return (
      <div className="flex h-screen overflow-hidden">
        <Sidebar />
        <main className="flex-1 flex items-center justify-center">
          <div className="animate-spin rounded-full w-8 h-8 border-2 border-blue-600 border-t-transparent" />
        </main>
      </div>
    )
  }

  if (!producto) return null

  const handleUpdate = async (data: Parameters<typeof actualizar>[0]) => {
    await actualizar(data)
    router.refresh()
  }

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto">
          <div className="max-w-3xl mx-auto px-6 py-6 space-y-4">
            {/* Header */}
            <div className="flex items-start gap-3">
              <Link href="/productos" className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors mt-0.5">
                <ArrowLeft className="w-5 h-5" />
              </Link>
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <h1 className="text-xl font-bold text-gray-900">{producto.nombre}</h1>
                  {!producto.activo && (
                    <span className="text-xs bg-red-50 text-red-500 px-2 py-0.5 rounded-full">Inactivo</span>
                  )}
                </div>
                <p className="text-sm text-gray-500 font-mono mt-0.5">{producto.sku}</p>
              </div>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-gray-100">
              {TABS.map((t) => (
                <button
                  key={t}
                  onClick={() => setTab(t)}
                  className={`px-4 py-2.5 text-sm font-medium transition-colors ${
                    tab === t ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'
                  }`}
                >
                  {t}
                </button>
              ))}
            </div>

            <div className="bg-white border border-gray-100 rounded-xl p-6">
              {tab === 'Ver' && (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <InfoRow label="Categoría" value={producto.categoria?.nombre} />
                    <InfoRow label="Tipo" value={<span className="capitalize">{producto.tipo}</span>} />
                    <InfoRow label="Unidad" value={producto.unidad_medida_principal} />
                    <InfoRow label="IGV" value={<span className="capitalize">{producto.igv_tipo}</span>} />
                    <InfoRow label="Precio compra" value={producto.precio_compra ? `S/ ${producto.precio_compra.toFixed(2)}` : '-'} />
                    <InfoRow label="Precio venta" value={`S/ ${producto.precio_venta.toFixed(2)}`} />
                    {producto.descripcion && (
                      <div className="col-span-2">
                        <InfoRow label="Descripción" value={producto.descripcion} />
                      </div>
                    )}
                  </div>
                </div>
              )}

              {tab === 'Editar' && (
                <ProductoForm
                  defaultValues={{
                    nombre:                  producto.nombre,
                    descripcion:             producto.descripcion ?? undefined,
                    categoria_id:            producto.categoria_id,
                    tipo:                    producto.tipo,
                    unidad_medida_principal: producto.unidad_medida_principal,
                    precio_compra:           producto.precio_compra ?? undefined,
                    precio_venta:            producto.precio_venta,
                    igv_tipo:                producto.igv_tipo,
                    codigo_barras:           producto.codigo_barras ?? undefined,
                  }}
                  onSubmit={handleUpdate}
                  loading={isPending}
                  mode="editar"
                  productoId={id}
                />
              )}

              {tab === 'Imágenes' && (
                <ImagenesUpload
                  productoId={id}
                  imagenes={producto.imagenes ?? []}
                  onUpdate={() => refetch()}
                />
              )}

              {tab === 'Historial precios' && (
                <div className="space-y-2">
                  {producto.historial_precios?.length === 0 && (
                    <p className="text-sm text-gray-400 text-center py-8">Sin historial de cambios</p>
                  )}
                  {producto.historial_precios?.map((h) => (
                    <div key={h.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                      <div className="flex items-center gap-2 text-gray-600">
                        <History className="w-4 h-4" />
                        <span>S/ {h.precio_anterior.toFixed(2)}</span>
                        <span className="text-gray-300">→</span>
                        <span className="font-medium text-gray-900">S/ {h.precio_nuevo.toFixed(2)}</span>
                      </div>
                      <div className="flex items-center gap-1 text-gray-400">
                        <Clock className="w-3.5 h-3.5" />
                        <span className="text-xs">{new Date(h.created_at).toLocaleDateString('es-PE')}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div>
      <dt className="text-xs font-medium text-gray-500 mb-0.5">{label}</dt>
      <dd className="text-sm text-gray-900">{value ?? '-'}</dd>
    </div>
  )
}
