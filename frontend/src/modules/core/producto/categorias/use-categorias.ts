import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { categoriasApi } from '../shared/productos.api'
import type { Categoria } from '../shared/producto.types'

export function useCategorias() {
  return useQuery({
    queryKey: ['categorias'],
    queryFn: categoriasApi.listar,
  })
}

export function useCrearCategoria() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: categoriasApi.crear,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['categorias'] }),
  })
}

export function useActualizarCategoria() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Categoria> }) =>
      categoriasApi.actualizar(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['categorias'] }),
  })
}

export function useEliminarCategoria() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: categoriasApi.eliminar,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['categorias'] }),
  })
}
