<?php
class InsumosController extends CrudController
{
    protected string $module = 'insumos';

    protected function config(): array
    {
        $u = ['kg' => 'Kilogramos (kg)', 'g' => 'Gramos (g)', 'lt' => 'Litros (lt)', 'und' => 'Unidades (und)', 'saco' => 'Sacos', 'caja' => 'Cajas', 'paq' => 'Paquetes'];
        return [
            'table' => 'insumos', 'title' => 'Insumos', 'singular' => 'insumo', 'icon' => 'bi-box-seam-fill', 'route' => 'insumos',
            'subtitle' => 'Materia prima y suministros: pollo, papa, aceite, carbón, empaques…', 'order' => 'nombre ASC', 'modal' => '',
            'columns' => [
                ['key' => 'nombre', 'label' => 'Insumo', 'type' => 'strong'],
                ['key' => 'unidad', 'label' => 'Unidad', 'type' => 'badge', 'class' => 'gray'],
                ['key' => 'stock', 'label' => 'Stock', 'type' => 'stock', 'dec' => 1, 'unit' => 'unidad'],
                ['key' => 'stock_minimo', 'label' => 'Mínimo', 'type' => 'number', 'dec' => 1],
                ['key' => 'costo', 'label' => 'Costo unit.', 'type' => 'money'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'extra_actions' => [['label' => 'Movimientos / ajuste', 'icon' => 'bi-arrow-left-right', 'url' => fn($r) => url('insumos/movimientos', ['id' => $r['id']])]],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => 1, 'col' => 12],
                ['name' => 'unidad', 'label' => 'Unidad de medida', 'type' => 'select', 'options' => $u, 'required' => 1, 'default' => 'kg', 'col' => 6],
                ['name' => 'costo', 'label' => 'Costo por unidad', 'type' => 'money', 'col' => 6],
                ['name' => 'stock', 'label' => 'Stock inicial / actual', 'type' => 'number', 'col' => 6],
                ['name' => 'stock_minimo', 'label' => 'Stock mínimo (alerta)', 'type' => 'number', 'col' => 6],
                ['name' => 'estado', 'label' => 'Insumo activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }

    protected function afterSave(int $id, bool $isNew): void { Receta::recalcPorInsumo($id); }

    public function movimientosAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ins = DB::one('SELECT * FROM insumos WHERE id = ?', [$id]);
        if (!$ins) redirect('insumos/index');
        $movs = DB::all('SELECT m.*, u.nombre AS usuario FROM insumo_movimientos m LEFT JOIN usuarios u ON u.id = m.usuario_id WHERE m.insumo_id = ? ORDER BY m.id DESC LIMIT 100', [$id]);
        $this->view('crud/insumo_mov', ['title' => 'Movimientos de insumo', 'ins' => $ins, 'movs' => $movs]);
    }

    public function ajustarAction(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        $cant = (float)str_replace(',', '.', $_POST['cantidad'] ?? '0');
        try {
            $ins = DB::one('SELECT * FROM insumos WHERE id = ?', [$id]);
            if (!$ins || !in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) throw new RuntimeException('Datos inválidos.');
            if ($cant < 0 || ($tipo !== 'ajuste' && $cant == 0)) throw new RuntimeException('Ingrese una cantidad válida.');
            $nuevo = match ($tipo) { 'entrada' => $ins['stock'] + $cant, 'salida' => max(0, $ins['stock'] - $cant), default => $cant };
            DB::begin();
            DB::update('insumos', ['stock' => $nuevo], 'id = ?', [$id]);
            DB::insert('insumo_movimientos', ['insumo_id' => $id, 'tipo' => $tipo, 'cantidad' => $cant, 'motivo' => trim($_POST['motivo'] ?? '') ?: null, 'usuario_id' => Auth::id()]);
            DB::commit();
            flash('success', 'Movimiento registrado. Nuevo stock: ' . num($nuevo, 2) . ' ' . $ins['unidad']);
        } catch (RuntimeException $e) {
            DB::rollback();
            flash('danger', $e->getMessage());
        }
        redirect('insumos/movimientos', ['id' => $id]);
    }
}
