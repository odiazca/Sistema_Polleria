<?php
class CajaController extends Controller
{
    protected string $module = 'caja';

    private function resumen(int $cajaId): array
    {
        $c = DB::one('SELECT * FROM cajas WHERE id = ?', [$cajaId]);
        $pm = array_column(DB::all("SELECT pg.metodo, SUM(pg.monto) t FROM pagos pg JOIN pedidos p ON p.id = pg.pedido_id WHERE p.caja_id = ? AND p.estado = 'pagado' GROUP BY pg.metodo", [$cajaId]), 't', 'metodo');
        $ing = (float)DB::val("SELECT COALESCE(SUM(monto),0) FROM caja_movimientos WHERE caja_id = ? AND tipo = 'ingreso'", [$cajaId]);
        $egr = (float)DB::val("SELECT COALESCE(SUM(monto),0) FROM caja_movimientos WHERE caja_id = ? AND tipo = 'egreso'", [$cajaId]);
        $efec = (float)($pm['efectivo'] ?? 0);
        $ventas = DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM pedidos WHERE caja_id = ? AND estado = 'pagado'", [$cajaId]);
        return [
            'caja' => $c, 'pm' => $pm, 'ing' => $ing, 'egr' => $egr, 'efectivo' => $efec,
            'esperado' => (float)$c['monto_inicial'] + $efec + $ing - $egr,
            'ventasN' => (int)$ventas['n'], 'ventasT' => (float)$ventas['t'],
            'movs' => DB::all('SELECT m.*, u.nombre AS usuario FROM caja_movimientos m LEFT JOIN usuarios u ON u.id = m.usuario_id WHERE m.caja_id = ? ORDER BY m.id DESC', [$cajaId]),
        ];
    }

    public function indexAction(): void
    {
        $uid = Auth::id();
        $verId = (int)($_GET['id'] ?? 0);
        $abierta = DB::one("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [$uid]);
        $cajaId = $verId ?: ($abierta['id'] ?? 0);
        $r = null;
        if ($cajaId) {
            $own = DB::val('SELECT usuario_id FROM cajas WHERE id = ?', [$cajaId]);
            if ($own === null || ((int)$own !== $uid && !Auth::isAdmin())) redirect('caja/index');
            $r = $this->resumen((int)$cajaId);
        }
        $hist = Auth::isAdmin()
            ? DB::all('SELECT c.*, u.nombre AS usuario FROM cajas c JOIN usuarios u ON u.id = c.usuario_id ORDER BY c.id DESC LIMIT 15')
            : DB::all('SELECT c.*, u.nombre AS usuario FROM cajas c JOIN usuarios u ON u.id = c.usuario_id WHERE c.usuario_id = ? ORDER BY c.id DESC LIMIT 15', [$uid]);
        $pendientes = (int)DB::val("SELECT COUNT(*) FROM pedidos WHERE estado = 'abierto'");
        $this->view('caja/index', ['title' => 'Caja', 'r' => $r, 'hist' => $hist, 'abierta' => $abierta, 'pendientes' => $pendientes]);
    }

    public function abrirAction(): void
    {
        if (DB::val("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta'", [Auth::id()])) { flash('warning', 'Ya tiene una caja abierta.'); redirect('caja/index'); }
        $monto = max(0, (float)str_replace(',', '.', $_POST['monto'] ?? '0'));
        $id = DB::insert('cajas', ['usuario_id' => Auth::id(), 'fecha_apertura' => date('Y-m-d H:i:s'), 'monto_inicial' => $monto, 'estado' => 'abierta']);
        Log::add('caja', 'Apertura de caja #' . $id . ' con ' . money($monto));
        flash('success', 'Caja abierta con ' . money($monto) . '. ¡Buen turno!');
        redirect('caja/index');
    }

    public function movimientoAction(): void
    {
        $caja = DB::one("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);
        $tipo = $_POST['tipo'] ?? '';
        $monto = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
        $concepto = trim($_POST['concepto'] ?? '');
        if (!$caja || !in_array($tipo, ['ingreso', 'egreso'], true) || $monto <= 0 || $concepto === '') {
            flash('danger', 'Complete tipo, concepto y un monto válido.');
        } else {
            DB::insert('caja_movimientos', ['caja_id' => $caja['id'], 'usuario_id' => Auth::id(), 'tipo' => $tipo, 'concepto' => mb_substr($concepto, 0, 150), 'monto' => $monto]);
            flash('success', ucfirst($tipo) . ' registrado.');
        }
        redirect('caja/index');
    }

    public function cerrarAction(): void
    {
        $caja = DB::one("SELECT id FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);
        if (!$caja) redirect('caja/index');
        $r = $this->resumen((int)$caja['id']);
        $contado = (float)str_replace(',', '.', $_POST['contado'] ?? '0');
        DB::update('cajas', [
            'fecha_cierre' => date('Y-m-d H:i:s'), 'monto_esperado' => $r['esperado'], 'monto_contado' => $contado,
            'diferencia' => round($contado - $r['esperado'], 2), 'observacion' => mb_substr(trim($_POST['observacion'] ?? ''), 0, 250) ?: null, 'estado' => 'cerrada',
        ], 'id = ?', [$caja['id']]);
        Log::add('caja', 'Cierre de caja #' . $caja['id']);
        flash('success', 'Caja cerrada correctamente.');
        redirect('caja/index', ['id' => $caja['id']]);
    }
}
