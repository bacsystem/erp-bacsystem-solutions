import { api } from '@/shared/lib/api';
import type { SuscripcionData } from '@/modules/core/suscripcion/get-suscripcion/use-suscripcion';

export interface UpgradePayload {
  plan_id: string;
  culqi_token: string;
}

export interface UpgradeResult extends Partial<SuscripcionData> {
  access_token?: string;
  user?: Record<string, unknown>;
}

export const upgradePlanApi = (data: UpgradePayload) =>
  api.post<{ data: UpgradeResult }>('/suscripcion/upgrade', data).then((r) => r.data.data);
