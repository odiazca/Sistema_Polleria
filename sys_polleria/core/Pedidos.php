<?php
/** Servicio de pedidos: crea/actualiza un pedido y lo envía a cocina */
class Pedidos
{
    public static function guardar(array $d): array
    {
        $tipo = in_array($d['tipo'] ?? '', ['mesa', 'llevar', 'delivery'], true) ? $d['tipo'] : 'mesa';
        $items = $d['items'] ?? [];
        if (!is_array($items) || !$items) throw new RuntimeException('Agregue al menos un producto al pedido.');
        $mesaId = $tipo === 'mesa' ? (int)($d['mesa_id'] ?? 0) : 0;
        if ($tipo === 'mesa' && !$mesaId) throw new RuntimeException('Seleccione la mesa del pedido.');
        $pid = (int)($d['pedido_id'] ?? 0);

        DB::begin();
        $old = null;
        if ($pid) {
            $old = DB::one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$pid]);
            if (!$old || $old['estado'] !== 'abierto') throw new RuntimeException('Este pedido ya no está abierto.');
        }
        if ($mesaId) {
            $ocup = DB::val("SELECT id FROM pedidos WHERE mesa_id = ? AND estado = 'abierto' AND id <> ? LIMIT 1", [$mesaId, $pid]);
            if ($ocup) throw new RuntimeException('Esa mesa ya tiene un pedido abierto.');
        }

        $ids = array_unique(array_map(fn($i) => (int)($i['producto_id'] ?? 0), $items));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $prods = [];
        foreach (DB::all("SELECT id, nombre, precio, para_cocina, stock_control, stock, estado FROM productos WHERE id IN ($in)", $ids) as $p) $prods[$p['id']] = $p;

        $cliente = (int)($d['cliente_id'] ?? 0) ?: null;
        if ($cliente && !DB::val('SELECT id FROM clientes WHERE id = ?', [$cliente])) $cliente = null;

        $head = [
            'tipo' => $tipo, 'mesa_id' => $mesaId ?: null, 'cliente_id' => $cliente,
            'costo_delivery' => $tipo === 'delivery' ? max(0, (float)($d['costo_delivery'] ?? 0)) : 0,
            'direccion_entrega' => $tipo === 'delivery' ? (mb_substr(trim($d['direccion'] ?? ''), 0, 220) ?: null) : null,
            'telefono_entrega' => $tipo === 'delivery' ? (mb_substr(trim($d['telefono'] ?? ''), 0, 30) ?: null) : null,
            'notas' => mb_substr(trim($d['notas'] ?? ''), 0, 250) ?: null,
        ];
        if ($tipo === 'delivery' && !$head['direccion_entrega']) throw new RuntimeException('Ingrese la dirección de entrega del delivery.');

        if ($pid) {
            if ($old['tipo'] !== 'delivery' && $tipo === 'delivery') $head['delivery_estado'] = 'pendiente';
            if ($tipo !== 'delivery') $head['delivery_estado'] = null;
            DB::update('pedidos', $head, 'id = ?', [$pid]);
            if ($old['mesa_id'] && (int)$old['mesa_id'] !== $mesaId) DB::update('mesas', ['estado' => 'libre'], "id = ? AND estado = 'ocupada'", [$old['mesa_id']]);
        } else {
            $head['usuario_id'] = Auth::id();
            if ($tipo === 'delivery') $head['delivery_estado'] = 'pendiente';
            $pid = DB::insert('pedidos', $head);
        }

        // Sincronizar detalle
        $exist = [];
        foreach (DB::all('SELECT id, estado FROM pedido_detalle WHERE pedido_id = ?', [$pid]) as $r) $exist[$r['id']] = $r;
        $keep = [];
        foreach ($items as $it) {
            $did = (int)($it['detalle_id'] ?? 0);
            $qty = max(0.01, min(999, (float)($it['cantidad'] ?? 1)));
            $nota = mb_substr(trim($it['nota'] ?? ''), 0, 200) ?: null;
            if ($did && isset($exist[$did])) {
                $keep[$did] = true;
                if ($exist[$did]['estado'] === 'pendiente') {
                    $pr = DB::val('SELECT precio FROM pedido_detalle WHERE id = ?', [$did]);
                    DB::update('pedido_detalle', ['cantidad' => $qty, 'subtotal' => round($qty * $pr, 2), 'nota' => $nota], 'id = ?', [$did]);
                } else {
                    DB::update('pedido_detalle', ['nota' => $nota], 'id = ?', [$did]);
                }
                continue;
            }
            $p = $prods[(int)($it['producto_id'] ?? 0)] ?? null;
            if (!$p || !(int)$p['estado']) throw new RuntimeException('Un producto del pedido ya no está disponible.');
            if ((int)$p['stock_control'] && $qty > (float)$p['stock']) throw new RuntimeException('Stock insuficiente de «' . $p['nombre'] . '» (disponible: ' . (float)$p['stock'] . ').');
            DB::insert('pedido_detalle', [
                'pedido_id' => $pid, 'producto_id' => $p['id'], 'nombre' => $p['nombre'], 'cantidad' => $qty,
                'precio' => $p['precio'], 'subtotal' => round($qty * $p['precio'], 2), 'nota' => $nota,
                'estado' => (int)$p['para_cocina'] ? 'pendiente' : 'entregado',
            ]);
        }
        foreach ($exist as $did => $r) {
            if (!isset($keep[$did]) && $r['estado'] === 'pendiente') DB::run('DELETE FROM pedido_detalle WHERE id = ?', [$did]);
        }

        $sub = (float)DB::val('SELECT COALESCE(SUM(subtotal),0) FROM pedido_detalle WHERE pedido_id = ?', [$pid]);
        if ($sub <= 0) throw new RuntimeException('El pedido no puede quedar vacío.');
        $desc = max(0, min($sub, (float)($d['descuento'] ?? 0)));
        $t = Calc::totales($sub, $desc, (float)$head['costo_delivery']);
        DB::update('pedidos', ['subtotal' => round($sub, 2), 'descuento' => round($desc, 2), 'igv' => $t['igv'], 'total' => $t['total']], 'id = ?', [$pid]);
        if ($mesaId) DB::update('mesas', ['estado' => 'ocupada'], 'id = ?', [$mesaId]);
        DB::commit();
        Log::add('pedido', 'Pedido #' . $pid . ' guardado');
        return ['id' => $pid, 'total' => $t['total']];
    }
}
