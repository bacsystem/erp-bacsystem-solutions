'use client'

import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import { CategoriasManager } from '@/modules/core/producto/categorias/CategoriasManager'

export default function CategoriasPage() {
  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto">
          <div className="max-w-4xl mx-auto px-6 py-6 space-y-4">

            <div>
              <h1 className="text-xl font-bold text-gray-900">Categorías</h1>
              <p className="text-sm text-gray-500 mt-0.5">
                Organiza tus productos en categorías y subcategorías
              </p>
            </div>

            <div className="bg-white border border-gray-100 rounded-xl p-6">
              <CategoriasManager />
            </div>

          </div>
        </div>
      </main>
    </div>
  )
}
