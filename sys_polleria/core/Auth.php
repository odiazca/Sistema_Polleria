<?php
/** Autenticación y permisos por módulo */
class Auth
{
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function id(): int { return (int)($_SESSION['user']['id'] ?? 0); }
    public static function isAdmin(): bool { return (self::user()['permisos'] ?? '') === '*'; }

    public static function modules(): array { static $m = null; return $m ??= require BASE_PATH . '/config/modules.php'; }

    public static function can(string $module): bool
    {
        $u = self::user();
        if (!$u) return false;
        if ($u['permisos'] === '*') return true;
        if ($module === 'cuenta') return true;
        if (!empty(self::modules()[$module][4])) return false; // módulos solo para administrador
        return in_array($module, array_map('trim', explode(',', $u['permisos'])), true);
    }

    /** Ruta del primer módulo permitido */
    public static function home(): string
    {
        foreach (self::modules() as $key => $m) {
            if (self::can($key)) return $m[3];
        }
        return 'auth/cuenta';
    }

    public static function attempt(string $usuario, string $clave): array
    {
        $now = time();
        $lock = $_SESSION['login_lock'] ?? 0;
        if ($lock > $now) return [false, 'Demasiados intentos. Espere ' . ($lock - $now) . ' segundos.'];

        $u = DB::one('SELECT u.*, r.nombre AS rol, r.permisos FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.usuario = ? LIMIT 1', [$usuario]);
        if (!$u || !password_verify($clave, $u['clave'])) {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) {
                $_SESSION['login_lock'] = $now + 60;
                $_SESSION['login_fails'] = 0;
            }
            return [false, 'Usuario o contraseña incorrectos.'];
        }
        if ((int)$u['estado'] !== 1) return [false, 'Su usuario está inactivo. Contacte al administrador.'];

        session_regenerate_id(true);
        unset($_SESSION['login_fails'], $_SESSION['login_lock']);
        DB::run('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?', [$u['id']]);
        self::setSession($u);
        Log::add('login', 'Inicio de sesión');
        return [true, ''];
    }

    public static function setSession(array $u): void
    {
        $_SESSION['user'] = [
            'id' => (int)$u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'],
            'rol' => $u['rol'], 'permisos' => $u['permisos'], 'avatar' => $u['avatar'] ?? null,
        ];
    }

    public static function logout(): void
    {
        Log::add('logout', 'Cierre de sesión');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
