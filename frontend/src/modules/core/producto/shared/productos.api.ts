import { api } from '@/shared/lib/api'
import type {
  Categoria,
  PaginatedProductos,
  Producto,
  ProductosFilters,
} from './producto.types'

// ─── Productos ───────────────────────────────────────────────────────────────

export const productosApi = {
  listar: (filters: ProductosFilters = {}) =>
    api
      .get<{ data: Producto[]; meta: PaginatedProductos['meta'] }>('/productos', {
        params: filters,
      })
      .then((r) => r.data),

  obtener: (id: string) =>
    api.get<{ data: Producto }>(`/productos/${id}`).then((r) => r.data.data),

  crear: (data: Partial<Producto>) =>
    api.post<{ data: Producto }>('/productos', data).then((r) => r.data.data),

  actualizar: (id: string, data: Partial<Omit<Producto, 'id' | 'sku'>>) =>
    api.put<{ data: Producto }>(`/productos/${id}`, data).then((r) => r.data.data),

  desactivar: (id: string) =>
    api.delete<{ data: Producto }>(`/productos/${id}`).then((r) => r.data.data),

  activar: (id: string) =>
    api.patch<{ data: Producto }>(`/productos/${id}/activar`).then((r) => r.data.data),

  subirImagen: (id: string, file: File) => {
    const form = new FormData()
    form.append('imagen', file)
    return api
      .post<{ data: { url: string } }>(`/productos/${id}/imagenes`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((r) => r.data.data)
  },

  eliminarImagen: (productoId: string, imagenId: string) =>
    api.delete(`/productos/${productoId}/imagenes/${imagenId}`),

  exportarExcel: (params: { formato?: string; categoria_id?: string } = {}) =>
    api.get('/productos/exportar', {
      params,
      responseType: 'blob',
    }),

  exportarPDF: () =>
    api.get('/productos/exportar/pdf', { responseType: 'blob' }),

  importarPreview: (file: File) => {
    const form = new FormData()
    form.append('archivo', file)
    return api
      .post<{
        data: {
          import_token: string
          total: number
          validos: number
          errores: number
          filas: { fila: number; datos: Record<string, string>; errores: string[]; valido: boolean }[]
        }
      }>('/productos/importar', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((r) => r.data.data)
  },

  importarConfirmar: (importToken: string) =>
    api
      .post<{ data: { creados: number } }>('/productos/importar', {
        import_token: importToken,
      })
      .then((r) => r.data.data),

  importarTemplate: () =>
    api.get('/productos/importar/template', { responseType: 'blob' }),

  getQR: (id: string) =>
    api.get(`/productos/${id}/qr`, { responseType: 'blob' }),
}

// ─── Categorías ──────────────────────────────────────────────────────────────

export const categoriasApi = {
  listar: () =>
    api.get<{ data: Categoria[] }>('/categorias').then((r) => r.data.data),

  crear: (data: Pick<Categoria, 'nombre' | 'descripcion' | 'categoria_padre_id'>) =>
    api.post<{ data: Categoria }>('/categorias', data).then((r) => r.data.data),

  actualizar: (id: string, data: Partial<Categoria>) =>
    api.put<{ data: Categoria }>(`/categorias/${id}`, data).then((r) => r.data.data),

  eliminar: (id: string) => api.delete(`/categorias/${id}`),
}
