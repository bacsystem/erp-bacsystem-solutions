import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { productosApi } from '../shared/productos.api'
import type { ProductosFilters } from '../shared/producto.types'

export function useProductos(filters: ProductosFilters = {}) {
  return useQuery({
    queryKey: ['productos', filters],
    queryFn: () => productosApi.listar(filters),
  })
}

export function useProductoDetalle(id: string) {
  return useQuery({
    queryKey: ['productos', id],
    queryFn: () => productosApi.obtener(id),
    enabled: !!id,
  })
}

export function useCrearProducto() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: productosApi.crear,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['productos'] }),
  })
}

export function useActualizarProducto(id: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Parameters<typeof productosApi.actualizar>[1]) =>
      productosApi.actualizar(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['productos'] }),
  })
}

export function useDesactivarProducto() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: productosApi.desactivar,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['productos'] }),
  })
}

export function useActivarProducto() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: productosApi.activar,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['productos'] }),
  })
}
