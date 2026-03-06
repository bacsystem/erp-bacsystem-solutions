'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export function useDesactivarUsuario() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => api.put(`/usuarios/${id}/desactivar`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['usuarios'] }),
  });
}
