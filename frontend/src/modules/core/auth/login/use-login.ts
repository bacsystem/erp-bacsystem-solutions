'use client';

import { useMutation } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/shared/stores/auth.store';
import { loginApi } from './login.api';
import type { LoginFormData } from './login.schema';

export function useLogin() {
  const router = useRouter();
  const { setAccessToken, setUser } = useAuthStore();

  return useMutation({
    mutationFn: (data: LoginFormData) => loginApi(data),
    onSuccess: (result) => {
      setAccessToken(result.access_token);
      setUser(result.user);

      if (result.user.suscripcion.redirect) {
        router.push(result.user.suscripcion.redirect);
      } else {
        router.push('/dashboard');
      }
    },
  });
}
