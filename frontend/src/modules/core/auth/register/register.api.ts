import { api } from '@/shared/lib/api';
import type { RegisterFormData } from './register.schema';
import type { UserPayload } from '@/shared/types';

export interface Plan {
  id: string;
  nombre: string;
  nombre_display: string;
  precio_mensual: number;
  max_usuarios: number | null;
  modulos: string[];
  recomendado: boolean;
}

export interface RegisterResponse {
  access_token: string;
  user: UserPayload;
}

export const getPlanes = () =>
  api.get<{ data: Plan[] }>('/planes').then((r) => r.data.data);

export const registerApi = (data: RegisterFormData) =>
  api.post<{ data: RegisterResponse }>('/auth/register', data).then((r) => r.data.data);
