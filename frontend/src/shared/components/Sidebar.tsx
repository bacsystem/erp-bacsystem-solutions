'use client';

import Link from 'next/link';
import { useAuthStore } from '@/shared/stores/auth.store';
import { api } from '@/shared/lib/api';
import { useRouter } from 'next/navigation';

const ALL_MODULES = [
  { key: 'facturacion', label: 'Facturación', href: '/facturacion' },
  { key: 'clientes', label: 'Clientes', href: '/clientes' },
  { key: 'productos', label: 'Productos', href: '/productos' },
  { key: 'inventario', label: 'Inventario', href: '/inventario' },
  { key: 'crm', label: 'CRM', href: '/crm' },
  { key: 'finanzas', label: 'Finanzas', href: '/finanzas' },
  { key: 'ia', label: 'Inteligencia Artificial', href: '/ia' },
];

export function Sidebar() {
  const { user, logout } = useAuthStore();
  const router = useRouter();
  const modulos: string[] = user?.suscripcion?.modulos ?? [];

  const handleLogout = async () => {
    try {
      await api.post('/auth/logout');
    } catch {}
    logout();
    router.push('/login');
  };

  return (
    <aside className="w-64 bg-white border-r h-screen flex flex-col">
      <div className="p-4 border-b">
        <span className="font-bold text-lg text-blue-700">OperaAI</span>
        <p className="text-xs text-gray-500 mt-0.5 truncate">{user?.empresa?.razon_social}</p>
      </div>

      <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
        <Link href="/dashboard" className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100">
          Dashboard
        </Link>

        <p className="text-xs font-medium text-gray-400 uppercase px-3 mt-4 mb-1">Módulos</p>

        {ALL_MODULES.map((mod) => {
          const activo = modulos.includes(mod.key);
          return activo ? (
            <Link
              key={mod.key}
              href={mod.href}
              className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100"
            >
              {mod.label}
            </Link>
          ) : (
            <div
              key={mod.key}
              className="flex items-center justify-between px-3 py-2 rounded-md text-sm text-gray-400"
            >
              <span>🔒 {mod.label}</span>
              <Link href="/configuracion/plan" className="text-xs text-blue-600 hover:underline">
                Mejorar
              </Link>
            </div>
          );
        })}

        <p className="text-xs font-medium text-gray-400 uppercase px-3 mt-4 mb-1">Configuración</p>
        <Link href="/configuracion/empresa" className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100">
          Empresa
        </Link>
        <Link href="/configuracion/plan" className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100">
          Plan y facturación
        </Link>
        <Link href="/configuracion/usuarios" className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100">
          Usuarios
        </Link>
      </nav>

      <div className="p-3 border-t">
        <Link href="/perfil" className="block px-3 py-2 rounded-md text-sm hover:bg-gray-100">
          {user?.nombre} ({user?.rol})
        </Link>
        <button
          onClick={handleLogout}
          className="w-full text-left px-3 py-2 rounded-md text-sm text-red-600 hover:bg-red-50"
        >
          Cerrar sesión
        </button>
      </div>
    </aside>
  );
}
