import { api } from '@/shared/lib/api';

export interface UpgradePayload {
  plan_id: string;
  culqi_token: string;
}

export const upgradePlanApi = (data: UpgradePayload) =>
  api.post<{ data: any }>('/suscripcion/upgrade', data).then((r) => r.data.data);
