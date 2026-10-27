<?php
/** Recetas: costo por plato y consumo de insumos */
class Receta
{
    public static function costo(int $productoId): float
    {
        return (float)DB::val('SELECT COALESCE(SUM(r.cantidad * i.costo),0) FROM recetas r JOIN insumos i ON i.id = r.insumo_id WHERE r.producto_id = ?', [$productoId]);
    }

    public static function recalc(int $productoId): void
    {
        if (DB::val('SELECT 1 FROM recetas WHERE producto_id = ? LIMIT 1', [$productoId])) {
            DB::update('productos', ['costo' => round(self::costo($productoId), 2)], 'id = ?', [$productoId]);
        }
    }

    public static function recalcPorInsumo(int $insumoId): void
    {
        foreach (DB::all('SELECT DISTINCT producto_id FROM recetas WHERE insumo_id = ?', [$insumoId]) as $r) self::recalc((int)$r['producto_id']);
    }

    public static function recalcTodos(): void
    {
        foreach (DB::all('SELECT DISTINCT producto_id FROM recetas') as $r) self::recalc((int)$r['producto_id']);
    }

    /** Descuenta del inventario los insumos de los platos vendidos (dentro de una transacción) */
    public static function consumir(int $pedidoId): void
    {
        $rows = DB::all('SELECT r.insumo_id, SUM(r.cantidad * d.cantidad) AS need FROM pedido_detalle d JOIN recetas r ON r.producto_id = d.producto_id WHERE d.pedido_id = ? GROUP BY r.insumo_id', [$pedidoId]);
        foreach ($rows as $r) {
            $stock = (float)DB::val('SELECT stock FROM insumos WHERE id = ? FOR UPDATE', [$r['insumo_id']]);
            $take = round(min($stock, (float)$r['need']), 3);
            if ($take <= 0) continue;
            DB::run('UPDATE insumos SET stock = stock - ? WHERE id = ?', [$take, $r['insumo_id']]);
            DB::insert('insumo_movimientos', ['insumo_id' => $r['insumo_id'], 'tipo' => 'salida', 'cantidad' => $take, 'motivo' => 'Venta pedido #' . $pedidoId, 'usuario_id' => Auth::id() ?: null, 'pedido_id' => $pedidoId]);
        }
    }

    /** Devuelve al inventario lo consumido por un pedido anulado */
    public static function revertir(int $pedidoId): void
    {
        if (DB::val("SELECT 1 FROM insumo_movimientos WHERE pedido_id = ? AND tipo = 'entrada' LIMIT 1", [$pedidoId])) return;
        foreach (DB::all("SELECT insumo_id, cantidad FROM insumo_movimientos WHERE pedido_id = ? AND tipo = 'salida'", [$pedidoId]) as $m) {
            DB::run('UPDATE insumos SET stock = stock + ? WHERE id = ?', [$m['cantidad'], $m['insumo_id']]);
            DB::insert('insumo_movimientos', ['insumo_id' => $m['insumo_id'], 'tipo' => 'entrada', 'cantidad' => $m['cantidad'], 'motivo' => 'Anulación pedido #' . $pedidoId, 'usuario_id' => Auth::id() ?: null, 'pedido_id' => $pedidoId]);
        }
    }
}
