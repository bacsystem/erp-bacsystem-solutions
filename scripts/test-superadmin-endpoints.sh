#!/usr/bin/env bash
# =============================================================================
# test-superadmin-endpoints.sh
# Valida todos los endpoints del módulo superadmin + core/auth
# Uso: ./scripts/test-superadmin-endpoints.sh [BASE_URL]
# =============================================================================

BASE_URL="${1:-http://localhost:8000}"
API="$BASE_URL/superadmin/api"
CORE="$BASE_URL/api"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

PASS=0
FAIL=0
SKIP=0

# -----------------------------------------------------------------------------
# Helpers
# -----------------------------------------------------------------------------

assert() {
  local label="$1"
  local expected_status="$2"
  local actual_status="$3"
  local body="$4"

  if [ "$actual_status" = "$expected_status" ]; then
    echo -e "  ${GREEN}✓${RESET} $label ${CYAN}[$actual_status]${RESET}"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${RESET} $label ${RED}[esperado: $expected_status | obtenido: $actual_status]${RESET}"
    if [ -n "$body" ]; then
      echo -e "    ${YELLOW}→ $(echo "$body" | head -c 200)${RESET}"
    fi
    FAIL=$((FAIL + 1))
  fi
}

request() {
  local method="$1"
  local url="$2"
  local token="$3"
  local data="$4"

  local args=(-s -o /tmp/sa_body.txt -w "%{http_code}" -X "$method")

  if [ -n "$token" ]; then
    args+=(-H "Authorization: Bearer $token")
  fi

  args+=(-H "Content-Type: application/json" -H "Accept: application/json")

  if [ -n "$data" ]; then
    args+=(-d "$data")
  fi

  local status
  status=$(curl "${args[@]}" "$url")
  echo "$status"
}

body() { cat /tmp/sa_body.txt; }
json_field() { body | python3 -c "import sys,json; d=json.load(sys.stdin); print($1)" 2>/dev/null; }

section() { echo -e "\n${BOLD}${CYAN}▶ $1${RESET}"; }

# -----------------------------------------------------------------------------
# Verificar servidor
# -----------------------------------------------------------------------------

echo -e "${BOLD}=== Test Suite: Superadmin API + Core/Auth ===${RESET}"
echo -e "Superadmin: ${CYAN}$API${RESET}"
echo -e "Core/Auth:  ${CYAN}$CORE${RESET}\n"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL")
if [ "$STATUS" = "000" ]; then
  echo -e "${RED}✗ Servidor no responde en $BASE_URL. Abortando.${RESET}"
  exit 1
fi
echo -e "${GREEN}✓ Servidor activo${RESET} [$BASE_URL]"

# -----------------------------------------------------------------------------
# HU-01: Autenticación
# -----------------------------------------------------------------------------

section "HU-01 — Autenticación"

# Login inválido
STATUS=$(request POST "$API/auth/login" "" '{"email":"noexiste@test.com","password":"wrong"}')
assert "Login con credenciales incorrectas → 401" "401" "$STATUS" "$(body)"

# Login inactivo (no podemos garantizarlo sin un superadmin inactivo, skip)
echo -e "  ${YELLOW}⊘${RESET} Login con superadmin inactivo → skipped (requiere fixture)"
SKIP=$((SKIP + 1))

# Login válido
STATUS=$(request POST "$API/auth/login" "" "{\"email\":\"admin@operaai.com\",\"password\":\"cambiar_esto_en_produccion\"}")
assert "Login con credenciales válidas → 200" "200" "$STATUS" "$(body)"

TOKEN=$(json_field "d['data']['access_token']")
SUPERADMIN_ID=$(json_field "d['data']['superadmin']['id']")

if [ -z "$TOKEN" ]; then
  echo -e "${RED}✗ No se pudo obtener token. Abortando tests protegidos.${RESET}"
  exit 1
fi
echo -e "  ${CYAN}→ Token obtenido: ${TOKEN:0:20}...${RESET}"

# Sin token → 401
STATUS=$(request GET "$API/dashboard" "")
assert "Ruta protegida sin token → 401" "401" "$STATUS" "$(body)"

# -----------------------------------------------------------------------------
# HU-02: Dashboard
# -----------------------------------------------------------------------------

section "HU-02 — Dashboard"

STATUS=$(request GET "$API/dashboard" "$TOKEN")
assert "GET /dashboard → 200" "200" "$STATUS" "$(body)"

MRR=$(json_field "str(d['data']['mrr_total'])")
TRIAL=$(json_field "str(d['data']['totales_por_estado']['trial'])")
HIST=$(json_field "str(len(d['data']['mrr_historico']))")
echo -e "  ${CYAN}→ MRR total: S/$MRR | trials: $TRIAL | historial: $HIST meses${RESET}"

# -----------------------------------------------------------------------------
# HU-03: Empresas — Listado y Detalle
# -----------------------------------------------------------------------------

section "HU-03 — Empresas (Listado & Detalle)"

STATUS=$(request GET "$API/empresas" "$TOKEN")
assert "GET /empresas → 200" "200" "$STATUS" "$(body)"

EMPRESA_ID=$(json_field "d['data'][0]['id']" 2>/dev/null || json_field "d['data']['data'][0]['id']" 2>/dev/null)
EMPRESA_NOMBRE=$(json_field "d['data'][0]['nombre']" 2>/dev/null || json_field "d['data']['data'][0]['nombre']" 2>/dev/null)
TOTAL=$(json_field "len(d['data'])" 2>/dev/null || json_field "d['data']['meta']['total']" 2>/dev/null)
echo -e "  ${CYAN}→ Total empresas: $TOTAL | Primera: $EMPRESA_NOMBRE${RESET}"

# Filtros
STATUS=$(request GET "$API/empresas?q=xyz_no_existe" "$TOKEN")
assert "GET /empresas?q=xyz_no_existe → 200 (filtro sin resultados)" "200" "$STATUS" "$(body)"

STATUS=$(request GET "$API/empresas?estado=trial" "$TOKEN")
assert "GET /empresas?estado=trial → 200" "200" "$STATUS" "$(body)"

if [ -n "$EMPRESA_ID" ]; then
  STATUS=$(request GET "$API/empresas/$EMPRESA_ID" "$TOKEN")
  assert "GET /empresas/:id → 200" "200" "$STATUS" "$(body)"
  echo -e "  ${CYAN}→ Detalle empresa: $EMPRESA_NOMBRE${RESET}"

  # ID inválido
  STATUS=$(request GET "$API/empresas/00000000-0000-0000-0000-000000000000" "$TOKEN")
  assert "GET /empresas/:id (no existe) → 404" "404" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} Detalle empresa → skipped (no hay empresas)"
  SKIP=$((SKIP + 2))
fi

# -----------------------------------------------------------------------------
# HU-04: Suspender / Activar empresa
# -----------------------------------------------------------------------------

section "HU-04 — Suspender / Activar"

if [ -n "$EMPRESA_ID" ]; then
  # Suspender empresa en estado trial/activa
  STATUS=$(request POST "$API/empresas/$EMPRESA_ID/suspender" "$TOKEN" '{"motivo":"Test de suspensión automatizado"}')
  assert "POST /empresas/:id/suspender → 200 o 422" "$([ "$STATUS" = "200" ] || [ "$STATUS" = "422" ] && echo "$STATUS" || echo "200")" "$STATUS" "$(body)"
  SUSPEND_STATUS="$STATUS"

  if [ "$SUSPEND_STATUS" = "200" ]; then
    echo -e "  ${CYAN}→ Empresa suspendida correctamente${RESET}"

    # Suspender de nuevo → 422
    STATUS=$(request POST "$API/empresas/$EMPRESA_ID/suspender" "$TOKEN" '{"motivo":"Doble suspensión"}')
    assert "POST /suspender empresa ya suspendida → 422" "422" "$STATUS" "$(body)"

    # Activar
    STATUS=$(request POST "$API/empresas/$EMPRESA_ID/activar" "$TOKEN" '{"motivo":"Reactivación de test"}')
    assert "POST /empresas/:id/activar → 200" "200" "$STATUS" "$(body)"

    if [ "$STATUS" = "200" ]; then
      # Activar de nuevo → 422
      STATUS=$(request POST "$API/empresas/$EMPRESA_ID/activar" "$TOKEN" '{"motivo":"Doble activación"}')
      assert "POST /activar empresa ya activa → 422" "422" "$STATUS" "$(body)"
    fi
  else
    echo -e "  ${YELLOW}⊘${RESET} Empresa ya cancelada, no se puede suspender"
    SKIP=$((SKIP + 2))
  fi
else
  echo -e "  ${YELLOW}⊘${RESET} Suspender/Activar → skipped (no hay empresas)"
  SKIP=$((SKIP + 4))
fi

# -----------------------------------------------------------------------------
# HU-05: Impersonación
# -----------------------------------------------------------------------------

section "HU-05 — Impersonación"

if [ -n "$EMPRESA_ID" ]; then
  STATUS=$(request POST "$API/empresas/$EMPRESA_ID/impersonar" "$TOKEN")
  assert "POST /empresas/:id/impersonar → 200 o 422" "$([ "$STATUS" = "200" ] || [ "$STATUS" = "422" ] && echo "$STATUS" || echo "200")" "$STATUS" "$(body)"

  if [ "$STATUS" = "200" ]; then
    IMP_TOKEN=$(json_field "d['data']['access_token']")
    echo -e "  ${CYAN}→ Token impersonado: ${IMP_TOKEN:0:20}...${RESET}"

    # Terminar impersonación
    STATUS=$(request DELETE "$API/empresas/$EMPRESA_ID/impersonar" "$TOKEN")
    assert "DELETE /empresas/:id/impersonar → 200" "200" "$STATUS" "$(body)"
  else
    echo -e "  ${YELLOW}⊘${RESET} Sin owner activo para impersonar (422 esperado)"
    SKIP=$((SKIP + 1))
  fi
else
  echo -e "  ${YELLOW}⊘${RESET} Impersonación → skipped (no hay empresas)"
  SKIP=$((SKIP + 2))
fi

# -----------------------------------------------------------------------------
# HU-06: Planes
# -----------------------------------------------------------------------------

section "HU-06 — Planes"

STATUS=$(request GET "$API/planes" "$TOKEN")
assert "GET /planes → 200" "200" "$STATUS" "$(body)"

PLAN_ID=$(json_field "d['data'][0]['id']")
PLAN_NOMBRE=$(json_field "d['data'][0]['nombre']")
PLAN_PRECIO=$(json_field "str(d['data'][0]['precio_mensual'])")
echo -e "  ${CYAN}→ Plan: $PLAN_NOMBRE | Precio: S/$PLAN_PRECIO${RESET}"

if [ -n "$PLAN_ID" ]; then
  # Update plan (sin cambios reales, mismo precio)
  STATUS=$(request PUT "$API/planes/$PLAN_ID" "$TOKEN" "{\"precio_mensual\":$PLAN_PRECIO}")
  assert "PUT /planes/:id → 200" "200" "$STATUS" "$(body)"

  # Validación: precio negativo → 422
  STATUS=$(request PUT "$API/planes/$PLAN_ID" "$TOKEN" '{"precio_mensual":-10}')
  assert "PUT /planes/:id precio negativo → 422" "422" "$STATUS" "$(body)"

  # Aplicar descuento
  STATUS=$(request POST "$API/empresas/$EMPRESA_ID/descuento" "$TOKEN" \
    '{"tipo":"porcentaje","valor":10,"motivo":"Test descuento 10%"}')
  assert "POST /empresas/:id/descuento → 200 o 422" "$([ "$STATUS" = "200" ] || [ "$STATUS" = "422" ] && echo "$STATUS" || echo "200")" "$STATUS" "$(body)"

  if [ "$STATUS" = "200" ]; then
    DESCUENTO_ID=$(json_field "d['data']['id']")
    echo -e "  ${CYAN}→ Descuento aplicado: id=$DESCUENTO_ID${RESET}"

    if [ -n "$DESCUENTO_ID" ]; then
      STATUS=$(request DELETE "$API/empresas/$EMPRESA_ID/descuento/$DESCUENTO_ID" "$TOKEN")
      assert "DELETE /empresas/:id/descuento/:id → 200" "200" "$STATUS" "$(body)"
    fi
  fi
else
  echo -e "  ${YELLOW}⊘${RESET} Planes → skipped (sin planes en DB)"
  SKIP=$((SKIP + 3))
fi

# -----------------------------------------------------------------------------
# HU-07: Logs
# -----------------------------------------------------------------------------

section "HU-07 — Logs Globales"

STATUS=$(request GET "$API/logs" "$TOKEN")
assert "GET /logs → 200" "200" "$STATUS" "$(body)"
LOG_TOTAL=$(json_field "d['data']['meta']['total']" 2>/dev/null || echo "?")
echo -e "  ${CYAN}→ Total logs: $LOG_TOTAL${RESET}"

STATUS=$(request GET "$API/logs?accion=login" "$TOKEN")
assert "GET /logs?accion=login → 200" "200" "$STATUS" "$(body)"

STATUS=$(request GET "$API/logs/resumen" "$TOKEN")
assert "GET /logs/resumen → 200" "200" "$STATUS" "$(body)"

STATUS=$(request GET "$API/logs/export" "$TOKEN")
assert "GET /logs/export (CSV) → 200" "200" "$STATUS" "$(body)"
CONTENT_TYPE=$(curl -s -o /dev/null -w "%{content_type}" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$API/logs/export")
if echo "$CONTENT_TYPE" | grep -q "text/csv\|application/octet"; then
  echo -e "  ${CYAN}→ Content-Type CSV correcto: $CONTENT_TYPE${RESET}"
else
  echo -e "  ${YELLOW}→ Content-Type: $CONTENT_TYPE${RESET}"
fi

# -----------------------------------------------------------------------------
# Aislamiento: token de tenant en ruta superadmin
# -----------------------------------------------------------------------------

section "Aislamiento — Token cruzado"

# Superadmin token en ruta tenant → 401
STATUS=$(request GET "$BASE_URL/api/me" "$TOKEN")
assert "Token superadmin en /api/me (ruta tenant) → 401" "401" "$STATUS" "$(body)"

# -----------------------------------------------------------------------------
# Logout
# -----------------------------------------------------------------------------

section "HU-01 — Logout"

STATUS=$(request POST "$API/auth/logout" "$TOKEN")
assert "POST /auth/logout → 200" "200" "$STATUS" "$(body)"

# Token revocado → 401
STATUS=$(request GET "$API/dashboard" "$TOKEN")
assert "Token revocado post-logout → 401" "401" "$STATUS" "$(body)"

# =============================================================================
# CORE / AUTH
# =============================================================================

echo -e "\n${BOLD}════════════════════════════════════════${RESET}"
echo -e "${BOLD}  CORE / AUTH — /api${RESET}"
echo -e "${BOLD}════════════════════════════════════════${RESET}"

# Datos de prueba únicos por ejecución
TENANT_EMAIL="test_$(date +%s)@acme.com"
TENANT_PASS="Password123!"
TENANT_RUC="20$(shuf -i 100000000-999999999 -n 1)"

# -----------------------------------------------------------------------------
# Público: Planes
# -----------------------------------------------------------------------------

section "Planes (público)"

STATUS=$(request GET "$CORE/planes" "")
assert "GET /api/planes → 200" "200" "$STATUS" "$(body)"
CORE_PLAN_ID=$(json_field "d['data'][0]['id']")
CORE_PLAN_NOMBRE=$(json_field "d['data'][0]['nombre']")
echo -e "  ${CYAN}→ Plan: $CORE_PLAN_NOMBRE (id: $CORE_PLAN_ID)${RESET}"

# -----------------------------------------------------------------------------
# Auth: Register
# -----------------------------------------------------------------------------

section "Auth — Register"

STATUS=$(request POST "$CORE/auth/register" "" '{}')
assert "POST /auth/register sin datos → 422" "422" "$STATUS" "$(body)"

if [ -n "$CORE_PLAN_ID" ]; then
  REG_BODY="{\"plan_id\":\"$CORE_PLAN_ID\",\"empresa\":{\"nombre\":\"Acme Test SA\",\"ruc\":\"$TENANT_RUC\",\"razon_social\":\"Acme Test SA\",\"direccion\":\"Av. Test 123\"},\"usuario\":{\"nombre\":\"Carlos Test\",\"email\":\"$TENANT_EMAIL\",\"password\":\"$TENANT_PASS\",\"password_confirmation\":\"$TENANT_PASS\"}}"

  STATUS=$(request POST "$CORE/auth/register" "" "$REG_BODY")
  assert "POST /auth/register datos válidos → 201" "201" "$STATUS" "$(body)"

  TENANT_TOKEN=$(json_field "d['data']['access_token']")
  TENANT_EMPRESA_ID=$(json_field "d['data']['empresa']['id']")
  TENANT_USER_ID=$(json_field "d['data']['usuario']['id']")
  [ -n "$TENANT_TOKEN" ] && echo -e "  ${CYAN}→ Empresa: $TENANT_EMPRESA_ID | Token: ${TENANT_TOKEN:0:20}...${RESET}"

  # Email duplicado → 422
  STATUS=$(request POST "$CORE/auth/register" "" "$REG_BODY")
  assert "POST /auth/register email duplicado → 422" "422" "$STATUS" "$(body)"

  # RUC inválido → 422
  BAD_BODY="{\"plan_id\":\"$CORE_PLAN_ID\",\"empresa\":{\"nombre\":\"X\",\"ruc\":\"12345\",\"razon_social\":\"X\",\"direccion\":\"X\"},\"usuario\":{\"nombre\":\"X\",\"email\":\"x@x.com\",\"password\":\"$TENANT_PASS\",\"password_confirmation\":\"$TENANT_PASS\"}}"
  STATUS=$(request POST "$CORE/auth/register" "" "$BAD_BODY")
  assert "POST /auth/register RUC inválido → 422" "422" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} Register → skipped (sin planes en DB)"
  SKIP=$((SKIP + 4))
  TENANT_TOKEN=""
fi

# -----------------------------------------------------------------------------
# Auth: Login
# -----------------------------------------------------------------------------

section "Auth — Login"

STATUS=$(request POST "$CORE/auth/login" "" '{"email":"noexiste@x.com","password":"wrong"}')
assert "POST /auth/login credenciales incorrectas → 401" "401" "$STATUS" "$(body)"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request POST "$CORE/auth/login" "" "{\"email\":\"$TENANT_EMAIL\",\"password\":\"$TENANT_PASS\"}")
  assert "POST /auth/login válido → 200" "200" "$STATUS" "$(body)"
  LOGIN_TOKEN=$(json_field "d['data']['access_token']")
  [ -n "$LOGIN_TOKEN" ] && TENANT_TOKEN="$LOGIN_TOKEN"
else
  echo -e "  ${YELLOW}⊘${RESET} Login tenant → skipped"
  SKIP=$((SKIP + 1))
fi

# -----------------------------------------------------------------------------
# Auth: Refresh Token
# -----------------------------------------------------------------------------

section "Auth — Refresh Token"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request POST "$CORE/auth/refresh" "$TENANT_TOKEN")
  assert "POST /auth/refresh → 200" "200" "$STATUS" "$(body)"
  NEW_TOKEN=$(json_field "d['data']['access_token']")
  [ -n "$NEW_TOKEN" ] && TENANT_TOKEN="$NEW_TOKEN"
  echo -e "  ${CYAN}→ Token renovado${RESET}"
else
  echo -e "  ${YELLOW}⊘${RESET} Refresh → skipped"
  SKIP=$((SKIP + 1))
fi

# -----------------------------------------------------------------------------
# Auth: Recuperar / Reset Password
# -----------------------------------------------------------------------------

section "Auth — Recuperar Password"

STATUS=$(request POST "$CORE/auth/recuperar-password" "" '{"email":"noexiste@x.com"}')
assert "POST /recuperar-password email inexistente → 200" "200" "$STATUS" "$(body)"

if [ -n "$TENANT_EMAIL" ]; then
  STATUS=$(request POST "$CORE/auth/recuperar-password" "" "{\"email\":\"$TENANT_EMAIL\"}")
  assert "POST /recuperar-password email válido → 200" "200" "$STATUS" "$(body)"
fi

STATUS=$(request POST "$CORE/auth/reset-password" "" '{"token":"token_invalido","email":"x@x.com","password":"NewPass123!","password_confirmation":"NewPass123!"}')
assert "POST /reset-password token inválido → 422 o 404" "$([ "$STATUS" = "422" ] || [ "$STATUS" = "404" ] && echo "$STATUS" || echo "422")" "$STATUS" "$(body)"

# -----------------------------------------------------------------------------
# Perfil — /me
# -----------------------------------------------------------------------------

section "Perfil — /me"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request GET "$CORE/me" "$TENANT_TOKEN")
  assert "GET /me → 200" "200" "$STATUS" "$(body)"
  ME_NOMBRE=$(json_field "d['data']['nombre']")
  ME_ROL=$(json_field "d['data']['rol']")
  echo -e "  ${CYAN}→ $ME_NOMBRE | rol: $ME_ROL${RESET}"

  STATUS=$(request PUT "$CORE/me" "$TENANT_TOKEN" '{"nombre":"Carlos Actualizado"}')
  assert "PUT /me → 200" "200" "$STATUS" "$(body)"

  STATUS=$(request GET "$CORE/me" "")
  assert "GET /me sin token → 401" "401" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} /me → skipped"
  SKIP=$((SKIP + 3))
fi

# -----------------------------------------------------------------------------
# Empresa
# -----------------------------------------------------------------------------

section "Empresa"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request GET "$CORE/empresa" "$TENANT_TOKEN")
  assert "GET /empresa → 200" "200" "$STATUS" "$(body)"
  EMP_NOMBRE=$(json_field "d['data']['nombre']")
  echo -e "  ${CYAN}→ $EMP_NOMBRE${RESET}"

  STATUS=$(request PUT "$CORE/empresa" "$TENANT_TOKEN" '{"nombre":"Acme Actualizada","direccion":"Av. Lima 456"}')
  assert "PUT /empresa → 200" "200" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} Empresa → skipped"
  SKIP=$((SKIP + 2))
fi

# -----------------------------------------------------------------------------
# Usuarios
# -----------------------------------------------------------------------------

section "Usuarios"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request GET "$CORE/usuarios" "$TENANT_TOKEN")
  assert "GET /usuarios → 200" "200" "$STATUS" "$(body)"
  USR_TOTAL=$(json_field "len(d['data'])" 2>/dev/null || echo "?")
  echo -e "  ${CYAN}→ Usuarios: $USR_TOTAL${RESET}"

  INV_EMAIL="invitado_$(date +%s)@acme.com"
  STATUS=$(request POST "$CORE/usuarios/invitar" "$TENANT_TOKEN" \
    "{\"email\":\"$INV_EMAIL\",\"nombre\":\"Invitado Test\",\"rol\":\"empleado\"}")
  assert "POST /usuarios/invitar → 201" "201" "$STATUS" "$(body)"
  INV_TOKEN=$(json_field "d['data']['token']" 2>/dev/null)
  INV_USER_ID=$(json_field "d['data']['usuario_id']" 2>/dev/null)

  STATUS=$(request POST "$CORE/usuarios/invitar" "$TENANT_TOKEN" \
    '{"email":"x@y.com","nombre":"X","rol":"superheroe"}')
  assert "POST /usuarios/invitar rol inválido → 422" "422" "$STATUS" "$(body)"

  if [ -n "$INV_TOKEN" ]; then
    STATUS=$(request POST "$CORE/auth/activar-cuenta" "" \
      "{\"token\":\"$INV_TOKEN\",\"password\":\"NewPass123!\",\"password_confirmation\":\"NewPass123!\"}")
    assert "POST /auth/activar-cuenta → 200" "200" "$STATUS" "$(body)"
  else
    echo -e "  ${YELLOW}⊘${RESET} Activar cuenta → skipped (token no expuesto en respuesta)"
    SKIP=$((SKIP + 1))
  fi

  STATUS=$(request POST "$CORE/auth/activar-cuenta" "" \
    '{"token":"token_invalido","password":"NewPass123!","password_confirmation":"NewPass123!"}')
  assert "POST /auth/activar-cuenta token inválido → 422" "422" "$STATUS" "$(body)"

  if [ -n "$INV_USER_ID" ]; then
    STATUS=$(request PUT "$CORE/usuarios/$INV_USER_ID/rol" "$TENANT_TOKEN" '{"rol":"admin"}')
    assert "PUT /usuarios/:id/rol → 200" "200" "$STATUS" "$(body)"

    STATUS=$(request PUT "$CORE/usuarios/$INV_USER_ID/desactivar" "$TENANT_TOKEN")
    assert "PUT /usuarios/:id/desactivar → 200" "200" "$STATUS" "$(body)"
  else
    echo -e "  ${YELLOW}⊘${RESET} Actualizar rol / desactivar → skipped (sin usuario_id)"
    SKIP=$((SKIP + 2))
  fi
else
  echo -e "  ${YELLOW}⊘${RESET} Usuarios → skipped"
  SKIP=$((SKIP + 5))
fi

# -----------------------------------------------------------------------------
# Suscripción
# -----------------------------------------------------------------------------

section "Suscripción"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request GET "$CORE/suscripcion" "$TENANT_TOKEN")
  assert "GET /suscripcion → 200" "200" "$STATUS" "$(body)"
  SUS_ESTADO=$(json_field "d['data']['estado']")
  SUS_PLAN=$(json_field "d['data']['plan']['nombre']")
  echo -e "  ${CYAN}→ Estado: $SUS_ESTADO | Plan: $SUS_PLAN${RESET}"

  # Upgrade (Culqi puede fallar en entorno sin credenciales reales — 422 aceptable)
  ALL_PLANS=$(request GET "$CORE/planes" "")
  SEGUNDO_PLAN=$(echo "$ALL_PLANS" | python3 -c \
    "import sys,json; d=json.load(sys.stdin); plans=d['data']; print(plans[1]['id'] if len(plans)>1 else '')" 2>/dev/null)

  if [ -n "$SEGUNDO_PLAN" ]; then
    STATUS=$(request POST "$CORE/suscripcion/upgrade" "$TENANT_TOKEN" \
      "{\"plan_id\":\"$SEGUNDO_PLAN\",\"culqi_token\":\"tkn_test_fake\"}")
    assert "POST /suscripcion/upgrade → 200 o 422" \
      "$([ "$STATUS" = "200" ] || [ "$STATUS" = "422" ] && echo "$STATUS" || echo "200")" \
      "$STATUS" "$(body)"

    STATUS=$(request POST "$CORE/suscripcion/downgrade" "$TENANT_TOKEN" \
      "{\"plan_id\":\"$CORE_PLAN_ID\"}")
    assert "POST /suscripcion/downgrade → 200 o 422" \
      "$([ "$STATUS" = "200" ] || [ "$STATUS" = "422" ] && echo "$STATUS" || echo "200")" \
      "$STATUS" "$(body)"
  else
    echo -e "  ${YELLOW}⊘${RESET} Upgrade/Downgrade → skipped (un solo plan disponible)"
    SKIP=$((SKIP + 2))
  fi
else
  echo -e "  ${YELLOW}⊘${RESET} Suscripción → skipped"
  SKIP=$((SKIP + 3))
fi

# -----------------------------------------------------------------------------
# Aislamiento cruzado
# -----------------------------------------------------------------------------

section "Aislamiento — Token tenant en ruta superadmin"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request GET "$API/dashboard" "$TENANT_TOKEN")
  assert "Token tenant en /superadmin/api/dashboard → 403" "403" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} Aislamiento tenant→superadmin → skipped"
  SKIP=$((SKIP + 1))
fi

# -----------------------------------------------------------------------------
# Logout tenant
# -----------------------------------------------------------------------------

section "Auth — Logout tenant"

if [ -n "$TENANT_TOKEN" ]; then
  STATUS=$(request POST "$CORE/auth/logout" "$TENANT_TOKEN")
  assert "POST /auth/logout → 200" "200" "$STATUS" "$(body)"

  STATUS=$(request GET "$CORE/me" "$TENANT_TOKEN")
  assert "Token revocado post-logout → 401" "401" "$STATUS" "$(body)"
else
  echo -e "  ${YELLOW}⊘${RESET} Logout tenant → skipped"
  SKIP=$((SKIP + 2))
fi

# -----------------------------------------------------------------------------
# Resumen
# -----------------------------------------------------------------------------

TOTAL=$((PASS + FAIL + SKIP))
echo -e "\n${BOLD}=== Resultado ===${RESET}"
echo -e "  ${GREEN}Pasaron: $PASS${RESET}"
echo -e "  ${RED}Fallaron: $FAIL${RESET}"
echo -e "  ${YELLOW}Skipped: $SKIP${RESET}"
echo -e "  Total:   $TOTAL"

if [ "$FAIL" -eq 0 ]; then
  echo -e "\n${GREEN}${BOLD}✓ Todos los tests pasaron${RESET}"
  exit 0
else
  echo -e "\n${RED}${BOLD}✗ $FAIL test(s) fallaron${RESET}"
  exit 1
fi
