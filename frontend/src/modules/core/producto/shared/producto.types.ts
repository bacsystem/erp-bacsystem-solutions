export interface Categoria {
  id: string
  nombre: string
  descripcion: string | null
  categoria_padre_id: string | null
  activo: boolean
  hijos?: Categoria[]
}

export interface ProductoImagen {
  id: string
  url: string
  orden: number
}

export interface PrecioLista {
  id: string
  lista: 'L1' | 'L2' | 'L3'
  nombre_lista: string
  precio: number
}

export interface Promocion {
  id: string
  nombre: string
  tipo: 'porcentaje' | 'monto_fijo'
  valor: number
  fecha_inicio: string
  fecha_fin: string | null
  activo: boolean
}

export interface PrecioHistorial {
  id: string
  precio_anterior: number
  precio_nuevo: number
  usuario_id: string | null
  created_at: string
}

export interface ProductoComponente {
  id?: string
  componente_id: string
  cantidad: number
  producto?: Producto
}

export interface Producto {
  id: string
  empresa_id: string
  categoria_id: string
  categoria: Categoria | null
  nombre: string
  descripcion: string | null
  sku: string
  codigo_barras: string | null
  tipo: 'simple' | 'compuesto' | 'servicio'
  unidad_medida_principal: string
  precio_compra: number | null
  precio_venta: number
  igv_tipo: 'gravado' | 'exonerado' | 'inafecto'
  activo: boolean
  imagenes?: ProductoImagen[]
  precios_lista?: PrecioLista[]
  unidades?: unknown[]
  componentes?: ProductoComponente[]
  promocion_activa?: Promocion[]
  historial_precios?: PrecioHistorial[]
  created_at?: string
}

export interface ProductosFilters {
  q?: string
  categoria_id?: string
  tipo?: string
  estado?: 'activo' | 'inactivo'
  precio_min?: number
  precio_max?: number
  sort?: string
  order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface PaginatedProductos {
  data: Producto[]
  meta: {
    page: number
    per_page: number
    total: number
  }
}
