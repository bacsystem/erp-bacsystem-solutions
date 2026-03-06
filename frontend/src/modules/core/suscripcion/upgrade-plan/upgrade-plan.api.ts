import { api } from '@/shared/lib/api';
import type { SuscripcionData } from '@/modules/core/suscripcion/get-suscripcion/use-suscripcion';

export interface UpgradePayload {
  plan_id: string;
  culqi_token: string;
}

export const upgradePlanApi = (data: UpgradePayload) =>
  api.post<{ data: SuscripcionData }>('/suscripcion/upgrade', data).then((r) => r.data.data);
