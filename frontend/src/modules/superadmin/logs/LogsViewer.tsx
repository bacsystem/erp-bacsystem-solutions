'use client'

import { useState } from 'react'
import { useLogs, exportLogs, type LogFilters } from './use-logs'
import LogsFilters from './LogsFilters'

export default function LogsViewer() {
  const [filters, setFilters] = useState<LogFilters>({ page: 1 })
  const [exporting, setExporting] = useState(false)
  const { data, isLoading } = useLogs(filters)
  const logs = data?.data ?? []
  const meta = data?.meta

  async function handleExport() {
    setExporting(true)
    try {
      await exportLogs(filters)
    } finally {
      setExporting(false)
    }
  }

  return (
    <div className="space-y-4">
      <LogsFilters
        filters={filters}
        onChange={setFilters}
        onExport={handleExport}
        exporting={exporting}
      />

      <div className="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-700">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Empresa</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Usuario</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Acción</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">IP</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Fecha</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-gray-400">Cargando logs...</td>
              </tr>
            ) : logs.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-gray-500">No hay logs para mostrar</td>
              </tr>
            ) : (
              logs.map((log: any) => (
                <tr key={log.id} className="border-b border-gray-700/50 hover:bg-gray-700/20">
                  <td className="px-4 py-2.5 text-gray-300 text-xs">{log.empresa ?? '—'}</td>
                  <td className="px-4 py-2.5 text-gray-300 text-xs">{log.usuario ?? '—'}</td>
                  <td className="px-4 py-2.5 font-mono text-xs text-indigo-400">{log.accion}</td>
                  <td className="px-4 py-2.5 text-gray-400 font-mono text-xs">{log.ip}</td>
                  <td className="px-4 py-2.5 text-gray-400 text-xs">
                    {new Date(log.created_at).toLocaleString('es-PE')}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {meta && meta.total > meta.per_page && (
        <div className="flex justify-center gap-2">
          <button
            onClick={() => setFilters(f => ({ ...f, page: Math.max(1, (f.page ?? 1) - 1) }))}
            disabled={(filters.page ?? 1) === 1}
            className="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 disabled:opacity-40 text-white text-sm rounded-lg"
          >
            Anterior
          </button>
          <span className="px-3 py-1.5 text-gray-400 text-sm">Página {filters.page ?? 1}</span>
          <button
            onClick={() => setFilters(f => ({ ...f, page: (f.page ?? 1) + 1 }))}
            disabled={(filters.page ?? 1) * meta.per_page >= meta.total}
            className="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 disabled:opacity-40 text-white text-sm rounded-lg"
          >
            Siguiente
          </button>
        </div>
      )}
    </div>
  )
}
