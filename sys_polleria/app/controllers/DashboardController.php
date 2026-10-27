<?php
class DashboardController extends Controller
{
    protected string $module = 'dashboard';

    private function rango(string $desde, string $hasta): array
    {
        return [$desde . ' 00:00:00', date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00'];
    }

    private function totales(string $desde, string $hasta): array
    {
        [$a, $b] = $this->rango($desde, $hasta);
        $r = DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM pedidos WHERE estado='pagado' AND fecha_pago >= ? AND fecha_pago < ?", [$a, $b]);
        return ['n' => (int)$r['n'], 't' => (float)$r['t']];
    }

    private function variacion(float $actual, float $previo): ?float
    {
        if ($previo <= 0) return null;
        return round(($actual - $previo) / $previo * 100, 1);
    }

    public function chartData(int $dias): array
    {
        $dias = in_array($dias, [7, 15, 30], true) ? $dias : 7;
        $desde = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
        $hasta = date('Y-m-d');
        [$a, $b] = $this->rango($desde, $hasta);

        $rows = DB::all("SELECT DATE(fecha_pago) d, COUNT(*) n, SUM(total) t FROM pedidos WHERE estado='pagado' AND fecha_pago >= ? AND fecha_pago < ? GROUP BY DATE(fecha_pago)", [$a, $b]);
        $map = [];
        foreach ($rows as $r) $map[$r['d']] = $r;
        $labels = $ventas = $pedidos = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date($dias > 15 ? 'd/m' : 'D d', strtotime($d));
            $ventas[] = round((float)($map[$d]['t'] ?? 0), 2);
            $pedidos[] = (int)($map[$d]['n'] ?? 0);
        }
        $dm = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom'];
        $labels = array_map(fn($l) => strtr($l, $dm), $labels);

        $tipos = DB::all("SELECT tipo, COUNT(*) n, SUM(total) t FROM pedidos WHERE estado='pagado' AND fecha_pago >= ? AND fecha_pago < ? GROUP BY tipo", [$a, $b]);
        $metodos = DB::all("SELECT pg.metodo, SUM(pg.monto) t FROM pagos pg JOIN pedidos p ON p.id=pg.pedido_id WHERE p.estado='pagado' AND p.fecha_pago >= ? AND p.fecha_pago < ? GROUP BY pg.metodo ORDER BY t DESC", [$a, $b]);
        $top = DB::all("SELECT d.nombre, SUM(d.cantidad) q, SUM(d.subtotal) t FROM pedido_detalle d JOIN pedidos p ON p.id=d.pedido_id WHERE p.estado='pagado' AND p.fecha_pago >= ? AND p.fecha_pago < ? GROUP BY d.producto_id, d.nombre ORDER BY q DESC LIMIT 6", [$a, $b]);
        $hrows = DB::all("SELECT HOUR(fecha_pago) h, SUM(total) t FROM pedidos WHERE estado='pagado' AND fecha_pago >= ? AND fecha_pago < ? GROUP BY HOUR(fecha_pago)", [$a, $b]);
        $horas = array_fill(10, 14, 0.0); // 10h a 23h
        foreach ($hrows as $r) if (isset($horas[(int)$r['h']])) $horas[(int)$r['h']] = round((float)$r['t'], 2);

        return [
            'labels' => $labels, 'ventas' => $ventas, 'pedidos' => $pedidos,
            'tipos' => array_map(fn($r) => ['k' => $r['tipo'], 'n' => (int)$r['n'], 't' => (float)$r['t']], $tipos),
            'metodos' => array_map(fn($r) => ['k' => $r['metodo'], 't' => (float)$r['t']], $metodos),
            'top' => array_map(fn($r) => ['k' => $r['nombre'], 'q' => (float)$r['q'], 't' => (float)$r['t']], $top),
            'horas' => ['labels' => array_map(fn($h) => $h . 'h', array_keys($horas)), 'v' => array_values($horas)],
        ];
    }

    public function chartsAction(): void
    {
        $this->json(['ok' => true] + $this->chartData((int)($_GET['dias'] ?? 7)));
    }

    public function indexAction(): void
    {
        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $t = $this->totales($hoy, $hoy);
        $y = $this->totales($ayer, $ayer);

        $ini = date('Y-m-01');
        $m = $this->totales($ini, $hoy);
        $p0 = new DateTime($ini);
        $p0->modify('first day of last month');
        $pEnd = clone $p0;
        $pEnd->setDate((int)$p0->format('Y'), (int)$p0->format('m'), min((int)date('j'), (int)$p0->format('t')));
        $mp = $this->totales($p0->format('Y-m-d'), $pEnd->format('Y-m-d'));

        $gastosMes = (float)DB::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE fecha >= ?', [$ini]);
        $abiertos = (int)DB::val("SELECT COUNT(*) FROM pedidos WHERE estado='abierto'");
        $mesasOc = (int)DB::val("SELECT COUNT(*) FROM mesas WHERE estado='ocupada'");
        $mesasTot = (int)DB::val("SELECT COUNT(*) FROM mesas WHERE estado<>'inactiva'");
        $deliveryPend = (int)DB::val("SELECT COUNT(*) FROM pedidos WHERE tipo='delivery' AND estado<>'anulado' AND delivery_estado IN ('pendiente','en_camino')");

        $ultimas = DB::all("SELECT p.id, p.tipo, p.total, p.fecha_pago, p.comprobante, p.serie, p.numero, c.nombre cliente, m.nombre mesa
            FROM pedidos p LEFT JOIN clientes c ON c.id=p.cliente_id LEFT JOIN mesas m ON m.id=p.mesa_id
            WHERE p.estado='pagado' ORDER BY p.fecha_pago DESC LIMIT 7");
        $stockBajo = DB::all('SELECT nombre, unidad, stock, stock_minimo FROM insumos WHERE estado=1 AND stock <= stock_minimo ORDER BY (stock/NULLIF(stock_minimo,0)) ASC LIMIT 5');
        $prodBajo = DB::all('SELECT nombre, stock, stock_minimo FROM productos WHERE estado=1 AND stock_control=1 AND stock <= stock_minimo ORDER BY stock ASC LIMIT 4');

        $mesasList = DB::all("SELECT m.id, m.nombre, m.zona, m.capacidad, m.estado, p.id AS pedido_id, p.total, p.fecha_pedido FROM mesas m LEFT JOIN pedidos p ON p.mesa_id = m.id AND p.estado = 'abierto' WHERE m.estado <> 'inactiva' ORDER BY m.zona, m.id");
        foreach ($mesasList as &$ml) {
            $ml['real'] = $ml['pedido_id'] ? 'ocupada' : ($ml['estado'] === 'reservada' ? 'reservada' : 'libre');
            $ml['mins'] = $ml['pedido_id'] ? max(0, (int)floor((time() - strtotime($ml['fecha_pedido'])) / 60)) : 0;
        }
        unset($ml);
        $cocina = DB::all("SELECT p.id, p.tipo, p.fecha_pedido, m.nombre AS mesa, c.nombre AS cliente,
                SUM(d.estado IN ('pendiente','preparando')) AS pend, SUM(d.estado = 'listo') AS listos, COUNT(d.id) AS items
            FROM pedidos p JOIN pedido_detalle d ON d.pedido_id = p.id LEFT JOIN mesas m ON m.id = p.mesa_id LEFT JOIN clientes c ON c.id = p.cliente_id
            WHERE p.estado = 'abierto' GROUP BY p.id HAVING pend > 0 OR listos > 0 ORDER BY p.fecha_pedido ASC LIMIT 6");
        foreach ($cocina as &$ck) $ck['mins'] = max(0, (int)floor((time() - strtotime($ck['fecha_pedido'])) / 60));
        unset($ck);
        $insumosKey = DB::all("SELECT i.id, i.nombre, i.unidad, i.stock, i.stock_minimo,
                COALESCE((SELECT SUM(mv.cantidad) FROM insumo_movimientos mv WHERE mv.insumo_id = i.id AND mv.tipo = 'salida' AND mv.pedido_id IS NOT NULL AND mv.fecha >= CURDATE()), 0) AS usado_hoy
            FROM insumos i WHERE i.estado = 1 ORDER BY (i.nombre LIKE '%pollo%') DESC, (i.stock / NULLIF(i.stock_minimo, 0)) ASC LIMIT 5");
        $cajaAb = DB::one("SELECT id, fecha_apertura FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);

        $hora = (int)date('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

        $this->view('dashboard/index', [
            'title' => 'Dashboard', 'useCharts' => true, 'scripts' => ['js/dashboard.js'],
            'saludo' => $saludo, 't' => $t, 'y' => $y, 'm' => $m, 'mp' => $mp,
            'vHoy' => $this->variacion($t['t'], $y['t']), 'vMes' => $this->variacion($m['t'], $mp['t']),
            'gastosMes' => $gastosMes, 'abiertos' => $abiertos, 'mesasOc' => $mesasOc, 'mesasTot' => $mesasTot, 'deliveryPend' => $deliveryPend,
            'resHoy' => (int)DB::val("SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE() AND estado IN ('pendiente','confirmada','sentada')"), 'mesasList' => $mesasList, 'cocina' => $cocina, 'insumosKey' => $insumosKey, 'cajaAb' => $cajaAb, 'ultimas' => $ultimas, 'stockBajo' => $stockBajo, 'prodBajo' => $prodBajo,
            'chart' => $this->chartData(7),
        ]);
    }
}
