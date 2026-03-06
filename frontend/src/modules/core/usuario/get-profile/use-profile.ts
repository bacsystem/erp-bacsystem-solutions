'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export interface ProfileData {
  id: string;
  nombre: string;
  email: string;
  rol: string;
  empresa: { razon_social: string } | null;
}

const fetchProfile = () =>
  api.get<{ data: ProfileData }>('/me').then((r) => r.data.data);

export function useProfile() {
  return useQuery({ queryKey: ['me'], queryFn: fetchProfile });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { nombre?: string; password_actual?: string; password?: string; password_confirmation?: string }) =>
      api.put<{ data: ProfileData }>('/me', data).then((r) => r.data.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['me'] }),
  });
}
