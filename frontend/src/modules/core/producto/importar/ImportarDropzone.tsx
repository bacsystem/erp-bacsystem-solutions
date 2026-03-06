'use client'

import { useCallback, useRef, useState } from 'react'
import { FileSpreadsheet, Loader2, Upload } from 'lucide-react'
import { productosApi } from '../shared/productos.api'

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

interface Props {
  onPreview: (data: PreviewData) => void
}

export function ImportarDropzone({ onPreview }: Props) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [dragging, setDragging] = useState(false)

  const handleFile = useCallback(
    async (file: File) => {
      const ext = file.name.split('.').pop()?.toLowerCase()
      if (!['csv', 'xlsx', 'xls'].includes(ext ?? '')) {
        setError('Solo se aceptan archivos CSV o Excel (.xlsx, .xls).')
        return
      }
      setError(null)
      setLoading(true)
      try {
        const data = await productosApi.importarPreview(file)
        onPreview(data)
      } catch {
        setError('Error al procesar el archivo.')
      } finally {
        setLoading(false)
      }
    },
    [onPreview],
  )

  const onDrop = (e: React.DragEvent) => {
    e.preventDefault()
    setDragging(false)
    const file = e.dataTransfer.files[0]
    if (file) handleFile(file)
  }

  const downloadTemplate = async () => {
    const res = await productosApi.importarTemplate()
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const a   = document.createElement('a')
    a.href    = url
    a.download = 'template-productos.csv'
    a.click()
    window.URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-4">
      <div
        onDrop={onDrop}
        onDragOver={(e) => { e.preventDefault(); setDragging(true) }}
        onDragLeave={() => setDragging(false)}
        onClick={() => inputRef.current?.click()}
        className={`border-2 border-dashed rounded-xl p-12 flex flex-col items-center justify-center cursor-pointer transition-colors ${
          dragging ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
        }`}
      >
        {loading ? (
          <Loader2 className="w-10 h-10 text-blue-500 animate-spin mb-3" />
        ) : (
          <FileSpreadsheet className="w-10 h-10 text-gray-300 mb-3" />
        )}
        <p className="text-sm font-medium text-gray-700">
          {loading ? 'Procesando archivo...' : 'Arrastra un archivo o haz clic para seleccionar'}
        </p>
        <p className="text-xs text-gray-400 mt-1">CSV, XLSX o XLS · Máx 10 MB</p>
      </div>

      <input
        ref={inputRef}
        type="file"
        accept=".csv,.xlsx,.xls"
        className="hidden"
        onChange={(e) => e.target.files?.[0] && handleFile(e.target.files[0])}
      />

      {error && <p className="text-sm text-red-500">{error}</p>}

      <button
        onClick={downloadTemplate}
        className="flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700"
      >
        <Upload className="w-4 h-4" />
        Descargar plantilla
      </button>
    </div>
  )
}
