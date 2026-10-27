<?php
/** Generador de datos de demostración (ventas de los últimos 30 días) */
class Demo
{
    private static function pick(array $weighted)
    {
        $sum = array_sum($weighted);
        $r = mt_rand(1, (int)($sum * 100)) / 100;
        foreach ($weighted as $k => $w) { if (($r -= $w) <= 0) return $k; }
        return array_key_first($weighted);
    }

    public static function run(int $days = 30): array
    {
        $pdo = DB::pdo();
        $stats = ['pedidos' => 0];
        mt_srand(2026);

        // Usuarios demo
        $rolCajero = (int)DB::val("SELECT id FROM roles WHERE nombre='Cajero'") ?: 1;
        $rolMozo = (int)DB::val("SELECT id FROM roles WHERE nombre='Mozo'") ?: 1;
        $rolCocina = (int)DB::val("SELECT id FROM roles WHERE nombre='Cocina'") ?: 1;
        $demoUsers = [
            ['Rosa Gutiérrez', 'cajero', 'cajero123', $rolCajero],
            ['Miguel Torres', 'mozo', 'mozo123', $rolMozo],
            ['Chef Andrés Paredes', 'cocina', 'cocina123', $rolCocina],
        ];
        foreach ($demoUsers as [$n, $u, $p, $rol]) {
            if (!DB::val('SELECT id FROM usuarios WHERE usuario=?', [$u])) {
                DB::insert('usuarios', ['rol_id' => $rol, 'nombre' => $n, 'usuario' => $u, 'clave' => password_hash($p, PASSWORD_DEFAULT), 'estado' => 1]);
            }
        }
        $cajeroId = (int)DB::val("SELECT id FROM usuarios WHERE usuario='cajero'");
        $adminId = (int)DB::val('SELECT id FROM usuarios ORDER BY id LIMIT 1');

        // Clientes
        $clientes = [
            ['DNI', '45871236', 'María Fernández Ríos', '987123456', 'Av. Los Olivos 245'],
            ['DNI', '40213587', 'José Luis Ramírez', '986555123', 'Jr. Lima 1020'],
            ['DNI', '47852139', 'Ana Lucía Paredes', '985321654', 'Calle Las Flores 88'],
            ['RUC', '20556677881', 'Constructora Andina S.A.C.', '014567890', 'Av. Industrial 450'],
            ['DNI', '43217896', 'Carlos Huamán', '984112233', 'Psj. San Martín 15'],
            ['DNI', '46789123', 'Lucía Mendoza', '983998877', 'Urb. Santa Rosa Mz C Lt 4'],
            ['RUC', '20601234567', 'Distribuidora El Sol E.I.R.L.', '017778899', 'Av. Brasil 3200'],
            ['DNI', '41236985', 'Pedro Castillo Vega', '982441100', 'Calle Real 777'],
        ];
        $cliIds = [];
        foreach ($clientes as [$t, $d, $n, $tel, $dir]) {
            $cliIds[] = DB::insert('clientes', ['tipo_doc' => $t, 'documento' => $d, 'nombre' => $n, 'telefono' => $tel, 'direccion' => $dir]);
        }

        // Proveedores
        $provIds = [
            DB::insert('proveedores', ['ruc' => '20505050501', 'razon_social' => 'Avícola San Fernando del Norte', 'contacto' => 'Jorge Salas', 'telefono' => '991223344']),
            DB::insert('proveedores', ['ruc' => '20606060602', 'razon_social' => 'Distribuidora de Abarrotes Lima', 'contacto' => 'Rocío Díaz', 'telefono' => '992334455']),
            DB::insert('proveedores', ['ruc' => '20707070703', 'razon_social' => 'Carbón y Leña El Roble', 'contacto' => 'Víctor Ríos', 'telefono' => '993445566']),
        ];

        $prods = DB::all('SELECT id, nombre, precio, categoria_id FROM productos WHERE estado=1');
        if (!$prods) return $stats;
        $weights = [];
        foreach ($prods as $i => $p) {
            $w = match ((int)$p['categoria_id']) { 1 => 9, 2 => 5, 3 => 2, 4 => 3, 5 => 4, 6 => 6, default => 1 };
            if (stripos($p['nombre'], '1/4') !== false) $w = 14;
            if (stripos($p['nombre'], '1/2') !== false) $w = 11;
            $weights[$i] = $w;
        }
        $mesas = array_column(DB::all('SELECT id FROM mesas'), 'id');
        $reps = array_column(DB::all('SELECT id FROM repartidores'), 'id');
        $hourW = [11 => 2, 12 => 7, 13 => 12, 14 => 11, 15 => 6, 16 => 3, 17 => 4, 18 => 8, 19 => 12, 20 => 12, 21 => 8, 22 => 3];

        $insPed = $pdo->prepare('INSERT INTO pedidos (tipo,mesa_id,cliente_id,usuario_id,caja_id,repartidor_id,estado,delivery_estado,direccion_entrega,telefono_entrega,comprobante,serie,numero,subtotal,descuento,costo_delivery,igv,total,fecha_pedido,fecha_pago) VALUES (?,?,?,?,?,?,"pagado",?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $insDet = $pdo->prepare('INSERT INTO pedido_detalle (pedido_id,producto_id,nombre,cantidad,precio,subtotal,estado) VALUES (?,?,?,?,?,?,"entregado")');
        $insPago = $pdo->prepare('INSERT INTO pagos (pedido_id,metodo,monto) VALUES (?,?,?)');

        $series = ['ticket' => cfg('serie_ticket', 'T001'), 'boleta' => cfg('serie_boleta', 'B001'), 'factura' => cfg('serie_factura', 'F001')];
        $correl = ['ticket' => 0, 'boleta' => 0, 'factura' => 0];
        $deliveryFee = (float)cfg('costo_delivery', 5);

        $pdo->beginTransaction();
        for ($d = $days - 1; $d >= 0; $d--) {
            $ts = strtotime("-$d days");
            $date = date('Y-m-d', $ts);
            $dow = (int)date('N', $ts);
            $isToday = ($d === 0);
            $uid = ($d % 2 === 0 && $cajeroId) ? $cajeroId : $adminId;

            $cajaId = DB::insert('cajas', [
                'usuario_id' => $uid, 'fecha_apertura' => "$date 10:30:00", 'monto_inicial' => 150, 'estado' => 'abierta',
            ]);

            $n = mt_rand(18, 32);
            if ($dow >= 5) $n = (int)($n * 1.5);
            if ($dow === 7) $n = (int)($n * 1.2);
            $efectivo = 0.0;
            $orders = [];
            for ($k = 0; $k < $n; $k++) {
                $h = self::pick($hourW);
                if ($isToday && $h > (int)date('G')) continue;
                $orders[] = sprintf('%s %02d:%02d:%02d', $date, $h, mt_rand(0, 59), mt_rand(0, 59));
            }
            sort($orders);

            foreach ($orders as $fecha) {
                $tipo = self::pick(['mesa' => 45, 'llevar' => 30, 'delivery' => 25]);
                $items = [];
                $cnt = mt_rand(1, 4);
                $sub = 0.0;
                for ($i = 0; $i < $cnt; $i++) {
                    $p = $prods[self::pick($weights)];
                    $q = mt_rand(1, 10) > 8 ? 2 : 1;
                    $items[] = [$p, $q];
                    $sub += $q * (float)$p['precio'];
                }
                $deliv = $tipo === 'delivery' ? $deliveryFee : 0.0;
                $desc = mt_rand(1, 20) === 1 ? round($sub * 0.1, 2) : 0.0;
                $t = Calc::totales($sub, $desc, $deliv);
                $comp = self::pick(['ticket' => 50, 'boleta' => 40, 'factura' => 10]);
                $correl[$comp]++;
                $cli = ($comp === 'factura' || mt_rand(1, 3) === 1 || $tipo === 'delivery') ? $cliIds[array_rand($cliIds)] : null;
                $cliRow = $cli ? $clientes[array_search($cli, $cliIds)] : null;
                $metodo = self::pick(['efectivo' => 45, 'yape' => 20, 'tarjeta' => 20, 'plin' => 10, 'transferencia' => 5]);
                $rep = $tipo === 'delivery' && $reps ? $reps[array_rand($reps)] : null;
                $insPed->execute([
                    $tipo, $tipo === 'mesa' ? $mesas[array_rand($mesas)] : null, $cli, $uid, $cajaId, $rep,
                    $tipo === 'delivery' ? 'entregado' : null, $tipo === 'delivery' && $cliRow ? $cliRow[4] : null, $tipo === 'delivery' && $cliRow ? $cliRow[3] : null,
                    $comp, $series[$comp], $correl[$comp], round($sub, 2), $desc, $deliv, $t['igv'], $t['total'], $fecha, $fecha,
                ]);
                $pid = (int)$pdo->lastInsertId();
                foreach ($items as [$p, $q]) {
                    $insDet->execute([$pid, $p['id'], $p['nombre'], $q, $p['precio'], $q * (float)$p['precio']]);
                }
                $insPago->execute([$pid, $metodo, $t['total']]);
                if ($metodo === 'efectivo') $efectivo += $t['total'];
                $stats['pedidos']++;
            }

            if (!$isToday) {
                $esperado = 150 + $efectivo;
                $dif = [0, 0, 0, -2, 1, 0, -5][mt_rand(0, 6)];
                DB::update('cajas', [
                    'fecha_cierre' => "$date 23:15:00", 'monto_esperado' => $esperado, 'monto_contado' => $esperado + $dif,
                    'diferencia' => $dif, 'estado' => 'cerrada',
                ], 'id = ?', [$cajaId]);
            }
            // Gastos ocasionales
            if (mt_rand(1, 3) === 1) {
                $g = [['Servicios', 'Pago de luz y agua', 220], ['Insumos', 'Compra de carbón', 90], ['Personal', 'Adelanto de sueldo', 150], ['Mantenimiento', 'Reparación de horno', 180], ['Limpieza', 'Productos de limpieza', 65], ['Marketing', 'Volantes y publicidad', 80]][mt_rand(0, 5)];
                DB::insert('gastos', ['usuario_id' => $adminId, 'fecha' => $date, 'categoria' => $g[0], 'descripcion' => $g[1], 'monto' => $g[2] + mt_rand(0, 20), 'metodo' => 'efectivo']);
            }
            // Compras semanales
            if ($dow === 1 || $dow === 4) {
                $cid = DB::insert('compras', ['proveedor_id' => $provIds[0], 'usuario_id' => $adminId, 'fecha' => $date, 'comprobante' => 'F001-' . mt_rand(1000, 9999), 'total' => 0]);
                $tot = 0;
                foreach ([[1, 60, 8.5], [2, 80, 2.2], [3, 20, 7.0]] as [$ins, $qty, $cost]) {
                    DB::insert('compra_detalle', ['compra_id' => $cid, 'insumo_id' => $ins, 'cantidad' => $qty, 'costo' => $cost, 'subtotal' => $qty * $cost]);
                    $tot += $qty * $cost;
                }
                DB::update('compras', ['total' => $tot], 'id = ?', [$cid]);
            }
        }
        $pdo->commit();
        return $stats;
    }
}
