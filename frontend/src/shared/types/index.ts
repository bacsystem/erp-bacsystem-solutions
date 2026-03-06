export interface SuscripcionPayload {
  plan: string
  estado: 'trial' | 'activa' | 'vencida' | 'cancelada'
  fecha_vencimiento?: string
  modulos: string[]
  redirect?: string
  fecha_cancelacion?: string
}

export interface EmpresaPayload {
  id: string
  razon_social: string
  nombre_comercial?: string | null
  ruc?: string
  logo_url: string | null
}

export interface UserPayload {
  id: string
  nombre: string
  email: string
  rol: 'owner' | 'admin' | 'empleado' | 'contador'
  empresa: EmpresaPayload
  suscripcion: SuscripcionPayload
}

export interface ApiError {
  success: false
  message: string
  errors?: Record<string, string[]>
}
