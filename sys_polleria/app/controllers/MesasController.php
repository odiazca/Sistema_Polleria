<?php
class MesasController extends CrudController
{
    protected string $module = 'mesas';

    protected function config(): array
    {
        return [
            'table' => 'mesas', 'title' => 'Administrar mesas', 'singular' => 'mesa', 'icon' => 'bi-grid-3x3-gap-fill', 'route' => 'mesas', 'list_action' => 'gestion',
            'subtitle' => 'Configure las mesas y zonas del local.', 'order' => 'zona, id', 'modal' => '',
            'header_actions' => '<a href="' . e(url('mesas/index')) . '" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver al plano</a>',
            'columns' => [
                ['key' => 'nombre', 'label' => 'Mesa', 'type' => 'strong'],
                ['key' => 'zona', 'label' => 'Zona', 'type' => 'badge', 'class' => 'info'],
                ['key' => 'capacidad', 'label' => 'Capacidad', 'type' => 'number'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'map', 'map' => ['libre' => ['ok', 'Libre'], 'ocupada' => ['brand', 'Ocupada'], 'reservada' => ['info', 'Reservada'], 'inactiva' => ['gray', 'Inactiva']]],
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => 1, 'col' => 6, 'placeholder' => 'Mesa 11'],
                ['name' => 'zona', 'label' => 'Zona', 'required' => 1, 'default' => 'Salón', 'col' => 6, 'placeholder' => 'Salón, Terraza…'],
                ['name' => 'capacidad', 'label' => 'Capacidad (personas)', 'type' => 'number', 'step' => '1', 'default' => 4, 'col' => 6],
                ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'options' => ['libre' => 'Libre', 'reservada' => 'Reservada', 'inactiva' => 'Inactiva'], 'required' => 1, 'default' => 'libre', 'col' => 6],
            ],
        ];
    }

    public function indexAction(): void
    {
        $mesas = DB::all("SELECT m.*, p.id AS pedido_id, p.total, p.fecha_pedido,
                (SELECT COUNT(*) FROM pedido_detalle d WHERE d.pedido_id = p.id) AS items
            FROM mesas m LEFT JOIN pedidos p ON p.mesa_id = m.id AND p.estado = 'abierto'
            WHERE m.estado <> 'inactiva' ORDER BY m.zona, m.id");
        $zonas = [];
        foreach ($mesas as $m) {
            $m['estado_real'] = $m['pedido_id'] ? 'ocupada' : ($m['estado'] === 'reservada' ? 'reservada' : 'libre');
            $zonas[$m['zona']][] = $m;
        }
        $sol = DB::all("SELECT s.*, m.nombre AS mesa, m.zona FROM qr_solicitudes s JOIN mesas m ON m.id = s.mesa_id WHERE s.estado = 'pendiente' ORDER BY s.id");
        foreach ($sol as &$r) { $r['lista'] = json_decode($r['items'], true) ?: []; $r['mins'] = max(0, (int)floor((time() - strtotime($r['created_at'])) / 60)); }
        unset($r);
        $this->view('mesas/index', ['title' => 'Mesas', 'proximas' => Reservas::proximasPorMesa(), 'zonas' => $zonas, 'solicitudes' => $sol]);
    }

    public function gestionAction(): void { $this->renderList(); }

    public function reservarAction(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $m = DB::one('SELECT estado FROM mesas WHERE id = ?', [$id]);
        if ($m && $m['estado'] !== 'ocupada') {
            DB::update('mesas', ['estado' => $m['estado'] === 'reservada' ? 'libre' : 'reservada'], 'id = ?', [$id]);
        }
        redirect('mesas/index');
    }
}
