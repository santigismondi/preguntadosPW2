# RBAC y reportes agregados

## Qué se implementó

### 1) Guard de autorización reutilizable
Se agregó `helpers/Access.php` como componente server-side para validar acceso por permiso o por rol.

### 2) Fuente central de permisos
Se creó `config/rbac.php` con la matriz de permisos por rol:

- `Usuario`
- `Editor`
- `Administrador`

Reglas:

- `Administrador` tiene wildcard `*`.
- Si el rol no existe, se deniega por defecto.
- Si el permiso no está definido, se deniega por defecto.

### 3) Integración con sesión real del proyecto
El guard usa `$_SESSION['usuario_id']` y `$_SESSION['rol']`.
No se creó una clase `Session` nueva porque el proyecto no la tenía.

### 4) Uso mínimo en controladores
Se agregaron ejemplos reales sin refactorizar el flujo:

```php
Access::allow('reports.view');
Access::allowAnyRole(['Usuario', 'Editor', 'Administrador']);
```

### 5) Modelo de reportes
Se creó `model/UsuarioReportModel.php` con:

```php
getUserMetrics(string $period, string $from, string $to, ?string $country = null, ?string $sex = null): array
```

Agrupa por:

- bucket temporal
- grupo etario
- país
- sexo

Periodos soportados:

- `day` -> `DATE(created_at)`
- `week` -> `YEARWEEK(created_at, 3)`
- `month` -> `DATE_FORMAT(created_at, '%Y-%m')`

## Archivos tocados

- `helpers/Access.php`
- `config/rbac.php`
- `model/UsuarioReportModel.php`
- `controller/ReporteController.php`
- `controller/PerfilController.php`
- `controller/LobbyController.php`
- `config/Configurator.php`
- `database.sql`

## Ejemplo de uso

```php
Access::allow('reports.view');

$metrics = $usuarioReportModel->getUserMetrics(
    'month',
    '2026-07-01 00:00:00',
    '2026-08-01 00:00:00',
    'AR',
    'M'
);
```

## Nota de performance

Recomendación de índices:

- `USUARIO(created_at)`
- `USUARIO(created_at, pais, genero)`