'use client'

import { useGlobalDashboard } from './use-dashboard'
import MrrChart from './MrrChart'

function StatCard({ label, value, color = 'text-white' }: { label: string; value: string | number; color?: string }) {
  return (
    <div className="bg-gray-800 rounded-xl p-5 border border-gray-700">
      <p className="text-gray-400 text-sm mb-1">{label}</p>
      <p className={`text-2xl font-bold ${color}`}>{value}</p>
    </div>
  )
}

export default function GlobalDashboard() {
  const { data, isLoading, error } = useGlobalDashboard()

  if (isLoading) {
    return <div className="text-gray-400 text-center py-12">Cargando métricas...</div>
  }

  if (error || !data) {
    return <div className="text-red-400 text-center py-12">Error al cargar las métricas.</div>
  }

  const t = data.totales_por_estado

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-white">Dashboard Global</h1>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="MRR Total" value={`S/ ${data.mrr_total.toFixed(2)}`} color="text-indigo-400" />
        <StatCard label="Activas" value={t.activa ?? 0} color="text-green-400" />
        <StatCard label="Trial" value={t.trial ?? 0} color="text-yellow-400" />
        <StatCard label="Canceladas" value={t.cancelada ?? 0} color="text-red-400" />
        <StatCard label="Vencidas" value={t.vencida ?? 0} color="text-orange-400" />
        <StatCard label="Nuevas hoy" value={data.nuevos_hoy} />
        <StatCard label="Nuevas este mes" value={data.nuevos_mes} />
        <StatCard label="Conversión" value={`${data.tasa_conversion}%`} color="text-teal-400" />
        <StatCard label="Churn (mes)" value={data.churn} color="text-red-400" />
      </div>

      <MrrChart data={data.mrr_historico} />
    </div>
  )
}
