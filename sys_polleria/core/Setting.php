<?php
/** Configuración clave/valor de la empresa y del sistema */
class Setting
{
    private static ?array $cache = null;

    public static function load(bool $force = false): void
    {
        if (self::$cache === null || $force) {
            self::$cache = [];
            foreach (DB::all('SELECT clave, valor FROM configuraciones') as $r) {
                self::$cache[$r['clave']] = $r['valor'];
            }
        }
    }

    public static function get(string $key, $default = '')
    {
        self::load();
        return array_key_exists($key, self::$cache) && self::$cache[$key] !== null ? self::$cache[$key] : $default;
    }

    public static function all(): array { self::load(); return self::$cache; }

    public static function set(string $key, $value): void
    {
        DB::q('INSERT INTO configuraciones (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$key, (string)$value]);
        self::load(true);
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $k => $v) self::set($k, $v);
    }
}
