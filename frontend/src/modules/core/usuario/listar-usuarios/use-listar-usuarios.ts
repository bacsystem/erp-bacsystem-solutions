'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export interface UsuarioItem {
  id: string;
  nombre: string;
  email: string;
  rol: string;
  activo: boolean;
  last_login: string | null;
}

export interface InvitacionItem {
  id: string;
  email: string;
  rol: string;
  expires_at: string;
}

export interface UsuariosData {
  usuarios: UsuarioItem[];
  invitaciones: InvitacionItem[];
}

const fetchUsuarios = () =>
  api.get<{ data: UsuariosData }>('/usuarios').then((r) => r.data.data);

export function useListarUsuarios() {
  return useQuery({ queryKey: ['usuarios'], queryFn: fetchUsuarios });
}
