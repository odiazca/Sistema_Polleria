<?php
class DeliveryController extends Controller
{
    protected string $module = 'delivery';

    public function indexAction(): void
    {
        $f = $_GET['estado'] ?? 'activos';
        $where = "p.tipo = 'delivery' AND p.estado <> 'anulado'";
        if ($f === 'activos') $where .= " AND p.delivery_estado IN ('pendiente','en_camino')";
        elseif (in_array($f, ['pendiente', 'en_camino'], true)) $where .= " AND p.delivery_estado = '$f'";
        elseif ($f === 'entregado') $where .= " AND p.delivery_estado = 'entregado' AND DATE(p.fecha_pedido) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
        $rows = DB::all("SELECT p.*, c.nombre AS cliente, r.nombre AS repartidor FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id LEFT JOIN repartidores r ON r.id = p.repartidor_id WHERE $where ORDER BY p.fecha_pedido DESC LIMIT 100");
        $cont = [];
        foreach (DB::all("SELECT delivery_estado e, COUNT(*) n FROM pedidos WHERE tipo='delivery' AND estado <> 'anulado' AND (delivery_estado IN ('pendiente','en_camino') OR DATE(fecha_pedido) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)) GROUP BY delivery_estado") as $r) $cont[$r['e']] = (int)$r['n'];
        $reps = DB::all('SELECT id, nombre FROM repartidores WHERE estado = 1 ORDER BY nombre');
        $this->view('delivery/index', ['title' => 'Delivery', 'rows' => $rows, 'reps' => $reps, 'f' => $f, 'cont' => $cont]);
    }

    public function actualizarAction(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $data = [];
        if (isset($_POST['repartidor_id'])) $data['repartidor_id'] = (int)$_POST['repartidor_id'] ?: null;
        if (isset($_POST['delivery_estado']) && in_array($_POST['delivery_estado'], ['pendiente', 'en_camino', 'entregado', 'cancelado'], true)) {
            $data['delivery_estado'] = $_POST['delivery_estado'];
            if ($data['delivery_estado'] === 'en_camino' && !DB::val('SELECT repartidor_id FROM pedidos WHERE id = ?', [$id]) && empty($data['repartidor_id'])) {
                flash('warning', 'Asigne un repartidor antes de enviar el pedido.');
                redirect('delivery/index', ['estado' => $_POST['back'] ?? 'activos']);
            }
        }
        if ($data) DB::update('pedidos', $data, "id = ? AND tipo = 'delivery'", [$id]);
        flash('success', 'Pedido delivery actualizado.');
        redirect('delivery/index', ['estado' => $_POST['back'] ?? 'activos']);
    }
}
