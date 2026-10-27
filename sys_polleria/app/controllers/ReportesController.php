<?php
class ReportesController extends Controller
{
    protected string $module = 'reportes';

    private function rango(): array
    {
        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-01');
        $h = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-d');
        if ($h < $d) [$d, $h] = [$h, $d];
        return [$d . ' 00:00:00', date('Y-m-d', strtotime("$h +1 day")) . ' 00:00:00', $d, $h];
    }

    public function indexAction(): void
    {
        [$a, $b, $desde, $hasta] = $this->rango();
        $base = "p.estado = 'pagado' AND p.fecha_pago >= ? AND p.fecha_pago < ?";
        $k = DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) t, COALESCE(SUM(descuento),0) d, COALESCE(SUM(igv),0) igv FROM pedidos p WHERE $base", [$a, $b]);
        $costo = (float)DB::val("SELECT COALESCE(SUM(d.cantidad * pr.costo),0) FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id WHERE $base", [$a, $b]);
        $gastos = (float)DB::val('SELECT COALESCE(SUM(monto),0) FROM gastos WHERE fecha >= ? AND fecha <= ?', [$desde, $hasta]);
        $compras = (float)DB::val('SELECT COALESCE(SUM(total),0) FROM compras WHERE fecha >= ? AND fecha <= ?', [$desde, $hasta]);
        $anulados = (int)DB::val("SELECT COUNT(*) FROM pedidos WHERE estado='anulado' AND fecha_pedido >= ? AND fecha_pedido < ?", [$a, $b]);

        $porDia = DB::all("SELECT DATE(p.fecha_pago) d, COUNT(*) n, SUM(p.total) t FROM pedidos p WHERE $base GROUP BY DATE(p.fecha_pago) ORDER BY d", [$a, $b]);
        $porCat = DB::all("SELECT c.nombre, SUM(d.subtotal) t, SUM(d.cantidad) q FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id JOIN categorias c ON c.id = pr.categoria_id WHERE $base GROUP BY c.id, c.nombre ORDER BY t DESC", [$a, $b]);
        $top = DB::all("SELECT d.nombre, SUM(d.cantidad) q, SUM(d.subtotal) t FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id WHERE $base GROUP BY d.producto_id, d.nombre ORDER BY q DESC LIMIT 12", [$a, $b]);
        $porUsuario = DB::all("SELECT u.nombre, COUNT(*) n, SUM(p.total) t FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE $base GROUP BY u.id, u.nombre ORDER BY t DESC", [$a, $b]);
        $porMetodo = DB::all("SELECT pg.metodo, SUM(pg.monto) t FROM pagos pg JOIN pedidos p ON p.id = pg.pedido_id WHERE $base GROUP BY pg.metodo ORDER BY t DESC", [$a, $b]);
        $porTipo = DB::all("SELECT p.tipo, COUNT(*) n, SUM(p.total) t FROM pedidos p WHERE $base GROUP BY p.tipo", [$a, $b]);
        $porDow = DB::all("SELECT DAYOFWEEK(p.fecha_pago) dw, SUM(p.total) t FROM pedidos p WHERE $base GROUP BY DAYOFWEEK(p.fecha_pago)", [$a, $b]);
        $consumo = DB::all("SELECT i.nombre, i.unidad, SUM(m.cantidad) q, SUM(m.cantidad * i.costo) t FROM insumo_movimientos m JOIN insumos i ON i.id = m.insumo_id WHERE m.tipo = 'salida' AND m.pedido_id IS NOT NULL AND m.fecha >= ? AND m.fecha < ? GROUP BY i.id, i.nombre, i.unidad ORDER BY t DESC", [$a, $b]);
        $dow = array_fill(1, 7, 0.0); foreach ($porDow as $r) $dow[(int)$r['dw']] = (float)$r['t'];

        $this->view('reportes/index', [
            'title' => 'Reportes', 'useCharts' => true, 'desde' => $desde, 'hasta' => $hasta,
            'k' => $k, 'costo' => $costo, 'gastos' => $gastos, 'compras' => $compras, 'anulados' => $anulados,
            'porDia' => $porDia, 'porCat' => $porCat, 'top' => $top, 'porUsuario' => $porUsuario, 'porMetodo' => $porMetodo, 'porTipo' => $porTipo,
            'dow' => array_values($dow), 'consumo' => $consumo,
        ]);
    }

    public function exportarAction(): void
    {
        [$a, $b, $desde, $hasta] = $this->rango();
        $tipo = $_GET['tipo'] ?? 'ventas';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . $desde . '_' . $hasta . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        if ($tipo === 'productos') {
            fputcsv($out, ['Producto', 'Cantidad vendida', 'Importe']);
            foreach (DB::all("SELECT d.nombre, SUM(d.cantidad) q, SUM(d.subtotal) t FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id WHERE p.estado='pagado' AND p.fecha_pago >= ? AND p.fecha_pago < ? GROUP BY d.producto_id, d.nombre ORDER BY q DESC", [$a, $b]) as $r)
                fputcsv($out, [$r['nombre'], $r['q'], $r['t']]);
        } else {
            fputcsv($out, ['Fecha', 'Comprobante', 'Tipo', 'Cliente', 'Subtotal', 'Descuento', 'Delivery', 'IGV', 'Total', 'Atendió']);
            foreach (DB::all("SELECT p.*, c.nombre cliente, u.nombre usuario FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id LEFT JOIN usuarios u ON u.id = p.usuario_id WHERE p.estado='pagado' AND p.fecha_pago >= ? AND p.fecha_pago < ? ORDER BY p.fecha_pago", [$a, $b]) as $r)
                fputcsv($out, [$r['fecha_pago'], Calc::numeroCompleto($r['serie'], $r['numero']), $r['tipo'], $r['cliente'] ?: 'Público general', $r['subtotal'], $r['descuento'], $r['costo_delivery'], $r['igv'], $r['total'], $r['usuario']]);
        }
        fclose($out);
        exit;
    }
}
