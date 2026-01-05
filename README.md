# Casa Alba - Headless Authentication Plugin

Plugin de WordPress para autenticación JWT completa en aplicaciones headless.

## Características

### 🔐 Autenticación JWT
- Autenticación mediante **correo electrónico** (no usuario) y contraseña
- Generación de tokens JWT con algoritmo seleccionable (HS256, HS384, HS512)
- Validación de tokens JWT
- Refresco de tokens expirados/inválidos
- Revocación de tokens (cierre de sesión)

### 🤖 Cloudflare Turnstile
- Integración opcional con Cloudflare Turnstile
- Validación anti-bot en el proceso de login
- Configurable desde el panel de administración

### ⚡ Rate Limiting
- Limitación de intentos de login fallidos
- Configurable: número de intentos, ventana de tiempo y duración de bloqueo
- Protección por IP y email

### 🖥️ Gestión de Sesiones
- Administración de múltiples sesiones por usuario
- Tracking detallado:
  - Tipo de dispositivo (móvil, tablet, escritorio)
  - Navegador y sistema operativo
  - Dirección IP
  - Última actividad
- Límite configurable de sesiones por usuario
- Revocación individual o masiva de sesiones

### 📊 Analítica y Monitoreo
- Dashboard completo de estadísticas
- Métricas en tiempo real:
  - Usuarios conectados
  - Tokens activos
  - Inicios de sesión exitosos/fallidos
  - Tasa de éxito
- Historial de actividad
- Top usuarios más activos
- Registro de intentos fallidos

## Instalación

1. Copia la carpeta `headless-authentication` a `/wp-content/plugins/`
2. Activa el plugin desde el panel de WordPress
3. Ve a **Headless Auth** > **Configuración** para configurar el plugin

## Configuración

### JWT
- **Algoritmo**: Selecciona el algoritmo de firma (HS256, HS384, HS512)
- **Clave Secreta**: Genera una clave secreta fuerte (se genera automáticamente)
- **Expiración del Token**: Duración del token de acceso (por defecto: 1 hora)
- **Expiración del Refresh Token**: Duración del refresh token (por defecto: 7 días)

### Cloudflare Turnstile
1. Obtén tus claves en [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Activa la opción en la configuración
3. Ingresa tu Site Key y Secret Key

### Rate Limiting
- **Máximo de Intentos**: Número de intentos fallidos permitidos
- **Ventana de Tiempo**: Período en el que se cuentan los intentos
- **Duración del Bloqueo**: Tiempo que durará el bloqueo

### Sesiones
- **Límite de Sesiones**: Número máximo de sesiones simultáneas por usuario (0 = ilimitado)

## API Endpoints

### Autenticación

#### Login
```
POST /wp-json/casa-alba/v1/auth/login
```
**Body:**
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña",
  "turnstile_token": "token_opcional_si_está_habilitado"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "display_name": "Usuario",
    "roles": ["customer"]
  }
}
```

#### Logout
```
POST /wp-json/casa-alba/v1/auth/logout
Authorization: Bearer {token}
```

#### Refresh Token
```
POST /wp-json/casa-alba/v1/auth/refresh
```
**Body:**
```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

#### Validate Token
```
POST /wp-json/casa-alba/v1/auth/validate
```
**Body:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

#### Get Current User
```
GET /wp-json/casa-alba/v1/auth/me
Authorization: Bearer {token}
```

### Gestión de Sesiones

#### Get User Sessions
```
GET /wp-json/casa-alba/v1/auth/sessions
Authorization: Bearer {token}
```

#### Revoke Specific Session
```
DELETE /wp-json/casa-alba/v1/auth/sessions/{id}
Authorization: Bearer {token}
```

#### Revoke All Sessions
```
DELETE /wp-json/casa-alba/v1/auth/sessions/all
Authorization: Bearer {token}
```
**Query Params:**
- `keep_current=true` - Mantiene la sesión actual activa

### Configuración

#### Get Turnstile Config
```
GET /wp-json/casa-alba/v1/auth/turnstile-key
```

## Uso en el Frontend

### Ejemplo con Axios (React/Vue)

```javascript
import axios from 'axios';

// Configurar axios con interceptor para tokens
const api = axios.create({
  baseURL: 'https://tudominio.com/wp-json/casa-alba/v1'
});

// Interceptor para añadir token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para refrescar token expirado
api.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      const refreshToken = localStorage.getItem('refresh_token');
      if (refreshToken) {
        try {
          const { data } = await axios.post(
            'https://tudominio.com/wp-json/casa-alba/v1/auth/refresh',
            { refresh_token: refreshToken }
          );
          localStorage.setItem('access_token', data.token);
          localStorage.setItem('refresh_token', data.refresh_token);
          
          // Reintentar request original
          error.config.headers.Authorization = `Bearer ${data.token}`;
          return axios(error.config);
        } catch (refreshError) {
          // Refresh falló, redirigir a login
          localStorage.clear();
          window.location.href = '/login';
        }
      }
    }
    return Promise.reject(error);
  }
);

// Login
export const login = async (email, password, turnstileToken = null) => {
  const { data } = await api.post('/auth/login', {
    email,
    password,
    turnstile_token: turnstileToken
  });
  
  localStorage.setItem('access_token', data.token);
  localStorage.setItem('refresh_token', data.refresh_token);
  
  return data.user;
};

// Logout
export const logout = async () => {
  await api.post('/auth/logout');
  localStorage.clear();
};

// Get current user
export const getCurrentUser = async () => {
  const { data } = await api.get('/auth/me');
  return data;
};
```

## Seguridad

### Recomendaciones

1. **Clave Secreta JWT**: 
   - Usa una clave fuerte (mínimo 64 caracteres)
   - Nunca la compartas ni la expongas en el código frontend
   - Cámbiala periódicamente (invalidará todas las sesiones)

2. **HTTPS**:
   - Siempre usa HTTPS en producción
   - Los tokens viajan en headers y son vulnerables sin encriptación

3. **Rate Limiting**:
   - Mantén activado el rate limiting
   - Ajusta los límites según tus necesidades

4. **Turnstile**:
   - Actívalo para proteger contra bots
   - Es especialmente importante en sitios públicos

5. **Sesiones**:
   - Limita el número de sesiones por usuario
   - Revisa periódicamente las sesiones activas

## Mantenimiento

### Limpieza Automática

El plugin limpia automáticamente:
- Sesiones expiradas
- Registros de rate limiting antiguos (>24 horas)
- Analítica antigua (>90 días)

### Monitoreo

Revisa regularmente:
- Panel de **Analítica** para detectar patrones sospechosos
- **Sesiones Activas** para verificar sesiones legítimas
- Intentos de login fallidos

## Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.7 o superior

## Soporte

Para reportar problemas o sugerencias, contacta a través de:
- Email: soporte@productoscasaalba.cl
- Web: https://productoscasaalba.cl

## Licencia

GPL v3

## Changelog

### 1.0.0 (2026-01-04)
- Lanzamiento inicial
- Autenticación JWT completa
- Integración con Cloudflare Turnstile
- Rate limiting
- Gestión de sesiones
- Analítica y monitoreo

---

**Desarrollado por Casa Alba**

