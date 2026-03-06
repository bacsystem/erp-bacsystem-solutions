'use client'

import { useState } from 'react'
import Link from 'next/link'
import { ArrowLeft, CheckCircle2 } from 'lucide-react'
import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import { ImportarDropzone } from '@/modules/core/producto/importar/ImportarDropzone'
import { ImportarPreview } from '@/modules/core/producto/importar/ImportarPreview'

interface FilaPreview {
  fila: number
  datos: Record<string, string>
  errores: string[]
  valido: boolean
}

interface PreviewData {
  import_token: string
  total: number
  validos: number
  errores: number
  filas: FilaPreview[]
}

type Step = 'upload' | 'preview' | 'success'

export default function ImportarProductosPage() {
  const [step, setStep] = useState<Step>('upload')
  const [preview, setPreview] = useState<PreviewData | null>(null)
  const [creados, setCreados] = useState(0)

  const handlePreview = (data: PreviewData) => {
    setPreview(data)
    setStep('preview')
  }

  const handleConfirmed = (n: number) => {
    setCreados(n)
    setStep('success')
  }

  const handleCancel = () => {
    setPreview(null)
    setStep('upload')
  }

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto">
          <div className="max-w-3xl mx-auto px-6 py-6 space-y-6">

            {/* Header */}
            <div className="flex items-center gap-3">
              <Link
                href="/productos"
                className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <ArrowLeft className="w-5 h-5" />
              </Link>
              <div>
                <h1 className="text-xl font-bold text-gray-900">Importar productos</h1>
                <p className="text-sm text-gray-500 mt-0.5">Sube un archivo CSV o Excel para importar productos en lote</p>
              </div>
            </div>

            {/* Steps indicator */}
            <div className="flex items-center gap-2 text-sm">
              <span className={`font-medium ${step === 'upload' ? 'text-blue-600' : 'text-gray-400'}`}>
                1. Subir archivo
              </span>
              <span className="text-gray-200">›</span>
              <span className={`font-medium ${step === 'preview' ? 'text-blue-600' : 'text-gray-400'}`}>
                2. Revisar
              </span>
              <span className="text-gray-200">›</span>
              <span className={`font-medium ${step === 'success' ? 'text-blue-600' : 'text-gray-400'}`}>
                3. Importado
              </span>
            </div>

            {/* Content */}
            <div className="bg-white border border-gray-100 rounded-xl p-6">
              {step === 'upload' && (
                <ImportarDropzone onPreview={handlePreview} />
              )}

              {step === 'preview' && preview && (
                <ImportarPreview
                  importToken={preview.import_token}
                  total={preview.total}
                  validos={preview.validos}
                  errores={preview.errores}
                  filas={preview.filas}
                  onConfirmed={handleConfirmed}
                  onCancel={handleCancel}
                />
              )}

              {step === 'success' && (
                <div className="flex flex-col items-center py-10 gap-4">
                  <CheckCircle2 className="w-14 h-14 text-green-500" />
                  <h2 className="text-lg font-semibold text-gray-900">Importación exitosa</h2>
                  <p className="text-sm text-gray-500">
                    Se importaron <span className="font-semibold text-gray-900">{creados}</span> productos correctamente.
                  </p>
                  <div className="flex gap-2 mt-2">
                    <button
                      onClick={handleCancel}
                      className="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600"
                    >
                      Importar más
                    </button>
                    <Link
                      href="/productos"
                      className="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                    >
                      Ver productos
                    </Link>
                  </div>
                </div>
              )}
            </div>

          </div>
        </div>
      </main>
    </div>
  )
}
