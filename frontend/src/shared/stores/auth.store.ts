import { create } from 'zustand'
import type { UserPayload } from '@/shared/types'

interface AuthState {
  accessToken: string | null
  user: UserPayload | null
  setAccessToken: (token: string) => void
  setUser: (user: UserPayload) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>((set) => ({
  accessToken: null,
  user: null,
  setAccessToken: (token) => set({ accessToken: token }),
  setUser: (user) => set({ user }),
  logout: () => set({ accessToken: null, user: null }),
}))
