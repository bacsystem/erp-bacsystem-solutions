'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/shared/lib/api';
import type { SuscripcionData } from '@/modules/core/suscripcion/get-suscripcion/use-suscripcion';

export function useDowngradePlan() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (planId: string) =>
      api.post<{ data: SuscripcionData }>('/suscripcion/downgrade', { plan_id: planId }).then((r) => r.data.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['suscripcion'] }),
  });
}
