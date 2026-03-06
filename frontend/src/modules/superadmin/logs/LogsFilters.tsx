'use client'

import type { LogFilters } from './use-logs'

interface Props {
  filters: LogFilters
  onChange: (f: LogFilters) => void
  onExport: () => void
  exporting?: boolean
}

export default function LogsFilters({ filters, onChange, onExport, exporting }: Props) {
  return (
    <div className="flex flex-wrap gap-3 items-end">
      <div>
        <label className="block text-xs text-gray-400 mb-1">Acción</label>
        <input
          value={filters.accion ?? ''}
          onChange={(e) => onChange({ ...filters, accion: e.target.value || undefined, page: 1 })}
          placeholder="ej: login, logout..."
          className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500 w-44"
        />
      </div>

      <div>
        <label className="block text-xs text-gray-400 mb-1">Desde</label>
        <input
          type="date"
          value={filters.fecha_desde ?? ''}
          onChange={(e) => onChange({ ...filters, fecha_desde: e.target.value || undefined, page: 1 })}
          className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
        />
      </div>

      <div>
        <label className="block text-xs text-gray-400 mb-1">Hasta</label>
        <input
          type="date"
          value={filters.fecha_hasta ?? ''}
          onChange={(e) => onChange({ ...filters, fecha_hasta: e.target.value || undefined, page: 1 })}
          className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
        />
      </div>

      <button
        onClick={onExport}
        disabled={exporting}
        className="px-4 py-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white text-sm rounded-lg"
      >
        {exporting ? 'Exportando...' : 'Exportar CSV'}
      </button>
    </div>
  )
}
