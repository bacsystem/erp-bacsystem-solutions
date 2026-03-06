# Tasks: Módulo Core / Auth

**Input**: Design documents from `/specs/001-core-auth/`
**Branch**: `001-core-auth`
**Generated**: 2026-03-05
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Data Model**: [data-model.md](./data-model.md)

**Tests**: Incluidos — requeridos por Definition of Done de la constitución (feature test por slice, happy path + error cases, tenant isolation).

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias incompletas)
- **[Story]**: A qué user story pertenece la tarea (US1..US10)
- Rutas relativas a la raíz del repositorio

---

## Phase 1: Setup

**Purpose**: Estructura de proyecto y configuración base

- [X] T001 Crear estructura de directorios backend: `backend/app/Modules/Core/`, `backend/app/Shared/`, `backend/tests/Feature/Core/`
- [X] T002 [P] Crear estructura de directorios frontend: `frontend/src/modules/core/`, `frontend/src/shared/lib/`, `frontend/src/shared/stores/`
- [X] T003 Instalar dependencias backend: `composer require laravel/sanctum culqi/culqi-php league/flysystem-aws-s3-v3` en `backend/composer.json`
- [X] T004 [P] Instalar dependencias frontend: `shadcn/ui zustand @tanstack/react-query axios react-hook-form @hookform/resolvers zod` en `frontend/package.json`
- [X] T005 Publicar y configurar Laravel Sanctum: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` — verifica `backend/config/sanctum.php`
- [X] T006 [P] Configurar filesystem Cloudflare R2 en `backend/config/filesystems.php` (driver s3-compatible, credenciales de `.env`)
- [X] T007 [P] Configurar servicios Culqi en `backend/config/services.php` (api_key, public_key, webhook_secret)
- [X] T008 [P] Configurar rate limiters en `backend/app/Providers/AppServiceProvider.php`: `login` (5/15min por IP), `register` (3/hora por IP)
- [X] T009 [P] Configurar variables de entorno: copiar `.env.example` con todas las variables del quickstart.md (DB, Redis, Culqi, R2, Resend, FRONTEND_URL)
- [X] T010 [P] Configurar `frontend/.env.local.example` con `NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_CULQI_PUBLIC_KEY`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura base que bloquea TODOS los user stories. Debe estar completa antes de cualquier implementación de story.

**⚠️ CRÍTICO**: Ningún user story puede comenzar hasta completar esta fase.

### Migraciones y Base de Datos

- [X] T011 Crear migración `backend/database/migrations/2026_03_05_000001_create_planes_table.php` con schema según plan.md §1
- [X] T012 [P] Crear migración `backend/database/migrations/2026_03_05_000002_create_empresas_table.php` con schema según plan.md §1
- [X] T013 Crear migración `backend/database/migrations/2026_03_05_000003_create_suscripciones_table.php` (incluye `downgrade_plan_id uuid nullable`, `culqi_customer_id`, `culqi_card_id`, `card_last4`, `card_brand`) según plan.md §1
- [X] T014 Crear migración `backend/database/migrations/2026_03_05_000004_create_usuarios_table.php` (email UNIQUE global) según plan.md §1
- [X] T015 Crear migración `backend/database/migrations/2026_03_05_000005_create_invitaciones_usuario_table.php` según plan.md §1
- [X] T016 Crear migración `backend/database/migrations/2026_03_05_000006_create_audit_logs_table.php` según plan.md §1
- [X] T017 Crear migración `backend/database/migrations/2026_03_05_000007_create_password_reset_tokens_table.php` (id uuid PK, email, token SHA-256, expires_at, used_at) según plan.md §1
- [X] T018 Crear migración `backend/database/migrations/2026_03_05_000008_add_rls_policies.php` — habilita RLS + policy `tenant_isolation` en 5 tablas según plan.md §1
- [X] T019 Ejecutar `php artisan migrate` y verificar las 8 migraciones + tabla Sanctum `personal_access_tokens`

### Seeders

- [X] T020 Implementar `backend/database/seeders/PlanSeeder.php` con los 3 planes (starter S/.59/3 usuarios, pyme S/.129/15 usuarios, enterprise S/.299/ilimitado) según plan.md §2
- [X] T021 Ejecutar `php artisan db:seed --class=PlanSeeder` y verificar 3 registros en `planes`

### Shared Backend — Infraestructura Base

- [X] T022 Implementar `backend/app/Shared/Models/BaseModel.php`: UUID PK, global scope `empresa_id`, `creating` hook para UUID automático según plan.md §3
- [X] T023 [P] Implementar `backend/app/Shared/Http/Responses/ApiResponse.php`: métodos `success()`, `error()`, `paginated()` según plan.md §4
- [X] T024 Implementar `backend/app/Shared/Middleware/TenantMiddleware.php`: extrae `empresa_id` de `auth()->user()`, ejecuta `SET LOCAL app.empresa_id` según plan.md §4
- [X] T025 [P] Implementar `backend/app/Shared/Middleware/SuscripcionActivaMiddleware.php`: bloquea escritura en estado `vencida` (→402), bloquea todo en `cancelada` (→402), excepciones por ruta según plan.md §4
- [X] T026 [P] Implementar `backend/app/Shared/Middleware/CheckPlanMiddleware.php`: verifica módulo en `suscripcion.plan.modulos` → 403 si no incluido según plan.md §4
- [X] T027 [P] Implementar `backend/app/Shared/Middleware/AuditLogMiddleware.php`: registra en `audit_logs` post-response si exitoso según plan.md §4
- [X] T028 Registrar todos los middleware con alias en `backend/bootstrap/app.php`: `tenant`, `suscripcion.activa`, `check.plan`, `audit`, `role` según plan.md §10

### Modelos

- [X] T029 Implementar `backend/app/Modules/Core/Models/Plan.php`: fillable, casts, scopes (`activos`, `esUpgradeDe`, `esDowngradeDe`), relación `suscripciones` según plan.md §3
- [X] T030 [P] Implementar `backend/app/Modules/Core/Models/Empresa.php` extends BaseModel: fillable, setter `ruc` inmutable (throws LogicException), relaciones (`suscripcionActiva`, `usuarios`, `auditLogs`) según plan.md §3
- [X] T031 [P] Implementar `backend/app/Modules/Core/Models/Suscripcion.php` extends BaseModel: fillable, casts fecha, métodos estado (`esTrial`, `esActiva`, etc.), `permiteEscritura()`, `calcularMontoProrrateo()`, relaciones según plan.md §3
- [X] T032 [P] Implementar `backend/app/Modules/Core/Models/Usuario.php` extends BaseModel + Authenticatable + HasApiTokens: fillable, hidden, casts, setter `email` inmutable, scopes (`activos`, `owners`), helpers de rol, relaciones según plan.md §3
- [X] T033 [P] Implementar `backend/app/Modules/Core/Models/InvitacionUsuario.php` extends BaseModel: fillable, casts, `estaVigente()`, scope `pendientes`, relación `invitadoPor` según plan.md §3
- [X] T034 [P] Implementar `backend/app/Modules/Core/Models/AuditLog.php` extends BaseModel: fillable, casts, método estático `registrar()`, `$timestamps = false` según plan.md §3

### Model Factories (para tests)

- [X] T035 Crear `backend/database/factories/PlanFactory.php`
- [X] T036 [P] Crear `backend/database/factories/EmpresaFactory.php`
- [X] T037 [P] Crear `backend/database/factories/SuscripcionFactory.php`
- [X] T038 [P] Crear `backend/database/factories/UsuarioFactory.php`
- [X] T039 [P] Crear `backend/database/factories/InvitacionUsuarioFactory.php`

### Shared Frontend — Infraestructura Base

- [X] T040 Implementar `frontend/src/shared/lib/api.ts`: instancia Axios, interceptor request (inyecta Bearer token desde Zustand), interceptor response 401 (refresh automático con cola de requests pendientes) según plan.md §9
- [X] T041 [P] Implementar `frontend/src/shared/stores/auth.store.ts`: Zustand store con `accessToken`, `user`, `setAccessToken`, `setUser`, `logout` según plan.md §9
- [X] T042 [P] Implementar `frontend/src/app/middleware.ts`: Next.js Edge Middleware, guarda rutas `(dashboard)`, redirige sin `has_session` cookie a `/login` según plan.md §9 — **Nota**: cookie `has_session` debe setearse con `secure: process.env.NODE_ENV === 'production'`; en desarrollo local (HTTP) usar `secure: false` para evitar que el browser la bloquee
- [X] T043 [P] Configurar `frontend/src/app/layout.tsx`: ReactQueryProvider wrapper, Toaster, fuentes

### Rutas Skeleton

- [X] T044 Crear `backend/routes/api.php` completo con todos los grupos y rutas según plan.md §10 (controllers pueden ser stubs vacíos en esta etapa)

**Checkpoint**: ✅ Foundational completa — implementación de user stories puede comenzar en paralelo

---

## Phase 3: User Story 1 — Registro de empresa (Priority: P1) 🎯 MVP

**Goal**: Un visitante puede seleccionar un plan y registrar su empresa, creando automáticamente empresa + usuario owner + suscripción trial de 30 días.

**Independent Test**:
```bash
php artisan test --filter=RegisterTest   # backend
# frontend: cargar /register en browser, completar 4 pasos, verificar redirect a /dashboard
```

### Tests — US1

- [X] T045 [P] [US1] Implementar `backend/tests/Feature/Core/Auth/RegisterTest.php`: happy path (201, empresa/suscripción/audit creados, mail en cola, cookie refresh_token), RUC duplicado (422), email duplicado (422), RUC inválido (422), contraseñas no coinciden (422), plan inexistente (422)

### Backend — US1

- [X] T046 [US1] Implementar `backend/app/Modules/Core/Auth/Register/RegisterRequest.php`: reglas de validación para `plan_id`, `empresa.*`, `owner.*` con mensajes en español según contracts/auth.md y plan.md §5
- [X] T047 [US1] Implementar `backend/app/Modules/Core/Auth/Register/RegisterService.php`: transacción DB (crea empresa → suscripción trial → usuario owner), emite tokens Sanctum, encola `BienvenidaMail`, registra `audit_logs` (accion=register), retorna payload según plan.md §5
- [X] T048 [US1] Implementar `backend/app/Modules/Core/Auth/Register/RegisterController.php`: llama `RegisterService`, retorna `ApiResponse::success(201)` + cookies `refresh_token` (httpOnly) + `has_session` según plan.md §5
- [X] T049 [US1] Implementar `backend/app/Modules/Core/Auth/Planes/GetPlanesController.php` + `GetPlanesService.php`: retorna `Plan::activos()->get()` con campo `recomendado: true` para pyme según contracts/planes.md
- [X] T050 [US1] Implementar `backend/app/Shared/Mail/BienvenidaMail.php`: implements ShouldQueue, subject "Bienvenido a OperaAI 🎉", markdown `emails.bienvenida` según plan.md §8
- [X] T051 [US1] Crear template `backend/resources/views/emails/bienvenida.blade.php`: nombre, empresa, plan, fecha vencimiento trial, link dashboard

### Frontend — US1

- [X] T052 [P] [US1] Implementar `frontend/src/modules/core/auth/register/register.schema.ts`: schemas Zod para cada paso (plan selection, empresa data, owner data, confirmación)
- [X] T053 [P] [US1] Implementar `frontend/src/modules/core/auth/register/register.api.ts`: llamada `POST /api/auth/register` y `GET /api/planes` usando `api.ts`
- [X] T054 [US1] Implementar `frontend/src/modules/core/auth/register/use-register.ts`: useMutation → onSuccess guarda token en Zustand + redirect `/dashboard`, manejo de errores de validación por campo
- [X] T055 [US1] Implementar `frontend/src/modules/core/auth/register/RegisterForm.tsx`: wizard de 4 pasos (PlanSelector, EmpresaForm, OwnerForm, Confirmacion), navegación atrás/adelante, shadcn/ui components
- [X] T056 [US1] Implementar `frontend/src/app/(auth)/register/page.tsx`: importa y renderiza `RegisterForm`
- [X] T057 [US1] Implementar `frontend/src/shared/components/PlanCard.tsx`: card de plan con precios, módulos, badge "Recomendado" para pyme

**Checkpoint**: ✅ US1 completa — registro end-to-end funcional. Visitar `/register`, completar 4 pasos, verificar redirect a `/dashboard`, revisar email en Mailpit, verificar `audit_logs` en DB.

---

## Phase 4: User Story 2 — Inicio de sesión (Priority: P1)

**Goal**: Un usuario registrado puede autenticarse con email + contraseña, recibir tokens y acceder al dashboard con sidebar filtrado por plan.

**Independent Test**:
```bash
php artisan test --filter=LoginTest
# frontend: ir a /login, ingresar credenciales, verificar redirect + sidebar con módulos correctos
```

### Tests — US2

- [X] T058 [P] [US2] Implementar `backend/tests/Feature/Core/Auth/LoginTest.php`: happy path (200, tokens, cookies, last_login actualizado, audit login), credenciales incorrectas (401), usuario inactivo (401), rate limit >5 intentos (429), audit login_failed

### Backend — US2

- [X] T059 [US2] Implementar `backend/app/Modules/Core/Auth/Login/LoginRequest.php`: validación email + password
- [X] T060 [US2] Implementar `backend/app/Modules/Core/Auth/Login/LoginService.php`: busca usuario sin scope empresa, verifica password (Hash::check), verifica activo, actualiza last_login, emite tokens Sanctum (access 15min + refresh 30días), registra audit login según plan.md §5
- [X] T061 [US2] Implementar `backend/app/Modules/Core/Auth/Login/LoginController.php`: llama service, retorna payload user+suscripcion+plan con cookies

### Frontend — US2

- [X] T062 [P] [US2] Implementar `frontend/src/modules/core/auth/login/login.schema.ts`: Zod schema (email, password requeridos)
- [X] T063 [P] [US2] Implementar `frontend/src/modules/core/auth/login/login.api.ts`: `POST /api/auth/login`
- [X] T064 [US2] Implementar `frontend/src/modules/core/auth/login/use-login.ts`: useMutation → guarda accessToken + user en Zustand, redirect `/dashboard`
- [X] T065 [US2] Implementar `frontend/src/modules/core/auth/login/LoginForm.tsx`: campos email + password (con toggle visibilidad), link "¿Olvidaste tu contraseña?", manejo de errores 401/429
- [X] T066 [US2] Implementar `frontend/src/app/(auth)/login/page.tsx`
- [X] T067 [US2] Implementar `frontend/src/shared/components/Sidebar.tsx`: lee `user.suscripcion.modulos` de Zustand, muestra módulos activos, ícono 🔒 + "Mejorar plan" para módulos no contratados
- [X] T068 [US2] Implementar `frontend/src/shared/components/SuscripcionBanner.tsx`: banner persistente si `suscripcion.estado === 'vencida'` o `'cancelada'` con CTA correspondiente
- [X] T069 [US2] Implementar `frontend/src/app/(dashboard)/page.tsx`: layout con Sidebar, SuscripcionBanner condicional, bienvenida

**Checkpoint**: ✅ US1+US2 completas — MVP funcional. Registro → Login → Dashboard con sidebar filtrado.

---

## Phase 5: User Story 3 — Cierre de sesión (Priority: P2)

**Goal**: Un usuario autenticado puede cerrar sesión invalidando TODAS sus sesiones en todos los dispositivos.

**Independent Test**:
```bash
php artisan test --filter=LogoutTest
# frontend: click "Cerrar sesión", verificar redirect /login, verificar token anterior devuelve 401
```

### Tests — US3

- [X] T070 [P] [US3] Implementar `backend/tests/Feature/Core/Auth/LogoutTest.php`: happy path (tokens eliminados, cookies borradas, audit logout_all), sin token (401)

### Backend — US3

- [X] T071 [US3] Implementar `backend/app/Modules/Core/Auth/Logout/LogoutService.php`: `$usuario->tokens()->delete()`, registra audit `logout_all` según plan.md §5
- [X] T072 [US3] Implementar `backend/app/Modules/Core/Auth/Logout/LogoutController.php`: llama service, borra cookies `refresh_token` + `has_session`, retorna 200

### Frontend — US3

- [X] T073 [US3] Agregar acción `logout()` en `frontend/src/shared/stores/auth.store.ts`: llama `POST /api/auth/logout`, limpia store Zustand, borra `has_session` cookie, redirect `/login`
- [X] T074 [US3] Agregar botón "Cerrar sesión" en `frontend/src/shared/components/Sidebar.tsx` que llama `useAuthStore().logout()`

**Checkpoint**: ✅ US3 completa — logout invalida todas las sesiones.

---

## Phase 6: User Story 4 — Renovación automática de sesión (Priority: P2)

**Goal**: La sesión se renueva transparentemente cuando el access token expira (15 min), usando el refresh token (30 días) sin que el usuario note nada.

**Independent Test**:
```bash
php artisan test --filter=RefreshTokenTest
# manual: esperar 15min (o forzar expiración en DB), hacer request, verificar que el interceptor renovó sin logout
```

### Tests — US4

- [X] T075 [P] [US4] Implementar `backend/tests/Feature/Core/Auth/RefreshTokenTest.php`: happy path (nuevo access token, rotación refresh, nuevo cookie), cookie ausente (401), refresh token expirado (401), refresh token ya rotado/inválido (401)

### Backend — US4

- [X] T076 [US4] Implementar `backend/app/Modules/Core/Auth/RefreshToken/RefreshTokenService.php`: lee cookie `refresh_token`, busca PAT por id+hash, verifica nombre='refresh' y expires_at, rota (borra anterior + emite nuevo refresh), emite nuevo access token según plan.md §5
- [X] T077 [US4] Implementar `backend/app/Modules/Core/Auth/RefreshToken/RefreshTokenController.php`: llama service, retorna nuevo access token + cookie refresh rotado

### Frontend — US4

- [X] T078 [US4] Verificar y completar `frontend/src/shared/lib/api.ts`: el interceptor de respuesta 401 ya implementado en T040 cubre este caso — confirmar que la cola de requests pendientes se drena correctamente tras el refresh
- [X] T078b [P] [US4] Documentar verificación del refresh en `specs/001-core-auth/test-results.md`: expirar manualmente un token en DB (`UPDATE personal_access_tokens SET expires_at = NOW() - INTERVAL '1 minute' WHERE name = 'access'`), hacer cualquier request autenticado, verificar en Network tab que: (1) request falla con 401, (2) interceptor llama `POST /api/auth/refresh`, (3) request original se reintenta con nuevo token, (4) usuario no percibe interrupción — registrar resultado PASS/FAIL

**Checkpoint**: ✅ US4 completa — renovación transparente de sesión funcional.

---

## Phase 7: User Story 5 — Recuperación de contraseña (Priority: P2)

**Goal**: Un usuario que olvidó su contraseña puede solicitar un link de reset (60 min) y establecer una nueva contraseña invalidando todas las sesiones.

**Independent Test**:
```bash
php artisan test --filter=RecuperarPasswordTest
# frontend: ir a /recuperar-password, ingresar email, verificar email en Mailpit, usar link, verificar redirect /login
```

### Tests — US5

- [X] T079 [P] [US5] Implementar `backend/tests/Feature/Core/Auth/RecuperarPasswordTest.php`: solicitud con email existente (200 genérico, mail en cola, token en DB), email inexistente (200 genérico, sin mail), reset con token válido (200, password actualizado, tokens eliminados, audit), token expirado (422), token ya usado (422), contraseñas no coinciden (422)

### Backend — US5

- [X] T080 [US5] Implementar `backend/app/Modules/Core/Auth/RecuperarPassword/RecuperarPasswordRequest.php` y `ResetPasswordRequest.php`: validaciones según contracts/auth.md
- [X] T081 [US5] Implementar `backend/app/Modules/Core/Auth/RecuperarPassword/RecuperarPasswordService.php`: `solicitarReset()` (busca sin scope, invalida tokens anteriores, genera token SHA-256, encola mail) + `resetPassword()` (valida token, actualiza password, marca used_at, elimina todos los tokens, audit) según plan.md §5
- [X] T082 [US5] Implementar `backend/app/Modules/Core/Auth/RecuperarPassword/RecuperarPasswordController.php` + `ResetPasswordController.php`
- [X] T083 [US5] Implementar `backend/app/Shared/Mail/RecuperarPasswordMail.php`: implements ShouldQueue, link `{FRONTEND_URL}/reset-password?token={token}&email={email}`, válido 60 minutos
- [X] T084 [US5] Crear template `backend/resources/views/emails/recuperar-password.blade.php`

### Frontend — US5

- [X] T085 [P] [US5] Implementar `frontend/src/modules/core/auth/recuperar-password/recuperar-password.api.ts`: `POST /api/auth/recuperar-password` y `POST /api/auth/reset-password`
- [X] T086 [US5] Implementar `frontend/src/modules/core/auth/recuperar-password/RecuperarPasswordForm.tsx`: campo email, submit, mensaje genérico de confirmación
- [X] T087 [US5] Implementar `frontend/src/modules/core/auth/recuperar-password/ResetPasswordForm.tsx`: campos password + confirmación, lee `token` y `email` de query params, redirect a `/login` tras éxito
- [X] T088 [US5] Implementar `frontend/src/app/(auth)/recuperar-password/page.tsx` y `frontend/src/app/(auth)/reset-password/page.tsx`

**Checkpoint**: ✅ US5 completa — flujo completo de recuperación de contraseña funcional.

---

## Phase 8: User Story 6 — Gestión de datos de la empresa (Priority: P2)

**Goal**: Owner o admin pueden ver y actualizar los datos de la empresa (excepto RUC) y cambiar el logo (Cloudflare R2).

**Independent Test**:
```bash
php artisan test --filter=GetEmpresaTest,UpdateEmpresaTest,UploadLogoTest
# frontend: ir a /configuracion/empresa, editar datos, subir logo JPG <2MB, verificar cambios en navbar
```

### Tests — US6

- [X] T089 [P] [US6] Implementar `backend/tests/Feature/Core/Empresa/GetEmpresaTest.php`: happy path (200, datos correctos, solo empresa del tenant), sin auth (401)
- [X] T090 [P] [US6] Implementar `backend/tests/Feature/Core/Empresa/UpdateEmpresaTest.php`: happy path (200, RUC intacto aunque se envíe, audit empresa_actualizada), empleado/contador (403), ubigeo inválido (422), regimen inválido (422)
- [X] T091 [P] [US6] Implementar `backend/tests/Feature/Core/Empresa/UploadLogoTest.php`: happy path (200, URL retornada, Storage::disk llamado, logo anterior borrado, audit logo_actualizado), >2MB (422), formato inválido (422), empleado (403)

### Backend — US6

- [X] T092 [US6] Implementar `backend/app/Modules/Core/Empresa/GetEmpresa/GetEmpresaController.php` + `GetEmpresaService.php`: retorna empresa del tenant según contracts/empresa.md
- [X] T093 [US6] Implementar `backend/app/Modules/Core/Empresa/UpdateEmpresa/UpdateEmpresaRequest.php`: campos opcionales `nombre_comercial`, `direccion`, `ubigeo`, `regimen_tributario` — excluye `ruc` y `razon_social` según contracts/empresa.md
- [X] T094 [US6] Implementar `backend/app/Modules/Core/Empresa/UpdateEmpresa/UpdateEmpresaService.php`: actualiza solo campos permitidos, registra audit `empresa_actualizada` con datos anterior/nuevo según plan.md §5
- [X] T095 [US6] Implementar `backend/app/Modules/Core/Empresa/UpdateEmpresa/UpdateEmpresaController.php`
- [X] T096 [US6] Implementar `backend/app/Modules/Core/Empresa/UploadLogo/UploadLogoRequest.php`: validación file, mimes jpg/jpeg/png, max 2048KB según contracts/empresa.md
- [X] T097 [US6] Implementar `backend/app/Modules/Core/Empresa/UploadLogo/UploadLogoService.php`: path `logos/{empresa_id}/{timestamp}.{ext}`, elimina logo anterior de R2, `Storage::disk('r2')->put()`, actualiza `empresa.logo_url`, audit `logo_actualizado` según plan.md §5
- [X] T098 [US6] Implementar `backend/app/Modules/Core/Empresa/UploadLogo/UploadLogoController.php`

### Frontend — US6

- [X] T099 [P] [US6] Implementar `frontend/src/modules/core/empresa/get-empresa/use-empresa.ts`: useQuery `GET /api/empresa` con react-query
- [X] T100 [P] [US6] Implementar `frontend/src/modules/core/empresa/update-empresa/empresa.schema.ts`: Zod schema para campos editables
- [X] T101 [US6] Implementar `frontend/src/modules/core/empresa/update-empresa/EmpresaForm.tsx`: formulario con campos editables, RUC en modo solo-lectura (disabled), integra `use-update-empresa`
- [X] T102 [US6] Implementar `frontend/src/modules/core/empresa/update-empresa/LogoUpload.tsx`: dropzone JPG/PNG con validación client-side <2MB, preview del logo actual, upload a `POST /api/empresa/logo`
- [X] T103 [US6] Implementar `frontend/src/app/(dashboard)/configuracion/empresa/page.tsx`: compone `EmpresaForm` + `LogoUpload`

**Checkpoint**: ✅ US6 completa — gestión de empresa funcional incluyendo upload de logo a R2.

---

## Phase 9: User Story 7 — Gestión de plan y suscripción (Priority: P2)

**Goal**: Owner puede ver su plan actual, hacer upgrade con pago Culqi (prorrateado, con reintentos automáticos) y programar downgrade para el siguiente período.

**Independent Test**:
```bash
php artisan test --filter=GetSuscripcionTest,UpgradePlanTest,DowngradePlanTest
# frontend: ir a /configuracion/plan, verificar plan actual, hacer upgrade con tarjeta de prueba Culqi
```

### Tests — US7

- [X] T104 [P] [US7] Implementar `backend/tests/Feature/Core/Suscripcion/GetSuscripcionTest.php`: happy path (200, plan + estado + días restantes), sin auth (401), solo owner puede ver datos del plan
- [X] T105 [P] [US7] Implementar `backend/tests/Feature/Core/Suscripcion/UpgradePlanTest.php`: pago exitoso inmediato (200, plan actualizado, nuevos tokens, mail), timeout Culqi → job encolado (200, estado procesando, audit queued), tarjeta rechazada (402), no es upgrade (422), rol no owner (403)
- [X] T106 [P] [US7] Implementar `backend/tests/Feature/Core/Suscripcion/DowngradePlanTest.php`: happy path (200, downgrade_plan_id guardado, mensaje fecha efectiva), mismo plan (422), no es downgrade (422), rol no owner (403)

### Backend — US7

- [X] T107 [US7] Implementar `backend/app/Modules/Core/Suscripcion/GetSuscripcion/GetSuscripcionController.php` + `GetSuscripcionService.php`: retorna suscripción con plan, estado, fechas y días restantes según contracts/suscripcion.md
- [X] T108 [US7] Implementar `backend/app/Modules/Core/Suscripcion/UpgradePlan/UpgradePlanRequest.php`: `plan_id` uuid existe, `culqi_token` string
- [X] T109 [US7] Implementar `backend/app/Modules/Core/Suscripcion/UpgradePlan/UpgradePlanService.php`: valida upgrade, calcula prorrateo, llama Culqi, en éxito actualiza suscripción + rota tokens, en timeout encola `UpgradePlanJob`, en rechazo lanza PaymentException según plan.md §5
- [X] T110 [US7] Implementar `backend/app/Modules/Core/Suscripcion/UpgradePlan/UpgradePlanController.php`: diferencia 200 (éxito) de 202 (job encolado) en respuesta
- [X] T111 [US7] Implementar `backend/app/Modules/Core/Suscripcion/UpgradePlan/UpgradePlanJob.php`: `$tries=3`, `$backoff=[0,120,600]`, cobro Culqi, en éxito actualiza suscripción, en `failed()` audit + mail según plan.md §6
- [X] T112 [US7] Implementar `backend/app/Modules/Core/Suscripcion/DowngradePlan/DowngradePlanRequest.php` + `DowngradePlanService.php` + `DowngradePlanController.php`: valida downgrade, guarda `downgrade_plan_id`, audit `plan_downgrade`, retorna fecha efectiva y módulos a perder según contracts/suscripcion.md y plan.md §5
- [X] T113 [US7] Implementar `backend/app/Console/Commands/ProcessMonthlyChargesCommand.php` (scheduler diario): cobra suscripciones activas con `fecha_proximo_cobro = today`, aplica downgrade pendiente si `downgrade_plan_id IS NOT NULL`
- [X] T114 [US7] Implementar `backend/app/Console/Commands/ProcesarSuscripcionesVencidasCommand.php` (scheduler diario): trial sin pago al día 30 → vencida; vencida sin pago al día 7 → cancelada; envía mails de aviso días 25, 28, 30
- [X] T115 [US7] Registrar ambos comandos en Schedule en `backend/routes/console.php` con frecuencia `->daily()`
- [X] T116 [US7] Implementar `backend/app/Shared/Mail/UpgradePlanMail.php` (ShouldQueue): "Plan actualizado ✅"
- [X] T117 [P] [US7] Implementar `backend/app/Shared/Mail/UpgradePlanFallidoMail.php` (ShouldQueue): "Problema con tu pago"
- [X] T118 [P] [US7] Implementar `backend/app/Shared/Mail/TrialVencimientoMail.php` (ShouldQueue): días restantes variable (5 o 2)
- [X] T119 [P] [US7] Implementar `backend/app/Shared/Mail/TrialVencidoMail.php` (ShouldQueue): "Tu trial ha vencido"

### Frontend — US7

- [X] T120 [P] [US7] Implementar `frontend/src/modules/core/suscripcion/get-suscripcion/use-suscripcion.ts`: useQuery `GET /api/suscripcion`
- [X] T121 [P] [US7] Implementar `frontend/src/modules/core/suscripcion/upgrade-plan/upgrade-plan.api.ts`: `POST /api/suscripcion/upgrade` con `plan_id` + `culqi_token`
- [X] T122 [US7] Implementar `frontend/src/modules/core/suscripcion/upgrade-plan/CulqiCheckoutForm.tsx`: integra Culqi.js (script externo), genera token en cliente, llama `use-upgrade-plan`, muestra monto prorrateado
- [X] T123 [US7] Implementar `frontend/src/modules/core/suscripcion/upgrade-plan/UpgradePlanModal.tsx`: modal con comparación de planes, monto prorrateado, `CulqiCheckoutForm`
- [X] T124 [US7] Implementar `frontend/src/modules/core/suscripcion/upgrade-plan/use-upgrade-plan.ts`: useMutation → en éxito actualiza token Zustand + invalida queries `me` + `suscripcion`; maneja estado procesando (202)
- [X] T125 [US7] Implementar `frontend/src/modules/core/suscripcion/downgrade-plan/DowngradePlanModal.tsx` + `use-downgrade-plan.ts`: muestra módulos que se perderán, fecha efectiva, confirm button
- [X] T126 [US7] Implementar `frontend/src/app/(dashboard)/configuracion/plan/page.tsx`: plan actual, estado, tabla comparativa 3 planes, botones upgrade/downgrade con modales

**Checkpoint**: ✅ US7 completa — gestión completa de suscripción con pagos Culqi funcional.

---

## Phase 10: User Story 8 — Invitar usuarios al equipo (Priority: P3)

**Goal**: Owner o admin pueden invitar usuarios por email asignando rol. El invitado recibe link de activación (48h) para crear su contraseña.

**Independent Test**:
```bash
php artisan test --filter=InviteUsuarioTest,ActivarCuentaTest
# frontend: ir a /configuracion/usuarios, invitar email nuevo, verificar email Mailpit, activar cuenta
```

### Tests — US8

- [X] T127 [P] [US8] Implementar `backend/tests/Feature/Core/Usuario/InviteUsuarioTest.php`: happy path (201, invitación creada, mail en cola, audit usuario_invitado), límite plan alcanzado (422), email ya usuario activo (422), invitación pendiente duplicada (422), rol no permitido owner (422), empleado invitando (403)
- [X] T128 [P] [US8] Implementar `backend/tests/Feature/Core/Usuario/ActivarCuentaTest.php`: happy path (201, usuario creado, invitación marcada used_at, tokens emitidos, audit usuario_activado), token inválido (422), token expirado (422), token ya usado (422)

### Backend — US8

- [X] T129 [US8] Implementar `backend/app/Modules/Core/Usuario/InviteUsuario/InviteUsuarioRequest.php`: email, rol (in:admin,empleado,contador)
- [X] T130 [US8] Implementar `backend/app/Modules/Core/Usuario/InviteUsuario/InviteUsuarioService.php`: verifica límite plan, verifica email no existe, verifica no hay invitación pendiente, crea `InvitacionUsuario` con token random(64) y expires_at+48h, encola `InvitacionUsuarioMail`, audit según plan.md §5
- [X] T131 [US8] Implementar `backend/app/Modules/Core/Usuario/InviteUsuario/InviteUsuarioController.php`
- [X] T132 [US8] Implementar `backend/app/Modules/Core/Usuario/ActivarCuenta/ActivarCuentaService.php`: busca invitación withoutGlobalScope por token (sin scope tenant), valida vigencia, crea Usuario en DB, marca used_at, emite tokens, audit `usuario_activado` según plan.md §5
- [X] T133 [US8] Implementar `backend/app/Modules/Core/Usuario/ActivarCuenta/ActivarCuentaController.php`
- [X] T134 [US8] Implementar `backend/app/Shared/Mail/InvitacionUsuarioMail.php` (ShouldQueue): link `{FRONTEND_URL}/activar?token={token}`, válido 48h, nombre empresa
- [X] T135 [US8] Crear template `backend/resources/views/emails/invitacion-usuario.blade.php`

### Frontend — US8

- [X] T136 [P] [US8] Implementar `frontend/src/modules/core/usuario/invite-usuario/invite-usuario.schema.ts`: Zod schema (email, rol)
- [X] T137 [US8] Implementar `frontend/src/modules/core/usuario/invite-usuario/InviteUsuarioModal.tsx` + `use-invite-usuario.ts`: modal con email + selector de rol, useMutation, invalida query `usuarios` al completar
- [X] T138 [US8] Implementar `frontend/src/app/(auth)/activar/page.tsx`: lee `token` de query params, muestra `ActivarCuentaForm` (nombre + password + confirmación), redirect `/dashboard` al activar

**Checkpoint**: ✅ US8 completa — invitaciones y activación de cuenta funcional.

---

## Phase 11: User Story 9 — Gestionar usuarios existentes (Priority: P3)

**Goal**: Owner o admin pueden ver todos los usuarios de su empresa, cambiar roles y desactivar usuarios (manteniendo aislamiento total entre tenants).

**Independent Test**:
```bash
php artisan test --filter=ListarUsuariosTest,ActualizarRolTest,DesactivarUsuarioTest,TenantIsolationTest
# frontend: ir a /configuracion/usuarios, cambiar rol de un usuario, desactivar uno, verificar que no aparecen datos de otra empresa
```

### Tests — US9

- [X] T139 [P] [US9] Implementar `backend/tests/Feature/Core/Usuario/ListarUsuariosTest.php`: happy path (200, activos + invitaciones pendientes, solo del propio tenant), sin auth (401)
- [X] T140 [P] [US9] Implementar `backend/tests/Feature/Core/Usuario/ActualizarRolTest.php`: happy path (200, rol actualizado, audit rol_actualizado), admin intentando asignar owner (403), usuario cambiando su propio rol (403), usuario de otra empresa (404 por scope), rol inválido (422)
- [X] T141 [P] [US9] Implementar `backend/tests/Feature/Core/Usuario/DesactivarUsuarioTest.php`: happy path (200, activo=false, tokens eliminados, audit usuario_desactivado), único owner (422), auto-desactivación (403), usuario ya inactivo (422), otra empresa (404)
- [X] T142 [US9] Implementar `backend/tests/Feature/Core/TenantIsolationTest.php`: empresa A no ve datos de empresa B en usuarios, empresa, suscripción; empresa A no puede modificar usuarios de empresa B; verificar RLS activo según plan.md §11

### Backend — US9

- [X] T143 [US9] Implementar `backend/app/Modules/Core/Usuario/ListarUsuarios/ListarUsuariosController.php` + `ListarUsuariosService.php`: retorna activos + invitaciones pendientes, filtrado por empresa_id (BaseModel scope) según contracts/usuarios.md
- [X] T144 [US9] Implementar `backend/app/Modules/Core/Usuario/ActualizarRol/ActualizarRolRequest.php`: rol in:owner,admin,empleado,contador
- [X] T145 [US9] Implementar `backend/app/Modules/Core/Usuario/ActualizarRol/ActualizarRolService.php`: verifica actor puede asignar rol (solo owner puede dar owner), verifica no auto-cambio, registra audit `rol_actualizado` con datos anterior/nuevo según plan.md §5
- [X] T146 [US9] Implementar `backend/app/Modules/Core/Usuario/ActualizarRol/ActualizarRolController.php`
- [X] T147 [US9] Implementar `backend/app/Modules/Core/Usuario/DesactivarUsuario/DesactivarUsuarioService.php`: verifica no auto-desactivación, verifica no es único owner activo, actualiza `activo=false`, `$usuario->tokens()->delete()`, audit `usuario_desactivado` según plan.md §5
- [X] T148 [US9] Implementar `backend/app/Modules/Core/Usuario/DesactivarUsuario/DesactivarUsuarioController.php`

### Frontend — US9

- [X] T149 [P] [US9] Implementar `frontend/src/modules/core/usuario/listar-usuarios/use-listar-usuarios.ts`: useQuery `GET /api/usuarios`
- [X] T150 [US9] Implementar `frontend/src/modules/core/usuario/listar-usuarios/UsuariosTable.tsx`: tabla con activos + pendientes, acciones inline de rol y desactivar, shadcn/ui Table + DropdownMenu
- [X] T151 [US9] Implementar `frontend/src/modules/core/usuario/actualizar-rol/use-actualizar-rol.ts` + `frontend/src/modules/core/usuario/desactivar-usuario/use-desactivar-usuario.ts`: useMutation, confirmar antes de desactivar, invalida query `usuarios`
- [X] T152 [US9] Implementar `frontend/src/app/(dashboard)/configuracion/usuarios/page.tsx`: compone `UsuariosTable` + `InviteUsuarioModal`

**Checkpoint**: ✅ US9 completa — gestión de usuarios con tenant isolation verificada.

---

## Phase 12: User Story 10 — Ver y editar perfil propio (Priority: P3)

**Goal**: Cualquier usuario autenticado puede ver su perfil, editar su nombre y cambiar su contraseña (invalidando todas las sesiones).

**Independent Test**:
```bash
php artisan test --filter=GetProfileTest,UpdateProfileTest
# frontend: click en nombre de usuario → perfil, editar nombre, cambiar contraseña, verificar redirect /login
```

### Tests — US10

- [X] T153 [P] [US10] Implementar `backend/tests/Feature/Core/Usuario/GetProfileTest.php`: happy path (200, id, nombre, email, rol, empresa, suscripción), sin auth (401)
- [X] T154 [P] [US10] Implementar `backend/tests/Feature/Core/Usuario/UpdateProfileTest.php`: actualizar solo nombre (200), cambiar contraseña válida (200, tokens eliminados, redirect esperado en frontend), password_actual incorrecto (422), nueva contraseña igual a actual (422), nueva contraseña <8 chars (422)

### Backend — US10

- [X] T155 [US10] Implementar `backend/app/Modules/Core/Usuario/GetProfile/GetProfileController.php` + `GetProfileService.php`: retorna usuario autenticado con empresa + suscripción + plan según contracts/me.md
- [X] T156 [US10] Implementar `backend/app/Modules/Core/Usuario/UpdateProfile/UpdateProfileRequest.php`: nombre (sometimes), password_actual (required_with:password), password (sometimes, min:8, confirmed, different:password_actual)
- [X] T157 [US10] Implementar `backend/app/Modules/Core/Usuario/UpdateProfile/UpdateProfileService.php`: actualiza nombre si viene, si viene password: verifica Hash::check(password_actual), actualiza password, `$usuario->tokens()->delete()`, audit `password_changed` según plan.md §5
- [X] T158 [US10] Implementar `backend/app/Modules/Core/Usuario/UpdateProfile/UpdateProfileController.php`

### Frontend — US10

- [X] T159 [US10] Implementar `frontend/src/modules/core/usuario/get-profile/use-profile.ts`: useQuery `GET /api/me` + formulario nombre con useMutation `PUT /api/me`
- [X] T160 [US10] Implementar `frontend/src/app/(dashboard)/perfil/page.tsx`: muestra nombre (editable), email (solo lectura, con nota "inmutable"), rol (solo lectura), sección cambio de contraseña separada — al cambiar contraseña exitosamente hace logout y redirect `/login`

**Checkpoint**: ✅ US10 completa — todos los user stories implementados.

---

## Phase 13: Polish & Cross-Cutting Concerns

**Purpose**: Validaciones finales, seguridad y Definition of Done de la constitución

- [X] T161 [P] Agregar `CheckRoleMiddleware` en `backend/app/Shared/Middleware/CheckRoleMiddleware.php`: valida que `auth()->user()->rol` esté en la lista de roles permitidos → 403 si no, usado en routes con `->middleware('role:owner,admin')`
- [X] T162 [P] Verificar que `TenantMiddleware` usa `SET LOCAL` (transaction-scoped) para compatibilidad con connection pooling — actualizar si es necesario en `backend/app/Shared/Middleware/TenantMiddleware.php`
- [X] T163 Ejecutar `php artisan test --filter=Core` y verificar 0 failures, 0 errors en toda la suite
- [X] T164 [P] Ejecutar `npm run build` en `frontend/` y verificar sin errores TypeScript
- [X] T165 [P] Ejecutar `php artisan route:list --path=api` y verificar todos los endpoints listados con sus middlewares correctos
- [X] T166 Verificar Definition of Done completa de la constitución: registro end-to-end <3 min en browser, JWT con empresa_id+rol+plan+modulos, sidebar solo módulos del plan, 🔒 en módulos no contratados, CheckPlanMiddleware bloquea con 403, tenant isolation empresa A vs empresa B confirmado, upgrade actualiza módulos al instante, límite de usuarios respetado
- [X] T167 [P] Revisar `backend/storage/logs/laravel.log` — sin errores inesperados tras ejecución de tests
- [X] T168 [P] Verificar rate limiting en producción: `throttle:login` y `throttle:register` activos en `backend/routes/api.php`
- [X] T169 Probar flujo Culqi sandbox end-to-end en `/configuracion/plan`: (1) **Éxito**: tarjeta `4111 1111 1111 1111`, CVV `123`, fecha futura → plan debe actualizarse al instante y sidebar reflejar nuevos módulos; (2) **Rechazo**: tarjeta `4000 0000 0000 0002` → toast de error "Tu tarjeta fue rechazada"; (3) **Timeout**: no existe tarjeta real para simular timeout — se prueba exclusivamente vía `Http::fake(['culqi.com/*' => Http::response(null, 504)])` en `UpgradePlanTest.php` (T105) — verificar que `UpgradePlanJob` queda en cola y audit log registra `plan_upgrade_queued`
- [X] T170 Commit final: `git commit -m "feat(core-auth): módulo Core/Auth completado y probado"`

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)        → No dependencies — comenzar inmediatamente
Phase 2 (Foundational) → Depende de Phase 1 — BLOQUEA todos los user stories
Phase 3 (US1 P1) ──┐
Phase 4 (US2 P1) ──┤
Phase 5 (US3 P2) ──┤
Phase 6 (US4 P2) ──┤→ Todos dependen de Phase 2 (Foundational)
Phase 7 (US5 P2) ──┤  Pueden ejecutarse en paralelo si hay capacidad
Phase 8 (US6 P2) ──┤
Phase 9 (US7 P2) ──┤
Phase 10 (US8 P3)──┤
Phase 11 (US9 P3)──┤
Phase 12 (US10 P3)─┘
Phase 13 (Polish)  → Depende de todos los user stories deseados
```

### Dependencias Internas por Story

| Story | Depende de | Independiente de |
|-------|-----------|-----------------|
| US1 (Registro) | Foundational completa, Plan model | US2-US10 |
| US2 (Login) | Foundational + US1 (usa mismo payload) | US3-US10 |
| US3 (Logout) | Foundational + US2 (tokens) | US1, US4-US10 |
| US4 (Refresh) | Foundational + US2 (tokens) | US1, US3, US5-US10 |
| US5 (Recover PW) | Foundational | US1-US4, US6-US10 |
| US6 (Empresa) | Foundational + US2 (auth) | US1, US3-US5, US7-US10 |
| US7 (Suscripción) | Foundational + US2 (auth) | US1, US3-US6, US8-US10 |
| US8 (Invitar) | Foundational + US2 (auth) | US1-US7, US9-US10 |
| US9 (Gestionar) | Foundational + US8 (InvitacionUsuario model) | US1-US8, US10 |
| US10 (Perfil) | Foundational + US2 (auth) | US1-US9 |

### Within Each User Story

1. Tests → deben escribirse PRIMERO y FALLAR antes de implementar
2. Models/Request → antes que Services
3. Services → antes que Controllers
4. Backend completo → antes de Frontend (necesita API disponible)
5. Story completa → antes de marcar como done y avanzar

---

## Parallel Opportunities

### Phase 2 — Foundational (máximo paralelismo):

```
Grupo A (DB):        T011-T019 (migraciones — deben ser secuenciales entre sí)
Grupo B (Shared BE): T022-T028 (pueden ir en paralelo con migraciones)
Grupo C (Models):    T029-T034 (paralelo entre modelos)
Grupo D (Factories): T035-T039 (paralelo entre factories)
Grupo E (Frontend):  T040-T043 (paralelo con todo lo anterior)
```

### Ejemplo: Phase 3 — US1 Registro

```
Paralelo (tests primero):
  Task "T045 — RegisterTest.php"

Luego paralelo (backend):
  Task "T046 — RegisterRequest.php"
  Task "T052 — register.schema.ts"
  Task "T053 — register.api.ts"
  Task "T049 — GetPlanesController.php"

Luego secuencial:
  Task "T047 — RegisterService.php" (depende de T046)
  Task "T048 — RegisterController.php" (depende de T047)
  Task "T050 — BienvenidaMail.php" (paralelo con T047-T048)
  Task "T054 — use-register.ts" (depende de T053)
  Task "T055 — RegisterForm.tsx" (depende de T054)
```

---

## Implementation Strategy

### MVP First (US1 + US2 — P1 stories only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRÍTICO — bloquea todo)
3. Complete Phase 3: US1 — Registro de empresa
4. Complete Phase 4: US2 — Login
5. **STOP y VALIDAR**: registro + login + dashboard funcional
6. Deploy/demo: el sistema ya es utilizable por un owner

### Incremental Delivery (P1 → P2 → P3)

```
Sprint 1: Phase 1 + 2 + US1(P1) + US2(P1) → MVP: Registro + Login
Sprint 2: US3(P2) + US4(P2) + US5(P2)     → Auth: Logout + Refresh + Recover PW
Sprint 3: US6(P2) + US7(P2)               → Empresa + Suscripción (Culqi)
Sprint 4: US8(P3) + US9(P3) + US10(P3)    → Equipo + Usuarios + Perfil
Sprint 5: Phase 13 Polish                  → DoD completa + entrega
```

### Estrategia con Equipo Paralelo

Con 2+ desarrolladores tras completar Phase 2:
- **Dev A**: US1 → US3 → US5 (flujos de auth)
- **Dev B**: US2 → US4 → US6 (sesión + empresa)
- **Dev C**: US7 → US9 (pagos + usuarios)
- Merge tras cada story completada e independientemente testeada

---

## Summary

| Phase | Stories | Tasks | Parallelizable |
|-------|---------|-------|---------------|
| Phase 1: Setup | — | T001–T010 | 7 de 10 |
| Phase 2: Foundational | — | T011–T044 | 20 de 34 |
| Phase 3: US1 Registro | P1 🎯 | T045–T057 | 5 de 13 |
| Phase 4: US2 Login | P1 | T058–T069 | 4 de 12 |
| Phase 5: US3 Logout | P2 | T070–T074 | 1 de 5 |
| Phase 6: US4 Refresh | P2 | T075–T078b | 2 de 5 |
| Phase 7: US5 Recuperar PW | P2 | T079–T088 | 3 de 10 |
| Phase 8: US6 Empresa | P2 | T089–T103 | 7 de 15 |
| Phase 9: US7 Suscripción | P2 | T104–T126 | 9 de 23 |
| Phase 10: US8 Invitar | P3 | T127–T138 | 3 de 12 |
| Phase 11: US9 Gestionar | P3 | T139–T152 | 5 de 14 |
| Phase 12: US10 Perfil | P3 | T153–T160 | 2 de 8 |
| Phase 13: Polish | — | T161–T170 | 6 de 10 |
| **TOTAL** | **10 stories** | **170 tasks** | **73 parallelizable** |

**MVP Scope**: Phase 1 + Phase 2 + Phase 3 (US1) + Phase 4 (US2) = **T001–T069** (69 tasks)
