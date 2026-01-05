# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.2] - 2026-01-05

### Añadido
- Middleware de autenticación JWT para WordPress REST API
  - Permite autenticar usuarios automáticamente en cualquier endpoint de WordPress REST API
  - Funciona con el header `Authorization: Bearer <token>`
  - Se integra con el sistema de autenticación nativo de WordPress mediante el filtro `determine_current_user`
  - Actualiza automáticamente la actividad de sesión en cada petición autenticada
  - Compatible con todos los endpoints nativos de WordPress y plugins de terceros
- Documentación extendida sobre el uso del middleware en README.md, API.md e INSTALLATION.md

## [1.0.0] - 2026-01-04

### Añadido
- Sistema completo de autenticación JWT para aplicaciones headless
- Autenticación mediante correo electrónico y contraseña
- Soporte para algoritmos JWT: HS256, HS384, HS512
- Generación automática de tokens de acceso y refresh tokens
- Validación de tokens JWT con verificación de firma y expiración
- Sistema de refresco de tokens expirados/inválidos
- Revocación de tokens (cierre de sesión)
- Integración con Cloudflare Turnstile para protección anti-bot
- Sistema de Rate Limiting configurable:
  - Límite de intentos de login por IP y email
  - Ventana de tiempo configurable
  - Duración de bloqueo ajustable
- Gestión completa de sesiones:
  - Tracking de múltiples sesiones por usuario
  - Detección automática de dispositivo (móvil, tablet, escritorio)
  - Identificación de navegador y sistema operativo
  - Registro de IP y user agent
  - Seguimiento de última actividad
  - Límite configurable de sesiones por usuario
- Panel de administración de sesiones activas:
  - Vista de todas las sesiones en el sistema
  - Información detallada por sesión
  - Revocación de sesiones individuales
  - Paginación para grandes volúmenes
- Sistema de analítica y monitoreo:
  - Dashboard con métricas en tiempo real
  - Usuarios conectados y tokens activos
  - Estadísticas de logins exitosos y fallidos
  - Tasa de éxito de autenticación
  - Gráficos de actividad por día
  - Top usuarios más activos
  - Registro de intentos fallidos con detalles
  - Filtros por período (7, 30, 90 días)
- REST API completa con endpoints:
  - POST /auth/login - Inicio de sesión
  - POST /auth/logout - Cierre de sesión
  - POST /auth/refresh - Refrescar token
  - POST /auth/validate - Validar token
  - GET /auth/me - Obtener usuario actual
  - GET /auth/sessions - Listar sesiones del usuario
  - DELETE /auth/sessions/{id} - Revocar sesión específica
  - DELETE /auth/sessions/all - Revocar todas las sesiones
  - GET /auth/turnstile-key - Obtener configuración de Turnstile
- Tablas de base de datos:
  - wp_casa_alba_auth_sessions - Gestión de sesiones
  - wp_casa_alba_auth_rate_limits - Control de rate limiting
  - wp_casa_alba_auth_analytics - Datos de analítica
- Panel de administración completo:
  - Página de configuración con todos los ajustes
  - Página de sesiones activas
  - Página de analítica y estadísticas
- Limpieza automática:
  - Sesiones expiradas
  - Registros de rate limiting antiguos
  - Datos de analítica antiguos (>90 días)
- Ejemplos de integración para frontend:
  - Servicio de autenticación en TypeScript
  - Componente de Login con Turnstile
  - Componente de gestión de sesiones
- Documentación completa:
  - README con guía de uso
  - INSTALLATION.md con instrucciones detalladas
  - Ejemplos de código
- Seguridad:
  - Hash SHA-256 para almacenar tokens
  - Validación de firma JWT
  - Verificación de expiración
  - Protección contra ataques de fuerza bruta
  - Soporte para CORS
  - Detección de proxy/Cloudflare para IPs reales

### Características Técnicas
- Compatible con WordPress 5.8+
- Requiere PHP 7.4+
- Sin dependencias externas (JWT nativo)
- Base de datos optimizada con índices
- Código PSR-12 compliant
- Internacionalización (i18n) lista
- Hooks y filtros personalizables
- Logging detallado para debugging

### Seguridad
- Generación de claves secretas criptográficamente seguras
- Almacenamiento seguro de tokens con hash
- Validación estricta de tokens
- Rate limiting para prevenir ataques
- Protección CSRF en formularios admin
- Sanitización y validación de inputs
- Prepared statements para queries SQL

## [Unreleased]

### Planeado
- Soporte para algoritmos RS256/RS384/RS512 (firma asimétrica)
- Autenticación de dos factores (2FA)
- Single Sign-On (SSO)
- Exportación de datos de analítica
- Webhooks para eventos de autenticación
- Integración con servicios de notificación
- API para gestión de usuarios
- Blacklist/whitelist de IPs
- Geolocalización de sesiones
- Notificaciones push para nuevas sesiones

---

## Tipos de Cambios
- `Añadido` para nuevas características.
- `Cambiado` para cambios en funcionalidad existente.
- `Obsoleto` para características que serán removidas.
- `Eliminado` para características removidas.
- `Corregido` para corrección de bugs.
- `Seguridad` en caso de vulnerabilidades.

