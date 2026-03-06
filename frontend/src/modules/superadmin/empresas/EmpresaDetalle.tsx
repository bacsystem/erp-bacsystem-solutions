'use client'

import { useState } from 'react'
import { useEmpresaDetalle } from './use-empresas'
import SuspenderModal from './SuspenderModal'
import ActivarModal from './ActivarModal'
import ImpersonarButton from '../impersonacion/ImpersonarButton'

interface DetalleUsuario {
  id: string;
  nombre: string;
  email: string;
  rol: string;
  activo: boolean;
}

interface DetalleSuscripcion {
  id: string;
  plan: { nombre_display: string } | null;
  estado: string;
  fecha_inicio: string;
  fecha_vencimiento: string;
}

interface DetalleLog {
  id: string;
  accion: string;
  ip: string;
  created_at: string;
}

const TABS = ['Datos', 'Usuarios', 'Suscripciones', 'Logs', 'Métricas'] as const
type Tab = typeof TABS[number]

const ESTADO_COLORS: Record<string, string> = {
  activa: 'text-green-400 bg-green-900/30',
  trial: 'text-yellow-400 bg-yellow-900/30',
  vencida: 'text-orange-400 bg-orange-900/30',
  cancelada: 'text-red-400 bg-red-900/30',
}

export default function EmpresaDetalle({ id }: { readonly id: string }) {
  const { data, isLoading } = useEmpresaDetalle(id)
  const [tab, setTab] = useState<Tab>('Datos')
  const [showSuspender, setShowSuspender] = useState(false)
  const [showActivar, setShowActivar] = useState(false)

  if (isLoading) return <div className="text-gray-400 text-center py-12">Cargando...</div>
  if (!data) return <div className="text-red-400 text-center py-12">Empresa no encontrada</div>

  const suscripcion = data.suscripcion
  const isSuspendida = suscripcion?.estado === 'cancelada'
  const isActiva = suscripcion?.estado === 'activa' || suscripcion?.estado === 'trial'

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">{data.razon_social}</h1>
          <p className="text-gray-400 text-sm">{data.ruc} • {data.nombre_comercial}</p>
        </div>
        <div className="flex gap-2">
          <ImpersonarButton empresaId={id} />
          {isActiva && (
            <button
              onClick={() => setShowSuspender(true)}
              className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg"
            >
              Suspender
            </button>
          )}
          {isSuspendida && (
            <button
              onClick={() => setShowActivar(true)}
              className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg"
            >
              Reactivar
            </button>
          )}
        </div>
      </div>

      <div className="flex gap-1 border-b border-gray-700">
        {TABS.map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
              tab === t
                ? 'border-indigo-500 text-indigo-400'
                : 'border-transparent text-gray-400 hover:text-white'
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      <div className="bg-gray-800 rounded-xl border border-gray-700 p-5">
        {tab === 'Datos' && (
          <dl className="space-y-3">
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Razón social</dt>
              <dd className="text-white text-sm">{data.razon_social}</dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">RUC</dt>
              <dd className="text-white text-sm font-mono">{data.ruc}</dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Nombre comercial</dt>
              <dd className="text-white text-sm">{data.nombre_comercial}</dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Estado suscripción</dt>
              <dd>
                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${ESTADO_COLORS[suscripcion?.estado] ?? ''}`}>
                  {suscripcion?.estado ?? '—'}
                </span>
              </dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Registro</dt>
              <dd className="text-white text-sm">{data.created_at ? new Date(data.created_at).toLocaleDateString('es-PE') : '—'}</dd>
            </div>
          </dl>
        )}

        {tab === 'Usuarios' && (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-700">
                <th className="text-left py-2 text-gray-400">Nombre</th>
                <th className="text-left py-2 text-gray-400">Email</th>
                <th className="text-left py-2 text-gray-400">Rol</th>
                <th className="text-left py-2 text-gray-400">Estado</th>
              </tr>
            </thead>
            <tbody>
              {(data.usuarios ?? [] as DetalleUsuario[]).map((u: DetalleUsuario) => (
                <tr key={u.id} className="border-b border-gray-700/50">
                  <td className="py-2 text-white">{u.nombre}</td>
                  <td className="py-2 text-gray-300">{u.email}</td>
                  <td className="py-2 text-gray-300 capitalize">{u.rol}</td>
                  <td className="py-2">
                    <span className={u.activo ? 'text-green-400' : 'text-red-400'}>
                      {u.activo ? 'Activo' : 'Inactivo'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {tab === 'Suscripciones' && (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-700">
                <th className="text-left py-2 text-gray-400">Plan</th>
                <th className="text-left py-2 text-gray-400">Estado</th>
                <th className="text-left py-2 text-gray-400">Inicio</th>
                <th className="text-left py-2 text-gray-400">Vencimiento</th>
              </tr>
            </thead>
            <tbody>
              {(data.suscripciones ?? [] as DetalleSuscripcion[]).map((s: DetalleSuscripcion) => (
                <tr key={s.id} className="border-b border-gray-700/50">
                  <td className="py-2 text-white">{s.plan?.nombre_display ?? '—'}</td>
                  <td className="py-2">
                    <span className={`${ESTADO_COLORS[s.estado] ?? ''} px-2 py-0.5 rounded-full text-xs`}>{s.estado}</span>
                  </td>
                  <td className="py-2 text-gray-300 text-xs">{s.fecha_inicio}</td>
                  <td className="py-2 text-gray-300 text-xs">{s.fecha_vencimiento}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {tab === 'Logs' && (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-700">
                <th className="text-left py-2 text-gray-400">Acción</th>
                <th className="text-left py-2 text-gray-400">IP</th>
                <th className="text-left py-2 text-gray-400">Fecha</th>
              </tr>
            </thead>
            <tbody>
              {(data.audit_logs ?? [] as DetalleLog[]).map((log: DetalleLog) => (
                <tr key={log.id} className="border-b border-gray-700/50">
                  <td className="py-2 text-white font-mono text-xs">{log.accion}</td>
                  <td className="py-2 text-gray-300 font-mono text-xs">{log.ip}</td>
                  <td className="py-2 text-gray-400 text-xs">{new Date(log.created_at).toLocaleString('es-PE')}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {tab === 'Métricas' && data.metricas && (
          <dl className="space-y-3">
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">MRR actual</dt>
              <dd className="text-white text-sm">S/ {Number(data.metricas.mrr ?? 0).toFixed(2)}</dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Días activo</dt>
              <dd className="text-white text-sm">{data.metricas.dias_activo}</dd>
            </div>
            <div className="flex gap-4">
              <dt className="text-gray-400 w-40 text-sm">Total usuarios</dt>
              <dd className="text-white text-sm">{data.metricas.total_usuarios}</dd>
            </div>
          </dl>
        )}
      </div>

      {showSuspender && (
        <SuspenderModal empresaId={id} onClose={() => setShowSuspender(false)} />
      )}
      {showActivar && (
        <ActivarModal empresaId={id} onClose={() => setShowActivar(false)} />
      )}
    </div>
  )
}
