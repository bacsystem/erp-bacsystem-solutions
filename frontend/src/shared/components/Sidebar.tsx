'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useState } from 'react';
import {
  BarChart2, CreditCard, FileText,
  Layers, Loader2, Lock, LogOut, ShoppingBag,
  Sparkles, User, Users, Wallet
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAuthStore } from '@/shared/stores/auth.store';
import { api } from '@/shared/lib/api';

const ALL_MODULES = [
  { key: 'facturacion',  label: 'Facturación',           href: '/facturacion',  Icon: FileText  },
  { key: 'clientes',     label: 'Clientes',               href: '/clientes',     Icon: Users     },
  { key: 'productos',    label: 'Productos',               href: '/productos',    Icon: ShoppingBag },
  { key: 'inventario',   label: 'Inventario',              href: '/inventario',   Icon: Layers    },
  { key: 'crm',          label: 'CRM',                     href: '/crm',          Icon: BarChart2 },
  { key: 'finanzas',     label: 'Finanzas',                href: '/finanzas',     Icon: Wallet    },
  { key: 'ia',           label: 'Inteligencia Artificial', href: '/ia',           Icon: Sparkles  },
];

const ROL_LABEL: Record<string, string> = {
  owner:    'Propietario',
  admin:    'Administrador',
  empleado: 'Empleado',
  contador: 'Contador',
};

const ROL_COLOR: Record<string, string> = {
  owner:    'bg-purple-100 text-purple-700',
  admin:    'bg-blue-100 text-blue-700',
  empleado: 'bg-gray-100 text-gray-600',
  contador: 'bg-amber-100 text-amber-700',
};

const AVATAR_COLORS = [
  'bg-blue-500', 'bg-violet-500', 'bg-emerald-500', 'bg-rose-500',
  'bg-amber-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-pink-500',
];

function avatarBg(name: string) {
  let h = 0;
  for (let i = 0; i < name.length; i++) h = (h * 31 + (name.codePointAt(i) ?? 0)) & 0xffff;
  return AVATAR_COLORS[h % AVATAR_COLORS.length];
}

export function Sidebar() {
  const { user, logout } = useAuthStore();
  const router   = useRouter();
  const pathname = usePathname();
  const modulos: string[] = user?.suscripcion?.modulos ?? [];

  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await api.post('/auth/logout');
    } catch { /* sesión ya expirada o sin red — seguimos */ }
    logout();
    document.cookie = 'has_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    router.push('/login');
  };

  const navLink = (href: string) =>
    `flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors ${
      pathname === href
        ? 'bg-blue-50 text-blue-700 font-medium'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
    }`;

  const initials = user?.nombre?.trim().charAt(0).toUpperCase() ?? '?';
  const rol = user?.rol ?? 'empleado';

  return (
    <aside className="w-64 bg-white border-r h-screen flex flex-col">

      {/* Logo */}
      <div className="px-5 py-4 border-b flex items-center gap-2.5">
        <div className="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
          <Sparkles className="w-4 h-4 text-white" />
        </div>
        <div className="min-w-0">
          <span className="font-bold text-base text-gray-900 leading-none">OperaAI</span>
          <p className="text-[11px] text-gray-400 truncate mt-0.5">{user?.empresa?.razon_social}</p>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <Link href="/dashboard" className={navLink('/dashboard')}>
          <BarChart2 className="w-4 h-4 shrink-0" />
          Dashboard
        </Link>

        <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-3 pt-4 pb-1">
          Módulos
        </p>

        {ALL_MODULES.map(({ key, label, href, Icon }) => {
          const activo = modulos.includes(key);
          return activo ? (
            <Link key={key} href={href} className={navLink(href)}>
              <Icon className="w-4 h-4 shrink-0" />
              {label}
            </Link>
          ) : (
            <div
              key={key}
              className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-400 cursor-default"
            >
              <Lock className="w-4 h-4 shrink-0" />
              <span className="flex-1 truncate">{label}</span>
              <Link
                href="/configuracion/plan"
                className="text-[10px] font-medium text-blue-500 hover:text-blue-700 shrink-0"
              >
                Mejorar
              </Link>
            </div>
          );
        })}

        <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-3 pt-4 pb-1">
          Configuración
        </p>
        <Link href="/configuracion/empresa"  className={navLink('/configuracion/empresa')}>
          <Users className="w-4 h-4 shrink-0" />
          Empresa
        </Link>
        <Link href="/configuracion/plan"     className={navLink('/configuracion/plan')}>
          <CreditCard className="w-4 h-4 shrink-0" />
          Plan y facturación
        </Link>
        <Link href="/configuracion/usuarios" className={navLink('/configuracion/usuarios')}>
          <Users className="w-4 h-4 shrink-0" />
          Usuarios
        </Link>
      </nav>

      {/* User section */}
      <div className="px-3 py-3 border-t">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <button className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group text-left">
              <div className={`w-8 h-8 rounded-full ${avatarBg(user?.nombre ?? '')} text-white flex items-center justify-center text-sm font-semibold shrink-0`}>
                {initials}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate leading-none">{user?.nombre}</p>
                <span className={`inline-block mt-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full ${ROL_COLOR[rol] ?? ROL_COLOR.empleado}`}>
                  {ROL_LABEL[rol] ?? rol}
                </span>
              </div>
              <div className="w-4 h-4 shrink-0 flex flex-col gap-0.5 items-center justify-center opacity-40 group-hover:opacity-70">
                <span className="w-3.5 h-px bg-gray-600 rounded" />
                <span className="w-3.5 h-px bg-gray-600 rounded" />
                <span className="w-3.5 h-px bg-gray-600 rounded" />
              </div>
            </button>
          </DropdownMenuTrigger>

          <DropdownMenuContent side="top" align="start" className="w-56 mb-1">
            <DropdownMenuLabel className="font-normal">
              <p className="text-sm font-medium text-gray-900 truncate">{user?.nombre}</p>
              <p className="text-xs text-gray-500 truncate">{user?.email}</p>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <Link href="/perfil" className="flex items-center gap-2 cursor-pointer">
                <User className="w-4 h-4" />
                Mi perfil
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => setShowConfirm(true)}
              className="flex items-center gap-2 text-red-600 focus:text-red-600 focus:bg-red-50 cursor-pointer"
            >
              <LogOut className="w-4 h-4" />
              Cerrar sesión
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* Modal de confirmación de cierre de sesión */}
      {showConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={() => !isLoggingOut && setShowConfirm(false)} />
          <div className="relative bg-white rounded-2xl shadow-xl p-6 w-80 flex flex-col gap-4">
            <div className="flex items-center justify-center w-12 h-12 rounded-full bg-red-50 mx-auto">
              <LogOut className="w-5 h-5 text-red-500" />
            </div>
            <div className="text-center">
              <p className="font-semibold text-gray-900">¿Cerrar sesión?</p>
              <p className="text-sm text-gray-500 mt-1">Se cerrará tu sesión en este dispositivo.</p>
            </div>
            <div className="flex gap-3">
              <button
                onClick={() => setShowConfirm(false)}
                disabled={isLoggingOut}
                className="flex-1 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                Cancelar
              </button>
              <button
                onClick={handleLogout}
                disabled={isLoggingOut}
                className="flex-1 px-4 py-2 rounded-lg bg-red-600 text-sm font-medium text-white hover:bg-red-700 transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
              >
                {isLoggingOut ? <Loader2 className="w-4 h-4 animate-spin" /> : <LogOut className="w-4 h-4" />}
                {isLoggingOut ? 'Saliendo…' : 'Cerrar sesión'}
              </button>
            </div>
          </div>
        </div>
      )}
    </aside>
  );
}
