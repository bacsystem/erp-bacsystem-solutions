'use client';

import { useState } from 'react';
import { UserPlus, Users } from 'lucide-react';
import { Sidebar } from '@/shared/components/Sidebar';
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner';
import { UsuariosTable } from '@/modules/core/usuario/listar-usuarios/UsuariosTable';
import { InviteUsuarioModal } from '@/modules/core/usuario/invite-usuario/InviteUsuarioModal';
import { useAuthStore } from '@/shared/stores/auth.store';

export default function UsuariosPage() {
  const { user } = useAuthStore();
  const [showInvite, setShowInvite] = useState(false);
  const puedeInvitar = user?.rol === 'owner' || user?.rol === 'admin';

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-5xl mx-auto p-8 space-y-8">
            {/* Page header */}
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                  <Users className="w-4 h-4" />
                  <span>Configuración</span>
                  <span>/</span>
                  <span className="text-gray-900 font-medium">Usuarios</span>
                </div>
                <h1 className="text-2xl font-bold text-gray-900">Usuarios</h1>
                <p className="text-sm text-gray-500 mt-1">Gestiona los accesos de tu equipo a OperaAI</p>
              </div>
              {puedeInvitar && (
                <button
                  onClick={() => setShowInvite(true)}
                  className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium transition"
                >
                  <UserPlus className="w-4 h-4" />
                  Invitar usuario
                </button>
              )}
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <UsuariosTable />
            </div>
          </div>
        </div>
      </main>
      {showInvite && <InviteUsuarioModal onClose={() => setShowInvite(false)} />}
    </div>
  );
}
