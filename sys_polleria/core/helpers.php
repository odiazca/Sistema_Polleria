<?php
/** Funciones auxiliares globales */

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function url(string $route = 'dashboard/index', array $params = []): string
{
    return BASE_URL . '/index.php?' . http_build_query(array_merge(['r' => $route], $params));
}

function asset(string $path): string
{
    $f = BASE_PATH . '/assets/' . $path;
    return BASE_URL . '/assets/' . $path . (is_file($f) ? '?v=' . filemtime($f) : '');
}

function upload_url(?string $path): string
{
    return $path ? BASE_URL . '/uploads/' . ltrim($path, '/') : '';
}

function cfg(string $key, $default = '') { return Setting::get($key, $default); }

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

/** Formatea un número con los separadores configurados */
function num($n, ?int $dec = null): string
{
    $dec ??= (int)cfg('decimales', 2);
    return number_format((float)$n, $dec, cfg('sep_decimal', '.'), cfg('sep_miles', ','));
}

/** Formatea un importe con símbolo de moneda */
function money($n, bool $symbol = true): string
{
    $s = num($n);
    if (!$symbol) return $s;
    $sym = cfg('moneda_simbolo', 'S/');
    return cfg('moneda_posicion', 'antes') === 'despues' ? $s . ' ' . $sym : $sym . ' ' . $s;
}

function fdate($d, bool $time = false): string
{
    if (!$d) return '—';
    $ts = strtotime($d);
    $fmt = cfg('formato_fecha', 'd/m/Y');
    return date($time ? $fmt . ' H:i' : $fmt, $ts);
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . csrf_token() . '">'; }

function post(string $key, $default = '')
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function is_ajax(): bool
{
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

/** Subida segura de imágenes. Devuelve la ruta relativa dentro de /uploads o null */
function save_image(array $file, string $folder, int $maxMb = 3): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxMb * 1024 * 1024) throw new RuntimeException("La imagen supera los {$maxMb} MB.");
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    if (!in_array($ext, $allowed, true)) throw new RuntimeException('Formato no permitido. Use JPG, PNG, WEBP, GIF o SVG.');
    if ($ext !== 'svg' && !@getimagesize($file['tmp_name'])) throw new RuntimeException('El archivo no es una imagen válida.');
    if ($ext === 'svg') {
        $c = file_get_contents($file['tmp_name']);
        if (preg_match('/<script|onload=|onerror=|javascript:/i', $c)) throw new RuntimeException('SVG no permitido.');
    }
    $dir = BASE_PATH . '/uploads/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('No se pudo guardar la imagen.');
    return $folder . '/' . $name;
}

function delete_upload(?string $rel): void
{
    if (!$rel) return;
    $p = realpath(BASE_PATH . '/uploads/' . $rel);
    $base = realpath(BASE_PATH . '/uploads');
    if ($p && $base && str_starts_with($p, $base) && is_file($p)) @unlink($p);
}

function human_size(int $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, $i ? 2 : 0) . ' ' . $u[$i];
}

function initials(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    return mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
}

/** Fecha larga en español: "domingo 11 de octubre de 2026" */
function strftime_es(?int $ts = null): string
{
    $ts ??= time();
    $d = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $m = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $d[(int)date('w', $ts)] . ' ' . date('j', $ts) . ' de ' . $m[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

/** 'YYYY-MM' → 'Oct 26' */
function mes_es(string $ym): string
{
    $m = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return $m[(int)substr($ym, 5, 2)] . ' ' . substr($ym, 2, 2);
}

/** Tarjeta KPI con degradado (estilo panel de estadísticas) */
function kpi_g(string $color, string $label, string $value, string $icon, string $badge = '', string $text = '', string $href = ''): string
{
    $tag = $href !== '' ? 'a href="' . e($href) . '"' : 'div';
    $end = $href !== '' ? 'a' : 'div';
    $foot = ($badge !== '' || $text !== '') ? '<div class="kg-foot">' . ($badge !== '' ? '<span class="kg-pill">' . $badge . '</span>' : '') . ($text !== '' ? '<span class="kg-txt">' . $text . '</span>' : '') . '</div>' : '';
    return '<' . $tag . ' class="kpi-g kg-' . e($color) . '"><div class="kg-top"><div class="kg-info"><div class="kg-lbl">' . e($label) . '</div><div class="kg-val">' . $value . '</div></div>'
        . '<div class="kg-ico"><i class="bi ' . e($icon) . '"></i></div></div>' . $foot . '</' . $end . '>';
}

/** Píldora de tendencia para kpi_g */
function kpi_trend(?float $v): string
{
    if ($v === null) return '—';
    return '<i class="bi bi-' . ($v >= 0 ? 'arrow-up' : 'arrow-down') . '-short"></i>' . ($v >= 0 ? '+' : '−') . abs($v) . '%';
}
