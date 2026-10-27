<?php
/** Utilidades para pedidos por QR en mesa */
class Qr
{
    public static function ensureTokens(): void
    {
        foreach (DB::all('SELECT id FROM mesas WHERE qr_token IS NULL') as $m) {
            DB::update('mesas', ['qr_token' => bin2hex(random_bytes(8))], 'id = ?', [$m['id']]);
        }
    }

    public static function pendientes(): int
    {
        return (int)DB::val("SELECT COUNT(*) FROM qr_solicitudes WHERE estado = 'pendiente'");
    }

    public static function baseUrl(): string
    {
        $b = trim((string)cfg('qr_base_url', ''));
        if ($b !== '') return rtrim($b, '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }
}
