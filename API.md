# API Reference - Casa Alba Headless Authentication

## Base URL
```
https://tudominio.com/wp-json/casa-alba/v1
```

## Authentication
La mayoría de los endpoints requieren autenticación mediante JWT token en el header:
```
Authorization: Bearer {token}
```

### Middleware de Autenticación

Este plugin incluye un middleware que permite autenticar usuarios automáticamente en **cualquier endpoint de WordPress REST API** (no solo los endpoints de este plugin) mediante el token JWT.

**Cómo funciona:**
1. Obtienes un token JWT mediante el endpoint `/auth/login`
2. Incluyes el token en el header `Authorization: Bearer {token}` en cualquier petición a la REST API
3. El middleware valida automáticamente el token y autentica al usuario
4. WordPress reconoce al usuario como autenticado para esa petición

**Ejemplo:**
```bash
# Acceder a un endpoint nativo de WordPress con autenticación JWT
curl -X GET https://tudominio.com/wp-json/wp/v2/posts \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

**Nota:** El middleware solo se activa en peticiones REST API y no afecta al frontend tradicional de WordPress.

---

## Endpoints

### 1. Login

Autentica un usuario con email y contraseña.

**Endpoint:** `POST /auth/login`

**Autenticación:** No requerida

**Request Body:**
```json
{
  "email": "string (requerido)",
  "password": "string (requerido)",
  "turnstile_token": "string (opcional, requerido si Turnstile está habilitado)"
}
```

**Response 200:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "display_name": "Usuario Ejemplo",
    "roles": ["customer"]
  }
}
```

**Errores:**
- `400` - Credenciales faltantes
- `401` - Credenciales inválidas
- `429` - Rate limit excedido

---

### 2. Logout

Cierra la sesión actual y revoca el token.

**Endpoint:** `POST /auth/logout`

**Autenticación:** Requerida

**Response 200:**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}
```

---

### 3. Refresh Token

Genera un nuevo par de tokens usando un refresh token válido.

**Endpoint:** `POST /auth/refresh`

**Autenticación:** No requerida

**Request Body:**
```json
{
  "refresh_token": "string (requerido)"
}
```

**Response 200:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Errores:**
- `400` - Refresh token faltante
- `401` - Refresh token inválido o expirado

---

### 4. Validate Token

Valida si un token es válido y retorna su payload.

**Endpoint:** `POST /auth/validate`

**Autenticación:** No requerida

**Request Body:**
```json
{
  "token": "string (requerido)"
}
```

**Response 200:**
```json
{
  "valid": true,
  "payload": {
    "iss": "https://tudominio.com",
    "iat": 1704326400,
    "exp": 1704330000,
    "sub": 1,
    "email": "usuario@ejemplo.com",
    "data": {
      "user": {
        "id": 1,
        "email": "usuario@ejemplo.com",
        "display_name": "Usuario Ejemplo",
        "roles": ["customer"]
      }
    }
  }
}
```

**Response 200 (Token Inválido):**
```json
{
  "valid": false,
  "error": "Token expirado"
}
```

---

### 5. Get Current User

Obtiene la información del usuario autenticado.

**Endpoint:** `GET /auth/me`

**Autenticación:** Requerida

**Response 200:**
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "display_name": "Usuario Ejemplo",
  "first_name": "Usuario",
  "last_name": "Ejemplo",
  "roles": ["customer"]
}
```

**Errores:**
- `401` - Token inválido
- `404` - Usuario no encontrado

---

### 6. Get User Sessions

Obtiene todas las sesiones activas del usuario.

**Endpoint:** `GET /auth/sessions`

**Autenticación:** Requerida

**Response 200:**
```json
{
  "sessions": [
    {
      "id": 1,
      "device_type": "desktop",
      "browser": "Chrome",
      "os": "Windows 10",
      "ip_address": "192.168.1.1",
      "last_activity": "2026-01-04 10:30:00",
      "created_at": "2026-01-04 09:00:00",
      "expires_at": "2026-01-11 09:00:00"
    },
    {
      "id": 2,
      "device_type": "mobile",
      "browser": "Safari",
      "os": "iOS",
      "ip_address": "192.168.1.2",
      "last_activity": "2026-01-04 08:15:00",
      "created_at": "2026-01-03 20:00:00",
      "expires_at": "2026-01-10 20:00:00"
    }
  ]
}
```

---

### 7. Revoke Session

Revoca una sesión específica del usuario.

**Endpoint:** `DELETE /auth/sessions/{id}`

**Autenticación:** Requerida

**URL Parameters:**
- `id` (integer) - ID de la sesión a revocar

**Response 200:**
```json
{
  "success": true,
  "message": "Sesión revocada correctamente"
}
```

**Errores:**
- `404` - Sesión no encontrada
- `403` - No tienes permiso para revocar esta sesión

---

### 8. Revoke All Sessions

Revoca todas las sesiones del usuario.

**Endpoint:** `DELETE /auth/sessions/all`

**Autenticación:** Requerida

**Query Parameters:**
- `keep_current` (boolean, opcional) - Si es `true`, mantiene la sesión actual activa

**Examples:**
```
DELETE /auth/sessions/all
DELETE /auth/sessions/all?keep_current=true
```

**Response 200:**
```json
{
  "success": true,
  "message": "Sesiones revocadas correctamente"
}
```

---

### 9. Get Turnstile Configuration

Obtiene la configuración de Cloudflare Turnstile para el frontend.

**Endpoint:** `GET /auth/turnstile-key`

**Autenticación:** No requerida

**Response 200:**
```json
{
  "enabled": true,
  "site_key": "0x4AAAAAAA..."
}
```

---

## Códigos de Error

### HTTP Status Codes

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 400 | Solicitud inválida (parámetros faltantes o incorrectos) |
| 401 | No autenticado (token inválido o expirado) |
| 403 | No autorizado (sin permisos) |
| 404 | Recurso no encontrado |
| 429 | Rate limit excedido |
| 500 | Error del servidor |

### Error Response Format

```json
{
  "code": "error_code",
  "message": "Descripción del error en español",
  "data": {
    "status": 400
  }
}
```

### Códigos de Error Comunes

| Código | Descripción |
|--------|-------------|
| `missing_credentials` | Email y/o contraseña faltantes |
| `invalid_credentials` | Email o contraseña incorrectos |
| `rate_limit_exceeded` | Demasiados intentos fallidos |
| `turnstile_required` | Token de Turnstile requerido |
| `turnstile_validation_failed` | Validación de Turnstile falló |
| `invalid_token` | Token JWT inválido |
| `token_expired` | Token JWT expirado |
| `invalid_signature` | Firma del token inválida |
| `user_not_found` | Usuario no encontrado |
| `session_not_found` | Sesión no encontrada |
| `missing_refresh_token` | Refresh token faltante |

---

## Rate Limiting

El sistema implementa rate limiting para proteger contra ataques de fuerza bruta.

### Configuración por Defecto
- **Máximo de intentos:** 5
- **Ventana de tiempo:** 15 minutos
- **Duración del bloqueo:** 30 minutos

### Response cuando se excede el límite

```json
{
  "code": "rate_limit_exceeded",
  "message": "Demasiados intentos fallidos. Intenta de nuevo en 28 minutos.",
  "data": {
    "status": 429,
    "retry_after": 1680
  }
}
```

---

## Ejemplos de Uso

### cURL

#### Login
```bash
curl -X POST https://tudominio.com/wp-json/casa-alba/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
    "password": "micontraseña"
  }'
```

#### Get Current User
```bash
curl -X GET https://tudominio.com/wp-json/casa-alba/v1/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

#### Refresh Token
```bash
curl -X POST https://tudominio.com/wp-json/casa-alba/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }'
```

### JavaScript (Axios)

#### Login
```javascript
const response = await axios.post(
  'https://tudominio.com/wp-json/casa-alba/v1/auth/login',
  {
    email: 'usuario@ejemplo.com',
    password: 'micontraseña'
  }
);

const { token, refresh_token, user } = response.data;
```

#### Get Current User
```javascript
const response = await axios.get(
  'https://tudominio.com/wp-json/casa-alba/v1/auth/me',
  {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
);

const user = response.data;
```

### Python (requests)

#### Login
```python
import requests

response = requests.post(
    'https://tudominio.com/wp-json/casa-alba/v1/auth/login',
    json={
        'email': 'usuario@ejemplo.com',
        'password': 'micontraseña'
    }
)

data = response.json()
token = data['token']
refresh_token = data['refresh_token']
```

---

## Seguridad

### HTTPS
⚠️ **IMPORTANTE:** Usa siempre HTTPS en producción. Los tokens viajan en headers y son vulnerables sin encriptación.

### Almacenamiento de Tokens
- **Frontend Web:** Usa `localStorage` o `sessionStorage`
- **Apps Móviles:** Usa keychain (iOS) o keystore (Android)
- **Nunca:** Cookies sin flag `httpOnly` o almacenamiento inseguro

### Renovación de Tokens
Implementa lógica para renovar tokens automáticamente antes de que expiren:

```javascript
// Interceptor de ejemplo
axios.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      // Token expirado, intentar renovar
      const newToken = await refreshToken();
      // Reintentar request con nuevo token
    }
    return Promise.reject(error);
  }
);
```

---

## Webhooks (Próximamente)

Configuración de webhooks para recibir notificaciones de eventos:
- Login exitoso
- Login fallido
- Logout
- Nueva sesión
- Sesión revocada

---

## Soporte

Para reportar problemas o consultas sobre la API:
- Email: soporte@productoscasaalba.cl
- Documentación: https://productoscasaalba.cl/docs/api

