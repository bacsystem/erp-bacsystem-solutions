import { z } from 'zod';

export const inviteUsuarioSchema = z.object({
  email: z.string().email('Email inválido'),
  rol: z.enum(['admin', 'empleado', 'contador'], {
    required_error: 'Selecciona un rol',
  }),
});

export type InviteUsuarioData = z.infer<typeof inviteUsuarioSchema>;
