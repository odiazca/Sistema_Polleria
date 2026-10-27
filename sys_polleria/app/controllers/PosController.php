<?php
class PosController extends Controller
{
    protected string $module = 'pos';

    public function indexAction(): void
    {
        $pedido = null; $items = [];
        $pid = (int)($_GET['pedido'] ?? 0);
        $mesaSel = (int)($_GET['mesa'] ?? 0);
        $tipo = in_array($_GET['tipo'] ?? '', ['llevar', 'delivery'], true) ? $_GET['tipo'] : 'mesa';

        if (!$pid && $mesaSel) {
            $abierto = DB::val("SELECT id FROM pedidos WHERE mesa_id = ? AND estado = 'abierto' LIMIT 1", [$mesaSel]);
            if ($abierto) redirect('pos/index', ['pedido' => $abierto]);
        }
        if ($pid) {
            $pedido = DB::one('SELECT p.*, c.nombre AS cliente_nombre, c.documento AS cliente_doc, c.tipo_doc AS cliente_tipo FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id WHERE p.id = ?', [$pid]);
            if (!$pedido) redirect('pos/index');
            if ($pedido['estado'] !== 'abierto') redirect('pos/ticket', ['id' => $pid]);
            $items = DB::all('SELECT id, producto_id, nombre, cantidad, precio, nota, estado FROM pedido_detalle WHERE pedido_id = ? ORDER BY id', [$pid]);
            $tipo = $pedido['tipo'];
        }

        $cats = DB::all('SELECT id, nombre, icono, color FROM categorias WHERE estado = 1 ORDER BY orden, nombre');
        $prods = DB::all('SELECT id, categoria_id, nombre, descripcion, precio, imagen, stock_control, stock FROM productos WHERE estado = 1 ORDER BY nombre');
        $mesas = DB::all("SELECT m.id, m.nombre, m.zona, m.capacidad, (SELECT p.id FROM pedidos p WHERE p.mesa_id = m.id AND p.estado = 'abierto' LIMIT 1) AS pedido_id FROM mesas m WHERE m.estado <> 'inactiva' ORDER BY m.zona, m.id");
        $caja = DB::one("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);

        $data = [
            'cats' => $cats, 'prods' => $prods, 'mesas' => $mesas, 'tipo' => $tipo, 'mesaSel' => $pedido['mesa_id'] ?? $mesaSel ?: null,
            'caja' => (bool)$caja, 'canCobrar' => Auth::can('caja') || Auth::isAdmin() || Auth::can('ventas'),
            'igv' => ['activo' => cfg('igv_activo', '1') === '1', 'pct' => (float)cfg('igv_porcentaje', 18), 'incluido' => cfg('igv_incluido', '1') === '1'],
            'deliveryFee' => (float)cfg('costo_delivery', 0),
            'pedido' => $pedido ? [
                'id' => (int)$pedido['id'], 'tipo' => $pedido['tipo'], 'mesa_id' => $pedido['mesa_id'], 'cliente_id' => $pedido['cliente_id'],
                'cliente' => $pedido['cliente_id'] ? ['id' => $pedido['cliente_id'], 'nombre' => $pedido['cliente_nombre'], 'documento' => $pedido['cliente_doc'], 'tipo_doc' => $pedido['cliente_tipo']] : null,
                'descuento' => (float)$pedido['descuento'], 'costo_delivery' => (float)$pedido['costo_delivery'], 'direccion' => $pedido['direccion_entrega'],
                'telefono' => $pedido['telefono_entrega'], 'notas' => $pedido['notas'],
            ] : null,
            'items' => $items,
        ];
        $this->view('pos/index', ['title' => 'Punto de Venta', 'pos' => $data, 'scripts' => ['js/pos.js']]);
    }

    /** Guarda / actualiza el pedido y lo envía a cocina */
    public function guardarAction(): void
    {
        try {
            $r = $this->persist($this->body());
            $this->json(['ok' => true, 'pedido_id' => $r['id'], 'total' => $r['total'], 'msg' => 'Pedido enviado a cocina']);
        } catch (RuntimeException $e) {
            DB::rollback();
            $this->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function cobrarAction(): void
    {
        try {
            if (!Auth::can('caja') && !Auth::isAdmin()) throw new RuntimeException('Su rol no tiene permiso para cobrar.');
            $d = $this->body();
            $r = $this->persist($d);
            $res = $this->pagar($r['id'], $d);
            $this->json(['ok' => true, 'pedido_id' => $r['id'], 'vuelto' => $res['vuelto'], 'ticket' => url('pos/ticket', ['id' => $r['id'], 'print' => 1])]);
        } catch (RuntimeException $e) {
            DB::rollback();
            $this->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    private function persist(array $d): array
    {
        return Pedidos::guardar($d);
    }

    private function pagar(int $pid, array $d): array
    {
        $caja = DB::one("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);
        if (!$caja) throw new RuntimeException('Debe abrir su caja antes de cobrar (módulo Caja).');
        $comp = in_array($d['comprobante'] ?? '', ['ticket', 'boleta', 'factura'], true) ? $d['comprobante'] : 'ticket';
        $metodos = ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];

        DB::begin();
        $ped = DB::one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$pid]);
        if (!$ped || $ped['estado'] !== 'abierto') throw new RuntimeException('El pedido ya fue cobrado o anulado.');
        if ($comp === 'factura') {
            $cli = $ped['cliente_id'] ? DB::one('SELECT tipo_doc, documento FROM clientes WHERE id = ?', [$ped['cliente_id']]) : null;
            if (!$cli || empty($cli['documento'])) throw new RuntimeException('Para factura seleccione un cliente con RUC.');
        }
        $total = (float)$ped['total'];
        $lines = [];
        $sum = 0.0;
        foreach (($d['pagos'] ?? []) as $p) {
            $m = $p['metodo'] ?? '';
            $a = round((float)($p['monto'] ?? 0), 2);
            if (!in_array($m, $metodos, true) || $a <= 0) continue;
            $lines[] = ['metodo' => $m, 'monto' => $a];
            $sum += $a;
        }
        if (!$lines) throw new RuntimeException('Indique al menos un método de pago.');
        if ($sum + 0.005 < $total) throw new RuntimeException('El monto recibido (' . money($sum) . ') es menor al total (' . money($total) . ').');
        $vuelto = round($sum - $total, 2);
        if ($vuelto > 0.005) {
            $cash = array_sum(array_map(fn($l) => $l['metodo'] === 'efectivo' ? $l['monto'] : 0, $lines));
            if ($cash + 0.005 < $vuelto) throw new RuntimeException('El vuelto solo puede darse del pago en efectivo.');
            $rest = $vuelto;
            foreach ($lines as &$l) {
                if ($l['metodo'] === 'efectivo' && $rest > 0) { $take = min($rest, $l['monto']); $l['monto'] = round($l['monto'] - $take, 2); $rest -= $take; }
            }
            unset($l);
        }
        [$serie, $num] = Calc::siguienteNumero($comp);
        foreach ($lines as $l) if ($l['monto'] > 0) DB::insert('pagos', ['pedido_id' => $pid, 'metodo' => $l['metodo'], 'monto' => $l['monto']]);

        DB::update('pedidos', [
            'estado' => 'pagado', 'caja_id' => $caja['id'], 'comprobante' => $comp, 'serie' => $serie, 'numero' => $num, 'fecha_pago' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$pid]);
        // Descontar stock de productos controlados
        DB::run('UPDATE productos p JOIN pedido_detalle d ON d.producto_id = p.id AND d.pedido_id = ? SET p.stock = GREATEST(0, p.stock - d.cantidad) WHERE p.stock_control = 1', [$pid]);
        Receta::consumir($pid);
        if ($ped['mesa_id']) DB::update('mesas', ['estado' => 'libre'], "id = ? AND estado = 'ocupada'", [$ped['mesa_id']]);
        if (Sunat::activo() && Sunat::aplica($comp)) DB::update('pedidos', ['fe_estado' => 'pendiente'], 'id = ?', [$pid]);
        DB::commit();
        if (Sunat::activo() && Sunat::aplica($comp) && cfg('fe_auto', '1') === '1') { try { Sunat::enviar($pid); } catch (Throwable $e) { /* queda pendiente */ } }
        Log::add('venta', 'Cobro pedido #' . $pid . ' ' . Calc::numeroCompleto($serie, $num) . ' por ' . money($total));
        return ['vuelto' => $vuelto];
    }

    /** Ticket / pre-cuenta imprimible */
    public function ticketAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $p = DB::one('SELECT p.*, c.nombre AS cliente, c.documento, c.tipo_doc, m.nombre AS mesa, u.nombre AS cajero FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id LEFT JOIN mesas m ON m.id = p.mesa_id LEFT JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = ?', [$id]);
        if (!$p) { http_response_code(404); exit('Pedido no encontrado'); }
        $items = DB::all('SELECT * FROM pedido_detalle WHERE pedido_id = ? ORDER BY id', [$id]);
        $pagos = DB::all('SELECT metodo, SUM(monto) monto FROM pagos WHERE pedido_id = ? GROUP BY metodo', [$id]);
        $this->view('pos/ticket', ['title' => 'Ticket', 'p' => $p, 'items' => $items, 'pagos' => $pagos, 'auto' => !empty($_GET['print']), 'precuenta' => $p['estado'] === 'abierto'], '');
    }
}
