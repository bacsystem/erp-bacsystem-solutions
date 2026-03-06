import { create } from 'zustand'

interface SuperadminData {
  id: string
  nombre: string
  email: string
}

interface SuperadminAuthState {
  accessToken: string | null
  superadmin: SuperadminData | null
  setAccessToken: (token: string) => void
  setSuperadmin: (s: SuperadminData) => void
  logout: () => void
}

export const useSuperadminAuthStore = create<SuperadminAuthState>((set) => ({
  accessToken: null,
  superadmin: null,
  setAccessToken: (token) => set({ accessToken: token }),
  setSuperadmin: (superadmin) => set({ superadmin }),
  logout: () => set({ accessToken: null, superadmin: null }),
}))
