# Guía de Instalación y Configuración

## Instalación

### 1. Instalar el Plugin

#### Opción A: Desde el repositorio
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone [repository-url] headless-authentication
```

#### Opción B: Descarga manual
1. Descarga el plugin
2. Extrae el archivo en `wp-content/plugins/`
3. Renombra la carpeta a `headless-authentication`

### 2. Activar el Plugin

1. Ve al panel de WordPress
2. Navega a **Plugins** > **Plugins instalados**
3. Busca "Casa Alba - Headless Authentication"
4. Haz clic en **Activar**

## Configuración Inicial

### 1. Configuración de JWT

1. Ve a **Headless Auth** > **Configuración**
2. Configura los siguientes parámetros:

#### Algoritmo JWT
- **HS256** (Recomendado) - SHA-256
- **HS384** - SHA-384  
- **HS512** - SHA-512 (Más seguro pero más lento)

#### Clave Secreta JWT
- Haz clic en "Generar Nueva" para crear una clave segura
- **IMPORTANTE**: Guarda esta clave en un lugar seguro
- Cambiar esta clave invalidará todas las sesiones existentes

#### Tiempos de Expiración
- **Token de Acceso**: 3600 segundos (1 hora) - recomendado
- **Refresh Token**: 604800 segundos (7 días) - recomendado

### 2. Configuración de Cloudflare Turnstile (Opcional)

#### Obtener Claves de Turnstile

1. Ve a [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Selecciona tu cuenta
3. Ve a **Turnstile**
4. Crea un nuevo sitio
5. Copia el **Site Key** y **Secret Key**

#### Configurar en WordPress

1. En **Headless Auth** > **Configuración**
2. Activa "Habilitar Turnstile"
3. Pega el **Site Key**
4. Pega el **Secret Key**
5. Guarda los cambios

### 3. Configuración de Rate Limiting

Recomendaciones por tipo de sitio:

#### Sitio de Alto Tráfico
- **Máximo de Intentos**: 10
- **Ventana de Tiempo**: 900 segundos (15 min)
- **Duración del Bloqueo**: 3600 segundos (1 hora)

#### Sitio de Tráfico Medio
- **Máximo de Intentos**: 5
- **Ventana de Tiempo**: 900 segundos (15 min)
- **Duración del Bloqueo**: 1800 segundos (30 min)

#### Sitio de Alto Riesgo/Seguridad
- **Máximo de Intentos**: 3
- **Ventana de Tiempo**: 600 segundos (10 min)
- **Duración del Bloqueo**: 7200 segundos (2 horas)

### 4. Configuración de Sesiones

- **Límite de Sesiones por Usuario**: 
  - 1 = Solo una sesión a la vez
  - 5 = Hasta 5 dispositivos simultáneos (recomendado)
  - 0 = Ilimitado (no recomendado)

## Configuración del Frontend

### Instalar Dependencias

```bash
npm install axios
# o
yarn add axios
```

### Copiar Archivos de Ejemplo

Copia los siguientes archivos a tu proyecto frontend:

```
examples/
├── auth-service.ts        → src/services/
├── LoginForm.tsx          → src/components/auth/
└── SessionsManager.tsx    → src/components/account/
```

### Configurar Variables de Entorno

Crea un archivo `.env` en tu proyecto:

```env
REACT_APP_API_URL=https://tudominio.com/wp-json
```

### Implementar Rutas Protegidas

```typescript
import { authService } from './services/auth-service';

// Protected Route Component
const ProtectedRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      const isValid = await authService.validateToken();
      setIsAuthenticated(isValid);
      setIsLoading(false);
    };
    
    checkAuth();
  }, []);

  if (isLoading) return <div>Cargando...</div>;
  
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }

  return <>{children}</>;
};
```

## Configuración de Seguridad

### 1. Configurar HTTPS

**IMPORTANTE**: El plugin solo debe usarse con HTTPS en producción.

#### En Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### En Nginx
```nginx
server {
    listen 80;
    server_name tudominio.com;
    return 301 https://$server_name$request_uri;
}
```

### 2. Configurar CORS

Si tu frontend está en un dominio diferente, agrega esto a `wp-config.php`:

```php
// Allow CORS for your frontend domain
header('Access-Control-Allow-Origin: https://tudominio.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
```

O usa el plugin "WP CORS" para configurarlo desde el panel.

### 3. Proteger Archivos Sensibles

Asegúrate de que tu archivo `.htaccess` proteja wp-config.php:

```apache
<files wp-config.php>
order allow,deny
deny from all
</files>
```

## Configuración Avanzada

### 1. Constantes en wp-config.php

Puedes definir configuraciones en `wp-config.php`:

```php
// JWT Secret (sobrescribe la configuración del plugin)
define('CASA_ALBA_AUTH_JWT_SECRET', 'tu-clave-super-secreta-aqui');

// JWT Algorithm
define('CASA_ALBA_AUTH_JWT_ALGORITHM', 'HS256');

// Token expiration (seconds)
define('CASA_ALBA_AUTH_JWT_EXPIRATION', 3600);

// Refresh token expiration (seconds)
define('CASA_ALBA_AUTH_JWT_REFRESH_EXPIRATION', 604800);
```

### 2. Hooks y Filtros Personalizados

#### Modificar payload del token

```php
add_filter('casa_alba_jwt_payload', function($payload, $user_id) {
    $payload['custom_claim'] = 'custom_value';
    return $payload;
}, 10, 2);
```

#### Acción después del login exitoso

```php
add_action('casa_alba_auth_login_success', function($user_id, $token) {
    // Tu código aquí
    error_log("Usuario $user_id inició sesión");
}, 10, 2);
```

#### Acción después del login fallido

```php
add_action('casa_alba_auth_login_failed', function($email, $error) {
    // Tu código aquí
    error_log("Login fallido para $email: " . $error);
}, 10, 2);
```

## Tareas de Mantenimiento

### Limpieza Automática

El plugin limpia automáticamente:
- Sesiones expiradas (diariamente)
- Registros de rate limiting antiguos (>24 horas)
- Datos de analítica antiguos (>90 días)

### Limpieza Manual

#### Limpiar todas las sesiones

```sql
TRUNCATE TABLE wp_casa_alba_auth_sessions;
```

#### Limpiar datos de rate limiting

```sql
TRUNCATE TABLE wp_casa_alba_auth_rate_limits;
```

#### Limpiar analítica

```sql
DELETE FROM wp_casa_alba_auth_analytics 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## Monitoreo y Alertas

### Configurar Alertas por Email

```php
// En functions.php o plugin personalizado
add_action('casa_alba_auth_failed_attempts_exceeded', function($email, $ip) {
    $admin_email = get_option('admin_email');
    $subject = 'Alerta de Seguridad - Intentos de Login Fallidos';
    $message = "Se han detectado múltiples intentos de login fallidos:\n\n";
    $message .= "Email: $email\n";
    $message .= "IP: $ip\n";
    
    wp_mail($admin_email, $subject, $message);
}, 10, 2);
```

### Logs en CloudWatch (AWS)

Si usas AWS, puedes enviar logs a CloudWatch para monitoreo avanzado.

## Troubleshooting

### Problema: "Token inválido" constantemente

**Solución**: Verifica que la clave secreta no haya cambiado y que el algoritmo sea correcto.

### Problema: CORS errors

**Solución**: Configura correctamente los headers CORS en WordPress.

### Problema: Sesiones no se guardan

**Solución**: Verifica que las tablas de la base de datos se crearon correctamente:

```sql
SHOW TABLES LIKE 'wp_casa_alba_auth_%';
```

### Problema: Turnstile no se muestra

**Solución**: 
1. Verifica que las claves sean correctas
2. Revisa la consola del navegador para errores
3. Asegúrate de que el dominio esté autorizado en Cloudflare

## Soporte

Para obtener ayuda:
- Email: soporte@productoscasaalba.cl
- Documentación: https://productoscasaalba.cl/docs
- Issues: [GitHub Issues]

## Recursos Adicionales

- [Documentación de JWT](https://jwt.io)
- [Cloudflare Turnstile Docs](https://developers.cloudflare.com/turnstile/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

