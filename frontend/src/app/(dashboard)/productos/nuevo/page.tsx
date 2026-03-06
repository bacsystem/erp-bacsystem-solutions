'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ArrowLeft, Package } from 'lucide-react'
import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import { ProductoForm } from '@/modules/core/producto/form/ProductoForm'
import { useCrearProducto } from '@/modules/core/producto/listar-productos/use-productos'

export default function NuevoProductoPage() {
  const router = useRouter()
  const { mutateAsync: crear, isPending } = useCrearProducto()

  const handleSubmit = async (data: Parameters<typeof crear>[0]) => {
    await crear(data)
    router.push('/productos')
  }

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-2xl mx-auto p-8 space-y-6">

            {/* Header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <Package className="w-4 h-4" />
                <Link href="/productos" className="hover:text-gray-700 transition-colors">Productos</Link>
                <span>/</span>
                <span className="text-gray-900 font-medium">Nuevo producto</span>
              </div>
              <div className="flex items-center gap-2 mt-2">
                <Link
                  href="/productos"
                  className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white border border-transparent hover:border-gray-200 rounded-lg transition-all"
                >
                  <ArrowLeft className="w-4 h-4" />
                </Link>
                <h1 className="text-2xl font-bold text-gray-900">Nuevo producto</h1>
              </div>
            </div>

            {/* Form card — mismo estilo que register */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
              <ProductoForm onSubmit={handleSubmit} loading={isPending} mode="crear" />
            </div>

          </div>
        </div>
      </main>
    </div>
  )
}
