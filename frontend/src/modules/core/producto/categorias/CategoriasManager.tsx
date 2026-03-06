'use client'

import { useState } from 'react'
import { ChevronRight, Edit2, Loader2, Plus, Trash2 } from 'lucide-react'
import { useCategorias, useCrearCategoria, useActualizarCategoria, useEliminarCategoria } from './use-categorias'
import type { Categoria } from '../shared/producto.types'

interface RowProps {
  cat: Categoria
  depth: number
  onEdit: (cat: Categoria) => void
  onDelete: (cat: Categoria) => void
  onAddChild: (parentId: string) => void
}

function CategoriaRow({ cat, depth, onEdit, onDelete, onAddChild }: RowProps) {
  const [expanded, setExpanded] = useState(true)
  const hasChildren = (cat.hijos?.length ?? 0) > 0

  return (
    <>
      <tr className="group border-t border-gray-50 hover:bg-gray-50/50">
        <td className="py-2 pr-3">
          <div className="flex items-center gap-1" style={{ paddingLeft: `${depth * 20}px` }}>
            {hasChildren ? (
              <button
                onClick={() => setExpanded((v) => !v)}
                className="p-0.5 rounded text-gray-400 hover:text-gray-600"
              >
                <ChevronRight className={`w-3.5 h-3.5 transition-transform ${expanded ? 'rotate-90' : ''}`} />
              </button>
            ) : (
              <span className="w-5" />
            )}
            <span className="text-sm font-medium text-gray-800">{cat.nombre}</span>
          </div>
        </td>
        <td className="py-2 text-xs text-gray-400">{cat.descripcion ?? '—'}</td>
        <td className="py-2">
          <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity justify-end pr-2">
            <button
              onClick={() => onAddChild(cat.id)}
              title="Agregar subcategoría"
              className="p-1 rounded hover:bg-blue-50 text-gray-400 hover:text-blue-500"
            >
              <Plus className="w-3.5 h-3.5" />
            </button>
            <button
              onClick={() => onEdit(cat)}
              title="Editar"
              className="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600"
            >
              <Edit2 className="w-3.5 h-3.5" />
            </button>
            <button
              onClick={() => onDelete(cat)}
              title="Eliminar"
              className="p-1 rounded hover:bg-red-50 text-gray-400 hover:text-red-500"
            >
              <Trash2 className="w-3.5 h-3.5" />
            </button>
          </div>
        </td>
      </tr>
      {expanded && hasChildren && cat.hijos!.map((hijo) => (
        <CategoriaRow
          key={hijo.id}
          cat={hijo}
          depth={depth + 1}
          onEdit={onEdit}
          onDelete={onDelete}
          onAddChild={onAddChild}
        />
      ))}
    </>
  )
}

interface FormState {
  nombre: string
  descripcion: string
}

export function CategoriasManager() {
  const { data: categorias = [], isLoading } = useCategorias()
  const { mutateAsync: crear, isPending: creando } = useCrearCategoria()
  const { mutateAsync: actualizar, isPending: actualizando } = useActualizarCategoria()
  const { mutateAsync: eliminar } = useEliminarCategoria()

  const [editing, setEditing] = useState<Categoria | null>(null)
  const [parentId, setParentId] = useState<string | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState<FormState>({ nombre: '', descripcion: '' })
  const [error, setError] = useState<string | null>(null)

  const openCreate = (pid?: string) => {
    setEditing(null)
    setParentId(pid ?? null)
    setForm({ nombre: '', descripcion: '' })
    setError(null)
    setShowForm(true)
  }

  const openEdit = (cat: Categoria) => {
    setEditing(cat)
    setParentId(null)
    setForm({ nombre: cat.nombre, descripcion: cat.descripcion ?? '' })
    setError(null)
    setShowForm(true)
  }

  const handleDelete = async (cat: Categoria) => {
    if (!confirm(`¿Eliminar la categoría "${cat.nombre}"?`)) return
    try {
      await eliminar(cat.id)
    } catch {
      alert('No se puede eliminar: tiene productos o subcategorías asociadas.')
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    if (!form.nombre.trim()) {
      setError('El nombre es requerido.')
      return
    }
    try {
      if (editing) {
        await actualizar({ id: editing.id, data: { nombre: form.nombre, descripcion: form.descripcion || undefined } })
      } else {
        await crear({ nombre: form.nombre, descripcion: form.descripcion || null, categoria_padre_id: parentId ?? null })
      }
      setShowForm(false)
    } catch {
      setError('Error al guardar la categoría.')
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12 text-gray-400 gap-2">
        <Loader2 className="w-5 h-5 animate-spin" />
        <span className="text-sm">Cargando categorías...</span>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-gray-500">{categorias.length} categorías raíz</p>
        <button
          onClick={() => openCreate()}
          className="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
        >
          <Plus className="w-4 h-4" />
          Nueva categoría
        </button>
      </div>

      {/* Form */}
      {showForm && (
        <form onSubmit={handleSubmit} className="bg-gray-50 rounded-xl p-4 space-y-3 border border-gray-200">
          <p className="text-sm font-semibold text-gray-700">
            {editing ? `Editar: ${editing.nombre}` : parentId ? 'Nueva subcategoría' : 'Nueva categoría raíz'}
          </p>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-gray-500 mb-1">Nombre *</label>
              <input
                autoFocus
                value={form.nombre}
                onChange={(e) => setForm((f) => ({ ...f, nombre: e.target.value }))}
                className="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ej: Electrónica"
              />
            </div>
            <div>
              <label className="block text-xs text-gray-500 mb-1">Descripción</label>
              <input
                value={form.descripcion}
                onChange={(e) => setForm((f) => ({ ...f, descripcion: e.target.value }))}
                className="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Opcional"
              />
            </div>
          </div>
          {error && <p className="text-xs text-red-500">{error}</p>}
          <div className="flex justify-end gap-2">
            <button
              type="button"
              onClick={() => setShowForm(false)}
              className="px-3 py-1.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={creando || actualizando}
              className="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60"
            >
              {(creando || actualizando) && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
              Guardar
            </button>
          </div>
        </form>
      )}

      {/* Tree table */}
      {categorias.length === 0 ? (
        <div className="text-center py-10 text-gray-400 text-sm">
          No hay categorías. Crea la primera.
        </div>
      ) : (
        <table className="w-full text-left">
          <thead>
            <tr>
              <th className="pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
              <th className="pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Descripción</th>
              <th className="pb-2 w-24" />
            </tr>
          </thead>
          <tbody>
            {categorias.map((cat) => (
              <CategoriaRow
                key={cat.id}
                cat={cat}
                depth={0}
                onEdit={openEdit}
                onDelete={handleDelete}
                onAddChild={(pid) => openCreate(pid)}
              />
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
