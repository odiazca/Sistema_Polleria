<?php
/** Gestión de pedidos recibidos por QR + códigos QR de las mesas */
class SolicitudesController extends Controller
{
    protected string $module = 'mesas';

    private function pendientes(): array
    {
        $rows = DB::all("SELECT s.*, m.nombre AS mesa, m.zona FROM qr_solicitudes s JOIN mesas m ON m.id = s.mesa_id WHERE s.estado = 'pendiente' ORDER BY s.id ASC");
        foreach ($rows as &$r) {
            $r['lista'] = json_decode($r['items'], true) ?: [];
            $r['mins'] = max(0, (int)floor((time() - strtotime($r['created_at'])) / 60));
        }
        return $rows;
    }

    public function listaAction(): void
    {
        $this->partial('mesas/solicitudes', ['solicitudes' => $this->pendientes()]);
    }

    public function aceptarAction(): void
    {
        $id = (int)($this->body()['id'] ?? 0);
        try {
            if (!Auth::can('pos')) throw new RuntimeException('Su rol no puede confirmar pedidos.');
            DB::begin();
            $s = DB::one("SELECT * FROM qr_solicitudes WHERE id = ? AND estado = 'pendiente' FOR UPDATE", [$id]);
            if (!$s) throw new RuntimeException('La solicitud ya fue atendida.');
            DB::commit();

            $old = DB::one("SELECT * FROM pedidos WHERE mesa_id = ? AND estado = 'abierto' LIMIT 1", [$s['mesa_id']]);
            $items = [];
            if ($old) {
                foreach (DB::all('SELECT id, producto_id, cantidad, nota FROM pedido_detalle WHERE pedido_id = ? ORDER BY id', [$old['id']]) as $d)
                    $items[] = ['detalle_id' => (int)$d['id'], 'producto_id' => (int)$d['producto_id'], 'cantidad' => (float)$d['cantidad'], 'nota' => $d['nota']];
            }
            foreach (json_decode($s['items'], true) as $i) {
                $nota = trim($i['nota'] . ($s['cliente_nombre'] ? ' [' . $s['cliente_nombre'] . ']' : ''));
                $items[] = ['detalle_id' => 0, 'producto_id' => $i['producto_id'], 'cantidad' => $i['cantidad'], 'nota' => $nota ?: null];
            }
            $notas = trim(($old['notas'] ?? '') . ($s['notas'] ? ' | QR: ' . $s['notas'] : ''), ' |');
            $r = Pedidos::guardar([
                'pedido_id' => $old['id'] ?? 0, 'tipo' => 'mesa', 'mesa_id' => $s['mesa_id'], 'cliente_id' => $old['cliente_id'] ?? null,
                'descuento' => $old['descuento'] ?? 0, 'notas' => $notas, 'items' => $items,
            ]);
            DB::update('qr_solicitudes', ['estado' => 'aceptada', 'pedido_id' => $r['id']], 'id = ?', [$id]);
            Log::add('qr', 'Pedido QR #' . $id . ' aceptado → pedido #' . $r['id']);
            $this->json(['ok' => true, 'msg' => 'Pedido enviado a cocina (pedido #' . $r['id'] . ')']);
        } catch (RuntimeException $e) {
            DB::rollback();
            $this->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function rechazarAction(): void
    {
        $id = (int)($this->body()['id'] ?? 0);
        DB::update('qr_solicitudes', ['estado' => 'rechazada'], "id = ? AND estado = 'pendiente'", [$id]);
        $this->json(['ok' => true, 'msg' => 'Solicitud rechazada']);
    }

    public function qrAction(): void
    {
        Qr::ensureTokens();
        $mesas = DB::all("SELECT id, nombre, zona, qr_token FROM mesas WHERE estado <> 'inactiva' ORDER BY zona, id");
        $this->view('mesas/qr', ['title' => 'Códigos QR de mesas', 'mesas' => $mesas, 'base' => Qr::baseUrl(), 'activo' => cfg('qr_activo', '1') === '1', 'baseCfg' => cfg('qr_base_url', '')]);
    }

    public function guardarQrAction(): void
    {
        $url = trim((string)($_POST['base_url'] ?? ''));
        if ($url !== '' && !preg_match('#^https?://[^\s]+$#i', $url)) { flash('danger', 'La dirección debe empezar con http:// o https://'); redirect('solicitudes/qr'); }
        Setting::set('qr_base_url', rtrim($url, '/'));
        Setting::set('qr_activo', isset($_POST['activo']) ? '1' : '0');
        flash('success', 'Configuración de QR guardada.');
        redirect('solicitudes/qr');
    }
}
