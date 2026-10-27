<?php
/** Servicio de copias de seguridad, restauración y reseteo del sistema */
class Backup
{
    const MARK = '-- POLLERIA-POS-BACKUP';

    public static function dir(): string
    {
        $d = BASE_PATH . '/backups';
        if (!is_dir($d)) mkdir($d, 0775, true);
        return $d;
    }

    public static function validName(string $f): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_\-\.]+\.(sql|zip)$/', $f) && strpos($f, '..') === false;
    }

    public static function path(string $f): ?string
    {
        if (!self::validName($f)) return null;
        $p = self::dir() . '/' . $f;
        return is_file($p) ? $p : null;
    }

    public static function tables(): array
    {
        return array_map('current', DB::pdo()->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM));
    }

    public static function listFiles(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*.{sql,zip}', GLOB_BRACE) ?: [] as $p) {
            $name = basename($p);
            $out[] = [
                'name' => $name, 'size' => filesize($p), 'time' => filemtime($p),
                'zip' => str_ends_with($name, '.zip'),
                'tipo' => preg_match('/_(manual|auto|pre-restauracion|pre-reseteo)\./', $name, $m) ? $m[1] : 'manual',
            ];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** Genera el volcado SQL (una sentencia por línea) en el archivo indicado */
    public static function dumpTo(string $file): array
    {
        $pdo = DB::pdo();
        $fh = fopen($file, 'wb');
        if (!$fh) throw new RuntimeException('No se pudo escribir en la carpeta backups/.');
        fwrite($fh, self::MARK . "\n-- Fecha: " . date('Y-m-d H:i:s') . "\n-- Base de datos: " . DB_NAME . "\n-- Versión: " . APP_VERSION . "\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
        $tablas = 0; $filas = 0;
        foreach (self::tables() as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            $create = preg_replace('/\s*\n\s*/', ' ', $create);
            fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n$create;\n");
            $st = $pdo->query("SELECT * FROM `$t`", PDO::FETCH_ASSOC);
            $cols = null; $buf = [];
            while ($row = $st->fetch()) {
                if ($cols === null) $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $vals = [];
                foreach ($row as $v) {
                    if ($v === null) { $vals[] = 'NULL'; continue; }
                    $q = $pdo->quote((string)$v);
                    $vals[] = str_replace(["\r", "\n"], ['\\r', '\\n'], $q);
                }
                $buf[] = '(' . implode(',', $vals) . ')';
                $filas++;
                if (count($buf) >= 200) {
                    fwrite($fh, "INSERT INTO `$t` ($cols) VALUES " . implode(',', $buf) . ";\n");
                    $buf = [];
                }
            }
            if ($buf) fwrite($fh, "INSERT INTO `$t` ($cols) VALUES " . implode(',', $buf) . ";\n");
            $tablas++;
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return ['tablas' => $tablas, 'filas' => $filas];
    }

    public static function create(bool $withFiles = false, string $label = 'manual'): string
    {
        @set_time_limit(0);
        $base = 'respaldo_' . preg_replace('/[^A-Za-z0-9]/', '', DB_NAME) . '_' . date('Ymd_His') . '_' . $label;
        $sql = self::dir() . '/' . $base . '.sql';
        self::dumpTo($sql);

        if ($withFiles && class_exists('ZipArchive')) {
            $zipPath = self::dir() . '/' . $base . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($sql, 'database.sql');
                $up = BASE_PATH . '/uploads';
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if ($f->isFile() && $f->getFilename() !== '.htaccess' && !str_contains($f->getPathname(), DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR)) {
                        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($up) + 1));
                        $zip->addFile($f->getPathname(), 'uploads/' . $rel);
                    }
                }
                $zip->close();
                @unlink($sql);
                self::retention();
                return basename($zipPath);
            }
        }
        self::retention();
        return basename($sql);
    }

    public static function retention(): void
    {
        $keep = max(3, (int)cfg('backup_retencion', 10));
        $files = self::listFiles();
        foreach (array_slice($files, $keep) as $f) @unlink(self::dir() . '/' . $f['name']);
    }

    /** Restaura desde un archivo .sql o .zip (ruta completa) */
    public static function restore(string $path): void
    {
        @set_time_limit(0);
        $tmpDir = null;
        $sqlPath = $path;
        if (str_ends_with(strtolower($path), '.zip')) {
            if (!class_exists('ZipArchive')) throw new RuntimeException('La extensión ZIP no está disponible en PHP.');
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) throw new RuntimeException('No se pudo abrir el archivo ZIP.');
            $tmpDir = BASE_PATH . '/uploads/tmp/restore_' . bin2hex(random_bytes(4));
            mkdir($tmpDir, 0775, true);
            $sqlPath = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if (str_contains($n, '..') || str_starts_with($n, '/') || str_ends_with($n, '/')) continue;
                if ($n === 'database.sql') {
                    file_put_contents($tmpDir . '/database.sql', $zip->getFromIndex($i));
                    $sqlPath = $tmpDir . '/database.sql';
                } elseif (str_starts_with($n, 'uploads/') && !str_contains($n, '.htaccess') && preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', $n)) {
                    $dest = BASE_PATH . '/' . $n;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
                    file_put_contents($dest, $zip->getFromIndex($i));
                }
            }
            $zip->close();
            if (!$sqlPath) throw new RuntimeException('El ZIP no contiene database.sql.');
        }

        $fh = fopen($sqlPath, 'rb');
        if (!$fh) throw new RuntimeException('No se pudo leer el archivo de respaldo.');
        $first = fgets($fh);
        $pdo = DB::pdo();
        try {
            if (trim((string)$first) === self::MARK) {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                while (($line = fgets($fh)) !== false) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '--')) continue;
                    $pdo->exec(rtrim($line, ';'));
                }
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            } else {
                fclose($fh); $fh = null;
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                DB::runScript(file_get_contents($sqlPath));
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            }
        } finally {
            if (is_resource($fh)) fclose($fh);
            if ($tmpDir) {
                foreach (glob($tmpDir . '/*') ?: [] as $f) @unlink($f);
                @rmdir($tmpDir);
            }
        }
    }

    /** Verifica que el SQL subido sea un respaldo válido */
    public static function looksValid(string $path): bool
    {
        if (str_ends_with(strtolower($path), '.zip')) return class_exists('ZipArchive');
        $h = fopen($path, 'rb');
        $head = fread($h, 4096);
        fclose($h);
        return str_contains($head, 'CREATE TABLE') || str_contains($head, self::MARK) || str_contains($head, 'INSERT INTO');
    }

    /**
     * Resetea el sistema para una nueva empresa.
     * $o: catalogo = mantener|base|vacio ; usuarios = bool (mantener) ; mantener_config = bool
     */
    public static function reset(array $o): void
    {
        @set_time_limit(0);
        $pdo = DB::pdo();
        $uid = Auth::id();
        $admin = DB::one('SELECT * FROM usuarios WHERE id = ?', [$uid]);

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $wipe = ['crm_envios', 'crm_campanas', 'crm_puntos', 'crm_notas', 'reservas', 'ia_consultas', 'qr_solicitudes', 'pedido_detalle', 'pagos', 'pedidos', 'caja_movimientos', 'cajas', 'compra_detalle', 'compras', 'gastos', 'insumo_movimientos', 'auditoria', 'clientes', 'proveedores', 'repartidores'];
        $catalogo = ['recetas', 'productos', 'categorias', 'mesas', 'insumos'];
        if (($o['catalogo'] ?? 'mantener') !== 'mantener') $wipe = array_merge($wipe, $catalogo);
        foreach ($wipe as $t) $pdo->exec("TRUNCATE TABLE `$t`");

        if (($o['catalogo'] ?? '') !== 'mantener') {
            foreach (['productos', 'categorias'] as $f) {
                foreach (glob(BASE_PATH . "/uploads/$f/*") ?: [] as $file) if (is_file($file)) @unlink($file);
            }
        }
        if (($o['catalogo'] ?? '') === 'base') {
            DB::runScript(file_get_contents(BASE_PATH . '/database/seed_catalog.sql'));
            Receta::recalcTodos();
        } elseif (($o['catalogo'] ?? '') === 'vacio') {
            DB::insert('categorias', ['nombre' => 'General', 'icono' => 'bi-egg-fried', 'color' => '#F97316', 'orden' => 1]);
        }

        if (empty($o['usuarios'])) {
            $pdo->exec('TRUNCATE TABLE `usuarios`');
            $st = $pdo->prepare('INSERT INTO usuarios (id, rol_id, nombre, usuario, clave, email, telefono, estado, ultimo_acceso) VALUES (?, 1, ?, ?, ?, ?, ?, 1, NOW())');
            $st->execute([$admin['id'], $admin['nombre'], $admin['usuario'], $admin['clave'], $admin['email'], $admin['telefono']]);
        }

        if (empty($o['mantener_config'])) {
            delete_upload(cfg('empresa_logo'));
            $pdo->exec('TRUNCATE TABLE `configuraciones`');
            DB::runScript(preg_replace('/INSERT INTO roles.*?;\s*\n/s', '', file_get_contents(BASE_PATH . '/database/seed_base.sql'), 1));
            foreach (['empresa_razon_social', 'empresa_ruc', 'empresa_direccion', 'empresa_ciudad', 'empresa_telefono', 'empresa_celular', 'empresa_email', 'empresa_web', 'empresa_eslogan'] as $k) {
                DB::q('UPDATE configuraciones SET valor = ? WHERE clave = ?', ['', $k]);
            }
            DB::q('UPDATE configuraciones SET valor = ? WHERE clave = ?', ['Mi Nueva Pollería', 'empresa_nombre']);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        Setting::load(true);
    }
}
