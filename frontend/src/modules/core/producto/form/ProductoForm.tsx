'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import {
  Barcode,
  Check,
  DollarSign,
  FileText,
  FolderOpen,
  Hash,
  Layers,
  Loader2,
  Package,
  Plus,
  Ruler,
  ShoppingCart,
  Tag,
  Wrench,
  X,
} from 'lucide-react'
import { useCategorias, useCrearCategoria } from '../categorias/use-categorias'
import { ComponentesForm } from './ComponentesForm'
import type { Producto } from '../shared/producto.types'

// ─── Schema ──────────────────────────────────────────────────────────────────

const productoSchema = z.object({
  nombre:                  z.string().min(1, 'Requerido').max(255),
  descripcion:             z.string().max(1000).optional(),
  categoria_id:            z.string().uuid('Selecciona una categoría'),
  tipo:                    z.enum(['simple', 'compuesto', 'servicio']),
  unidad_medida_principal: z.string().min(1, 'Requerido').max(20),
  precio_compra:           z.coerce.number().min(0).optional(),
  precio_venta:            z.coerce.number().min(0.01, 'Debe ser mayor a 0'),
  igv_tipo:                z.enum(['gravado', 'exonerado', 'inafecto']),
  codigo_barras:           z.string().max(50).optional(),
  sku:                     z.string().min(1, 'Requerido').max(100).optional(),
})

type FormData = z.infer<typeof productoSchema>

interface ComponenteInput {
  componente_id: string
  cantidad: number
  producto?: Producto
}

export interface Props {
  defaultValues?: Partial<FormData>
  onSubmit: (data: FormData & { componentes: ComponenteInput[] }) => Promise<void>
  loading?: boolean
  mode?: 'crear' | 'editar'
  productoId?: string
}

// ─── Styles (mirrors RegisterForm) ───────────────────────────────────────────

const INPUT_CLASS   = 'w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder:text-gray-400'
const BTN_SECONDARY = 'flex-1 border border-gray-300 rounded-lg py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition'
const BTN_PRIMARY   = 'flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed'
const LABEL         = 'block text-sm font-semibold text-gray-700 mb-1.5'
const ERROR         = 'text-red-500 text-xs mt-1'

// ─── Steps ───────────────────────────────────────────────────────────────────

const STEPS = [
  { label: 'General',       subtitle: 'Nombre, SKU y descripción'       },
  { label: 'Clasificación', subtitle: 'Categoría y tipo de producto'     },
  { label: 'Precios',       subtitle: 'Precio de venta e IGV'            },
]

const STEP_FIELDS: (keyof FormData)[][] = [
  ['nombre'],           // step 0 — sku added dynamically
  ['categoria_id'],     // step 1
  ['precio_venta', 'unidad_medida_principal'], // step 2
]

// ─── Option data ─────────────────────────────────────────────────────────────

const TIPOS = [
  { value: 'simple',    label: 'Simple',         desc: 'Artículo individual con inventario',        icon: Package },
  { value: 'compuesto', label: 'Kit / Compuesto', desc: 'Agrupa varios productos (bundle)',          icon: Layers  },
  { value: 'servicio',  label: 'Servicio',        desc: 'Sin stock físico — honorarios o servicios', icon: Wrench  },
] as const

const IGV_TIPOS = [
  { value: 'gravado',   label: 'Gravado',   desc: '18% IGV incluido' },
  { value: 'exonerado', label: 'Exonerado', desc: 'Exonerado del IGV' },
  { value: 'inafecto',  label: 'Inafecto',  desc: 'Fuera del ámbito'  },
] as const

// ─── Step indicator helpers ───────────────────────────────────────────────────

const circleClass = (i: number, current: number) => {
  const base = 'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold'
  if (i < current)  return `${base} bg-green-500 text-white`
  if (i === current) return `${base} bg-blue-600 text-white ring-4 ring-blue-100`
  return `${base} bg-gray-100 text-gray-400`
}

const labelClass = (i: number, current: number) => {
  if (i === current) return 'text-xs mt-1 font-medium text-blue-600'
  if (i < current)   return 'text-xs mt-1 font-medium text-green-600'
  return 'text-xs mt-1 font-medium text-gray-400'
}

// ─── Component ────────────────────────────────────────────────────────────────

export function ProductoForm({ defaultValues, onSubmit, loading, mode = 'crear', productoId }: Props) {
  const [step,           setStep]           = useState(0)
  const [componentes,    setComponentes]    = useState<ComponenteInput[]>([])
  const [showNuevaCat,   setShowNuevaCat]   = useState(false)
  const [nuevaCatNombre, setNuevaCatNombre] = useState('')
  const [nuevaCatError,  setNuevaCatError]  = useState<string | null>(null)

  const { data: categorias = [] }                              = useCategorias()
  const { mutateAsync: crearCategoria, isPending: creandoCat } = useCrearCategoria()

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    trigger,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(productoSchema),
    defaultValues: {
      tipo:                    'simple',
      igv_tipo:                'gravado',
      unidad_medida_principal: 'NIU',
      ...defaultValues,
    },
  })

  const tipo    = watch('tipo')
  const igvTipo = watch('igv_tipo')

  const goNext = async () => {
    const fields = [...STEP_FIELDS[step]]
    if (step === 0 && mode === 'crear') fields.push('sku')
    const ok = await trigger(fields)
    if (ok) setStep((s) => s + 1)
  }

  const submit = handleSubmit((data) => onSubmit({ ...data, componentes }))

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
    <form onSubmit={submit} className="space-y-6">

      {/* ── Step indicator (igual que RegisterForm) ───────────────── */}
      <div className="flex items-center gap-0">
        {STEPS.map((s, i) => (
          <div key={s.label} className="flex items-center flex-1">
            <div className="flex flex-col items-center flex-1">
              <div className={circleClass(i, step)}>
                {i < step ? <Check size={14} strokeWidth={3} /> : i + 1}
              </div>
              <span className={labelClass(i, step)}>{s.label}</span>
            </div>
            {i < STEPS.length - 1 && (
              <div className={`h-0.5 flex-1 mb-4 transition-all ${i < step ? 'bg-green-400' : 'bg-gray-200'}`} />
            )}
          </div>
        ))}
      </div>

      {/* ── Step title ────────────────────────────────────────────── */}
      <div>
        <h2 className="text-xl font-bold text-gray-900">{STEPS[step].label}</h2>
        <p className="text-sm text-gray-500 mt-1">{STEPS[step].subtitle}</p>
      </div>

      {/* ── Step 0: General ───────────────────────────────────────── */}
      {step === 0 && (
        <div className="space-y-5">
          <div>
            <label className={LABEL}>Nombre del producto <span className="text-red-500">*</span></label>
            <div className="relative">
              <Tag className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <input
                {...register('nombre')}
                placeholder="Ej: Laptop ASUS VivoBook 15"
                className={`${INPUT_CLASS} pl-8`}
              />
            </div>
            {errors.nombre && <p className={ERROR}>{errors.nombre.message}</p>}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {mode === 'crear' && (
              <div>
                <label className={LABEL}>SKU <span className="text-red-500">*</span></label>
                <div className="relative">
                  <Hash className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                  <input
                    {...register('sku')}
                    placeholder="PROD-001"
                    className={`${INPUT_CLASS} pl-8 font-mono`}
                  />
                </div>
                {errors.sku && <p className={ERROR}>{errors.sku.message}</p>}
              </div>
            )}

            <div className={mode !== 'crear' ? 'sm:col-span-2' : ''}>
              <label className={LABEL}>Código de barras</label>
              <div className="relative">
                <Barcode className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                  {...register('codigo_barras')}
                  placeholder="7501000000000"
                  className={`${INPUT_CLASS} pl-8 font-mono`}
                />
              </div>
            </div>
          </div>

          <div>
            <label className={LABEL}>Descripción</label>
            <div className="relative">
              <FileText className="absolute left-3 top-3 w-3.5 h-3.5 text-gray-400" />
              <textarea
                {...register('descripcion')}
                rows={3}
                placeholder="Descripción opcional del producto..."
                className={`${INPUT_CLASS} pl-8 resize-none`}
              />
            </div>
          </div>

          <div className="flex gap-3 pt-2">
            <button type="button" onClick={goNext} className={BTN_PRIMARY}>
              Continuar →
            </button>
          </div>
        </div>
      )}

      {/* ── Step 1: Clasificación ─────────────────────────────────── */}
      {step === 1 && (
        <div className="space-y-5">
          {/* Categoría */}
          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className={LABEL.replace('mb-1.5', '')}>
                Categoría <span className="text-red-500">*</span>
              </label>
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
                    autoFocus
                    value={nuevaCatNombre}
                    onChange={(e) => setNuevaCatNombre(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), handleCrearCategoria())}
                    placeholder="Nombre de la categoría"
                    className={`${INPUT_CLASS} pl-8`}
                  />
                </div>
                <button
                  type="button"
                  onClick={handleCrearCategoria}
                  disabled={creandoCat}
                  className="flex items-center gap-1.5 px-4 py-2.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60 font-medium whitespace-nowrap"
                >
                  {creandoCat ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-3.5 h-3.5" />}
                  Crear
                </button>
              </div>
            ) : (
              <div className="relative">
                <FolderOpen className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <select
                  {...register('categoria_id')}
                  className={`${INPUT_CLASS} pl-8 appearance-none`}
                >
                  <option value="">Seleccionar categoría...</option>
                  {categorias.map((c) => (
                    <option key={c.id} value={c.id}>{c.nombre}</option>
                  ))}
                </select>
              </div>
            )}
            {nuevaCatError && <p className={ERROR}>{nuevaCatError}</p>}
            {errors.categoria_id && !showNuevaCat && <p className={ERROR}>{errors.categoria_id.message}</p>}
          </div>

          {/* Tipo — card selector (igual que régimen tributario en RegisterForm) */}
          <div>
            <label className={LABEL}>Tipo de producto <span className="text-red-500">*</span></label>
            <div className="grid grid-cols-1 gap-2">
              {TIPOS.map(({ value, label, desc, icon: Icon }) => {
                const selected = tipo === value
                return (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('tipo', value)}
                    className={`flex items-start gap-3 border-2 rounded-xl px-4 py-3 text-left transition-all ${
                      selected ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                    }`}
                  >
                    <div className={`mt-0.5 w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition ${
                      selected ? 'border-blue-600' : 'border-gray-300'
                    }`}>
                      {selected && <div className="w-2.5 h-2.5 rounded-full bg-blue-600" />}
                    </div>
                    <div className="flex items-start gap-2 flex-1">
                      <Icon className={`w-4 h-4 mt-0.5 flex-shrink-0 ${selected ? 'text-blue-600' : 'text-gray-400'}`} />
                      <div>
                        <span className={`text-sm font-semibold ${selected ? 'text-blue-700' : 'text-gray-800'}`}>{label}</span>
                        <p className="text-xs text-gray-500 mt-0.5">{desc}</p>
                      </div>
                    </div>
                  </button>
                )
              })}
            </div>
          </div>

          {/* Componentes — solo si tipo=compuesto */}
          {tipo === 'compuesto' && (
            <div>
              <label className={LABEL}>Componentes del kit</label>
              <ComponentesForm
                value={componentes}
                onChange={setComponentes}
                currentProductoId={productoId}
              />
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="button" onClick={() => setStep(0)} className={BTN_SECONDARY}>← Atrás</button>
            <button type="button" onClick={goNext} className={BTN_PRIMARY}>Continuar →</button>
          </div>
        </div>
      )}

      {/* ── Step 2: Precios ───────────────────────────────────────── */}
      {step === 2 && (
        <div className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className={LABEL}>Unidad principal <span className="text-red-500">*</span></label>
              <div className="relative">
                <Ruler className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                  {...register('unidad_medida_principal')}
                  placeholder="NIU"
                  className={`${INPUT_CLASS} pl-8`}
                />
              </div>
              {errors.unidad_medida_principal && <p className={ERROR}>{errors.unidad_medida_principal.message}</p>}
            </div>

            <div>
              <label className={LABEL}>Precio de compra</label>
              <div className="relative">
                <ShoppingCart className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <span className="absolute left-8 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">S/</span>
                <input
                  {...register('precio_compra')}
                  type="number" step="0.01" min="0"
                  placeholder="0.00"
                  className={`${INPUT_CLASS} pl-14`}
                />
              </div>
            </div>

            <div>
              <label className={LABEL}>Precio de venta <span className="text-red-500">*</span></label>
              <div className="relative">
                <DollarSign className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <span className="absolute left-8 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">S/</span>
                <input
                  {...register('precio_venta')}
                  type="number" step="0.01" min="0.01"
                  placeholder="0.00"
                  className={`${INPUT_CLASS} pl-14`}
                />
              </div>
              {errors.precio_venta && <p className={ERROR}>{errors.precio_venta.message}</p>}
            </div>
          </div>

          {/* IGV — card selector */}
          <div>
            <label className={LABEL}>Tipo de IGV <span className="text-red-500">*</span></label>
            <div className="grid grid-cols-1 gap-2">
              {IGV_TIPOS.map(({ value, label, desc }) => {
                const selected = igvTipo === value
                return (
                  <button
                    key={value}
                    type="button"
                    onClick={() => setValue('igv_tipo', value)}
                    className={`flex items-start gap-3 border-2 rounded-xl px-4 py-3 text-left transition-all ${
                      selected ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                    }`}
                  >
                    <div className={`mt-0.5 w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition ${
                      selected ? 'border-blue-600' : 'border-gray-300'
                    }`}>
                      {selected && <div className="w-2.5 h-2.5 rounded-full bg-blue-600" />}
                    </div>
                    <div>
                      <span className={`text-sm font-semibold ${selected ? 'text-blue-700' : 'text-gray-800'}`}>{label}</span>
                      <p className="text-xs text-gray-500 mt-0.5">{desc}</p>
                    </div>
                  </button>
                )
              })}
            </div>
          </div>

          <div className="flex gap-3 pt-2">
            <button type="button" onClick={() => setStep(1)} className={BTN_SECONDARY}>← Atrás</button>
            <button type="submit" disabled={loading} className={BTN_PRIMARY}>
              {loading
                ? <span className="flex items-center justify-center gap-2"><Loader2 className="w-4 h-4 animate-spin" /> Guardando...</span>
                : mode === 'crear' ? '🚀 Crear producto' : '💾 Guardar cambios'
              }
            </button>
          </div>
        </div>
      )}

    </form>
  )
}
