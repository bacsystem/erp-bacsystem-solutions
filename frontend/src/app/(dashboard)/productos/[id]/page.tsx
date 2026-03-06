'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import {
  ArrowLeft, ArrowRight, Barcode, Check, Clock,
  DollarSign, FileText, FolderOpen, Hash, Image as ImageIcon,
  Layers, Loader2, Package, PencilLine, Plus, Power,
  Ruler, Save, ShoppingCart, Tag, TrendingDown, TrendingUp, Wrench, X,
} from 'lucide-react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Sidebar } from '@/shared/components/Sidebar'
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner'
import {
  useProductoDetalle,
  useActualizarProducto,
  useActivarProducto,
  useDesactivarProducto,
} from '@/modules/core/producto/listar-productos/use-productos'
import { useCategorias, useCrearCategoria } from '@/modules/core/producto/categorias/use-categorias'
import { ImagenesUpload } from '@/modules/core/producto/form/ImagenesUpload'
import { ComponentesForm } from '@/modules/core/producto/form/ComponentesForm'
import type { Producto, PrecioHistorial, ProductoComponente } from '@/modules/core/producto/shared/producto.types'

// ─── Schema ───────────────────────────────────────────────────────────────────

const schema = z.object({
  nombre:                  z.string().min(1, 'Requerido').max(255),
  descripcion:             z.string().max(1000).optional(),
  categoria_id:            z.string().uuid('Selecciona una categoría'),
  tipo:                    z.enum(['simple', 'compuesto', 'servicio']),
  unidad_medida_principal: z.string().min(1, 'Requerido').max(20),
  precio_compra:           z.coerce.number().min(0).optional(),
  precio_venta:            z.coerce.number().min(0.01, 'Debe ser mayor a 0'),
  igv_tipo:                z.enum(['gravado', 'exonerado', 'inafecto']),
  codigo_barras:           z.string().max(50).optional(),
})
type FormData = z.infer<typeof schema>

// ─── Style constants ──────────────────────────────────────────────────────────

const INPUT = 'w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder:text-gray-400 bg-white'
const LABEL = 'block text-sm font-medium text-gray-700 mb-1.5'
const ERROR = 'text-red-500 text-xs mt-1'

// ─── Metadata ────────────────────────────────────────────────────────────────

const TIPO_META = {
  simple:    { label: 'Simple',    icon: Package, color: 'text-blue-600 bg-blue-50 border-blue-200'        },
  compuesto: { label: 'Compuesto', icon: Layers,  color: 'text-violet-600 bg-violet-50 border-violet-200'  },
  servicio:  { label: 'Servicio',  icon: Wrench,  color: 'text-amber-600 bg-amber-50 border-amber-200'     },
} as const

const IGV_META = {
  gravado:   { label: 'Gravado',   color: 'text-blue-700 bg-blue-50 border-blue-200'    },
  exonerado: { label: 'Exonerado', color: 'text-green-700 bg-green-50 border-green-200' },
  inafecto:  { label: 'Inafecto',  color: 'text-gray-600 bg-gray-50 border-gray-200'    },
} as const

const TIPOS_OPT = [
  { value: 'simple',    label: 'Simple',    desc: 'Artículo individual',       icon: Package },
  { value: 'compuesto', label: 'Compuesto', desc: 'Kit / bundle',              icon: Layers  },
  { value: 'servicio',  label: 'Servicio',  desc: 'Sin stock físico',          icon: Wrench  },
] as const

const IGV_OPT = [
  { value: 'gravado',   label: 'Gravado',   desc: '18% IGV'      },
  { value: 'exonerado', label: 'Exonerado', desc: 'Sin IGV'      },
  { value: 'inafecto',  label: 'Inafecto',  desc: 'Fuera ámbito' },
] as const

type Tab = 'ver' | 'editar' | 'imagenes' | 'historial'

const TABS: { id: Tab; label: string; icon: React.ElementType }[] = [
  { id: 'ver',       label: 'Resumen',          icon: Package    },
  { id: 'editar',    label: 'Editar',           icon: PencilLine },
  { id: 'imagenes',  label: 'Imágenes',         icon: ImageIcon  },
  { id: 'historial', label: 'Historial precios',icon: Clock      },
]

// ─── Tab: Resumen ─────────────────────────────────────────────────────────────

function TabVer({ producto }: { producto: Producto }) {
  const tipoMeta = TIPO_META[producto.tipo]
  const igvMeta  = IGV_META[producto.igv_tipo]
  const TipoIcon = tipoMeta.icon

  return (
    <div className="space-y-4">
      {/* Hero */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 flex gap-5">
        <div className="w-28 h-28 rounded-xl overflow-hidden border border-gray-100 shrink-0 bg-gray-50 flex items-center justify-center">
          {producto.imagenes?.[0]
            ? <img src={producto.imagenes[0].url} alt={producto.nombre} className="w-full h-full object-cover" />
            : <Package className="w-10 h-10 text-gray-200" />}
        </div>
        <div className="flex-1 min-w-0">
          <h2 className="text-xl font-bold text-gray-900">{producto.nombre}</h2>
          <div className="flex flex-wrap items-center gap-2 mt-1.5">
            <span className="text-xs font-mono text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded">
              <Hash className="inline w-3 h-3 mr-0.5 -mt-0.5" />{producto.sku}
            </span>
            {producto.codigo_barras && (
              <span className="text-xs font-mono text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded">
                <Barcode className="inline w-3 h-3 mr-0.5 -mt-0.5" />{producto.codigo_barras}
              </span>
            )}
          </div>
          {producto.descripcion && (
            <p className="mt-2.5 text-sm text-gray-500 leading-relaxed line-clamp-3">{producto.descripcion}</p>
          )}
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Precio venta</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">S/ {producto.precio_venta.toFixed(2)}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Precio compra</p>
          {producto.precio_compra != null ? (
            <>
              <p className="text-2xl font-bold text-gray-900 mt-1">S/ {producto.precio_compra.toFixed(2)}</p>
              {producto.precio_compra > 0 && (
                <p className="text-xs text-gray-400 mt-0.5">
                  Margen {(((producto.precio_venta - producto.precio_compra) / producto.precio_compra) * 100).toFixed(1)}%
                </p>
              )}
            </>
          ) : (
            <p className="text-2xl font-bold text-gray-200 mt-1">—</p>
          )}
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Tipo</p>
          <div className={`inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-full border text-xs font-medium ${tipoMeta.color}`}>
            <TipoIcon className="w-3.5 h-3.5" />{tipoMeta.label}
          </div>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-4">
          <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">IGV</p>
          <div className={`inline-flex items-center mt-2 px-2.5 py-1 rounded-full border text-xs font-medium ${igvMeta.color}`}>
            {igvMeta.label}
          </div>
          <p className="text-xs text-gray-400 mt-1">{producto.unidad_medida_principal}</p>
        </div>
      </div>

      {/* Details grid */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Detalles</h3>
        <dl className="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
          <div>
            <dt className="text-xs text-gray-400 mb-0.5">Categoría</dt>
            <dd className="font-medium text-gray-900">{producto.categoria?.nombre ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-xs text-gray-400 mb-0.5">Unidad de medida</dt>
            <dd className="font-medium text-gray-900">{producto.unidad_medida_principal}</dd>
          </div>
          <div>
            <dt className="text-xs text-gray-400 mb-0.5">SKU</dt>
            <dd className="font-mono text-gray-900">{producto.sku}</dd>
          </div>
          <div>
            <dt className="text-xs text-gray-400 mb-0.5">Código de barras</dt>
            <dd className="font-mono text-gray-900">{producto.codigo_barras ?? '—'}</dd>
          </div>
        </dl>
      </div>
    </div>
  )
}

// ─── Tab: Editar ──────────────────────────────────────────────────────────────

function TabEditar({ producto }: { producto: Producto }) {
  const [componentes, setComponentes] = useState<{ componente_id: string; cantidad: number; producto?: Producto }[]>(
    () => (producto.componentes ?? []).map((c: ProductoComponente) => ({
      componente_id: c.componente_id,
      cantidad:      c.cantidad,
      producto:      c.producto,
    })),
  )
  const [showNuevaCat,   setShowNuevaCat]   = useState(false)
  const [nuevaCatNombre, setNuevaCatNombre] = useState('')
  const [nuevaCatError,  setNuevaCatError]  = useState<string | null>(null)
  const [saved,          setSaved]          = useState(false)

  const { data: categorias = [] }                              = useCategorias()
  const { mutateAsync: crearCategoria, isPending: creandoCat } = useCrearCategoria()
  const { mutateAsync: actualizar,     isPending: saving }     = useActualizarProducto(producto.id)

  const {
    register, handleSubmit, watch, setValue,
    formState: { errors, isDirty },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      nombre:                  producto.nombre,
      descripcion:             producto.descripcion ?? '',
      categoria_id:            producto.categoria_id,
      tipo:                    producto.tipo,
      unidad_medida_principal: producto.unidad_medida_principal,
      precio_compra:           producto.precio_compra ?? undefined,
      precio_venta:            producto.precio_venta,
      igv_tipo:                producto.igv_tipo,
      codigo_barras:           producto.codigo_barras ?? '',
    },
  })

  const tipo    = watch('tipo')
  const igvTipo = watch('igv_tipo')

  const onSubmit = handleSubmit(async (data) => {
    await actualizar({ ...data, componentes })
    setSaved(true)
    setTimeout(() => setSaved(false), 2500)
  })

  const handleCrearCategoria = async () => {
    if (!nuevaCatNombre.trim()) { setNuevaCatError('El nombre es requerido.'); return }
    setNuevaCatError(null)
    try {
      const cat = await crearCategoria({ nombre: nuevaCatNombre.trim(), descripcion: null, categoria_padre_id: null })
      setValue('categoria_id', cat.id)
      setNuevaCatNombre('')
      setShowNuevaCat(false)
    } catch {
      setNuevaCatError('Ya existe una categoría con ese nombre.')
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4">

      {/* Sticky save bar */}
      <div className="sticky top-0 z-10 flex items-center justify-between bg-white rounded-xl border border-gray-200 px-5 py-3 shadow-sm">
        <span className="text-sm">
          {isDirty
            ? <span className="text-amber-600 font-medium">Tienes cambios sin guardar</span>
            : <span className="text-gray-400">Sin cambios pendientes</span>}
        </span>
        <button
          type="submit"
          disabled={saving || !isDirty}
          className="flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {saving  ? <><Loader2 className="w-4 h-4 animate-spin" />Guardando...</>
           : saved ? <><Check   className="w-4 h-4" />Guardado</>
           :         <><Save    className="w-4 h-4" />Guardar cambios</>}
        </button>
      </div>

      {/* General */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Información general</h3>

        <div>
          <label className={LABEL}>Nombre <span className="text-red-500">*</span></label>
          <div className="relative">
            <Tag className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
            <input {...register('nombre')} className={`${INPUT} pl-8`} placeholder="Nombre del producto" />
          </div>
          {errors.nombre && <p className={ERROR}>{errors.nombre.message}</p>}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className={LABEL}>SKU</label>
            <div className="relative">
              <Hash className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-300" />
              <input value={producto.sku} disabled className={`${INPUT} pl-8 font-mono bg-gray-50 text-gray-400 cursor-not-allowed`} />
            </div>
            <p className="text-xs text-gray-400 mt-1">El SKU no se puede modificar</p>
          </div>
          <div>
            <label className={LABEL}>Código de barras</label>
            <div className="relative">
              <Barcode className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <input {...register('codigo_barras')} className={`${INPUT} pl-8 font-mono`} placeholder="7501000000000" />
            </div>
          </div>
        </div>

        <div>
          <label className={LABEL}>Descripción</label>
          <div className="relative">
            <FileText className="absolute left-3 top-3 w-3.5 h-3.5 text-gray-400" />
            <textarea {...register('descripcion')} rows={3} className={`${INPUT} pl-8 resize-none`} placeholder="Descripción opcional..." />
          </div>
        </div>
      </div>

      {/* Clasificación */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Clasificación</h3>

        <div>
          <div className="flex items-center justify-between mb-1.5">
            <label className="text-sm font-medium text-gray-700">Categoría <span className="text-red-500">*</span></label>
            <button
              type="button"
              onClick={() => { setShowNuevaCat((v) => !v); setNuevaCatError(null) }}
              className="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium"
            >
              {showNuevaCat ? <X className="w-3.5 h-3.5" /> : <Plus className="w-3.5 h-3.5" />}
              {showNuevaCat ? 'Cancelar' : 'Nueva categoría'}
            </button>
          </div>
          {showNuevaCat ? (
            <div className="flex gap-2">
              <div className="relative flex-1">
                <FolderOpen className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                  autoFocus value={nuevaCatNombre}
                  onChange={(e) => setNuevaCatNombre(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), handleCrearCategoria())}
                  placeholder="Nombre de la categoría"
                  className={`${INPUT} pl-8`}
                />
              </div>
              <button
                type="button" onClick={handleCrearCategoria} disabled={creandoCat}
                className="flex items-center gap-1.5 px-4 py-2.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60 font-medium whitespace-nowrap"
              >
                {creandoCat ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-3.5 h-3.5" />} Crear
              </button>
            </div>
          ) : (
            <div className="relative">
              <FolderOpen className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <select {...register('categoria_id')} className={`${INPUT} pl-8 appearance-none`}>
                <option value="">Seleccionar categoría...</option>
                {categorias.map((c) => <option key={c.id} value={c.id}>{c.nombre}</option>)}
              </select>
            </div>
          )}
          {nuevaCatError && <p className={ERROR}>{nuevaCatError}</p>}
          {errors.categoria_id && !showNuevaCat && <p className={ERROR}>{errors.categoria_id.message}</p>}
        </div>

        <div>
          <label className={LABEL}>Tipo <span className="text-red-500">*</span></label>
          <div className="grid grid-cols-3 gap-2">
            {TIPOS_OPT.map(({ value, label, desc, icon: Icon }) => {
              const sel = tipo === value
              return (
                <button key={value} type="button"
                  onClick={() => setValue('tipo', value, { shouldDirty: true })}
                  className={`flex flex-col items-center gap-1.5 border-2 rounded-xl p-3 text-center transition-all ${
                    sel ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                  }`}
                >
                  <Icon className={`w-5 h-5 ${sel ? 'text-blue-600' : 'text-gray-400'}`} />
                  <span className={`text-xs font-semibold ${sel ? 'text-blue-700' : 'text-gray-700'}`}>{label}</span>
                  <span className="text-[10px] text-gray-400 leading-tight">{desc}</span>
                </button>
              )
            })}
          </div>
        </div>

        {tipo === 'compuesto' && (
          <div>
            <label className={LABEL}>Componentes del kit</label>
            <ComponentesForm value={componentes} onChange={setComponentes} currentProductoId={producto.id} />
          </div>
        )}
      </div>

      {/* Precios */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Precios</h3>

        <div className="grid grid-cols-3 gap-4">
          <div>
            <label className={LABEL}>Unidad <span className="text-red-500">*</span></label>
            <div className="relative">
              <Ruler className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <input {...register('unidad_medida_principal')} className={`${INPUT} pl-8`} placeholder="NIU" />
            </div>
            {errors.unidad_medida_principal && <p className={ERROR}>{errors.unidad_medida_principal.message}</p>}
          </div>
          <div>
            <label className={LABEL}>Precio de compra</label>
            <div className="relative">
              <ShoppingCart className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <span className="absolute left-8 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">S/</span>
              <input {...register('precio_compra')} type="number" step="0.01" min="0" placeholder="0.00" className={`${INPUT} pl-14`} />
            </div>
          </div>
          <div>
            <label className={LABEL}>Precio de venta <span className="text-red-500">*</span></label>
            <div className="relative">
              <DollarSign className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <span className="absolute left-8 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">S/</span>
              <input {...register('precio_venta')} type="number" step="0.01" min="0.01" placeholder="0.00" className={`${INPUT} pl-14`} />
            </div>
            {errors.precio_venta && <p className={ERROR}>{errors.precio_venta.message}</p>}
          </div>
        </div>

        <div>
          <label className={LABEL}>Tipo de IGV <span className="text-red-500">*</span></label>
          <div className="grid grid-cols-3 gap-2">
            {IGV_OPT.map(({ value, label, desc }) => {
              const sel = igvTipo === value
              return (
                <button key={value} type="button"
                  onClick={() => setValue('igv_tipo', value, { shouldDirty: true })}
                  className={`flex flex-col items-center gap-1 border-2 rounded-xl p-3 text-center transition-all ${
                    sel ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                  }`}
                >
                  <span className={`text-sm font-semibold ${sel ? 'text-blue-700' : 'text-gray-700'}`}>{label}</span>
                  <span className="text-[10px] text-gray-400">{desc}</span>
                </button>
              )
            })}
          </div>
        </div>
      </div>
    </form>
  )
}

// ─── Tab: Imágenes ────────────────────────────────────────────────────────────

function TabImagenes({ producto, onRefetch }: { producto: Producto; onRefetch: () => void }) {
  const imagenes = producto.imagenes ?? []

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold text-gray-900">Imágenes del producto</h3>
          <p className="text-xs text-gray-400 mt-0.5">{imagenes.length} de 5 · JPG, PNG o WebP · máx. 5 MB c/u</p>
        </div>
      </div>

      {imagenes.length > 0 ? (
        <div className="w-full aspect-video rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex items-center justify-center">
          <img src={imagenes[0].url} alt={producto.nombre} className="max-h-full max-w-full object-contain" />
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center py-14 text-gray-200">
          <ImageIcon className="w-14 h-14 mb-3" />
          <p className="text-sm text-gray-400">Sin imágenes aún</p>
          <p className="text-xs text-gray-400 mt-0.5">Sube la primera imagen del producto</p>
        </div>
      )}

      <ImagenesUpload productoId={producto.id} imagenes={imagenes} onUpdate={onRefetch} />
    </div>
  )
}

// ─── Tab: Historial de precios ────────────────────────────────────────────────

function TabHistorial({ producto }: { producto: Producto }) {
  const historial = (producto.historial_precios ?? []) as PrecioHistorial[]

  if (!historial.length) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex flex-col items-center justify-center py-16 text-gray-200">
          <Clock className="w-12 h-12 mb-3" />
          <p className="text-sm text-gray-400">Sin historial de precios</p>
          <p className="text-xs text-gray-400 mt-0.5">Los cambios de precio quedarán registrados aquí</p>
        </div>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold text-gray-900">Historial de precios</h3>
          <p className="text-xs text-gray-400 mt-0.5">{historial.length} cambios registrados</p>
        </div>
        <span className="text-xs text-gray-400">
          Precio actual: <span className="font-semibold text-gray-700">S/ {producto.precio_venta.toFixed(2)}</span>
        </span>
      </div>

      <div className="divide-y divide-gray-50">
        {historial.map((h) => {
          const subio = h.precio_nuevo > h.precio_anterior
          const diff  = Math.abs(h.precio_nuevo - h.precio_anterior)
          const pct   = h.precio_anterior > 0 ? ((diff / h.precio_anterior) * 100).toFixed(1) : null
          const fecha = new Date(h.created_at)

          return (
            <div key={h.id} className="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/60 transition-colors">
              <div className={`w-9 h-9 rounded-full flex items-center justify-center border-2 shrink-0 ${
                subio ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
              }`}>
                {subio
                  ? <TrendingUp   className="w-4 h-4 text-green-600" />
                  : <TrendingDown className="w-4 h-4 text-red-500"   />}
              </div>

              <div className="flex items-center gap-3 flex-1 min-w-0">
                <span className="text-sm text-gray-400 line-through">S/ {h.precio_anterior.toFixed(2)}</span>
                <ArrowRight className="w-4 h-4 text-gray-300 shrink-0" />
                <span className={`text-sm font-semibold ${subio ? 'text-green-700' : 'text-red-600'}`}>
                  S/ {h.precio_nuevo.toFixed(2)}
                </span>
                {pct && (
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    subio ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'
                  }`}>
                    {subio ? '+' : '-'}{pct}%
                  </span>
                )}
              </div>

              <div className="text-right shrink-0">
                <p className="text-xs font-medium text-gray-600">
                  {fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })}
                </p>
                <p className="text-xs text-gray-400">
                  {fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })}
                </p>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function ProductoDetallePage({ params }: { params: Promise<{ id: string }> }) {
  const { id }        = use(params)
  const [tab, setTab] = useState<Tab>('ver')

  const { data: producto, isLoading, refetch } = useProductoDetalle(id)
  const { mutate: activar,    isPending: activando }    = useActivarProducto()
  const { mutate: desactivar, isPending: desactivando } = useDesactivarProducto()

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-4xl mx-auto px-6 py-6 space-y-5">

            {/* Header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-400 mb-2">
                <Package className="w-4 h-4" />
                <Link href="/productos" className="hover:text-gray-600 transition-colors">Productos</Link>
                <span>/</span>
                <span className="text-gray-700 font-medium truncate max-w-xs">
                  {isLoading ? '...' : (producto?.nombre ?? 'Producto')}
                </span>
              </div>

              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3 min-w-0">
                  <Link
                    href="/productos"
                    className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white border border-transparent hover:border-gray-200 rounded-lg transition-all shrink-0"
                  >
                    <ArrowLeft className="w-4 h-4" />
                  </Link>
                  <div className="min-w-0">
                    <h1 className="text-2xl font-bold text-gray-900 truncate">
                      {isLoading ? 'Cargando...' : (producto?.nombre ?? 'Producto')}
                    </h1>
                    {producto && (
                      <div className="flex items-center gap-2 mt-0.5">
                        <span className="text-xs text-gray-400 font-mono">SKU: {producto.sku}</span>
                        <span className={`text-xs px-2 py-0.5 rounded-full border font-medium ${
                          producto.activo
                            ? 'bg-green-50 text-green-700 border-green-200'
                            : 'bg-red-50 text-red-500 border-red-200'
                        }`}>
                          {producto.activo ? 'Activo' : 'Inactivo'}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {producto && (
                  producto.activo ? (
                    <button
                      disabled={desactivando}
                      onClick={() => desactivar(producto.id)}
                      className="flex items-center gap-1.5 px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition-colors shrink-0"
                    >
                      {desactivando ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Power className="w-3.5 h-3.5" />}
                      Desactivar
                    </button>
                  ) : (
                    <button
                      disabled={activando}
                      onClick={() => activar(producto.id)}
                      className="flex items-center gap-1.5 px-3 py-2 text-sm text-green-700 border border-green-200 rounded-lg hover:bg-green-50 disabled:opacity-50 transition-colors shrink-0"
                    >
                      {activando ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Power className="w-3.5 h-3.5" />}
                      Activar
                    </button>
                  )
                )}
              </div>
            </div>

            {isLoading ? (
              <div className="flex items-center justify-center py-32">
                <Loader2 className="w-8 h-8 text-blue-500 animate-spin" />
              </div>
            ) : !producto ? (
              <div className="flex flex-col items-center justify-center py-32 text-gray-400">
                <Package className="w-12 h-12 mb-3 opacity-30" />
                <p className="text-sm">Producto no encontrado</p>
                <Link href="/productos" className="mt-3 text-sm text-blue-600 hover:underline">Volver a productos</Link>
              </div>
            ) : (
              <>
                {/* Tabs */}
                <div className="flex bg-white rounded-xl border border-gray-200 overflow-hidden">
                  {TABS.map(({ id: tid, label, icon: Icon }) => (
                    <button
                      key={tid}
                      onClick={() => setTab(tid)}
                      className={`flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 flex-1 justify-center transition-colors ${
                        tab === tid
                          ? 'border-blue-600 text-blue-600 bg-blue-50/40'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      {label}
                    </button>
                  ))}
                </div>

                {/* Content */}
                <div>
                  {tab === 'ver'       && <TabVer       producto={producto} />}
                  {tab === 'editar'    && <TabEditar    producto={producto} />}
                  {tab === 'imagenes'  && <TabImagenes  producto={producto} onRefetch={refetch} />}
                  {tab === 'historial' && <TabHistorial producto={producto} />}
                </div>
              </>
            )}
          </div>
        </div>
      </main>
    </div>
  )
}
