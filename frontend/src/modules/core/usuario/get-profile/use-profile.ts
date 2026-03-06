'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

const fetchProfile = () =>
  api.get<{ data: any }>('/me').then((r) => r.data.data);

export function useProfile() {
  return useQuery({ queryKey: ['me'], queryFn: fetchProfile });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: { nombre?: string; password_actual?: string; password?: string; password_confirmation?: string }) =>
      api.put<{ data: any }>('/me', data).then((r) => r.data.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['me'] }),
  });
}
