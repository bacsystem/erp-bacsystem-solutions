import { z } from 'zod';

export const empresaUpdateSchema = z.object({
  nombre_comercial: z.string().max(255).optional(),
  direccion: z.string().max(500).optional(),
  ubigeo: z.string().length(6).optional().or(z.literal('')),
  regimen_tributario: z.enum(['RER', 'RG', 'RMT']).optional(),
});

export type EmpresaUpdateData = z.infer<typeof empresaUpdateSchema>;
