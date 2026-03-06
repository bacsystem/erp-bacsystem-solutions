'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Search } from 'lucide-react'
import { useEmpresas } from './use-empresas'

interface EmpresaRow {
  id: string;
  razon_social: string;
  ruc: string;
  plan: string | null;
  estado: string | null;
  mrr: number | null;
  fecha_registro: string | null;
}

const ESTADO_COLORS: Record<string, string> = {
  activa: 'text-green-400 bg-green-900/30',
  trial: 'text-yellow-400 bg-yellow-900/30',
  vencida: 'text-orange-400 bg-orange-900/30',
  cancelada: 'text-red-400 bg-red-900/30',
}

export default function EmpresasTable() {
  const [q, setQ] = useState('')
  const [estado, setEstado] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useEmpresas({ q: q || undefined, estado: estado || undefined, page })
  const empresas = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-4">
      <div className="flex gap-3">
        <div className="relative flex-1">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            value={q}
            onChange={(e) => { setQ(e.target.value); setPage(1) }}
            placeholder="Buscar por nombre, RUC..."
            className="w-full pl-9 pr-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:border-indigo-500"
          />
        </div>
        <select
          value={estado}
          onChange={(e) => { setEstado(e.target.value); setPage(1) }}
          className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
        >
          <option value="">Todos los estados</option>
          <option value="activa">Activa</option>
          <option value="trial">Trial</option>
          <option value="vencida">Vencida</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>

      <div className="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-700">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Empresa</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">RUC</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Plan</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Estado</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">MRR</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Registro</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-gray-400">Cargando...</td>
              </tr>
            )}
            {!isLoading && empresas.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-gray-500">No se encontraron empresas</td>
              </tr>
            )}
            {!isLoading && empresas.length > 0 && empresas.map((e: EmpresaRow) => (
                <tr key={e.id} className="border-b border-gray-700/50 hover:bg-gray-700/30">
                  <td className="px-4 py-3">
                    <Link href={`/superadmin/empresas/${e.id}`} className="text-indigo-400 hover:text-indigo-300 font-medium">
                      {e.razon_social}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-gray-300 font-mono text-xs">{e.ruc}</td>
                  <td className="px-4 py-3 text-gray-300">{e.plan ?? '—'}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${ESTADO_COLORS[e.estado ?? ''] ?? 'text-gray-400'}`}>
                      {e.estado ?? '—'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-gray-300">S/ {Number(e.mrr ?? 0).toFixed(2)}</td>
                  <td className="px-4 py-3 text-gray-400 text-xs">
                    {e.fecha_registro ? new Date(e.fecha_registro).toLocaleDateString('es-PE') : '—'}
                  </td>
                </tr>
              ))
            }
          </tbody>
        </table>
      </div>

      {meta && meta.total > meta.per_page && (
        <div className="flex justify-center gap-2">
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 disabled:opacity-40 text-white text-sm rounded-lg"
          >
            Anterior
          </button>
          <span className="px-3 py-1.5 text-gray-400 text-sm">Página {page}</span>
          <button
            onClick={() => setPage(p => p + 1)}
            disabled={page * meta.per_page >= meta.total}
            className="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 disabled:opacity-40 text-white text-sm rounded-lg"
          >
            Siguiente
          </button>
        </div>
      )}
    </div>
  )
}
