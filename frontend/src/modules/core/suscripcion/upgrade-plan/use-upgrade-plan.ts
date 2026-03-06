'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/shared/stores/auth.store';
import { upgradePlanApi, type UpgradePayload } from './upgrade-plan.api';

export function useUpgradePlan() {
  const queryClient = useQueryClient();
  const { setAccessToken, setUser } = useAuthStore();

  return useMutation({
    mutationFn: (data: UpgradePayload) => upgradePlanApi(data),
    onSuccess: (result) => {
      if (result?.access_token) {
        setAccessToken(result.access_token);
      }
      if (result?.user) {
        setUser(result.user);
      }
      queryClient.invalidateQueries({ queryKey: ['me'] });
      queryClient.invalidateQueries({ queryKey: ['suscripcion'] });
    },
  });
}
