<?php
/**
 * Copia de seguridad automática (línea de comandos).
 * Ejemplo (Windows, Programador de tareas):
 *   C:\laragon\bin\php\php-8.x\php.exe C:\laragon\www\sys_polleria\backup_cron.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo ejecución por consola.'); }
require_once __DIR__ . '/core/bootstrap.php';
DB::pdo();
Setting::load();
$name = Backup::create(true, 'auto');
echo '[' . date('Y-m-d H:i:s') . "] Copia creada: $name\n";
