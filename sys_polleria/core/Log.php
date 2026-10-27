<?php
/** Registro de actividad (auditoría) */
class Log
{
    public static function add(string $accion, string $detalle = ''): void
    {
        try {
            DB::insert('auditoria', [
                'usuario_id' => Auth::id() ?: null,
                'accion' => $accion,
                'detalle' => mb_substr($detalle, 0, 500),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (Throwable $e) { /* no interrumpir el flujo */ }
    }
}
