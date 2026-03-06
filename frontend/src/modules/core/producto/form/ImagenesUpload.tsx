'use client'

import { useCallback, useRef, useState } from 'react'
import { ImageIcon, Loader2, Trash2, Upload } from 'lucide-react'
import type { ProductoImagen } from '../shared/producto.types'
import { productosApi } from '../shared/productos.api'

interface Props {
  productoId: string
  imagenes: ProductoImagen[]
  onUpdate: () => void
}

const MAX_IMAGENES = 5
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp']

export function ImagenesUpload({ productoId, imagenes, onUpdate }: Props) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleFiles = useCallback(
    async (files: FileList | null) => {
      if (!files?.length) return
      const file = files[0]

      if (!ACCEPTED_TYPES.includes(file.type)) {
        setError('Solo se aceptan imágenes JPG, PNG o WebP.')
        return
      }
      if (file.size > 5 * 1024 * 1024) {
        setError('La imagen no puede superar los 5 MB.')
        return
      }
      if (imagenes.length >= MAX_IMAGENES) {
        setError(`Límite máximo de ${MAX_IMAGENES} imágenes.`)
        return
      }

      setError(null)
      setUploading(true)
      try {
        await productosApi.subirImagen(productoId, file)
        onUpdate()
      } catch {
        setError('Error al subir la imagen.')
      } finally {
        setUploading(false)
      }
    },
    [imagenes.length, productoId, onUpdate],
  )

  const handleDelete = async (imagenId: string) => {
    try {
      await productosApi.eliminarImagen(productoId, imagenId)
      onUpdate()
    } catch {
      setError('Error al eliminar la imagen.')
    }
  }

  return (
    <div className="space-y-3">
      {/* Preview grid */}
      <div className="grid grid-cols-5 gap-2">
        {imagenes.map((img) => (
          <div key={img.id} className="relative group aspect-square rounded-lg overflow-hidden border border-gray-200">
            <img src={img.url} alt="" className="w-full h-full object-cover" />
            <button
              onClick={() => handleDelete(img.id)}
              className="absolute inset-0 bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
            >
              <Trash2 className="w-5 h-5" />
            </button>
          </div>
        ))}

        {imagenes.length < MAX_IMAGENES && (
          <button
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
            className="aspect-square rounded-lg border-2 border-dashed border-gray-200 flex flex-col items-center justify-center text-gray-400 hover:border-blue-300 hover:text-blue-500 transition-colors disabled:opacity-50"
          >
            {uploading ? (
              <Loader2 className="w-5 h-5 animate-spin" />
            ) : (
              <>
                <Upload className="w-5 h-5 mb-1" />
                <span className="text-xs">Subir</span>
              </>
            )}
          </button>
        )}

        {imagenes.length === 0 && !uploading && (
          <div className="col-span-4 flex flex-col items-center justify-center py-8 text-gray-400">
            <ImageIcon className="w-8 h-8 mb-2 opacity-30" />
            <p className="text-xs">Sin imágenes</p>
          </div>
        )}
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp"
        className="hidden"
        onChange={(e) => handleFiles(e.target.files)}
      />

      {error && <p className="text-xs text-red-500">{error}</p>}
      <p className="text-xs text-gray-400">{imagenes.length}/{MAX_IMAGENES} imágenes. Formatos: JPG, PNG, WebP. Máx 5 MB.</p>
    </div>
  )
}
