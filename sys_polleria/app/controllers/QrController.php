<?php
/** Carta digital pública: el cliente escanea el QR de su mesa y envía su pedido */
class QrController extends Controller
{
    protected bool $public = true;

    private function mesa(): ?array
    {
        $t = (string)($_GET['t'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $t) || cfg('qr_activo', '1') !== '1') return null;
        return DB::one("SELECT id, nombre, zona, estado FROM mesas WHERE qr_token = ? AND estado <> 'inactiva'", [$t]);
    }

    public function menuAction(): void
    {
        $mesa = $this->mesa();
        if (!$mesa) {
            http_response_code(404);
            $this->view('qr/menu', ['title' => 'Carta', 'mesa' => null, 'cats' => [], 'prods' => []], '');
            return;
        }
        $cats = DB::all('SELECT id, nombre, icono, color FROM categorias WHERE estado = 1 ORDER BY orden, nombre');
        $prods = DB::all('SELECT id, categoria_id, nombre, descripcion, precio, imagen FROM productos WHERE estado = 1 AND (stock_control = 0 OR stock > 0) ORDER BY nombre');
        $this->view('qr/menu', ['title' => 'Carta digital', 'mesa' => $mesa, 'cats' => $cats, 'prods' => $prods], '');
    }

    public function pedirAction(): void
    {
        $mesa = $this->mesa();
        if (!$mesa || !$this->isPost()) $this->json(['ok' => false, 'msg' => 'Código QR no válido.'], 404);
        $d = $this->body();
        $items = array_slice((array)($d['items'] ?? []), 0, 30);
        if (!$items) $this->json(['ok' => false, 'msg' => 'Agregue productos a su pedido.']);
        if ((int)DB::val("SELECT COUNT(*) FROM qr_solicitudes WHERE mesa_id = ? AND estado = 'pendiente'", [$mesa['id']]) >= 3) {
            $this->json(['ok' => false, 'msg' => 'Ya hay pedidos esperando confirmación en esta mesa. Un mozo los atenderá en breve.']);
        }
        $ids = array_unique(array_map(fn($i) => (int)($i['id'] ?? 0), $items));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $prods = [];
        foreach (DB::all("SELECT id, nombre, precio, stock_control, stock FROM productos WHERE estado = 1 AND id IN ($in)", $ids) as $p) $prods[$p['id']] = $p;
        $clean = []; $total = 0.0;
        foreach ($items as $i) {
            $p = $prods[(int)($i['id'] ?? 0)] ?? null;
            if (!$p) continue;
            $q = max(1, min(20, (int)($i['cantidad'] ?? 1)));
            if ((int)$p['stock_control'] && $q > (float)$p['stock']) $this->json(['ok' => false, 'msg' => 'No hay stock suficiente de «' . $p['nombre'] . '».']);
            $clean[] = ['producto_id' => (int)$p['id'], 'nombre' => $p['nombre'], 'cantidad' => $q, 'precio' => (float)$p['precio'], 'nota' => mb_substr(trim((string)($i['nota'] ?? '')), 0, 120)];
            $total += $q * (float)$p['precio'];
        }
        if (!$clean) $this->json(['ok' => false, 'msg' => 'Los productos elegidos ya no están disponibles.']);
        $id = DB::insert('qr_solicitudes', [
            'mesa_id' => $mesa['id'], 'cliente_nombre' => mb_substr(trim((string)($d['nombre'] ?? '')), 0, 80) ?: null,
            'notas' => mb_substr(trim((string)($d['notas'] ?? '')), 0, 250) ?: null, 'items' => json_encode($clean, JSON_UNESCAPED_UNICODE), 'total' => round($total, 2),
        ]);
        $this->json(['ok' => true, 'id' => $id, 'total' => round($total, 2)]);
    }

    public function estadoAction(): void
    {
        $mesa = $this->mesa();
        $id = (int)($_GET['id'] ?? 0);
        $e = $mesa ? DB::val('SELECT estado FROM qr_solicitudes WHERE id = ? AND mesa_id = ?', [$id, $mesa['id']]) : null;
        $this->json(['ok' => (bool)$e, 'estado' => $e]);
    }
}
