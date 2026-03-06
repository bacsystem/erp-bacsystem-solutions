'use client';

import Link from 'next/link';
import { ArrowRight, Building2, LayoutDashboard, Package, UserCircle, Users } from 'lucide-react';
import { Sidebar } from '@/shared/components/Sidebar';
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner';

const shortcuts = [
  { icon: Building2, label: 'Empresa', desc: 'Datos fiscales y logo', href: '/configuracion/empresa' },
  { icon: Users, label: 'Usuarios', desc: 'Gestión del equipo', href: '/configuracion/usuarios' },
  { icon: Package, label: 'Plan', desc: 'Suscripción y facturación', href: '/configuracion/plan' },
  { icon: UserCircle, label: 'Mi perfil', desc: 'Nombre y contraseña', href: '/perfil' },
];

export default function DashboardPage() {
  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-4xl mx-auto p-8 space-y-8">

            {/* Page header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <LayoutDashboard className="w-4 h-4" />
                <span className="text-gray-900 font-medium">Dashboard</span>
              </div>
              <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
              <p className="text-sm text-gray-500 mt-1">Bienvenido a OperaAI. Gestiona tu empresa desde un solo lugar.</p>
            </div>

            {/* Quick access */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {shortcuts.map(({ icon: Icon, label, desc, href }) => (
                <Link
                  key={href}
                  href={href}
                  className="group bg-white rounded-2xl border border-gray-200 p-5 flex items-center gap-4 hover:border-blue-200 hover:shadow-sm transition-all"
                >
                  <div className="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition-colors">
                    <Icon className="w-5 h-5 text-blue-600" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-gray-900 text-sm">{label}</p>
                    <p className="text-xs text-gray-500 truncate">{desc}</p>
                  </div>
                  <ArrowRight className="w-4 h-4 text-gray-300 group-hover:text-blue-400 transition-colors shrink-0" />
                </Link>
              ))}
            </div>

          </div>
        </div>
      </main>
    </div>
  );
}
