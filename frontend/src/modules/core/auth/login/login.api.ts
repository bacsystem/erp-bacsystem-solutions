import { api } from '@/shared/lib/api';
import type { LoginFormData } from './login.schema';
import type { UserPayload } from '@/shared/types';

export interface LoginResponse {
  access_token: string;
  user: UserPayload;
}

export const loginApi = (data: LoginFormData) =>
  api.post<{ data: LoginResponse }>('/auth/login', data).then((r) => r.data.data);
