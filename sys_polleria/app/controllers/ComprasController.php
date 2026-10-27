<?php
class ComprasController extends Controller
{
    protected string $module = 'compras';

    public function indexAction(): void
    {
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-d', strtotime('-30 days'));
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-d');
        $rows = DB::all('SELECT c.*, p.razon_social AS proveedor, u.nombre AS usuario, (SELECT COUNT(*) FROM compra_detalle d WHERE d.compra_id = c.id) AS items
            FROM compras c LEFT JOIN proveedores p ON p.id = c.proveedor_id LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.fecha BETWEEN ? AND ? ORDER BY c.fecha DESC, c.id DESC', [$desde, $hasta]);
        $this->view('compras/index', ['title' => 'Compras', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'total' => array_sum(array_column($rows, 'total'))]);
    }

    public function nuevaAction(): void
    {
        $this->view('compras/form', [
            'title' => 'Nueva compra',
            'provs' => DB::all('SELECT id, razon_social FROM proveedores WHERE estado = 1 ORDER BY razon_social'),
            'insumos' => DB::all('SELECT id, nombre, unidad, costo FROM insumos WHERE estado = 1 ORDER BY nombre'),
        ]);
    }

    public function guardarAction(): void
    {
        try {
            $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha'] ?? '') ? $_POST['fecha'] : date('Y-m-d');
            $ids = (array)($_POST['insumo_id'] ?? []); $cants = (array)($_POST['cantidad'] ?? []); $costos = (array)($_POST['costo'] ?? []);
            $lines = [];
            foreach ($ids as $i => $iid) {
                $q = (float)str_replace(',', '.', $cants[$i] ?? 0); $c = (float)str_replace(',', '.', $costos[$i] ?? 0);
                if ((int)$iid && $q > 0 && $c >= 0) $lines[] = [(int)$iid, $q, $c];
            }
            if (!$lines) throw new RuntimeException('Agregue al menos un insumo con cantidad.');
            DB::begin();
            $total = array_sum(array_map(fn($l) => $l[1] * $l[2], $lines));
            $cid = DB::insert('compras', [
                'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0) ?: null, 'usuario_id' => Auth::id(), 'fecha' => $fecha,
                'comprobante' => trim($_POST['comprobante'] ?? '') ?: null, 'total' => round($total, 2), 'notas' => trim($_POST['notas'] ?? '') ?: null,
            ]);
            foreach ($lines as [$iid, $q, $c]) {
                DB::insert('compra_detalle', ['compra_id' => $cid, 'insumo_id' => $iid, 'cantidad' => $q, 'costo' => $c, 'subtotal' => round($q * $c, 2)]);
                DB::run('UPDATE insumos SET stock = stock + ?, costo = ? WHERE id = ?', [$q, $c, $iid]);
                Receta::recalcPorInsumo($iid);
                DB::insert('insumo_movimientos', ['insumo_id' => $iid, 'tipo' => 'entrada', 'cantidad' => $q, 'motivo' => 'Compra #' . $cid, 'usuario_id' => Auth::id()]);
            }
            DB::commit();
            Log::add('compra', 'Compra #' . $cid . ' por ' . money($total));
            flash('success', 'Compra registrada y stock actualizado.');
            redirect('compras/ver', ['id' => $cid]);
        } catch (RuntimeException $e) {
            DB::rollback();
            flash('danger', $e->getMessage());
            redirect('compras/nueva');
        }
    }

    public function verAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $c = DB::one('SELECT c.*, p.razon_social AS proveedor, p.ruc, u.nombre AS usuario FROM compras c LEFT JOIN proveedores p ON p.id = c.proveedor_id LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = ?', [$id]);
        if (!$c) redirect('compras/index');
        $items = DB::all('SELECT d.*, i.nombre, i.unidad FROM compra_detalle d JOIN insumos i ON i.id = d.insumo_id WHERE d.compra_id = ?', [$id]);
        $this->view('compras/ver', ['title' => 'Compra #' . $id, 'c' => $c, 'items' => $items]);
    }

    public function eliminarAction(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if (!Auth::isAdmin()) { flash('danger', 'Solo un administrador puede eliminar compras.'); redirect('compras/index'); }
        DB::begin();
        foreach (DB::all('SELECT insumo_id, cantidad FROM compra_detalle WHERE compra_id = ?', [$id]) as $d) {
            DB::run('UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?', [$d['cantidad'], $d['insumo_id']]);
            DB::insert('insumo_movimientos', ['insumo_id' => $d['insumo_id'], 'tipo' => 'salida', 'cantidad' => $d['cantidad'], 'motivo' => 'Eliminación de compra #' . $id, 'usuario_id' => Auth::id()]);
        }
        DB::run('DELETE FROM compras WHERE id = ?', [$id]);
        DB::commit();
        Log::add('compra', 'Compra #' . $id . ' eliminada');
        flash('success', 'Compra eliminada y stock revertido.');
        redirect('compras/index');
    }
}
