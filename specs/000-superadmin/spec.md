# Feature Specification: Módulo 0 — Superadmin OperaAI

**Feature Branch**: `000-superadmin`
**Created**: 2026-03-06
**Status**: Draft
**Módulo**: 0 — Panel de control interno del SaaS. Completamente separado del sistema tenant.

---

> **Nota del proyecto**: Este módulo es el panel de control del dueño del SaaS.
> Permite gestionar todas las empresas registradas en OperaAI desde un único punto,
> sin pertenecer a ningún tenant. Es invisible para los usuarios tenant y opera
> sobre rutas, middleware y modelos completamente independientes.

---

## Usuarios del módulo

| Usuario    | Descripción                                                       |
|------------|-------------------------------------------------------------------|
| Superadmin | Dueño del SaaS. Acceso total de lectura y escritura sobre todo el sistema. Solo puede existir uno o un equipo pequeño con acceso por invitación manual. |

---

## User Scenarios & Testing *(mandatory)*

---

### HU-01 — Login superadmin (Priority: P0)

Como superadmin, quiero acceder al panel interno mediante credenciales propias para gestionar el SaaS sin interferir con ningún tenant.

**Why this priority**: Sin login superadmin no existe acceso a ninguna otra funcionalidad del panel. Es el prerequisito absoluto.

**Independent Test**: Un superadmin con credenciales válidas puede autenticarse en `/superadmin/login`, recibir un JWT tipo `superadmin` y acceder a `/superadmin/dashboard`. Un usuario tenant con su token NO puede acceder a rutas `/superadmin/api/*`.

**Acceptance Scenarios**:

1. **Given** un superadmin con email y password correctos, **When** hace POST a `/superadmin/api/auth/login`, **Then** recibe `access_token` con `tipo=superadmin`, duración 4 horas, sin `empresa_id` en el payload.

2. **Given** un superadmin que ingresa password incorrecto 3 veces en 15 minutos, **When** intenta el 4to login, **Then** recibe HTTP 429 con mensaje "Demasiados intentos. Espera 15 minutos."

3. **Given** un token Sanctum de usuario tenant (owner/admin/empleado), **When** se usa para llamar a `GET /superadmin/api/empresas`, **Then** recibe HTTP 403 Forbidden.

4. **Given** un superadmin con `activo=false`, **When** intenta autenticarse, **Then** recibe HTTP 401 con mensaje "Tu cuenta de superadmin está desactivada."

5. **Given** un token superadmin expirado (más de 4 horas), **When** se usa en cualquier ruta protegida, **Then** recibe HTTP 401. No hay refresh token — debe re-autenticarse.

---

### HU-02 — Dashboard global (Priority: P1)

Como superadmin, quiero ver métricas globales de todo el SaaS para tomar decisiones de negocio.

**Why this priority**: La razón principal del panel. Las métricas son el valor central.

**Independent Test**: Con al menos 2 empresas activas y 1 en trial, el dashboard devuelve MRR correcto, totales por estado y tasa de conversión calculada.

**Acceptance Scenarios**:

1. **Given** el sistema tiene 10 empresas activas en PYME (S/.129), 3 en Starter (S/.59) y 2 en Enterprise (S/.299), **When** el superadmin consulta `GET /superadmin/api/dashboard`, **Then** el MRR total es S/.1.675,00 desglosado por plan.

2. **Given** el mes anterior tuvo 20 empresas en trial y 8 convirtieron a pago, **When** se consulta el dashboard, **Then** la `tasa_conversion` es 40%.

3. **Given** el mes anterior tuvo 5 empresas activas que cancelaron, **When** se consulta el dashboard, **Then** el `churn_mes` es 5.

4. **Given** se consulta el dashboard, **When** el superadmin revisa el gráfico, **Then** recibe `mrr_historico` con 6 puntos (últimos 6 meses) con `mes` y `mrr` cada uno.

5. **Given** no hay empresas registradas aún, **When** se consulta el dashboard, **Then** todos los valores numéricos son 0 y `mrr_historico` retorna array de 6 meses con mrr=0 cada uno.

---

### HU-03 — Lista y detalle de empresas (Priority: P1)

Como superadmin, quiero ver todas las empresas del sistema con filtros y acceder al detalle completo de cada una.

**Why this priority**: Funcionalidad core de operaciones diarias del SaaS.

**Independent Test**: Con 5 empresas en distintos estados y planes, el listado respeta filtros combinados y el detalle de una empresa muestra sus usuarios, historial de suscripciones y audit logs sin filtro de tenant.

**Acceptance Scenarios**:

1. **Given** existen 50 empresas en el sistema, **When** el superadmin llama `GET /superadmin/api/empresas?plan=pyme&estado=trial`, **Then** recibe solo las empresas en plan PYME con estado trial, paginadas de 25 en 25.

2. **Given** el superadmin busca por `q=empresa+ejemplo`, **When** la búsqueda coincide con el nombre, RUC o email del owner, **Then** se devuelven todas las empresas que coincidan con alguno de esos campos.

3. **Given** el superadmin accede al detalle de una empresa, **When** llama `GET /superadmin/api/empresas/{id}`, **Then** recibe: datos de empresa, lista de usuarios (nombre, email, rol, activo, last_login), historial de suscripciones (plan, estado, fechas), últimos 50 audit_logs y métricas (MRR, días activo, total upgrades).

4. **Given** el superadmin aplica filtro `fecha_desde=2026-01-01&fecha_hasta=2026-03-01`, **When** consulta el listado, **Then** solo aparecen empresas registradas en ese rango.

5. **Given** el superadmin ordena por `sort=mrr&order=desc`, **When** consulta el listado, **Then** las empresas con mayor MRR aparecen primero.

---

### HU-04 — Activar / Suspender tenants (Priority: P1)

Como superadmin, quiero poder suspender o reactivar empresas para gestionar incumplimientos o errores operativos.

**Why this priority**: Control crítico de operaciones. Necesario para compliance y gestión de impagos.

**Independent Test**: Al suspender una empresa activa, su suscripción cambia a `cancelada`, todos sus tokens Sanctum son eliminados, y el owner no puede volver a autenticarse hasta ser reactivado. Al reactivar, la suscripción vuelve a `activa` y el owner recibe email.

**Acceptance Scenarios**:

1. **Given** una empresa con suscripción en estado `activa` y 3 usuarios con tokens activos, **When** el superadmin hace `POST /superadmin/api/empresas/{id}/suspender`, **Then** la suscripción pasa a `cancelada`, los 3 tokens Sanctum son eliminados, y se registra en `audit_logs` con `accion=superadmin_suspend`.

2. **Given** una empresa suspendida (suscripción `cancelada` por superadmin), **When** el owner intenta hacer login, **Then** recibe HTTP 401 con mensaje de cuenta suspendida.

3. **Given** una empresa suspendida, **When** el superadmin hace `POST /superadmin/api/empresas/{id}/activar`, **Then** la suscripción vuelve a `activa`, se registra en `audit_logs` con `accion=superadmin_activate`, y el owner recibe email de reactivación.

4. **Given** el superadmin suspende una empresa, **When** se consultan los `audit_logs`, **Then** el registro incluye `superadmin_id`, `ip` del superadmin, y el campo `datos_nuevos` refleja el cambio de estado.

5. **Given** una empresa ya suspendida, **When** el superadmin intenta suspenderla de nuevo, **Then** recibe HTTP 422 con mensaje "La empresa ya está suspendida."

---

### HU-05 — Impersonar tenant (Priority: P2)

Como superadmin, quiero poder entrar temporalmente al dashboard de un tenant para dar soporte sin conocer su contraseña.

**Why this priority**: Funcionalidad de soporte crítica para reducir fricción con clientes.

**Independent Test**: El superadmin inicia impersonación de una empresa, recibe un token temporal válido 2 horas con `abilities=['impersonated']`, el frontend muestra banner rojo, y al terminar la impersonación el token queda invalidado y registrado en `impersonation_logs`.

**Acceptance Scenarios**:

1. **Given** el superadmin hace `POST /superadmin/api/empresas/{id}/impersonar`, **When** la empresa tiene un owner activo, **Then** recibe `token` temporal (2 horas), `empresa` datos del tenant, y se guarda el hash del token en `impersonation_logs`.

2. **Given** el superadmin tiene un token de impersonación activo, **When** hace cualquier request al API del tenant con ese token, **Then** el sistema lo reconoce como impersonación (`abilities=['impersonated']`) y permite el acceso.

3. **Given** el frontend recibe el token de impersonación, **When** abre el dashboard del tenant, **Then** muestra un banner rojo persistente: "Estás viendo la cuenta de [Empresa X] como superadmin" con botón "Salir".

4. **Given** el superadmin hace `DELETE /superadmin/api/empresas/{id}/impersonar`, **When** el backend recibe la solicitud, **Then** el token temporal es eliminado de Sanctum, `ended_at` se registra en `impersonation_logs`, y se crea `audit_log` con `accion=superadmin_impersonation_end`.

5. **Given** han pasado 2 horas desde el inicio de la impersonación, **When** el frontend intenta usar el token, **Then** recibe HTTP 401 (token expirado) y redirige automáticamente al panel superadmin.

6. **Given** la empresa no tiene ningún owner activo, **When** el superadmin intenta impersonar, **Then** recibe HTTP 422 con mensaje "Esta empresa no tiene un owner activo."

---

### HU-06 — Gestión de planes (Priority: P2)

Como superadmin, quiero gestionar los planes del sistema y aplicar descuentos personalizados a tenants específicos.

**Why this priority**: Flexibilidad comercial necesaria para negociaciones y correcciones de precios.

**Independent Test**: El superadmin edita el precio mensual del plan PYME. La suscripción de tenants existentes en ese plan NO cambia (solo afecta nuevos cobros). Al crear un descuento, el endpoint de suscripción del tenant lo refleja en `datos_pago.descuento`.

**Acceptance Scenarios**:

1. **Given** el plan PYME tiene precio S/.129, **When** el superadmin hace `PUT /superadmin/api/planes/{id}` con `precio_mensual=139`, **Then** el plan actualiza el precio, las suscripciones existentes no cambian su precio cobrado actual, y se registra en `audit_logs` con `accion=superadmin_update_plan`.

2. **Given** el superadmin edita los módulos del plan Enterprise, **When** agrega `rrhh` al array `modulos`, **Then** todos los tenants en Enterprise inmediatamente tienen acceso al módulo `rrhh` (sin necesidad de re-login).

3. **Given** el superadmin quiere dar descuento a una empresa específica, **When** hace `POST /superadmin/api/empresas/{id}/descuento` con `tipo=porcentaje&valor=20&motivo=Acuerdo comercial`, **Then** se crea en `descuentos_tenant` y el tenant ve su descuento en `GET /api/suscripcion`.

4. **Given** existe un descuento activo para un tenant, **When** el superadmin lo desactiva con `DELETE /superadmin/api/empresas/{id}/descuento/{descuento_id}`, **Then** el descuento pasa a `activo=false` y el tenant ya no lo ve en su suscripción.

5. **Given** el superadmin lista los planes, **When** hace `GET /superadmin/api/planes`, **Then** recibe los 3 planes con precio actual, módulos, cantidad de tenants activos en cada plan y MRR por plan.

---

### HU-07 — Logs y actividad global (Priority: P2)

Como superadmin, quiero ver la actividad de todos los tenants en un solo lugar y exportar logs para análisis.

**Why this priority**: Auditoría y compliance. Necesario para detectar fraude, abuso o problemas operativos.

**Independent Test**: Con audit_logs de 3 empresas distintas, el endpoint retorna TODOS sin filtro de tenant. Los filtros combinados (empresa + acción + fecha) reducen correctamente los resultados. La exportación CSV contiene todas las columnas y respeta los filtros aplicados.

**Acceptance Scenarios**:

1. **Given** existen audit_logs de 5 empresas distintas, **When** el superadmin llama `GET /superadmin/api/logs`, **Then** recibe logs de TODAS las empresas sin filtro de tenant, paginados de 50 en 50, ordenados por `created_at DESC`.

2. **Given** el superadmin filtra por `empresa_id=X&accion=login_failed&fecha_desde=2026-03-01`, **When** llama al endpoint, **Then** solo recibe los login fallidos de esa empresa desde esa fecha.

3. **Given** el superadmin filtra por `accion=login_failed` sin especificar empresa, **When** llama al endpoint, **Then** recibe todos los intentos fallidos del sistema incluyendo los que tienen `empresa_id=null` (emails desconocidos).

4. **Given** el superadmin aplica filtros y hace `GET /superadmin/api/logs/export?format=csv`, **When** el backend procesa la solicitud, **Then** devuelve un CSV con columnas: `id,empresa,usuario,accion,ip,created_at,datos_anteriores,datos_nuevos` respetando los mismos filtros.

5. **Given** el superadmin consulta `GET /superadmin/api/logs/resumen`, **When** llama al endpoint, **Then** recibe: total de logins fallidos hoy, upgrades este mes, downgrades este mes, suspensiones activas y top 5 empresas con más actividad.
