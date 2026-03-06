# Tasks: Módulo 0 — Superadmin OperaAI

**Input**: Design documents from `/specs/000-superadmin/`
**Branch**: `000-superadmin`
**Generated**: 2026-03-06
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Data Model**: [data-model.md](./data-model.md)

**Tests**: Incluidos — requeridos por Definition of Done. Feature test por slice, happy path + error cases. Aislamiento superadmin/tenant verificado en Phase 9.

## Format: `[ID] [P?] [HU?] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias incompletas)
- **[HU]**: A qué historia de usuario pertenece la tarea (HU-01..HU-07)
- Rutas relativas a la raíz del repositorio

---

## Phase 1: Setup superadmin

**Purpose**: Infraestructura base del módulo. Bloquea todas las fases siguientes.

**⚠️ CRÍTICO**: Ninguna HU puede comenzar hasta completar esta fase.

### Migraciones

- [X] T001 Crear migración `backend/database/migrations/2026_03_06_000001_create_superadmins_table.php` con schema según plan.md §5 — uuid PK, nombre, email UNIQUE, password, activo, last_login nullable, timestamps
- [X] T002 Crear migración `backend/database/migrations/2026_03_06_000002_create_impersonation_logs_table.php` con schema según data-model.md — FK superadmins, FK empresas, token_hash, started_at, ended_at nullable, ip, índices
- [X] T003 [P] Crear migración `backend/database/migrations/2026_03_06_000003_create_descuentos_tenant_table.php` con schema según data-model.md — FK empresas, FK superadmins, tipo varchar(15), valor decimal, motivo, activo, timestamps, índice compuesto [empresa_id, activo]
- [X] T004 Crear migración `backend/database/migrations/2026_03_06_000004_add_superadmin_id_to_audit_logs.php` — agrega columna `superadmin_id uuid nullable FK → superadmins` con manejo de RLS igual al patrón de `empresa_id` nullable: drop policy, ALTER COLUMN, recrear policy
- [X] T005 Ejecutar `php artisan migrate` y verificar las 4 nuevas tablas

### Seeder

- [X] T006 Implementar `backend/database/seeders/SuperadminSeeder.php` usando `firstOrCreate` con email de `env('SUPERADMIN_EMAIL')` y password bcrypt de `env('SUPERADMIN_PASSWORD')` según plan.md §10
- [X] T007 Agregar `SuperadminSeeder` a `DatabaseSeeder::run()` y ejecutar `php artisan db:seed --class=SuperadminSeeder`; agregar `SUPERADMIN_EMAIL`, `SUPERADMIN_NOMBRE`, `SUPERADMIN_PASSWORD` a `.env` y `.env.example`

### Modelo

- [X] T008 Crear `backend/app/Modules/Superadmin/Models/Superadmin.php`: extiende `Authenticatable` (NO `BaseModel`), usa `HasApiTokens`, uuid PK no auto-increment, fillable, hidden password, casts, hook `creating` para UUID automático según plan.md §2

### Middleware y routing

- [X] T009 Implementar `backend/app/Shared/Middleware/SuperadminMiddleware.php`: verifica que `auth('sanctum')->user()` sea instancia de `Superadmin`, verifica `activo=true`, retorna 403/401 según caso según plan.md §4
- [X] T010 Registrar alias `'superadmin' => SuperadminMiddleware::class` en `backend/bootstrap/app.php`
- [X] T011 Crear `backend/routes/superadmin.php` con skeleton: grupo público para login + grupo protegido con `['auth:sanctum', 'superadmin']` para todas las rutas según plan.md §12
- [X] T012 Registrar `routes/superadmin.php` en `backend/bootstrap/app.php` con `then:` callback, prefijo `superadmin/api`, middleware group `api` según plan.md §1
- [X] T013 Agregar rate limiter `superadmin-login` (3/15min) en `AppServiceProvider::boot()` y variable `SUPERADMIN_LOGIN_RATE_LIMIT=3` en `phpunit.xml` según plan.md §6

### Helper de tests

- [X] T014 Crear `backend/tests/Feature/Superadmin/Helpers/SuperadminHelper.php` trait con método `actingAsSuperadmin(): array` que crea un `Superadmin` factory + token Sanctum de 4 horas y retorna `[$superadmin, $token]`
- [X] T015 [P] Crear `backend/database/factories/SuperadminFactory.php` con faker para nombre, email unique, password bcrypt, activo=true

---

## Phase 2: HU-01 Login superadmin

**Purpose**: Autenticación del superadmin — prerequisito de todas las demás HUs.

- [X] T016 [HU-01] Escribir `backend/tests/Feature/Superadmin/Auth/LoginSuperadminTest.php` con tests: login válido retorna token 4h, password incorrecto retorna 401, rate limit 3 intentos retorna 429, superadmin inactivo retorna 401, token tenant en ruta superadmin retorna 403
- [X] T017 [HU-01] Implementar `backend/app/Modules/Superadmin/Auth/Login/LoginSuperadminRequest.php`: valida email y password
- [X] T018 [HU-01] Implementar `backend/app/Modules/Superadmin/Auth/Login/LoginSuperadminService.php`: busca superadmin por email, verifica Hash::check, verifica activo, actualiza last_login, crea token Sanctum con expiry=4h, registra audit_log accion=superadmin_login (sin empresa_id), retorna token + datos superadmin
- [X] T019 [HU-01] Implementar `backend/app/Modules/Superadmin/Auth/Login/LoginSuperadminController.php`: invocable, llama al servicio, retorna ApiResponse::success con token
- [X] T020 [HU-01] Implementar `backend/app/Modules/Superadmin/Auth/Logout/LogoutSuperadminService.php` y `LogoutSuperadminController.php`: elimina todos los tokens del superadmin, registra audit_log
- [X] T021 [HU-01] Ejecutar `php artisan test --filter=LoginSuperadminTest` — todos los tests deben pasar
- [X] T022 [P] [HU-01] Crear `frontend/src/modules/superadmin/auth/superadmin-auth.store.ts`: Zustand store separado con `accessToken`, `superadmin`, `setAccessToken`, `setSuperadmin`, `logout` — NO compartir con el store tenant
- [X] T023 [P] [HU-01] Crear `frontend/src/modules/superadmin/auth/superadmin.api.ts`: cliente axios con `baseURL=/superadmin/api`, interceptor de token desde `superadmin-auth.store`
- [X] T024 [HU-01] Crear `frontend/src/modules/superadmin/auth/SuperadminLoginForm.tsx`: form con email + password, usa React Hook Form + Zod, muestra error de credenciales y de rate limit, redirige a `/superadmin/dashboard` al éxito
- [X] T025 [HU-01] Crear `frontend/src/app/(superadmin)/superadmin/login/page.tsx` que renderiza `SuperadminLoginForm`
- [X] T026 [HU-01] Actualizar `frontend/src/middleware.ts`: proteger rutas `/superadmin/*` verificando cookie/token superadmin, redirigir a `/superadmin/login` si no autenticado; las rutas superadmin NUNCA redirigen a `/login` tenant

---

## Phase 3: HU-02 Dashboard global

**Purpose**: Métricas globales del SaaS para el superadmin.

- [X] T027 [HU-02] Escribir `backend/tests/Feature/Superadmin/Dashboard/DashboardTest.php` con tests: MRR total correcto con empresas en múltiples planes, tasa de conversión calculada, churn del mes, mrr_historico con 6 puntos, todos los valores en 0 cuando no hay empresas
- [X] T028 [HU-02] Implementar `backend/app/Modules/Superadmin/Dashboard/DashboardService.php`: ejecuta `SET LOCAL app.empresa_id = ''` para bypasear RLS, calcula MRR (suma precio_mensual de suscripciones activas/trial), totales por estado, nuevos hoy/mes, tasa de conversión, churn, mrr_historico (6 meses con query agrupada por mes)
- [X] T029 [HU-02] Implementar `backend/app/Modules/Superadmin/Dashboard/DashboardController.php`: invocable, sin parámetros, retorna ApiResponse::success con payload completo
- [X] T030 [HU-02] Registrar ruta `GET /superadmin/api/dashboard` en `routes/superadmin.php`
- [X] T031 [HU-02] Ejecutar `php artisan test --filter=DashboardTest` — todos los tests deben pasar
- [X] T032 [HU-02] Crear `frontend/src/modules/superadmin/dashboard/use-dashboard.ts`: React Query hook `useGlobalDashboard()` que llama al endpoint
- [X] T033 [P] [HU-02] Crear `frontend/src/modules/superadmin/dashboard/MrrChart.tsx`: gráfico de línea con 6 puntos de MRR histórico (usar recharts o chart.js — instalar si no existe)
- [X] T034 [HU-02] Crear `frontend/src/modules/superadmin/dashboard/GlobalDashboard.tsx`: layout con cards (MRR total, activos, trial, vencidos, cancelados, nuevos hoy, conversión, churn) + `MrrChart`
- [X] T035 [HU-02] Crear `frontend/src/app/(superadmin)/superadmin/dashboard/page.tsx` que renderiza `GlobalDashboard`

---

## Phase 4: HU-03 Lista y detalle de empresas

**Purpose**: Visibilidad total de todos los tenants con búsqueda y filtros.

- [X] T036 [HU-03] Escribir `backend/tests/Feature/Superadmin/Empresas/ListarEmpresasTest.php` con tests: retorna todas las empresas sin filtro de tenant, filtro por plan funciona, filtro por estado funciona, búsqueda por nombre/RUC/email del owner funciona, filtro por fecha_desde/fecha_hasta funciona, paginación de 25
- [X] T037 [HU-03] Implementar `backend/app/Modules/Superadmin/Empresas/ListarEmpresas/ListarEmpresasService.php`: bypasea RLS con `SET LOCAL`, query builder con joins a suscripciones y usuarios (para email owner), aplica filtros opcionales (plan, estado, q, fecha_desde, fecha_hasta, sort, order), pagina de 25, retorna array con empresa + plan + estado + mrr + fecha_registro + ultimo_login_owner
- [X] T038 [HU-03] Implementar `backend/app/Modules/Superadmin/Empresas/ListarEmpresas/ListarEmpresasController.php`: invocable, recibe query params como request, retorna ApiResponse paginado
- [X] T039 [HU-03] Escribir `backend/tests/Feature/Superadmin/Empresas/GetEmpresaDetalleTest.php` con tests: retorna datos completos de empresa, incluye lista de usuarios, historial de suscripciones, últimos 50 audit_logs, métricas (mrr, días activo, total upgrades)
- [X] T040 [HU-03] Implementar `backend/app/Modules/Superadmin/Empresas/GetEmpresaDetalle/GetEmpresaDetalleService.php`: bypasea RLS, busca empresa por id, carga relaciones (usuarios, suscripciones con plan, audit_logs limit 50 desc), calcula métricas
- [X] T041 [HU-03] Implementar `backend/app/Modules/Superadmin/Empresas/GetEmpresaDetalle/GetEmpresaDetalleController.php`
- [X] T042 [HU-03] Registrar rutas `GET /superadmin/api/empresas` y `GET /superadmin/api/empresas/{empresa}` en `routes/superadmin.php`
- [X] T043 [HU-03] Ejecutar `php artisan test --filter="ListarEmpresasTest|GetEmpresaDetalleTest"` — todos los tests deben pasar
- [X] T044 [P] [HU-03] Crear `frontend/src/modules/superadmin/empresas/use-empresas.ts`: hook `useEmpresas(filters)` con React Query + parámetros de filtro; hook `useEmpresaDetalle(id)` para detalle
- [X] T045 [HU-03] Crear `frontend/src/modules/superadmin/empresas/EmpresasTable.tsx`: tabla con columnas (nombre, RUC, plan, estado, MRR, fecha registro, último login owner), barra de búsqueda, selects de filtro (plan, estado), paginación, link a detalle
- [X] T046 [HU-03] Crear `frontend/src/modules/superadmin/empresas/EmpresaDetalle.tsx`: layout con tabs (Datos, Usuarios, Suscripciones, Logs, Métricas)
- [X] T047 [HU-03] Crear `frontend/src/app/(superadmin)/superadmin/empresas/page.tsx` y `frontend/src/app/(superadmin)/superadmin/empresas/[id]/page.tsx`

---

## Phase 5: HU-04 Activar / Suspender tenants

**Purpose**: Control operativo de tenants por el superadmin.

- [X] T048 [HU-04] Escribir `backend/tests/Feature/Superadmin/Empresas/SuspenderEmpresaTest.php` con tests: empresa activa se suspende (suscripción → cancelada, tokens eliminados, audit_log creado), empresa ya suspendida retorna 422, login del owner después de suspensión retorna 401
- [X] T049 [HU-04] Implementar `backend/app/Modules/Superadmin/Empresas/SuspenderEmpresa/SuspenderEmpresaService.php`: bypasea RLS, verifica que la empresa no esté ya suspendida (422), cambia estado suscripción a `cancelada`, elimina todos los tokens Sanctum de los usuarios de esa empresa, registra `audit_log` con `superadmin_id`, `accion=superadmin_suspend`
- [X] T050 [HU-04] Implementar `backend/app/Modules/Superadmin/Empresas/SuspenderEmpresa/SuspenderEmpresaController.php`
- [X] T051 [HU-04] Escribir `backend/tests/Feature/Superadmin/Empresas/ActivarEmpresaTest.php` con tests: empresa suspendida se activa (suscripción → activa, audit_log creado, email enviado al owner), empresa ya activa retorna 422
- [X] T052 [HU-04] Implementar `backend/app/Modules/Superadmin/Empresas/ActivarEmpresa/ActivarEmpresaService.php`: verifica que la empresa esté suspendida (422), cambia estado a `activa`, envía email de reactivación al owner via `Mail::to($owner)->send(new ReactivacionMail())`, registra audit_log
- [X] T053 [P] [HU-04] Crear `backend/app/Shared/Mail/ReactivacionMail.php` y su vista `resources/views/emails/reactivacion.blade.php`
- [X] T054 [HU-04] Implementar `backend/app/Modules/Superadmin/Empresas/ActivarEmpresa/ActivarEmpresaController.php`
- [X] T055 [HU-04] Registrar rutas `POST /superadmin/api/empresas/{empresa}/suspender` y `POST /superadmin/api/empresas/{empresa}/activar` en `routes/superadmin.php`
- [X] T056 [HU-04] Ejecutar `php artisan test --filter="SuspenderEmpresaTest|ActivarEmpresaTest"` — todos los tests deben pasar
- [X] T057 [P] [HU-04] Crear `frontend/src/modules/superadmin/empresas/SuspenderModal.tsx`: modal de confirmación con motivo opcional, botón "Suspender empresa" rojo con spinner
- [X] T058 [P] [HU-04] Crear `frontend/src/modules/superadmin/empresas/ActivarModal.tsx`: modal de confirmación, botón "Reactivar empresa" verde con spinner
- [X] T059 [HU-04] Integrar botones Suspender/Activar en `EmpresaDetalle.tsx` con estado condicional según `suscripcion.estado`

---

## Phase 6: HU-05 Impersonar tenant

**Purpose**: Soporte técnico sin requerir credenciales del tenant.

- [X] T060 [HU-05] Escribir `backend/tests/Feature/Superadmin/Empresas/ImpersonarTest.php` con tests: impersonación exitosa retorna token temporal con abilities=['impersonated'], token expira en 2h, se guarda hash en impersonation_logs, empresa sin owner activo retorna 422, terminar impersonación invalida token y actualiza ended_at, audit_log creado en inicio y fin
- [X] T061 [HU-05] Implementar `backend/app/Modules/Superadmin/Empresas/Impersonar/ImpersonarService.php`: bypasea RLS, busca owner activo (422 si no existe), crea token Sanctum para ese owner con `abilities=['impersonated']` y `expires_at=now()->addHours(2)`, guarda `hash('sha256', $token)` en `impersonation_logs`, registra audit_log con `superadmin_id`
- [X] T062 [HU-05] Implementar `backend/app/Modules/Superadmin/Empresas/Impersonar/ImpersonarController.php`
- [X] T063 [HU-05] Implementar `backend/app/Modules/Superadmin/Empresas/Impersonar/TerminarImpersonacionService.php`: busca impersonation_log activo por `empresa_id + superadmin_id + ended_at IS NULL` (triple condición para evitar ambigüedad si el superadmin abrió múltiples impersonaciones en el pasado), retorna 404 si no hay sesión activa, elimina el token Sanctum via `PersonalAccessToken::where('id', ...)->delete()` usando el hash almacenado, actualiza `ended_at=now()`, registra audit_log `superadmin_impersonation_end`
- [X] T064 [HU-05] Implementar `backend/app/Modules/Superadmin/Empresas/Impersonar/TerminarImpersonacionController.php`
- [X] T065 [HU-05] Registrar rutas `POST` y `DELETE /superadmin/api/empresas/{empresa}/impersonar` en `routes/superadmin.php`
- [X] T066 [HU-05] Ejecutar `php artisan test --filter=ImpersonarTest` — todos los tests deben pasar
- [X] T067 [HU-05] Crear `frontend/src/modules/superadmin/impersonacion/use-impersonacion.ts`: mutación que llama a POST impersonar, guarda token temporal en store tenant, abre dashboard en nueva tab o ruta; mutación para terminar impersonación
- [X] T068 [HU-05] Crear `frontend/src/modules/superadmin/impersonacion/ImpersonacionBanner.tsx`: banner rojo sticky en la parte superior con mensaje "Estás viendo la cuenta de [Empresa] como superadmin", botón "Salir" que llama a DELETE impersonar y redirige al panel superadmin; visible SOLO cuando `abilities=['impersonated']` en el token
- [X] T069 [HU-05] Integrar `ImpersonacionBanner` en el layout del dashboard tenant (`src/app/(dashboard)/layout.tsx` si existe, o en cada página); el banner solo se muestra si `abilities` del token incluye `impersonated`
- [X] T070 [HU-05] Agregar botón "Entrar como esta empresa" en `EmpresaDetalle.tsx` que dispara la mutación de impersonación

---

## Phase 7: HU-06 Gestión de planes

**Purpose**: Control de precios y módulos del catálogo de planes.

- [X] T071 [HU-06] Escribir `backend/tests/Feature/Superadmin/Planes/UpdatePlanTest.php` con tests: precio actualizado correctamente, módulos actualizados correctamente, suscripciones existentes no cambian su MRR actual (solo nuevas), audit_log registrado con accion=superadmin_update_plan
- [X] T072 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/UpdatePlan/UpdatePlanRequest.php`: valida `precio_mensual` (numeric, >0) y `modulos` (array de strings) — ambos opcionales para permitir edición parcial
- [X] T073 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/UpdatePlan/UpdatePlanService.php`: actualiza solo los campos enviados en el request, registra audit_log con `datos_anteriores` y `datos_nuevos`
- [X] T074 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/UpdatePlan/UpdatePlanController.php`
- [X] T075 [HU-06] Escribir `backend/tests/Feature/Superadmin/Planes/DescuentoTest.php` con tests: descuento porcentaje creado correctamente, descuento monto_fijo creado, solo un descuento activo por empresa (desactiva el anterior al crear nuevo), desactivar descuento funciona, tenant ve descuento en GET /api/suscripcion
- [X] T076 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/Descuento/AplicarDescuentoRequest.php`: valida tipo (in:porcentaje,monto_fijo), valor (numeric, >0, ≤100 si porcentaje), motivo (string, required)
- [X] T077 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/Descuento/AplicarDescuentoService.php`: desactiva descuento activo existente si lo hay, crea nuevo descuento en `descuentos_tenant`, registra audit_log `superadmin_apply_discount`
- [X] T078 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/Descuento/DesactivarDescuentoService.php` y sus controllers
- [X] T079 [HU-06] Implementar `backend/app/Modules/Superadmin/Planes/ListarPlanes/ListarPlanesService.php`: retorna planes con precio, módulos, count de tenants activos y MRR por plan (query global sin RLS)
- [X] T080 [HU-06] Registrar rutas en `routes/superadmin.php`: `GET/PUT /planes/{plan}`, `POST /empresas/{empresa}/descuento`, `DELETE /empresas/{empresa}/descuento/{descuento}`
- [X] T081 [HU-06] Ejecutar `php artisan test --filter="UpdatePlanTest|DescuentoTest"` — todos los tests deben pasar
- [X] T082 [P] [HU-06] Crear `frontend/src/modules/superadmin/planes/use-planes.ts`: hooks `usePlanes()`, `useUpdatePlan()`, `useAplicarDescuento()`, `useDesactivarDescuento()`
- [X] T083 [P] [HU-06] Crear `frontend/src/modules/superadmin/planes/EditPlanModal.tsx`: modal con campos precio_mensual y checkboxes de módulos, submit con spinner
- [X] T084 [P] [HU-06] Crear `frontend/src/modules/superadmin/planes/DescuentoModal.tsx`: modal con selector tipo (porcentaje/monto_fijo), input valor, input motivo
- [X] T085 [HU-06] Crear `frontend/src/modules/superadmin/planes/PlanesManager.tsx`: tabla de planes con columnas (nombre, precio, módulos, tenants activos, MRR) y botón editar; botón "Aplicar descuento" disponible desde `EmpresaDetalle`
- [X] T086 [HU-06] Crear `frontend/src/app/(superadmin)/superadmin/planes/page.tsx`

---

## Phase 8: HU-07 Logs y actividad global

**Purpose**: Auditoría y compliance sobre toda la actividad del sistema.

- [X] T087 [HU-07] Escribir `backend/tests/Feature/Superadmin/Logs/LogsGlobalesTest.php` con tests: retorna logs de TODAS las empresas sin filtro, filtro por empresa_id funciona, filtro por accion funciona, filtro por fecha_desde/fecha_hasta funciona, logs con empresa_id=null incluidos en resultados, paginación de 50, orden created_at DESC
- [X] T088 [HU-07] Implementar `backend/app/Modules/Superadmin/Logs/LogsGlobalesService.php`: `SET LOCAL app.empresa_id = ''` para bypasear RLS, query builder con filtros opcionales (empresa_id, usuario_id, accion, superadmin_id, fecha_desde, fecha_hasta), pagina de 50, join a empresas y usuarios para mostrar nombres
- [X] T089 [HU-07] Implementar `backend/app/Modules/Superadmin/Logs/LogsGlobalesController.php`
- [X] T090 [HU-07] Implementar `backend/app/Modules/Superadmin/Logs/ExportLogsCSVService.php`: mismos filtros que `LogsGlobalesService` pero sin paginación, genera CSV con columnas `id,empresa,ruc,usuario,email,accion,ip,created_at,datos_anteriores,datos_nuevos`, retorna `StreamedResponse` con headers Content-Type: text/csv
- [X] T091 [HU-07] Implementar `backend/app/Modules/Superadmin/Logs/ExportLogsCSVController.php`
- [X] T092 [HU-07] Implementar `backend/app/Modules/Superadmin/Logs/ResumenLogsController.php` que llama a una query inline: logins fallidos hoy, upgrades este mes, downgrades este mes, suspensiones activas, top 5 empresas por actividad
- [X] T093 [HU-07] Registrar rutas `GET /superadmin/api/logs`, `GET /superadmin/api/logs/export`, `GET /superadmin/api/logs/resumen` en `routes/superadmin.php`
- [X] T094 [HU-07] Ejecutar `php artisan test --filter=LogsGlobalesTest` — todos los tests deben pasar
- [X] T095 [P] [HU-07] Crear `frontend/src/modules/superadmin/logs/use-logs.ts`: hook `useLogs(filters)` con React Query; función `exportLogs(filters)` que hace GET al endpoint CSV y dispara descarga del archivo
- [X] T096 [P] [HU-07] Crear `frontend/src/modules/superadmin/logs/LogsFilters.tsx`: selects para empresa, accion, fecha_desde, fecha_hasta con botón "Aplicar" y "Exportar CSV"
- [X] T097 [HU-07] Crear `frontend/src/modules/superadmin/logs/LogsViewer.tsx`: tabla de logs con paginación, columnas (empresa, usuario, acción, IP, fecha, datos), `LogsFilters` en el encabezado
- [X] T098 [HU-07] Crear `frontend/src/app/(superadmin)/superadmin/logs/page.tsx`

---

## Phase 9: Polish y verificación de aislamiento

**Purpose**: Verificar que la separación superadmin/tenant es total y el build es limpio.

- [X] T099 Ejecutar suite completa backend: `php artisan test` — los 78 tests existentes deben seguir pasando + todos los tests nuevos del módulo superadmin
- [X] T100 [P] Ejecutar `npm run build` en frontend — debe compilar sin errores TypeScript
- [X] T101 Verificar aislamiento: escribir test en `backend/tests/Feature/Superadmin/AislamientoTest.php` con escenarios:
  - token de usuario tenant en `GET /superadmin/api/dashboard` → 403
  - token superadmin en `GET /api/me` → 401 (no es instancia de Usuario)
  - token superadmin en `GET /api/empresa` → 401
  - request sin token en cualquier ruta superadmin protegida → 401
- [X] T102 Verificar que `SuperadminSeeder` no falla si el superadmin ya existe (idempotente via `firstOrCreate`)
- [X] T103 Agregar `SUPERADMIN_EMAIL`, `SUPERADMIN_NOMBRE`, `SUPERADMIN_PASSWORD` a `.env.example` con valores placeholder
- [X] T104 Commit final: `feat(superadmin): módulo Superadmin completado — panel de control del SaaS con HU-01..HU-07`
