'use client';

import { useMutation } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/shared/stores/auth.store';
import { registerApi } from './register.api';
import type { RegisterFormData } from './register.schema';

export function useRegister() {
  const router = useRouter();
  const { setAccessToken, setUser } = useAuthStore();

  return useMutation({
    mutationFn: (data: RegisterFormData) => registerApi(data),
    onSuccess: (result) => {
      setAccessToken(result.access_token);
      setUser(result.user);
      router.push('/dashboard');
    },
  });
}
