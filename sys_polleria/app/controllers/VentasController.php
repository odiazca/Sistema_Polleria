<?php
class VentasController extends Controller
{
    protected string $module = 'ventas';

    public function indexAction(): void
    {
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-d', strtotime('-6 days'));
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-d');
        $estado = in_array($_GET['estado'] ?? '', ['pagado', 'anulado', 'abierto'], true) ? $_GET['estado'] : '';
        $tipo = in_array($_GET['tipo'] ?? '', ['mesa', 'llevar', 'delivery'], true) ? $_GET['tipo'] : '';
        $q = trim($_GET['q'] ?? '');

        $w = ['DATE(COALESCE(p.fecha_pago, p.fecha_pedido)) BETWEEN ? AND ?']; $par = [$desde, $hasta];
        if ($estado) { $w[] = 'p.estado = ?'; $par[] = $estado; }
        if ($tipo) { $w[] = 'p.tipo = ?'; $par[] = $tipo; }
        if ($q !== '') { $w[] = '(c.nombre LIKE ? OR p.numero LIKE ? OR CONCAT(p.serie,"-",LPAD(p.numero,6,"0")) LIKE ? OR p.id = ?)'; $par = array_merge($par, ["%$q%", "%$q%", "%$q%", (int)$q]); }
        $rows = DB::all('SELECT p.*, c.nombre AS cliente, m.nombre AS mesa, u.nombre AS usuario,
                (SELECT GROUP_CONCAT(DISTINCT metodo SEPARATOR ", ") FROM pagos WHERE pedido_id = p.id) AS metodos
            FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id LEFT JOIN mesas m ON m.id = p.mesa_id LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE ' . implode(' AND ', $w) . ' ORDER BY p.id DESC LIMIT 500', $par);
        $sum = ['n' => 0, 't' => 0.0, 'a' => 0];
        foreach ($rows as $r) { if ($r['estado'] === 'pagado') { $sum['n']++; $sum['t'] += $r['total']; } elseif ($r['estado'] === 'anulado') $sum['a']++; }
        $this->view('ventas/index', ['title' => 'Historial de ventas', 'rows' => $rows, 'sum' => $sum, 'f' => compact('desde', 'hasta', 'estado', 'tipo', 'q')]);
    }

    public function anularAction(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        try {
            if ($motivo === '') throw new RuntimeException('Indique el motivo de la anulación.');
            DB::begin();
            $p = DB::one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$id]);
            if (!$p || $p['estado'] === 'anulado') throw new RuntimeException('El pedido no existe o ya está anulado.');
            if ($p['estado'] === 'pagado') {
                DB::run('UPDATE productos pr JOIN pedido_detalle d ON d.producto_id = pr.id AND d.pedido_id = ? SET pr.stock = pr.stock + d.cantidad WHERE pr.stock_control = 1', [$id]);
                Receta::revertir($id);
            }
            DB::update('pedidos', ['estado' => 'anulado', 'motivo_anulacion' => mb_substr($motivo, 0, 200), 'delivery_estado' => $p['tipo'] === 'delivery' ? 'cancelado' : null], 'id = ?', [$id]);
            if ($p['mesa_id']) DB::update('mesas', ['estado' => 'libre'], "id = ? AND estado = 'ocupada'", [$p['mesa_id']]);
            DB::commit();
            Sunat::anular($p, $motivo);
            Log::add('anular', 'Pedido #' . $id . ': ' . $motivo);
            flash('success', 'Pedido anulado.');
        } catch (RuntimeException $e) {
            DB::rollback();
            flash('danger', $e->getMessage());
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('ventas/index')));
        exit;
    }
}
