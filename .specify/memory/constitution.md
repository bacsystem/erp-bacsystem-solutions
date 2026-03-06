<!--
SYNC IMPACT REPORT
==================
Version change: 1.0.0 → 2.0.0

Bump rationale: MAJOR — Proyecto renombrado de "erp-solutions" a "OperaAI".
Estructura completa reemplazada: los 7 principios genéricos fueron sustituidos
por reglas de producto específicas (arquitectura VSA, multi-tenancy, stack tech,
modelo de suscripción, contexto peruano, Definition of Done).

Modified principles (old → new):
  I.   Test-First Development            → Definition of Done (obligatorio por módulo)
  II.  Domain-Driven Design              → Vertical Slice Architecture
  III. Monolith-First Architecture       → Módulo Core/Auth como base bloqueante
  IV.  API Contract Clarity              → Respuestas API Estandarizadas + JWT obligatorio
  V.   Observability & Auditability      → AuditLogMiddleware + Log::error()
  VI.  Data Integrity & Consistency      → Multi-Tenancy (3 capas) + Modelos UUID
  VII. Simplicity & YAGNI                → Calidad de Código (reglas explícitas)

Added sections:
  - Visión del Producto
  - Stack Tecnológico (Backend, Frontend, Integraciones, Infraestructura)
  - Multi-Tenancy — Regla Crítica
  - Autenticación y Autorización
  - Modelo de Suscripción y Planes
  - Módulo Core / Auth — Responsabilidades
  - Modelos y Base de Datos
  - Naming Conventions
  - Respuestas API Estandarizadas
  - Definition of Done — Obligatorio por Módulo
  - Calidad de Código
  - Orden de Construcción de Módulos
  - Contexto Peruano
  - Gobernanza (nueva, requerida por speckit)

Removed sections:
  - Technology & Quality Standards (reemplazado por secciones específicas)
  - Development Workflow genérico (reemplazado por DoD + Orden de Módulos)

Templates requiring updates:
  - .specify/templates/plan-template.md  ✅ alineado — Constitution Check es dinámico
  - .specify/templates/spec-template.md  ✅ alineado — User Stories + FR + SC consistentes
  - .specify/templates/tasks-template.md ✅ alineado — TDD ordering respetado en DoD
  - .specify/templates/constitution-template.md ✅ sin cambios (template genérico)

Deferred TODOs: Ninguno.
-->

# OperaAI — Constitution

> Reglas no negociables que gobiernan todo el desarrollo.
> Todo código generado debe cumplir este documento al 100%.
> Este archivo es la fuente de verdad del proyecto OperaAI.

---

## 🎯 VISIÓN DEL PRODUCTO

OperaAI es un SaaS de gestión empresarial modular para PYMEs peruanas.
Reemplaza Excel, WhatsApp y sistemas desconectados con una plataforma
inteligente que integra facturación electrónica SUNAT, CRM, inventario,
RRHH, finanzas y un copiloto IA en español.

- **Mercado objetivo:** PYMEs peruanas de 1 a 200 empleados
- **Modelo de negocio:** Suscripción mensual por paquetes de módulos
- **Stack de monetización:** Culqi para pagos en Perú
- **Diferencial:** IA Copiloto en español + cumplimiento legal peruano nativo

---

## 🏗️ ARQUITECTURA

### Patrón principal: Vertical Slice Architecture

Cada funcionalidad vive completa y aislada en su propio slice.

```
Módulo/
└── NombreAccion/
    ├── NombreAccionController.php   # recibe HTTP, sin lógica
    ├── NombreAccionRequest.php      # valida entrada
    ├── NombreAccionService.php      # toda la lógica de negocio
    └── NombreAccionResponse.php     # formatea salida (opcional)
```

### Reglas de arquitectura

- Un slice = una sola acción del usuario
- Un slice NO importa clases de otro slice directamente
- Si dos slices comparten lógica → mueve a /Shared
- Las páginas Next.js son tontas: solo componen slices
- Los slices frontend son autocontenidos: Form + Hook + API + Schema

### Estructura de módulos

```
backend/app/
├── Modules/
│   ├── Core/          # Auth, Empresa, Usuario, Suscripcion
│   ├── Clientes/
│   ├── Productos/
│   ├── Facturacion/
│   ├── Inventario/
│   ├── RRHH/
│   ├── Finanzas/
│   └── IA/
└── Shared/            # BaseModel, Traits, Exceptions, Responses

frontend/src/
├── modules/           # slices por módulo
├── shared/            # componentes, hooks, lib compartidos
└── app/               # páginas Next.js (solo componen slices)
```

---

## 🛠️ STACK TECNOLÓGICO

### Backend

| Tecnología         | Versión | Uso                        |
|--------------------|---------|----------------------------|
| PHP                | 8.3     | Lenguaje principal         |
| Laravel            | 11.x    | Framework                  |
| PostgreSQL         | 16      | Base de datos principal    |
| Redis              | 7       | Cache + colas              |
| Laravel Sanctum    | latest  | Autenticación JWT          |
| Spatie Permission  | latest  | Roles y permisos           |
| Laravel DomPDF     | latest  | Generación de PDFs         |
| Guzzle             | latest  | HTTP client externo        |

### Frontend

| Tecnología       | Versión | Uso                        |
|------------------|---------|----------------------------|
| Next.js          | 14.x    | Framework React            |
| TypeScript       | 5.x     | Tipado estático            |
| Tailwind CSS     | 3.x     | Estilos                    |
| shadcn/ui        | latest  | Componentes UI             |
| React Query      | 5.x     | Cache + estado servidor    |
| Zustand          | 4.x     | Estado global cliente      |
| React Hook Form  | 7.x     | Formularios                |
| Zod              | 3.x     | Validación schemas         |
| Axios            | 1.x     | HTTP client                |

### Integraciones externas

| Servicio                        | Propósito                                    |
|---------------------------------|----------------------------------------------|
| Nubefact                        | OSE homologado SUNAT — facturación electrónica |
| Claude API (claude-sonnet-4-6)  | Copiloto IA en español                       |
| Culqi                           | Pagos con tarjeta en Perú                    |
| Meta Cloud API                  | Notificaciones WhatsApp                      |
| Resend                          | Emails transaccionales                       |
| Cloudflare R2                   | Storage PDFs y archivos                      |

### Infraestructura

| Entorno        | Servicio                          |
|----------------|-----------------------------------|
| Frontend       | Vercel                            |
| Backend        | Railway → AWS ECS Fargate         |
| Base de datos  | Supabase → AWS RDS PostgreSQL     |
| Cache          | Upstash Redis → AWS ElastiCache   |
| CI/CD          | GitHub Actions                    |

---

## 🔐 MULTI-TENANCY — REGLA CRÍTICA

### Principio fundamental

**Un usuario NUNCA puede ver ni modificar datos de otra empresa.**
Esta regla no tiene excepciones. Es la regla más importante del sistema.

### Implementación obligatoria en 3 capas

**Capa 1 — BaseModel (PHP)**

```php
// Todos los modelos extienden BaseModel
// empresa_id se asigna automáticamente desde el JWT
// Global Scope filtra SIEMPRE por empresa_id

abstract class BaseModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('empresa', function ($query) {
            if (auth()->check()) {
                $query->where('empresa_id', auth()->user()->empresa_id);
            }
        });
    }
}
```

**Capa 2 — TenantMiddleware**

```php
// empresa_id SIEMPRE viene del JWT verificado
// NUNCA del body, query params o headers del request
$empresa_id = auth()->user()->empresa_id; // ✅ correcto
$empresa_id = $request->empresa_id;       // ❌ PROHIBIDO
```

**Capa 3 — PostgreSQL Row Level Security**

```sql
ALTER TABLE clientes ENABLE ROW LEVEL SECURITY;
CREATE POLICY tenant_isolation ON clientes
    USING (empresa_id = current_setting('app.empresa_id')::uuid);
```

### Verificación obligatoria en cada slice

```php
// Al buscar un recurso por ID, SIEMPRE verificar empresa_id
$cliente = Cliente::where('id', $id)
    ->where('empresa_id', auth()->user()->empresa_id) // ← obligatorio
    ->firstOrFail();
```

---

## 🔑 AUTENTICACIÓN Y AUTORIZACIÓN

### JWT — Payload obligatorio

```json
{
  "sub": "uuid-usuario",
  "empresa_id": "uuid-empresa",
  "rol": "owner|admin|empleado|contador",
  "plan": "starter|pyme|enterprise",
  "modulos": ["facturacion", "clientes", "productos"],
  "exp": 1234567890
}
```

### Tokens

- Access token: duración 15 minutos
- Refresh token: duración 30 días, rotativo
- Al expirar el access token → usar refresh token para renovar
- Al expirar el refresh token → redirigir a login
- Refresh token se guarda en httpOnly Cookie (nunca en localStorage)
- Access token se guarda en memoria Zustand (nunca en localStorage)

### Roles y permisos

| Rol       | Acceso                                        |
|-----------|-----------------------------------------------|
| owner     | Total — incluyendo billing y configuración    |
| admin     | Total — excepto billing                       |
| empleado  | Solo módulos asignados, sin configuración     |
| contador  | Solo lectura en finanzas y facturación        |

### Respuestas de error de auth

- 401 → token inválido o expirado
- 402 → suscripción vencida o inactiva
- 403 → sin permisos para este módulo o acción

---

## 💰 MODELO DE SUSCRIPCIÓN Y PLANES

### Planes disponibles

| Plan       | Precio       | Usuarios  | Módulos incluidos                                               |
|------------|--------------|-----------|------------------------------------------------------------------|
| starter    | S/. 59/mes   | 3         | facturacion, clientes, productos                                 |
| pyme       | S/. 129/mes  | 15        | facturacion, clientes, productos, inventario, crm, finanzas, ia  |
| enterprise | S/. 299/mes  | ilimitado | todos los módulos incluyendo rrhh                                |

### Módulos y sus identificadores

| Identificador | Descripción                                  |
|---------------|----------------------------------------------|
| facturacion   | Boletas, facturas, notas de crédito/débito   |
| clientes      | Directorio, historial de compras             |
| productos     | Catálogo, categorías                         |
| inventario    | Stock, movimientos, alertas                  |
| crm           | Pipeline, oportunidades, actividades         |
| finanzas      | Flujo de caja, transacciones, dashboard      |
| rrhh          | Empleados, asistencia, planillas peruanas    |
| ia            | Copiloto, insights, resumen mensual          |

### Reglas de suscripción

- Todo tenant nuevo inicia con trial de 30 días
- El plan se elige en el momento del registro
- El trial incluye TODOS los módulos del plan elegido
- Sin datos de tarjeta durante el trial
- Al vencer el trial sin pago → estado `vencida` → solo lectura
- A los 7 días en estado `vencida` → estado `cancelada` → acceso bloqueado
- Datos conservados 90 días después de cancelación
- Pagos recurrentes mensuales vía Culqi
- Upgrade efectivo inmediatamente con pago prorrateado
- Downgrade efectivo al inicio del siguiente período

### Estados de suscripción

```
trial     → acceso completo, sin cobro, máximo 30 días
activa    → acceso completo, cobro mensual al día
vencida   → solo lectura, 7 días para regularizar
cancelada → acceso bloqueado, datos conservados 90 días
```

### Protección de módulos — dos capas obligatorias

1. **Frontend:** sidebar oculta módulos no contratados con 🔒 + botón "Mejorar plan"
2. **Backend:** CheckPlanMiddleware devuelve 403 si el módulo no está en el plan

### Emails automáticos de suscripción

- Día 25 del trial → recordatorio de vencimiento próximo
- Día 28 del trial → segundo recordatorio con CTA de pago
- Día 30 del trial → último aviso antes de restricción
- Al vencer → notificación de restricción a solo lectura
- Al pagar → confirmación de activación del plan

---

## 🏗️ MÓDULO CORE / AUTH — Responsabilidades

Este módulo es la base de todo el sistema.
Ningún otro módulo puede funcionar sin él.

### Qué incluye Core/Auth

**Gestión de empresa:**
- Registro de empresa con RUC, razón social, nombre comercial, dirección, régimen tributario
- Actualizar datos de la empresa
- Subir logo a Cloudflare R2

**Gestión de planes y suscripción:**
- Seeder de los 3 planes (starter, pyme, enterprise)
- Selección de plan en el registro
- Creación automática de suscripción trial al registrarse
- Página de gestión de plan (/configuracion/plan)
- Flujo de upgrade con pago Culqi
- Flujo de downgrade efectivo al siguiente período

**Autenticación:**
- Registro: empresa + usuario owner + suscripción trial
- Login: devuelve JWT con empresa_id + rol + plan + modulos[]
- Logout: invalida token actual
- Refresh token: renueva access token sin nuevo login
- Recuperar contraseña: email con link de reset

**Gestión de usuarios:**
- Invitar usuarios adicionales por email
- Asignar roles: owner, admin, empleado, contador
- Activar / desactivar usuarios
- Respetar límite de usuarios según plan

**Seguridad:**
- TenantMiddleware: extrae empresa_id del JWT en cada request
- CheckPlanMiddleware: verifica módulos habilitados por plan
- AuditLogMiddleware: registra acciones críticas con IP y timestamp

### Slices del módulo Core/Auth

**Backend — app/Modules/Core/**

```
Auth/
├── Register/
├── Login/
├── Logout/
├── RefreshToken/
└── RecuperarPassword/

Empresa/
├── GetEmpresa/
├── UpdateEmpresa/
└── UploadLogo/

Suscripcion/
├── GetSuscripcion/
├── UpgradePlan/
└── DowngradePlan/

Usuario/
├── GetProfile/
├── UpdateProfile/
├── InviteUsuario/
├── ListarUsuarios/
├── ActualizarRol/
└── DesactivarUsuario/

Models/
├── Empresa.php
├── Usuario.php
├── Plan.php
├── Suscripcion.php
└── AuditLog.php

Middleware/
├── TenantMiddleware.php
├── CheckPlanMiddleware.php
└── AuditLogMiddleware.php
```

**Frontend — src/modules/core/**

```
auth/
├── register/
├── login/
├── logout/
└── recuperar-password/

empresa/
├── get-empresa/
└── update-empresa/

suscripcion/
├── get-suscripcion/
├── upgrade-plan/
└── downgrade-plan/

usuario/
├── get-profile/
├── update-profile/
├── invite-usuario/
└── listar-usuarios/
```

**Páginas — src/app/**

```
(auth)/
├── login/page.tsx
├── register/page.tsx
└── recuperar-password/page.tsx

(dashboard)/
├── page.tsx                        → dashboard principal
└── configuracion/
    ├── empresa/page.tsx            → datos de la empresa
    ├── usuarios/page.tsx           → gestión de usuarios
    └── plan/page.tsx               → gestión de suscripción
```

### Migraciones del módulo Core/Auth

```
001_create_planes_table
002_create_empresas_table
003_create_suscripciones_table
004_create_usuarios_table
005_create_audit_logs_table
```

### Seeder obligatorio

```
PlanSeeder → crea los 3 planes (starter, pyme, enterprise)
             debe ejecutarse antes de cualquier registro
```

### Flujo de registro — 4 pasos

```
Paso 1 — Selección de plan
├── Muestra los 3 planes con precios y módulos incluidos
├── Plan recomendado: PYME (resaltado visualmente)
└── Botón "Empezar gratis 30 días" en cada plan

Paso 2 — Datos de la empresa
├── RUC (11 dígitos, validación de formato)
├── Razón social
├── Nombre comercial
├── Dirección
└── Régimen tributario (RER / RG / RMT)

Paso 3 — Datos del usuario owner
├── Nombre completo
├── Email (será el username)
├── Contraseña (mínimo 8 caracteres)
└── Confirmar contraseña

Paso 4 — Confirmación
├── Resumen del plan elegido
├── "30 días gratis, cancela cuando quieras"
├── Sin datos de tarjeta en el trial
└── Botón "Crear mi cuenta"
```

### Flujo de upgrade de plan

```
Usuario en /configuracion/plan
├── Ve su plan actual resaltado
├── Ve los módulos que le faltan en planes superiores
└── Click en "Cambiar a PYME" o "Cambiar a Enterprise"
        ↓
Modal de confirmación con precio prorrateado
        ↓
Formulario Culqi (tarjeta)
        ↓
Pago exitoso
        ↓
Backend actualiza suscripcion.plan_id + genera nuevo JWT
        ↓
Frontend actualiza store + sidebar al instante
        ↓
Toast: "¡Plan actualizado! Ya tienes acceso a X módulos nuevos"
```

---

## 📦 MODELOS Y BASE DE DATOS

### Reglas de modelos

- UUID como primary key en TODOS los modelos (nunca integer)
- empresa_id presente en TODOS los modelos (excepto planes)
- timestamps (created_at, updated_at) en todos los modelos
- Soft deletes donde el dato tiene valor histórico
- Nunca borrar físicamente: facturas, comprobantes, empleados

### Naming de base de datos

- Tablas en snake_case plural: `comprobantes`, `items_comprobante`
- Columnas en snake_case: `empresa_id`, `razon_social`
- Foreign keys: `tabla_id` → `empresa_id`, `cliente_id`
- Índices obligatorios en: empresa_id, created_at, campos de búsqueda frecuente

### Migraciones

- Una migración por tabla
- Numeradas secuencialmente: `2024_01_01_000001_`
- Siempre incluir método `down()` para rollback
- Nunca modificar una migración ya ejecutada en producción

---

## 🎨 NAMING CONVENTIONS

### Backend (PHP/Laravel)

```
Clases:          PascalCase     → CrearClienteService
Métodos:         camelCase      → emitirComprobante()
Variables:       camelCase      → $empresaId
Constantes:      UPPER_SNAKE    → MAX_INTENTOS_LOGIN
Tablas DB:       snake_case     → items_comprobante
Columnas DB:     snake_case     → empresa_id
Archivos:        PascalCase     → CrearClienteService.php
Slices:          Español        → CrearCliente, EmitirComprobante
```

### Frontend (TypeScript/React)

```
Componentes:     PascalCase     → CrearClienteForm.tsx
Hooks:           camelCase      → useCrearCliente.ts
Archivos API:    kebab-case     → crear-cliente.api.ts
Schemas:         kebab-case     → crear-cliente.schema.ts
Tipos:           kebab-case     → crear-cliente.types.ts
Carpetas:        kebab-case     → crear-cliente/
Variables:       camelCase      → empresaActiva
Constantes:      UPPER_SNAKE    → MAX_REINTENTOS
```

### Rutas API

```
Recursos:        kebab-case plural     → /api/comprobantes
Acciones:        verbos infinitivo     → /api/comprobantes/{id}/anular
Módulos:         prefijo claro         → /api/facturacion/comprobantes
```

---

## 🔄 RESPUESTAS API ESTANDARIZADAS

### Éxito

```json
{
  "success": true,
  "data": {},
  "message": "Cliente creado exitosamente",
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Error

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "ruc": ["El RUC debe tener 11 dígitos"],
    "email": ["El email ya está registrado"]
  }
}
```

### Implementación obligatoria

```php
// Todos los controllers usan ApiResponse
return ApiResponse::success($data, 'Cliente creado', 201);
return ApiResponse::error('Validación fallida', $errors, 422);
```

---

## ✅ DEFINITION OF DONE — OBLIGATORIO POR MÓDULO

Un módulo NO está terminado hasta cumplir TODOS estos criterios.
Sin excepción. Sin negociación.

### 1. Pruebas Backend

```bash
php artisan test --filter=NombreModulo
# Resultado esperado: PASS — 0 failures, 0 errors
```

- [ ] Feature Test por cada slice — happy path
- [ ] Feature Test por cada slice — casos de error (400, 401, 403, 404, 422)
- [ ] Test de tenant isolation: empresa A no puede ver ni modificar datos de empresa B
- [ ] Test de autorización por rol

### 2. Pruebas Frontend

- [ ] Cada formulario valida con Zod antes de enviar al backend
- [ ] Loading state visible durante llamadas a la API
- [ ] Error state manejado y mostrado al usuario en español
- [ ] Sin errores TypeScript: `npm run build` exitoso
- [ ] Sin warnings en consola del navegador

### 3. Integración Backend + Frontend

- [ ] Frontend consume exitosamente cada endpoint del módulo
- [ ] JWT adjunto en cada request (verificado en Network tab del browser)
- [ ] Errores 401 redirigen al login automáticamente
- [ ] Errores 422 muestran mensajes de validación en los campos
- [ ] Errores 500 muestran mensaje genérico amigable al usuario

### 4. Prueba E2E Manual

- [ ] Flujo completo ejecutado en navegador desde cero
- [ ] Probado con empresa A → verifica sus datos
- [ ] Probado con empresa B → verifica SUS datos (no los de A)
- [ ] Sin errores en `storage/logs/laravel.log`
- [ ] Sin errores en consola del navegador

### 5. Cierre del módulo

- [ ] `php artisan test` → todos en verde
- [ ] `npm run build` → sin errores TypeScript
- [ ] `php artisan route:list` → rutas del módulo visibles
- [ ] Endpoints probados manualmente en Postman/Insomnia
- [ ] `git commit -m "feat(modulo): módulo completado y probado"`

### Definition of Done adicional — Core/Auth

Además de los criterios generales, este módulo requiere:

- [ ] Registro completo funciona end-to-end en el browser
- [ ] Los 3 planes aparecen correctamente en la página de registro
- [ ] Trial de 30 días se crea automáticamente al registrarse
- [ ] JWT contiene empresa_id, rol, plan y modulos[] correctos
- [ ] Sidebar muestra solo módulos del plan contratado
- [ ] Módulos no contratados muestran 🔒 y botón de upgrade
- [ ] CheckPlanMiddleware bloquea acceso con 403 desde el backend
- [ ] Tenant isolation verificado: empresa A no ve datos de empresa B
- [ ] Upgrade de plan actualiza módulos disponibles inmediatamente
- [ ] Límite de usuarios respetado según plan

---

## 📋 CALIDAD DE CÓDIGO

### Backend

- FormRequest para validación en CADA endpoint que recibe datos
- Try/catch en llamadas externas (SUNAT, Claude API, Culqi)
- Jobs para tareas asíncronas: emails, WhatsApp, notificaciones
- Events + Listeners para acciones secundarias al emitir comprobante
- Log::error() en excepciones con contexto útil
- No lógica de negocio en Controllers ni en Models

### Frontend

- No fetch directo — siempre usar el cliente axios configurado en `shared/lib/api.ts`
- No llamadas a la API fuera de archivos `.api.ts`
- No estado global para datos del servidor — usar React Query
- Zustand solo para: usuario autenticado, empresa activa, UI state
- Mensajes de error siempre en español peruano

### General

- No hardcodear URLs, keys o configuraciones — usar variables de entorno
- No comentarios obvios — el código debe ser autodescriptivo
- No dejar `console.log` ni `dd()` en código commiteado
- No funciones de más de 30 líneas — extraer a métodos privados

---

## 🚀 ORDEN DE CONSTRUCCIÓN DE MÓDULOS

Cada módulo DEBE pasar su Definition of Done completa antes de empezar el siguiente.

```
Módulo 1  →  Core / Auth
             Empresa, Usuario, Suscripción, Roles, Planes

Módulo 2  →  Clientes
             CRUD básico + historial de compras

Módulo 3  →  Productos
             Catálogo + categorías (sin stock aún)

Módulo 4  →  Facturación
             Boletas y facturas electrónicas SUNAT via Nubefact

Módulo 5  →  Inventario
             Stock + movimientos + alertas WhatsApp

Módulo 6  →  RRHH
             Empleados + asistencia + planilla peruana

Módulo 7  →  Finanzas
             Flujo de caja + transacciones + dashboard

Módulo 8  →  IA Copiloto
             Chat + insights + resumen mensual con Claude API
```

---

## 🌍 CONTEXTO PERUANO

### Facturación electrónica

- Comprobantes válidos: Boleta (B001), Factura (F001), Nota de Crédito, Nota de Débito
- IGV: 18% sobre el valor de venta
- Tipos de documento: DNI (8 dígitos), RUC (11 dígitos), CE
- OSE homologado: Nubefact (ya certificado ante SUNAT)
- Monedas aceptadas: PEN (soles) y USD (dólares)
- Anulación: baja ante SUNAT dentro de las 24 horas

### RRHH y planillas

- AFP: ONP (13%) o AFP privada (~10% + comisión variable)
- ESSALUD: 9% a cargo del empleador
- Gratificaciones: julio y diciembre (equivale a 1 sueldo cada una)
- CTS: mayo y noviembre (equivale a ~0.5 sueldos cada depósito)
- Vacaciones: 30 días calendario por año trabajado
- Exportar en formato PLAME para declaración ante SUNAT

### Regímenes tributarios

- RER: Régimen Especial de Renta
- RG: Régimen General
- RMT: Régimen MYPE Tributario

### Validaciones peruanas obligatorias

- RUC: exactamente 11 dígitos numéricos
- DNI: exactamente 8 dígitos numéricos
- Ubigeo: código de 6 dígitos del INEI
- Número de teléfono: formato peruano (+51 9XX XXX XXX)

---

## ⚖️ GOBERNANZA

Esta constitución es la fuente de verdad del proyecto OperaAI y prevalece sobre
cualquier otro documento en caso de conflicto.

**Procedimiento de enmienda:**
1. Proponer el cambio en un PR a `.specify/memory/constitution.md` con justificación.
2. Describir principios afectados y plan de migración para código existente.
3. Obtener aprobación de al menos un miembro adicional del equipo.
4. Incrementar `CONSTITUTION_VERSION` según política de versionado.
5. Ejecutar `/speckit.constitution` para propagar cambios a templates dependientes.

**Política de versionado:**
- MAJOR: Eliminación o redefinición incompatible de reglas/arquitectura.
- MINOR: Nueva sección o expansión material de reglas existentes.
- PATCH: Clarificaciones, correcciones de redacción, typos.

**Cumplimiento:** Todo PR DEBE incluir verificación explícita de cumplimiento con
esta constitución. PRs no conformes NO DEBEN fusionarse.

---

**Version**: 2.0.0 | **Ratified**: 2026-03-04 | **Last Amended**: 2026-03-04
