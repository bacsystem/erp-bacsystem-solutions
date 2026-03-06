# Feature Specification: Módulo Core / Auth

**Feature Branch**: `001-core-auth`
**Created**: 2026-03-04
**Status**: Draft
**Módulo**: 1 de 8 — Base bloqueante. Ningún otro módulo puede funcionar sin este.

---

> **Nota del proyecto**: Este módulo es la puerta de entrada de OperaAI.
> Define registro de empresas peruanas, autenticación multi-rol,
> gestión de suscripciones y aislamiento completo de datos entre empresas.

---

## Usuarios del módulo

| Usuario   | Descripción                                              |
|-----------|----------------------------------------------------------|
| Visitante | Llega desde la landing, aún no tiene cuenta              |
| Owner     | Se registra; es el dueño de la empresa en el sistema     |
| Admin     | Invitado por el owner; gestión completa sin billing      |
| Empleado  | Acceso limitado a módulos asignados por el owner/admin   |
| Contador  | Solo lectura en finanzas y facturación                   |

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Registro de empresa (Priority: P1)

Como visitante, quiero registrar mi empresa en OperaAI para empezar a
gestionar mi negocio sin tener que pagar de entrada.

**Why this priority**: Sin registro no existe ningún tenant. Es el punto
de entrada absoluto del sistema. Nada más puede funcionar sin él.

**Independent Test**: Un visitante puede completar el registro desde cero
— eligiendo plan, ingresando datos de empresa y datos de usuario — y quedar
logueado en el dashboard con su trial activo, sin haber ingresado tarjeta.

**Acceptance Scenarios**:

1. **Given** un visitante en la página de registro, **When** selecciona el
   plan PYME y completa los 4 pasos con datos válidos, **Then** se crea la
   empresa, el usuario owner y la suscripción trial de 30 días; el visitante
   es redirigido al dashboard y recibe email de bienvenida.

2. **Given** un visitante en el paso 2 (datos de empresa), **When** ingresa
   un RUC con menos o más de 11 dígitos, **Then** ve el mensaje
   "El RUC debe tener 11 dígitos" y no puede continuar.

3. **Given** un visitante en el paso 3 (datos de usuario), **When** ingresa
   un email ya registrado en el sistema, **Then** ve el mensaje
   "Este email ya tiene una cuenta".

4. **Given** un visitante en el paso 4 (confirmación), **When** hace clic en
   "Crear mi cuenta", **Then** no se le solicita tarjeta de crédito y el
   trial se activa inmediatamente.

---

### User Story 2 — Inicio de sesión (Priority: P1)

Como usuario registrado, quiero iniciar sesión en OperaAI para acceder
al sistema de mi empresa de forma segura.

**Why this priority**: Sin login no hay acceso al sistema. Es P1 junto
con el registro porque ambos son la puerta de entrada.

**Independent Test**: Un usuario registrado puede ingresar email y contraseña
y quedar dentro del dashboard con el sidebar mostrando solo los módulos de
su plan.

**Acceptance Scenarios**:

1. **Given** un usuario con credenciales válidas, **When** ingresa email y
   contraseña correctos, **Then** accede al dashboard; el sidebar muestra
   solo los módulos de su plan contratado.

2. **Given** un usuario con suscripción vencida, **When** inicia sesión,
   **Then** accede al dashboard con un banner persistente de pago pendiente;
   puede navegar en modo solo lectura (GET) pero POST/PUT/PATCH/DELETE
   devuelven 402.

3. **Given** un usuario con suscripción cancelada, **When** inicia sesión,
   **Then** es redirigido a /reactivar (no al dashboard). Ve los 3 planes con
   precios, botón de pago Culqi y un aviso de retención de datos hasta
   90 días desde la fecha de cancelación. No tiene acceso a ningún dato.

4. **Given** un usuario que ingresa contraseña incorrecta 5 veces seguidas,
   **When** intenta el sexto intento, **Then** su acceso queda bloqueado
   temporalmente por 15 minutos.

---

### User Story 3 — Cierre de sesión (Priority: P2)

Como usuario autenticado, quiero cerrar sesión para proteger mi cuenta
cuando termino de usar el sistema.

**Why this priority**: Funcionalidad de seguridad básica, depende de login (P1).

**Independent Test**: Un usuario logueado puede cerrar sesión y no puede
reutilizar su sesión anterior.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado en múltiples dispositivos, **When** hace
   clic en "Cerrar sesión" en cualquiera de ellos, **Then** TODAS sus sesiones
   activas quedan invalidadas, los datos de sesión se limpian del navegador
   actual y es redirigido a /login.

2. **Given** un usuario que cerró sesión desde otro dispositivo, **When** el
   dispositivo restante realiza cualquier request, **Then** recibe 401 y es
   redirigido a /login automáticamente.

3. **Given** un usuario que cerró sesión, **When** intenta usar una URL del
   dashboard directamente, **Then** es redirigido a /login.

---

### User Story 4 — Renovación automática de sesión (Priority: P2)

Como usuario autenticado, quiero que mi sesión se renueve automáticamente
para no perder mi trabajo mientras uso el sistema.

**Why this priority**: Mejora la experiencia de uso; sin esto el usuario
sería interrumpido cada 15 minutos.

**Independent Test**: Un usuario puede trabajar más de 15 minutos seguidos
sin que el sistema le pida nuevas credenciales.

**Acceptance Scenarios**:

1. **Given** un usuario cuya sesión está próxima a expirar, **When** realiza
   cualquier acción en el sistema, **Then** la sesión se renueva
   transparentemente sin que el usuario lo note.

2. **Given** un usuario inactivo por más de 30 días, **When** intenta usar
   el sistema, **Then** es redirigido a /login con mensaje claro de sesión
   expirada.

---

### User Story 5 — Recuperación de contraseña (Priority: P2)

Como usuario que olvidó su contraseña, quiero recuperar acceso a mi
cuenta sin necesitar soporte.

**Why this priority**: Reduce tickets de soporte y desbloquea usuarios
sin intervención manual.

**Independent Test**: Un usuario puede recuperar acceso a su cuenta
siguiendo un flujo por email, sin conocer su contraseña anterior.

**Acceptance Scenarios**:

1. **Given** un usuario en la página de recuperación, **When** ingresa su
   email registrado, **Then** recibe un email con un link de recuperación
   válido por 60 minutos.

2. **Given** un usuario con el link de recuperación, **When** establece una
   nueva contraseña válida, **Then** todas sus sesiones activas son
   invalidadas y es redirigido a /login con mensaje de éxito.

3. **Given** un visitante que ingresa un email no registrado, **When**
   envía el formulario, **Then** ve el mismo mensaje genérico que si el
   email existiera (seguridad por enumeración).

---

### User Story 6 — Gestión de datos de la empresa (Priority: P2)

Como owner o admin, quiero ver y actualizar los datos de mi empresa para
mantener la información correcta para la facturación electrónica.

**Why this priority**: Los datos de empresa son necesarios para emitir
comprobantes válidos ante SUNAT.

**Independent Test**: Un owner puede editar nombre comercial, dirección y
régimen tributario, y ver los cambios reflejados de inmediato en el navbar.

**Acceptance Scenarios**:

1. **Given** un owner en la página de configuración de empresa, **When**
   actualiza el nombre comercial y guarda, **Then** el nuevo nombre aparece
   en el navbar inmediatamente.

2. **Given** un owner en el formulario de empresa, **When** intenta editar
   el RUC, **Then** el campo aparece deshabilitado indicando que es
   inmutable.

3. **Given** un admin subiendo un logo, **When** selecciona un archivo mayor
   a 2MB, **Then** ve el mensaje "El archivo no debe superar 2MB" antes de
   enviar el formulario.

---

### User Story 7 — Gestión de plan y suscripción (Priority: P2)

Como owner, quiero gestionar el plan de mi empresa para acceder a los
módulos que mi negocio necesita.

**Why this priority**: El plan determina qué módulos están disponibles;
es parte del modelo de negocio central.

**Independent Test**: Un owner puede ver su plan actual, hacer upgrade y
ver los nuevos módulos disponibles inmediatamente después del pago.

**Acceptance Scenarios**:

1. **Given** un owner con plan Starter, **When** hace upgrade al plan PYME
   y completa el pago exitosamente, **Then** el sidebar muestra los nuevos
   módulos al instante.

2. **Given** un owner haciendo upgrade, **When** el pago es rechazado,
   **Then** ve el mensaje "Tu tarjeta fue rechazada, intenta con otra" y
   su plan no cambia.

3. **Given** un owner haciendo upgrade, **When** Culqi no responde o devuelve
   timeout, **Then** ve el mensaje "Estamos procesando tu pago. Te
   notificaremos por email cuando se confirme." El pago se encola con
   reintentos automáticos (inmediato → 2 min → 10 min). Si los 3 fallan,
   recibe email de fallo. Si se confirma, recibe email de éxito y su plan
   queda actualizado. El plan NO cambia hasta confirmación de Culqi.

4. **Given** un owner con plan PYME haciendo downgrade a Starter, **When**
   confirma el cambio, **Then** ve un aviso de qué módulos perderá y la
   fecha efectiva del cambio.

---

### User Story 8 — Invitar usuarios al equipo (Priority: P3)

Como owner o admin, quiero invitar usuarios a mi empresa para que mi
equipo pueda usar el sistema.

**Why this priority**: Funcionalidad de equipo; depende de auth y planes
funcionando (P1/P2).

**Independent Test**: Un owner puede invitar a un nuevo usuario por email,
y ese usuario puede activar su cuenta y acceder al sistema.

**Acceptance Scenarios**:

1. **Given** un owner con plan Starter y ya 3 usuarios activos, **When**
   intenta invitar un cuarto, **Then** ve el mensaje "Tu plan permite máximo
   3 usuarios. Mejora tu plan para agregar más".

2. **Given** un owner que envía invitación a un email válido, **When** el
   invitado hace clic en el link dentro de las 48 horas, **Then** puede
   crear su contraseña y accede al sistema con el rol asignado.

3. **Given** un invitado que recibe un link de invitación, **When** intenta
   usarlo después de 48 horas, **Then** ve el mensaje "Esta invitación ha
   expirado".

---

### User Story 9 — Gestionar usuarios existentes (Priority: P3)

Como owner o admin, quiero gestionar los usuarios de mi empresa para
mantener el control de accesos.

**Why this priority**: Operaciones de mantenimiento; depende de que la
invitación funcione.

**Independent Test**: Un owner puede cambiar el rol de un usuario y
desactivarlo sin eliminar su historial.

**Acceptance Scenarios**:

1. **Given** un owner con múltiples usuarios, **When** desactiva a un
   empleado, **Then** ese empleado no puede iniciar sesión pero su historial
   se conserva.

2. **Given** un owner que es el único owner activo, **When** intenta
   desactivarse a sí mismo, **Then** ve el mensaje "Debe existir al menos
   un owner activo".

3. **Given** un usuario de Empresa A consultando la lista de usuarios,
   **When** visualiza la lista, **Then** solo ve usuarios de Empresa A,
   nunca de Empresa B.

---

### User Story 10 — Ver y editar perfil propio (Priority: P3)

Como cualquier usuario autenticado, quiero ver y editar mi perfil para
mantener mis datos actualizados.

**Why this priority**: Self-service básico; no bloquea otras
funcionalidades.

**Independent Test**: Cualquier usuario puede actualizar su nombre y
cambiar su contraseña de forma autónoma.

**Acceptance Scenarios**:

1. **Given** un usuario en su página de perfil, **When** actualiza su nombre
   y guarda, **Then** el nuevo nombre aparece en el navbar.

2. **Given** un usuario intentando cambiar su contraseña, **When** ingresa
   la contraseña actual incorrecta, **Then** ve el mensaje "La contraseña
   actual es incorrecta" y la contraseña no cambia.

---

### Edge Cases

- ¿Qué pasa si el servicio de email cae durante el registro? → El usuario
  queda registrado y puede acceder; el email de bienvenida se reintenta
  automáticamente; no bloquea el registro.
- ¿Qué pasa si el procesador de pagos rechaza el pago durante un upgrade?
  → El plan no cambia; el usuario puede intentarlo con otra tarjeta.
- ¿Qué pasa si el trial vence durante una sesión activa? → En el próximo
  request el sistema detecta el estado vencido y aplica restricción de solo
  lectura (GET permitidos, POST/PUT/PATCH/DELETE → 402) con banner de aviso.
- ¿Qué pasa si dos owners de la misma empresa intentan hacer upgrade
  simultáneamente? → Solo el primero en completar produce el cambio; el
  segundo ve el plan ya actualizado.
- ¿Qué pasa si se intenta registrar un RUC ya existente en otra empresa?
  → "Ya existe una empresa con este RUC".

---

## Requirements *(mandatory)*

### Functional Requirements

**Registro y empresa:**
- **FR-001**: El sistema DEBE permitir el registro de una empresa con RUC
  (exactamente 11 dígitos numéricos), razón social, nombre comercial,
  dirección y régimen tributario (RER / RG / RMT).
- **FR-002**: El sistema DEBE validar unicidad del RUC y del email al
  momento del registro, informando con mensaje específico si ya existen.
- **FR-003**: El sistema DEBE crear automáticamente una suscripción trial
  de 30 días al completar el registro, sin requerir tarjeta de crédito.
- **FR-004**: El RUC NO DEBE ser editable una vez registrado.
- **FR-005**: El email del usuario NO DEBE ser editable una vez registrado.

**Autenticación:**
- **FR-006**: El sistema DEBE generar una sesión de acceso de corta duración
  (15 minutos) renovable automáticamente mediante una sesión de larga
  duración (30 días) almacenada de forma segura e inaccesible desde scripts
  del navegador.
- **FR-007**: Después de 5 intentos de login fallidos, el sistema DEBE
  bloquear temporalmente el acceso por 15 minutos.
- **FR-008**: El sistema DEBE invalidar TODAS las sesiones activas del
  usuario en los siguientes eventos: (1) logout estándar, (2) cambio de
  contraseña, (3) desactivación del usuario por un owner/admin, (4) cancelación
  de suscripción. Los dispositivos con sesión activa detectan el cierre en el
  próximo request (401 → redirect /login).
- **FR-009**: Los links de recuperación de contraseña DEBEN expirar a los
  60 minutos de generados y ser de un solo uso.

**Planes y módulos:**
- **FR-010**: El sistema DEBE ofrecer 3 planes: Starter (S/. 59/mes, 3
  usuarios), PYME (S/. 129/mes, 15 usuarios), Enterprise (S/. 299/mes,
  ilimitados).
- **FR-011**: El menú de navegación DEBE mostrar únicamente los módulos
  del plan activo; los no incluidos DEBEN mostrarse bloqueados con opción
  de upgrade.
- **FR-012**: El backend DEBE rechazar con error de autorización (403)
  cualquier acceso a un módulo no incluido en el plan activo, sin depender
  del frontend.
- **FR-012b**: Cuando la suscripción está en estado `vencida`, el backend
  DEBE permitir todos los métodos GET (ver, listar, exportar, descargar) y
  DEBE bloquear todos los métodos POST, PUT, PATCH y DELETE con error 402,
  excepto `POST /api/suscripcion/upgrade` que siempre DEBE estar disponible.
  El mensaje de error DEBE ser: "Tu suscripción ha vencido. Activa tu plan
  para continuar operando". Esta restricción aplica a TODOS los módulos sin
  excepción.
- **FR-012c**: El dashboard DEBE mostrar un banner persistente con CTA de
  pago mientras la suscripción esté en estado `vencida`.
- **FR-013**: Un upgrade de plan DEBE ser efectivo inmediatamente tras la
  confirmación exitosa de Culqi. Si Culqi devuelve timeout, el intento DEBE
  encolarse con hasta 3 reintentos exponenciales (inmediato, 2 min, 10 min).
  El plan NO DEBE cambiar hasta recibir confirmación. Si los 3 reintentos
  fallan, el usuario DEBE recibir un email de fallo. Si se confirma, DEBE
  recibir email de éxito y su sesión queda renovada en el próximo login.
  Cada intento DEBE registrarse en el audit_log.
- **FR-013b**: La transición de `vencida` a `cancelada` DEBE ocurrir
  automáticamente a los 7 días de ingresar al estado `vencida`, mediante
  un proceso programado en background.
- **FR-013c**: Cuando la suscripción está en estado `cancelada`, el sistema
  DEBE redirigir al usuario a /reactivar tras el login. El middleware DEBE
  permitir solo el acceso a /reactivar; cualquier otra ruta DEBE redirigir
  a /reactivar. No se permite acceso a ningún dato (ni GET). Al reactivar
  exitosamente, la suscripción pasa a `activa` y el usuario es redirigido
  al dashboard.
- **FR-014**: Un downgrade de plan DEBE ser efectivo al inicio del siguiente
  período de facturación.

**Usuarios y equipo:**
- **FR-015**: El sistema DEBE respetar el límite de usuarios por plan al
  momento de invitar, incluso si se realizan invitaciones simultáneas.
- **FR-016**: Los usuarios desactivados NO DEBEN poder iniciar sesión; su
  historial DEBE conservarse.
- **FR-017**: Un usuario NO DEBE poder ver ni modificar datos de otra
  empresa bajo ninguna circunstancia.
- **FR-018**: DEBE existir al menos un owner activo por empresa en todo
  momento.
- **FR-019**: Los links de invitación DEBEN expirar a las 48 horas de
  generados y ser de un solo uso.

**Comunicaciones:**
- **FR-020**: El sistema DEBE enviar los siguientes emails de forma
  asíncrona: bienvenida al registrarse, invitación al ser invitado, link
  de recuperación, recordatorios de trial (días 25/28/30), confirmación
  de upgrade exitoso, aviso de pago fallido (incluyendo timeout de Culqi
  tras 3 reintentos agotados), confirmación de upgrade procesado en background,
  y confirmación de reactivación desde estado `cancelada` ("¡Tu cuenta está
  activa nuevamente! Bienvenido de vuelta.").

**Auditoría:**
- **FR-021**: El sistema DEBE registrar en audit_log las siguientes acciones
  con actor, fecha/hora y IP de origen: login, logout_all (cierre de todas
  las sesiones), registro de empresa, cambio de plan, cambio de rol de usuario,
  desactivación de usuario, reactivación de suscripción cancelada, e intentos
  de pago (incluyendo fallos y timeouts de Culqi).

### Key Entities

- **Empresa**: Unidad de aislamiento (tenant). Atributos: RUC, razón
  social, nombre comercial, dirección, régimen tributario, logo. El RUC
  es inmutable post-registro.
- **Plan**: Define precio mensual, límite de usuarios y módulos habilitados.
  3 planes fijos: Starter, PYME, Enterprise.
- **Suscripción**: Vincula empresa a plan. Estados: trial → activa →
  vencida → cancelada. Transición vencida→cancelada: automática a los 7 días.
  Diferencia clave: `vencida` permite GET (solo lectura); `cancelada` bloquea
  todo acceso a datos y redirige a /reactivar. Datos conservados 90 días post
  cancelación. Define fechas de inicio, vencimiento, próximo cobro y
  fecha de cancelación.
- **Usuario**: Pertenece a una empresa. Atributos: nombre, email (inmutable),
  contraseña, rol (owner/admin/empleado/contador), estado (activo/inactivo).
- **AuditLog**: Registro inmutable de acciones críticas con actor,
  timestamp, acción, entidad afectada y IP.

---

## Clarifications

### Session 2026-03-04

- Q: ¿El logout invalida solo la sesión actual o todas las sesiones del usuario? → A: Invalida TODAS las sesiones activas en todos los dispositivos. Regla global: logout estándar, cambio de contraseña, desactivación de usuario por owner, y cancelación de suscripción — todos invalidan la totalidad de sesiones activas. Audit log registra "logout_all". La opción "cerrar todas las sesiones" separada no es necesaria.
- Q: ¿Qué ve y puede hacer el usuario cuando su suscripción está en estado `cancelada`? → A: Login permitido → redirige a /reactivar (no al dashboard). La pantalla muestra los 3 planes con Culqi y aviso de retención de datos (fecha_cancelación + 90 días). Middleware: cancelada + ruta ≠ /reactivar → redirect /reactivar. Sin acceso a datos (distinto de `vencida` que permite GET). Pago exitoso: suscripción → activa, JWT renovado, redirect dashboard. Email automático de reactivación.
- Q: ¿Qué ocurre cuando Culqi no responde o devuelve timeout durante un upgrade? → A: Se muestra "Estamos procesando tu pago. Te notificaremos por email cuando se confirme." El pago se encola en un Job con reintentos exponenciales (inmediato → 2min → 10min). Si los 3 fallan: email de fallo al usuario. Si se confirma: email de éxito + JWT renovado en el próximo login. El plan NO cambia hasta confirmación de Culqi. Cada intento se registra en audit_log.
- Q: ¿Qué operaciones están permitidas cuando la suscripción está en estado `vencida`? → A: GET ilimitado (ver/exportar/descargar PDF); POST/PUT/PATCH/DELETE → 402 con mensaje "Tu suscripción ha vencido. Activa tu plan para continuar operando"; banner persistente en dashboard; único POST permitido: `/api/suscripcion/upgrade`; aplica a TODOS los módulos sin excepción.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un visitante puede completar el registro de su empresa en
  menos de 3 minutos desde que llega a la página de registro.
- **SC-002**: El 100% de los intentos de acceso a módulos no contratados
  son bloqueados (0 bypasses detectados en pruebas).
- **SC-003**: Empresa A no puede acceder a ningún dato de Empresa B
  (0 fugas entre tenants en todas las pruebas de aislamiento).
- **SC-004**: Los usuarios no notan la renovación automática de sesión
  durante jornadas de hasta 8 horas de uso continuo.
- **SC-005**: El tiempo de respuesta de cualquier acción del módulo no
  supera 500ms aunque el servicio de email esté caído (emails en background).
- **SC-006**: El límite de usuarios por plan se respeta en el 100% de los
  intentos, incluyendo invitaciones simultáneas.
- **SC-007**: Los nuevos módulos tras un upgrade aparecen en el menú de
  navegación en menos de 5 segundos tras el pago exitoso.
- **SC-008**: Todas las pruebas automatizadas del módulo pasan con 0 fallas
  antes del release (php artisan test --filter=Core → 0 failures).