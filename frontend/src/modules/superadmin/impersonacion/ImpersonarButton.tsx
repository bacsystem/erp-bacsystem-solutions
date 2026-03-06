'use client'

import { useImpersonar } from './use-impersonacion'

interface Props {
  empresaId: string
}

export default function ImpersonarButton({ empresaId }: Props) {
  const { mutate, isPending } = useImpersonar()

  return (
    <button
      onClick={() => mutate(empresaId)}
      disabled={isPending}
      className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg"
    >
      {isPending ? 'Entrando...' : 'Entrar como esta empresa'}
    </button>
  )
}
