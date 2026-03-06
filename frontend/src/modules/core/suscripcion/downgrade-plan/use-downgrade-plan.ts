'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';

export function useDowngradePlan() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (planId: string) =>
      api.post<{ data: any }>('/suscripcion/downgrade', { plan_id: planId }).then((r) => r.data.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['suscripcion'] }),
  });
}
