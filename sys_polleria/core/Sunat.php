<?php
/**
 * Facturación electrónica SUNAT vía OSE/PSE (Nubefact, API JSON v1) + modo simulado para pruebas.
 * Estados en pedidos.fe_estado: pendiente | aceptado | rechazado | error | anulado
 */
class Sunat
{
    public static function activo(): bool { return cfg('fe_activo', '0') === '1'; }
    public static function aplica(?string $comp): bool { return in_array($comp, ['boleta', 'factura'], true); }

    private static function docTipo(?string $t, ?string $doc): string
    {
        $doc = trim((string)$doc);
        if ($doc === '') return '-';
        return ['RUC' => '6', 'DNI' => '1', 'CE' => '4', 'PAS' => '7'][strtoupper((string)$t)] ?? '0';
    }

    /** Construye el JSON de Nubefact para un pedido pagado */
    public static function payload(array $p): array
    {
        $cli = $p['cliente_id'] ? DB::one('SELECT * FROM clientes WHERE id = ?', [$p['cliente_id']]) : null;
        $items = DB::all('SELECT pd.*, pr.codigo FROM pedido_detalle pd LEFT JOIN productos pr ON pr.id = pd.producto_id WHERE pd.pedido_id = ? ORDER BY pd.id', [$p['id']]);
        $igvOn = cfg('igv_activo', '1') === '1';
        $r = $igvOn ? (float)cfg('igv_porcentaje', 18) / 100 : 0.0;
        $incl = cfg('igv_incluido', '1') === '1';
        $factor = 1 + $r;
        $line = function (string $cod, string $desc, float $cant, float $precio) use ($igvOn, $r, $incl, $factor) {
            $conIgv = $incl ? $precio : $precio * $factor;     // precio unitario con IGV
            $sinIgv = $igvOn ? $conIgv / $factor : $conIgv;    // valor unitario
            $sub = round($sinIgv * $cant, 2);
            $igv = $igvOn ? round($sub * $r, 2) : 0.0;
            return [
                'unidad_de_medida' => 'NIU', 'codigo' => $cod, 'descripcion' => mb_substr($desc, 0, 250), 'cantidad' => $cant,
                'valor_unitario' => round($sinIgv, 6), 'precio_unitario' => round($conIgv, 6), 'descuento' => '', 'subtotal' => $sub,
                'tipo_de_igv' => $igvOn ? 1 : 8, 'igv' => $igv, 'total' => round($sub + $igv, 2), 'anticipo_regularizacion' => false, 'anticipo_documento_serie' => '', 'anticipo_documento_numero' => '',
            ];
        };
        $det = [];
        foreach ($items as $i) $det[] = $line((string)($i['codigo'] ?: 'P' . $i['producto_id']), $i['nombre'], (float)$i['cantidad'], (float)$i['precio']);
        if ((float)$p['costo_delivery'] > 0) $det[] = $line('DELIVERY', 'Servicio de delivery', 1.0, (float)$p['costo_delivery']);

        $desc = (float)$p['descuento'];
        $descSin = $desc > 0 ? round($igvOn ? ($incl ? $desc : $desc * $factor) / $factor : ($incl ? $desc : $desc), 2) : 0.0;
        $gravada = round(array_sum(array_column($det, 'subtotal')) - $descSin, 2);
        $igvT = $igvOn ? round($gravada * $r, 2) : 0.0;
        $total = round($gravada + $igvT, 2);
        // Los totales del comprobante deben coincidir con lo cobrado (evita diferencias de redondeo)
        if (abs($total - (float)$p['total']) < 0.05) { $total = round((float)$p['total'], 2); $igvT = $igvOn ? round((float)$p['igv'], 2) : 0.0; $gravada = round($total - $igvT, 2); }

        $factura = $p['comprobante'] === 'factura';
        $tipoDoc = self::docTipo($cli['tipo_doc'] ?? null, $cli['documento'] ?? null);
        return [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $factura ? 1 : 2,
            'serie' => $p['serie'], 'numero' => (int)$p['numero'], 'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => $tipoDoc,
            'cliente_numero_de_documento' => $tipoDoc === '-' ? '-' : $cli['documento'],
            'cliente_denominacion' => $cli['nombre'] ?? 'CLIENTES VARIOS',
            'cliente_direccion' => $cli['direccion'] ?? '', 'cliente_email' => $cli['email'] ?? '',
            'fecha_de_emision' => date('d-m-Y', strtotime($p['fecha_pago'] ?: 'now')), 'moneda' => 1, 'tipo_de_cambio' => '',
            'porcentaje_de_igv' => $igvOn ? (float)cfg('igv_porcentaje', 18) : 0,
            'descuento_global' => $descSin > 0 ? $descSin : '', 'total_descuento' => $descSin > 0 ? $descSin : '',
            'total_gravada' => $igvOn ? $gravada : 0, 'total_inafecta' => $igvOn ? '' : $gravada, 'total_igv' => $igvT, 'total' => $total,
            'enviar_automaticamente_a_la_sunat' => true, 'enviar_automaticamente_al_cliente' => !empty($cli['email']),
            'observaciones' => 'Pedido #' . $p['id'], 'items' => $det,
        ];
    }

    private static function post(array $payload): array
    {
        $url = trim((string)cfg('fe_url', ''));
        $token = (string)cfg('fe_token', '');
        if ($url === '' || $token === '') throw new RuntimeException('Configure la ruta y el token del proveedor (Facturación → Configuración).');
        if (!function_exists('curl_init')) throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Token token="' . $token . '"'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $out = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($out === false) throw new RuntimeException('Sin conexión con el proveedor: ' . $err);
        $j = json_decode((string)$out, true);
        if (!is_array($j)) throw new RuntimeException('Respuesta inválida del proveedor.');
        return $j;
    }

    /** Envía (o reenvía) el comprobante. Devuelve ['ok'=>bool,'estado'=>..,'msg'=>..] y actualiza el pedido. */
    public static function enviar(int $id): array
    {
        $p = DB::one('SELECT * FROM pedidos WHERE id = ?', [$id]);
        if (!$p || $p['estado'] !== 'pagado' || !self::aplica($p['comprobante'])) return ['ok' => false, 'estado' => null, 'msg' => 'Solo se envían boletas/facturas cobradas.'];
        if (in_array($p['fe_estado'], ['aceptado', 'anulado'], true)) return ['ok' => true, 'estado' => $p['fe_estado'], 'msg' => 'Ya fue enviado.'];
        $upd = ['fe_fecha' => date('Y-m-d H:i:s')];
        try {
            if (cfg('fe_modo', 'simulado') === 'simulado') {
                $hash = base64_encode(hash('sha1', $p['serie'] . $p['numero'] . $p['total'], true));
                $upd += ['fe_estado' => 'aceptado', 'fe_hash' => $hash, 'fe_enlace' => '', 'fe_qr' => implode('|', [cfg('empresa_ruc'), $p['comprobante'] === 'factura' ? '01' : '03', $p['serie'], $p['numero'], $p['igv'], $p['total'], date('Y-m-d', strtotime($p['fecha_pago'] ?: 'now'))]) . '|',
                    'fe_mensaje' => 'Aceptado (modo simulado: no se envió a SUNAT)'];
            } else {
                $j = self::post(self::payload($p));
                if (!empty($j['errors'])) {
                    $msg = mb_substr((string)$j['errors'], 0, 240);
                    // Si el documento ya existe en Nubefact, se consulta su estado
                    if (stripos($msg, 'ya existe') !== false) {
                        $j = self::post(['operacion' => 'consultar_comprobante', 'tipo_de_comprobante' => $p['comprobante'] === 'factura' ? 1 : 2, 'serie' => $p['serie'], 'numero' => (int)$p['numero']]);
                    }
                }
                if (!empty($j['errors'])) {
                    $upd += ['fe_estado' => 'rechazado', 'fe_mensaje' => mb_substr((string)$j['errors'], 0, 240)];
                } else {
                    $ok = !empty($j['aceptada_por_sunat']);
                    $upd += [
                        'fe_estado' => $ok ? 'aceptado' : 'pendiente', 'fe_hash' => $j['codigo_hash'] ?? null, 'fe_enlace' => $j['enlace'] ?? null,
                        'fe_qr' => $j['cadena_para_codigo_qr'] ?? null,
                        'fe_mensaje' => mb_substr((string)($j['sunat_description'] ?? ($ok ? 'Aceptado por SUNAT' : 'Enviado, pendiente de respuesta de SUNAT')), 0, 240),
                    ];
                }
            }
        } catch (RuntimeException $e) {
            $upd += ['fe_estado' => 'error', 'fe_mensaje' => mb_substr($e->getMessage(), 0, 240)];
        }
        DB::update('pedidos', $upd, 'id = ?', [$id]);
        return ['ok' => $upd['fe_estado'] === 'aceptado' || $upd['fe_estado'] === 'pendiente', 'estado' => $upd['fe_estado'], 'msg' => $upd['fe_mensaje']];
    }

    /** Comunica la baja de un comprobante ya aceptado (se llama al anular la venta) */
    public static function anular(array $p, string $motivo): void
    {
        if (!self::activo() || !self::aplica($p['comprobante']) || $p['fe_estado'] !== 'aceptado') return;
        try {
            if (cfg('fe_modo', 'simulado') !== 'simulado') {
                $j = self::post(['operacion' => 'generar_anulacion', 'tipo_de_comprobante' => $p['comprobante'] === 'factura' ? 1 : 2, 'serie' => $p['serie'], 'numero' => (int)$p['numero'], 'motivo' => mb_substr($motivo, 0, 100), 'codigo_unico' => '']);
                if (!empty($j['errors'])) throw new RuntimeException((string)$j['errors']);
            }
            DB::update('pedidos', ['fe_estado' => 'anulado', 'fe_mensaje' => 'Baja comunicada a SUNAT'], 'id = ?', [$p['id']]);
        } catch (RuntimeException $e) {
            DB::update('pedidos', ['fe_mensaje' => 'Anulado en el sistema, pero la baja en SUNAT falló: ' . mb_substr($e->getMessage(), 0, 150)], 'id = ?', [$p['id']]);
        }
    }

    public static function badge(?string $e): array
    {
        return ['pendiente' => ['warn', 'Pendiente'], 'aceptado' => ['ok', 'Aceptado'], 'rechazado' => ['bad', 'Rechazado'], 'error' => ['bad', 'Error'], 'anulado' => ['gray', 'Baja']][$e ?? ''] ?? ['gray', '—'];
    }
}
