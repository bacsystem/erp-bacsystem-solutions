'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export function useActualizarRol() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, rol }: { id: string; rol: string }) =>
      api.put(`/usuarios/${id}/rol`, { rol }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['usuarios'] }),
  });
}
