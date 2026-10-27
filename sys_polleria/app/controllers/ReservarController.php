<?php
/** Página pública para que el cliente reserve su mesa en línea */
class ReservarController extends Controller
{
    protected bool $public = true;

    private function activo(): bool { return Reservas::cfg()['web']; }

    public function indexAction(): void
    {
        $this->view('reservas/publico', ['title' => 'Reservar mesa', 'rc' => Reservas::cfg(), 'activo' => $this->activo(), 'franjas' => Reservas::franjas()], '');
    }

    /** Horas con lugar para una fecha y número de personas */
    public function horasAction(): void
    {
        if (!$this->activo()) $this->json(['ok' => false, 'msg' => 'Las reservas en línea no están disponibles.']);
        $f = (string)($_GET['fecha'] ?? ''); $p = (int)($_GET['personas'] ?? 2);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) || !checkdate((int)substr($f, 5, 2), (int)substr($f, 8, 2), (int)substr($f, 0, 4))) $this->json(['ok' => false, 'msg' => 'Fecha no válida.']);
        $c = Reservas::cfg();
        $p = max(1, min($c['max'], $p));
        $min = time() + $c['horas_min'] * 3600;
        $out = [];
        foreach (Reservas::franjas() as $h) {
            $t = strtotime("$f $h");
            $out[] = ['h' => $h, 'ok' => $t >= $min && $t <= strtotime('+' . $c['dias'] . ' days 23:59') && Reservas::hayLugar($f, $h, $p)];
        }
        $this->json(['ok' => true, 'horas' => $out]);
    }

    public function crearAction(): void
    {
        if (!$this->isPost() || !$this->activo()) $this->json(['ok' => false, 'msg' => 'Las reservas en línea no están disponibles.'], 403);
        $d = $this->body();
        if (!empty($d['web'])) $this->json(['ok' => true, 'codigo' => 'R00000']); // trampa para robots
        $_SESSION['rv_hits'] = array_values(array_filter($_SESSION['rv_hits'] ?? [], fn($t) => $t > time() - 3600));
        if (count($_SESSION['rv_hits']) >= 4) $this->json(['ok' => false, 'msg' => 'Demasiados intentos. Inténtelo de nuevo más tarde o llámenos.']);
        try {
            $r = Reservas::limpiar($d, true);
            if (!Reservas::hayLugar($r['fecha'], substr($r['hora'], 0, 5), $r['personas'])) throw new RuntimeException('Lo sentimos, ya no hay mesas disponibles en ese horario. Elija otra hora.');
            $tel = preg_replace('/\D/', '', (string)$r['telefono']);
            if ((int)DB::val("SELECT COUNT(*) FROM reservas WHERE fecha >= CURDATE() AND estado IN ('pendiente','confirmada') AND REPLACE(REPLACE(REPLACE(telefono,' ',''),'-',''),'+','') LIKE ?", ['%' . substr($tel, -9)]) >= 3) {
                throw new RuntimeException('Ya tiene reservas activas con este teléfono. Para más cambios, comuníquese con el local.');
            }
            $r['estado'] = 'pendiente'; $r['origen'] = 'web'; $r['cliente_id'] = Reservas::clientePorTelefono($r['telefono']);
            $id = DB::insert('reservas', $r);
            $codigo = 'R' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
            DB::update('reservas', ['codigo' => $codigo], 'id = ?', [$id]);
            $_SESSION['rv_hits'][] = time();
            Log::add('reservas', 'Reserva web ' . $codigo . ' de ' . $r['nombre']);
            $this->json(['ok' => true, 'codigo' => $codigo]);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
