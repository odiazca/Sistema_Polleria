<?php
/** CRM: segmentación automática (RFM simplificada), fidelidad por puntos y utilidades */
class Crm
{
    /** clave => [etiqueta, clase badge, color, icono, descripción] */
    const SEG = [
        'vip'        => ['VIP', 'brand', '#F59E0B', 'bi-gem', 'Compra mucho y con frecuencia'],
        'frecuente'  => ['Frecuente', 'ok', '#10B981', 'bi-arrow-repeat', 'Viene seguido'],
        'nuevo'      => ['Nuevo', 'info', '#3B82F6', 'bi-stars', 'Primera compra reciente'],
        'ocasional'  => ['Ocasional', 'gray', '#94A3B8', 'bi-cup-hot', 'Compra de vez en cuando'],
        'riesgo'     => ['En riesgo', 'warn', '#F97316', 'bi-exclamation-triangle-fill', 'Hace tiempo que no vuelve'],
        'perdido'    => ['Perdido', 'bad', '#EF4444', 'bi-person-dash-fill', 'Sin compras hace mucho'],
        'prospecto'  => ['Sin compras', 'violet', '#8B5CF6', 'bi-person-plus', 'Registrado, aún no compra'],
    ];
    const TIPOS_NOTA = ['nota' => ['bi-sticky-fill', 'Nota', '#64748B'], 'llamada' => ['bi-telephone-fill', 'Llamada', '#0EA5E9'], 'whatsapp' => ['bi-whatsapp', 'WhatsApp', '#16A34A'], 'visita' => ['bi-shop', 'Visita', '#F97316'], 'reclamo' => ['bi-exclamation-octagon-fill', 'Reclamo', '#EF4444'], 'felicitacion' => ['bi-heart-fill', 'Felicitación', '#EC4899']];
    const NIVELES = ['bronce' => ['Bronce', '#B45309', 'bi-award'], 'plata' => ['Plata', '#64748B', 'bi-award-fill'], 'oro' => ['Oro', '#D97706', 'bi-trophy-fill']];

    public static function reglas(): array
    {
        return [
            'soles_por_punto' => max(1, (float)cfg('crm_soles_por_punto', 10)),
            'plata' => max(1, (int)cfg('crm_nivel_plata', 200)), 'oro' => max(1, (int)cfg('crm_nivel_oro', 500)),
            'vip_gasto' => max(1, (float)cfg('crm_vip_gasto', 1000)), 'frecuente' => max(2, (int)cfg('crm_frecuente_visitas', 4)),
            'riesgo' => max(7, (int)cfg('crm_riesgo_dias', 45)), 'perdido' => max(14, (int)cfg('crm_perdido_dias', 120)),
        ];
    }

    public static function segmento(array $c, array $r): string
    {
        $n = (int)$c['n'];
        if ($n === 0) return 'prospecto';
        $dias = (int)floor((time() - strtotime($c['ultima'])) / 86400);
        if ($dias > $r['perdido']) return 'perdido';
        if ($dias > $r['riesgo']) return 'riesgo';
        if ((float)$c['total'] >= $r['vip_gasto']) return 'vip';
        if ($n === 1) return 'nuevo';
        if ($n >= $r['frecuente']) return 'frecuente';
        return 'ocasional';
    }

    /** Puntos históricos ganados por compras (nunca bajan) */
    public static function ganados(float $total, array $r): int { return (int)floor($total / $r['soles_por_punto']); }

    public static function nivel(int $historicos, array $r): string { return $historicos >= $r['oro'] ? 'oro' : ($historicos >= $r['plata'] ? 'plata' : 'bronce'); }

    /** Clientes con métricas, segmento, puntos y nivel */
    public static function clientes(string $where = '', array $p = []): array
    {
        $r = self::reglas();
        $rows = DB::all("SELECT c.*, COALESCE(s.n,0) n, COALESCE(s.total,0) total, s.ultima, s.primera, COALESCE(a.aj,0) ajustes
            FROM clientes c
            LEFT JOIN (SELECT cliente_id, COUNT(*) n, SUM(total) total, MAX(fecha_pago) ultima, MIN(fecha_pago) primera FROM pedidos WHERE estado = 'pagado' AND cliente_id IS NOT NULL GROUP BY cliente_id) s ON s.cliente_id = c.id
            LEFT JOIN (SELECT cliente_id, SUM(puntos) aj FROM crm_puntos GROUP BY cliente_id) a ON a.cliente_id = c.id
            WHERE c.estado = 1 " . $where . ' ORDER BY c.nombre', $p);
        foreach ($rows as &$c) {
            $c['n'] = (int)$c['n']; $c['total'] = (float)$c['total'];
            $c['seg'] = self::segmento($c, $r);
            $c['ticket'] = $c['n'] ? $c['total'] / $c['n'] : 0;
            $c['dias'] = $c['ultima'] ? (int)floor((time() - strtotime($c['ultima'])) / 86400) : null;
            $gan = self::ganados($c['total'], $r);
            $c['hist'] = $gan + max(0, (int)$c['ajustes']);          // para el nivel
            $c['puntos'] = max(0, $gan + (int)$c['ajustes']);        // saldo canjeable
            $c['nivel'] = self::nivel($c['hist'], $r);
        }
        return $rows;
    }

    public static function cliente(int $id): ?array
    {
        $x = self::clientes('AND c.id = ?', [$id]);
        return $x[0] ?? null;
    }

    public static function iniciales(string $n): string
    {
        $w = preg_split('/\s+/', trim($n)); $s = mb_strtoupper(mb_substr($w[0] ?? '?', 0, 1));
        if (count($w) > 1) $s .= mb_strtoupper(mb_substr($w[1], 0, 1));
        return $s;
    }

    public static function wa(?string $tel, string $msg): string
    {
        $n = preg_replace('/\D/', '', (string)$tel);
        if (strlen($n) === 9) $n = '51' . $n;
        return $n ? 'https://wa.me/' . $n . '?text=' . rawurlencode($msg) : '';
    }

    public static function plantilla(string $msg, array $c): string
    {
        $nom = trim(explode(' ', trim((string)$c['nombre']))[0]);
        return strtr($msg, ['{nombre}' => ucfirst(mb_strtolower($nom)), '{puntos}' => (string)($c['puntos'] ?? 0), '{empresa}' => cfg('empresa_nombre', APP_NAME)]);
    }

    /** Clientes que cumplen años en los próximos N días */
    public static function cumpleanos(int $dias = 30): array
    {
        $out = [];
        foreach (DB::all("SELECT id, nombre, telefono, fecha_nacimiento FROM clientes WHERE estado = 1 AND fecha_nacimiento IS NOT NULL") as $c) {
            $f = strtotime($c['fecha_nacimiento']);
            $y = (int)date('Y');
            $prox = strtotime($y . '-' . date('m-d', $f));
            if ($prox < strtotime('today')) $prox = strtotime(($y + 1) . '-' . date('m-d', $f));
            $d = (int)round(($prox - strtotime('today')) / 86400);
            if ($d <= $dias) $out[] = $c + ['en' => $d, 'edad' => (int)date('Y', $prox) - (int)date('Y', $f), 'dia' => date('d/m', $prox)];
        }
        usort($out, fn($a, $b) => $a['en'] <=> $b['en']);
        return $out;
    }

    public static function seguimientos(): array
    {
        return DB::all("SELECT n.*, c.nombre, c.telefono FROM crm_notas n JOIN clientes c ON c.id = n.cliente_id WHERE n.seguimiento IS NOT NULL AND n.hecho = 0 ORDER BY n.seguimiento LIMIT 30");
    }

    public static function pendientesHoy(): int
    {
        return (int)DB::val("SELECT COUNT(*) FROM crm_notas WHERE seguimiento IS NOT NULL AND hecho = 0 AND seguimiento <= CURDATE()");
    }
}
