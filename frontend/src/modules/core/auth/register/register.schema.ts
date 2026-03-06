import { z } from 'zod';

export const planSelectionSchema = z.object({
  plan_id: z.string().uuid('Selecciona un plan válido'),
});

export const empresaSchema = z.object({
  razon_social: z.string().min(3, 'Mínimo 3 caracteres').max(200),
  ruc: z
    .string()
    .length(11, 'El RUC debe tener 11 dígitos')
    .regex(/^\d{11}$/, 'Solo dígitos'),
  regimen_tributario: z.enum(['RER', 'RG', 'RMT'], {
    required_error: 'Selecciona un régimen',
  }),
});

export const ownerSchema = z.object({
  nombre: z.string().min(2, 'Mínimo 2 caracteres').max(255),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string(),
}).refine((d) => d.password === d.password_confirmation, {
  message: 'Las contraseñas no coinciden',
  path: ['password_confirmation'],
});

export const registerSchema = planSelectionSchema
  .merge(z.object({ empresa: empresaSchema, owner: ownerSchema }));

export type RegisterFormData = z.infer<typeof registerSchema>;
export type PlanSelectionData = z.infer<typeof planSelectionSchema>;
export type EmpresaRegisterData = z.infer<typeof empresaSchema>;
export type OwnerData = z.infer<typeof ownerSchema>;
