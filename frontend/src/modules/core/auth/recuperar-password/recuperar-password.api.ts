import { api } from '@/shared/lib/api';

export const recuperarPasswordApi = (email: string) =>
  api.post('/auth/recuperar-password', { email });

export const resetPasswordApi = (data: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}) => api.post('/auth/reset-password', data);
