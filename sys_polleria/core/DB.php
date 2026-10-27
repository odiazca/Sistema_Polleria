<?php
/** Acceso a datos (PDO) */
class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    /** Conexión al servidor sin base de datos (instalador) */
    public static function server(): PDO
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function q(string $sql, array $p = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st;
    }

    public static function all(string $sql, array $p = []): array { return self::q($sql, $p)->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $r = self::q($sql, $p)->fetch(); return $r ?: null; }
    public static function val(string $sql, array $p = [])
    {
        $r = self::q($sql, $p)->fetch(PDO::FETCH_NUM);
        return $r ? $r[0] : null;
    }
    public static function run(string $sql, array $p = []): int { return self::q($sql, $p)->rowCount(); }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::q($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $wp = []): int
    {
        $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
        return self::run("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $wp));
    }

    public static function begin(): void { if (!self::pdo()->inTransaction()) self::pdo()->beginTransaction(); }
    public static function commit(): void { if (self::pdo()->inTransaction()) self::pdo()->commit(); }
    public static function rollback(): void { if (self::pdo()->inTransaction()) self::pdo()->rollBack(); }

    /** Ejecuta un script SQL (sentencias separadas por ';' al final de línea). */
    public static function runScript(string $sql, ?PDO $pdo = null): int
    {
        $pdo = $pdo ?: self::pdo();
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
        $sql = preg_replace('/^[ \t]*--.*$/m', '', $sql);
        $parts = preg_split('/;[ \t]*\r?\n/', $sql);
        $n = 0;
        foreach ($parts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            $pdo->exec($stmt);
            $n++;
        }
        return $n;
    }
}
