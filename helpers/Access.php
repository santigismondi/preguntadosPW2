<?php

class Access
{
    private const ROLE_ALIASES = [
        'jugador' => 'Usuario',
        'usuario' => 'Usuario',
        'editor' => 'Editor',
        'administrador' => 'Administrador',
    ];

    private static $config;

    public static function allow(string $permission): void
    {
        self::requireLogin();

        if (!self::isGranted($permission)) {
            self::forbidden();
        }
    }

    public static function allowAnyRole(array $roles): void
    {
        self::requireLogin();

        $currentRole = self::currentRole();
        if ($currentRole === null) {
            self::forbidden();
        }

        $allowedRoles = array_map([self::class, 'normalizeRole'], $roles);
        if (!in_array($currentRole, $allowedRoles, true)) {
            self::forbidden();
        }
    }

    public static function isGranted(string $permission): bool
    {
        $currentRole = self::currentRole();
        if ($currentRole === null || !self::permissionExists($permission)) {
            return false;
        }

        $permissions = self::permissionsForRole($currentRole);
        if ($permissions === null) {
            return false;
        }

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    private static function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            Redirect::to(self::loginUrl());
        }
    }

    private static function forbidden(): void
    {
        http_response_code(403);
        echo '403 Forbidden';
        exit();
    }

    private static function currentRole(): ?string
    {
        if (empty($_SESSION['rol'])) {
            return null;
        }

        return self::normalizeRole((string) $_SESSION['rol']);
    }

    private static function normalizeRole(string $role): string
    {
        $role = trim($role);
        $lowerRole = strtolower($role);

        if (array_key_exists($lowerRole, self::ROLE_ALIASES)) {
            return self::ROLE_ALIASES[$lowerRole];
        }

        return $role;
    }

    private static function permissionsForRole(string $role): ?array
    {
        $config = self::config();
        return $config['roles'][$role] ?? null;
    }

    private static function permissionExists(string $permission): bool
    {
        $config = self::config();

        return in_array($permission, $config['permissions'] ?? [], true);
    }

    private static function config(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/rbac.php';
        }

        return self::$config;
    }

    private static function loginUrl(): string
    {
        return (new ConfigParser())->get('baseUrl', '') . '/usuario/login';
    }
}
