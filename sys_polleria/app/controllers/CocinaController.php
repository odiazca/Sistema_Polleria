<?php
class CocinaController extends Controller
{
    protected string $module = 'cocina';

    private function pedidos(): array
    {
        $rows = DB::all("SELECT p.id, p.tipo, p.notas, p.fecha_pedido, m.nombre AS mesa, u.nombre AS mozo, c.nombre AS cliente
            FROM pedidos p LEFT JOIN mesas m ON m.id = p.mesa_id LEFT JOIN usuarios u ON u.id = p.usuario_id LEFT JOIN clientes c ON c.id = p.cliente_id
            WHERE p.estado = 'abierto' AND EXISTS (SELECT 1 FROM pedido_detalle d WHERE d.pedido_id = p.id AND d.estado IN ('pendiente','preparando','listo'))
            ORDER BY p.fecha_pedido ASC");
        foreach ($rows as &$r) {
            $r['items'] = DB::all("SELECT id, nombre, cantidad, nota, estado FROM pedido_detalle WHERE pedido_id = ? AND estado <> 'entregado' ORDER BY id", [$r['id']]);
            $r['mins'] = max(0, (int)floor((time() - strtotime($r['fecha_pedido'])) / 60));
        }
        return $rows;
    }

    public function indexAction(): void
    {
        $this->view('cocina/index', ['title' => 'Cocina', 'pedidos' => $this->pedidos()]);
    }

    public function listaAction(): void
    {
        $this->partial('cocina/lista', ['pedidos' => $this->pedidos()]);
    }

    public function itemAction(): void
    {
        $d = $this->body();
        $est = $d['estado'] ?? '';
        if (!in_array($est, ['pendiente', 'preparando', 'listo', 'entregado'], true)) $this->json(['ok' => false, 'msg' => 'Estado inválido']);
        if (!empty($d['pedido_id'])) {
            // Acción masiva sobre un pedido
            $flow = ['pendiente' => 0, 'preparando' => 1, 'listo' => 2, 'entregado' => 3];
            $rows = DB::all("SELECT id, estado FROM pedido_detalle WHERE pedido_id = ? AND estado <> 'entregado'", [(int)$d['pedido_id']]);
            foreach ($rows as $r) if ($flow[$r['estado']] < $flow[$est]) DB::update('pedido_detalle', ['estado' => $est], 'id = ?', [$r['id']]);
        } else {
            DB::update('pedido_detalle', ['estado' => $est], 'id = ?', [(int)($d['id'] ?? 0)]);
        }
        $this->json(['ok' => true]);
    }
}
