<?php
class RecetasController extends Controller
{
    protected string $module = 'recetas';

    public function indexAction(): void
    {
        $rows = DB::all("SELECT p.id, p.nombre, p.precio, p.costo, p.estado, c.nombre AS categoria,
                (SELECT COUNT(*) FROM recetas r WHERE r.producto_id = p.id) AS n_insumos
            FROM productos p JOIN categorias c ON c.id = p.categoria_id WHERE p.estado = 1 AND p.stock_control = 0 ORDER BY c.orden, p.nombre");
        $this->view('recetas/index', ['title' => 'Recetas', 'rows' => $rows]);
    }

    public function editarAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $p = DB::one('SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON c.id = p.categoria_id WHERE p.id = ?', [$id]);
        if (!$p) redirect('recetas/index');
        $lineas = DB::all('SELECT insumo_id, cantidad FROM recetas WHERE producto_id = ? ORDER BY id', [$id]);
        $insumos = DB::all('SELECT id, nombre, unidad, costo, stock FROM insumos WHERE estado = 1 ORDER BY nombre');
        $this->view('recetas/form', ['title' => 'Receta: ' . $p['nombre'], 'p' => $p, 'lineas' => $lineas, 'insumos' => $insumos]);
    }

    public function guardarAction(): void
    {
        $id = (int)($_POST['producto_id'] ?? 0);
        if (!DB::val('SELECT id FROM productos WHERE id = ?', [$id])) redirect('recetas/index');
        $ids = (array)($_POST['insumo_id'] ?? []); $cants = (array)($_POST['cantidad'] ?? []);
        $lines = [];
        foreach ($ids as $i => $iid) {
            $q = (float)str_replace(',', '.', $cants[$i] ?? 0);
            if ((int)$iid && $q > 0) $lines[(int)$iid] = ($lines[(int)$iid] ?? 0) + $q;
        }
        DB::begin();
        DB::run('DELETE FROM recetas WHERE producto_id = ?', [$id]);
        foreach ($lines as $iid => $q) DB::insert('recetas', ['producto_id' => $id, 'insumo_id' => $iid, 'cantidad' => $q]);
        Receta::recalc($id);
        DB::commit();
        Log::add('receta', 'Receta del producto #' . $id . ' actualizada');
        flash('success', $lines ? 'Receta guardada. El costo del producto se actualizó a ' . money(Receta::costo($id)) . '.' : 'Receta eliminada. El producto ya no descuenta insumos.');
        redirect('recetas/index');
    }
}
