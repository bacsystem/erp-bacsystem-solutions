'use client';

import { useState } from 'react';
import { Clock, Mail, UserX, Users } from 'lucide-react';
import { useListarUsuarios } from './use-listar-usuarios';
import { useActualizarRol } from '../actualizar-rol/use-actualizar-rol';
import { useDesactivarUsuario } from '../desactivar-usuario/use-desactivar-usuario';
import { useAuthStore } from '@/shared/stores/auth.store';

const ROLES = ['owner', 'admin', 'empleado', 'contador'];

const ROLE_BADGE: Record<string, string> = {
  owner: 'bg-purple-100 text-purple-700',
  admin: 'bg-blue-100 text-blue-700',
  empleado: 'bg-gray-100 text-gray-700',
  contador: 'bg-amber-100 text-amber-700',
};

const AVATAR_COLORS = [
  'bg-blue-500', 'bg-violet-500', 'bg-emerald-500', 'bg-rose-500',
  'bg-amber-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-pink-500',
];

function avatarColor(name: string): string {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = (hash * 31 + (name.codePointAt(i) ?? 0)) & 0xffff;
  return AVATAR_COLORS[hash % AVATAR_COLORS.length];
}

function Avatar({ nombre }: Readonly<{ nombre: string }>) {
  return (
    <div className={`w-8 h-8 rounded-full ${avatarColor(nombre)} text-white flex items-center justify-center text-sm font-semibold flex-shrink-0`}>
      {nombre.trim().charAt(0).toUpperCase()}
    </div>
  );
}

function RoleBadge({ rol }: Readonly<{ rol: string }>) {
  return (
    <span className={`text-xs px-2 py-0.5 rounded-full font-medium capitalize ${ROLE_BADGE[rol] ?? 'bg-gray-100 text-gray-600'}`}>
      {rol}
    </span>
  );
}

function SkeletonRow({ cols }: Readonly<{ cols: number }>) {
  return (
    <tr className="border-t border-gray-100">
      <td className="px-6 py-3">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-gray-200 animate-pulse" />
          <div className="space-y-1.5">
            <div className="h-3 w-28 bg-gray-200 rounded animate-pulse" />
            <div className="h-2.5 w-36 bg-gray-100 rounded animate-pulse" />
          </div>
        </div>
      </td>
      <td className="px-6 py-3"><div className="h-5 w-16 bg-gray-100 rounded-full animate-pulse" /></td>
      <td className="px-6 py-3"><div className="h-5 w-12 bg-gray-100 rounded-full animate-pulse" /></td>
      {cols > 3 && <td className="px-6 py-3" />}
    </tr>
  );
}

export function UsuariosTable() {
  const { user } = useAuthStore();
  const { data, isLoading } = useListarUsuarios();
  const { mutate: cambiarRol } = useActualizarRol();
  const { mutate: desactivar } = useDesactivarUsuario();
  const [confirmId, setConfirmId] = useState<string | null>(null);

  const puedeGestionar = user?.rol === 'owner' || user?.rol === 'admin';
  const cols = puedeGestionar ? 4 : 3;

  return (
    <div>
      {/* ── Active users ── */}
      <div className="px-6 pt-5 pb-3 flex items-center justify-between">
        <h2 className="text-sm font-semibold text-gray-800">Usuarios del equipo</h2>
        {!isLoading && data?.usuarios && (
          <span className="text-xs text-gray-400">
            {data.usuarios.length === 1 ? '1 miembro' : `${data.usuarios.length} miembros`}
          </span>
        )}
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead className="border-y border-gray-100 bg-gray-50/80">
            <tr>
              <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
              <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</th>
              <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
              {puedeGestionar && <th className="px-6 py-2.5 w-36" />}
            </tr>
          </thead>
          <tbody>
            {isLoading && (
              <>
                <SkeletonRow cols={cols} />
                <SkeletonRow cols={cols} />
                <SkeletonRow cols={cols} />
              </>
            )}
            {!isLoading && (data?.usuarios.length ?? 0) === 0 && (
              <tr>
                <td colSpan={cols} className="px-6 py-10 text-center">
                  <Users className="w-8 h-8 text-gray-200 mx-auto mb-2" />
                  <p className="text-sm text-gray-400">No hay usuarios activos aún.</p>
                </td>
              </tr>
            )}
            {data?.usuarios.map((u) => (
              <tr key={u.id} className="border-t border-gray-100 hover:bg-gray-50/70 transition">
                <td className="px-6 py-3.5">
                  <div className="flex items-center gap-3">
                    <Avatar nombre={u.nombre} />
                    <div>
                      <div className="flex items-center gap-1.5">
                        <p className="font-medium text-gray-900 text-sm">{u.nombre}</p>
                        {u.id === user?.id && (
                          <span className="text-[10px] px-1.5 py-0.5 rounded border border-blue-200 bg-blue-50 text-blue-500 font-semibold leading-none">
                            Tú
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-gray-400 mt-0.5">{u.email}</p>
                    </div>
                  </div>
                </td>
                <td className="px-6 py-3.5">
                  {puedeGestionar && u.id !== user?.id ? (
                    <select
                      value={u.rol}
                      onChange={(e) => cambiarRol({ id: u.id, rol: e.target.value })}
                      className="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    >
                      {ROLES.map((r) => (
                        <option key={r} value={r} disabled={r === 'owner' && user?.rol !== 'owner'}>
                          {r}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <RoleBadge rol={u.rol} />
                  )}
                </td>
                <td className="px-6 py-3.5">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    u.activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {u.activo ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                {puedeGestionar && (
                  <td className="px-6 py-3.5 text-right">
                    {u.id !== user?.id && u.activo && (
                      confirmId === u.id ? (
                        <div className="flex items-center justify-end gap-2">
                          <span className="text-xs text-gray-500">¿Desactivar?</span>
                          <button
                            onClick={() => { desactivar(u.id); setConfirmId(null); }}
                            className="text-xs font-semibold text-red-600 hover:text-red-700"
                          >
                            Confirmar
                          </button>
                          <button
                            onClick={() => setConfirmId(null)}
                            className="text-xs text-gray-400 hover:text-gray-600"
                          >
                            Cancelar
                          </button>
                        </div>
                      ) : (
                        <button
                          onClick={() => setConfirmId(u.id)}
                          className="flex items-center gap-1 text-xs text-gray-400 hover:text-red-600 transition ml-auto"
                        >
                          <UserX className="w-3.5 h-3.5" />
                          Desactivar
                        </button>
                      )
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* ── Pending invitations ── */}
      <div className="border-t border-gray-100 px-6 pt-5 pb-3 flex items-center justify-between">
        <h2 className="text-sm font-semibold text-gray-800">Invitaciones pendientes</h2>
        {!isLoading && data?.invitaciones && (data.invitaciones.length > 0) && (
          <span className="text-xs text-gray-400">
            {data.invitaciones.length === 1 ? '1 pendiente' : `${data.invitaciones.length} pendientes`}
          </span>
        )}
      </div>

      {(!isLoading && (data?.invitaciones.length ?? 0) === 0) ? (
        <div className="px-6 pb-8 flex flex-col items-center gap-1.5 pt-2">
          <Mail className="w-7 h-7 text-gray-200" />
          <p className="text-sm text-gray-400">No hay invitaciones pendientes.</p>
        </div>
      ) : (
        <div className="overflow-x-auto pb-2">
          <table className="min-w-full text-sm">
            <thead className="border-y border-gray-100 bg-gray-50/80">
              <tr>
                <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol asignado</th>
                <th className="px-6 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Expira</th>
              </tr>
            </thead>
            <tbody>
              {data?.invitaciones.map((inv) => (
                <tr key={inv.id} className="border-t border-gray-100 hover:bg-gray-50/70 transition">
                  <td className="px-6 py-3.5 text-gray-600">{inv.email}</td>
                  <td className="px-6 py-3.5"><RoleBadge rol={inv.rol} /></td>
                  <td className="px-6 py-3.5">
                    <span className="flex items-center gap-1.5 text-xs text-gray-400">
                      <Clock className="w-3 h-3" />
                      {new Date(inv.expires_at).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
