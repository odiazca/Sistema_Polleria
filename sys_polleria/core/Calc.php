<?php
/** Cálculos de importes (IGV, totales) */
class Calc
{
    public static function totales(float $subtotal, float $descuento, float $delivery): array
    {
        $base = max(0, $subtotal - $descuento) + $delivery;
        if (cfg('igv_activo', '1') !== '1') {
            return ['igv' => 0.0, 'total' => round($base, 2)];
        }
        $r = (float)cfg('igv_porcentaje', 18) / 100;
        if (cfg('igv_incluido', '1') === '1') {
            $total = $base;
            $igv = $total - $total / (1 + $r);
        } else {
            $igv = $base * $r;
            $total = $base + $igv;
        }
        return ['igv' => round($igv, 2), 'total' => round($total, 2)];
    }

    /** Siguiente correlativo para un comprobante */
    public static function siguienteNumero(string $comprobante): array
    {
        $serie = cfg('serie_' . $comprobante, 'X001');
        $n = (int)DB::val('SELECT COALESCE(MAX(numero),0) FROM pedidos WHERE comprobante = ? AND serie = ? FOR UPDATE', [$comprobante, $serie]);
        return [$serie, $n + 1];
    }

    public static function numeroCompleto(?string $serie, $numero): string
    {
        return $serie ? $serie . '-' . str_pad((string)$numero, 6, '0', STR_PAD_LEFT) : '—';
    }
}
