import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SuperadminData {
  id: string
  nombre: string
  email: string
}

interface SuperadminAuthState {
  accessToken: string | null
  superadmin: SuperadminData | null
  _hasHydrated: boolean
  setHasHydrated: (val: boolean) => void
  setAccessToken: (token: string) => void
  setSuperadmin: (s: SuperadminData) => void
  logout: () => void
}

export const useSuperadminAuthStore = create<SuperadminAuthState>()(
  persist(
    (set) => ({
      accessToken: null,
      superadmin: null,
      _hasHydrated: false,
      setHasHydrated: (val) => set({ _hasHydrated: val }),
      setAccessToken: (token) => set({ accessToken: token }),
      setSuperadmin: (superadmin) => set({ superadmin }),
      logout: () => set({ accessToken: null, superadmin: null }),
    }),
    {
      name: 'superadmin-auth',
      // Solo persiste el token y datos del superadmin, no el flag de hidratación
      partialize: (state) => ({
        accessToken: state.accessToken,
        superadmin: state.superadmin,
      }),
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true)
      },
    },
  ),
)
