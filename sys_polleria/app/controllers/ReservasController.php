<?php
/** Módulo de reservaciones (gestión interna) */
class ReservasController extends Controller
{
    protected string $module = 'reservas';

    private function fecha(): string
    {
        $f = (string)($_GET['fecha'] ?? date('Y-m-d'));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && checkdate((int)substr($f, 5, 2), (int)substr($f, 8, 2), (int)substr($f, 0, 4)) ? $f : date('Y-m-d');
    }

    public function indexAction(): void
    {
        $fecha = $this->fecha();
        $this->view('reservas/index', [
            'title' => 'Reservaciones', 'scripts' => ['js/reservas.js'],
            'fecha' => $fecha, 'rc' => Reservas::cfg(), 'franjas' => Reservas::franjas(),
            'mesas' => DB::all("SELECT id, nombre, zona, capacidad FROM mesas WHERE estado <> 'inactiva' ORDER BY zona, id"),
            'semana' => $this->semana($fecha), 'pendTotal' => Reservas::pendientes(),
            'publica' => $this->urlPublica(),
        ]);
    }

    private function urlPublica(): string
    {
        $base = trim((string)cfg('qr_base_url', ''));
        $base = $base !== '' ? rtrim($base, '/') : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(BASE_URL, '/'));
        return $base . '/index.php?r=reservar/index';
    }

    /** 7 días alrededor de la fecha con su número de reservas */
    private function semana(string $fecha): array
    {
        $ini = date('Y-m-d', strtotime($fecha . ' -3 days'));
        $fin = date('Y-m-d', strtotime($fecha . ' +3 days'));
        $cnt = [];
        foreach (DB::all("SELECT fecha, COUNT(*) n, SUM(estado = 'pendiente') p FROM reservas WHERE fecha BETWEEN ? AND ? AND estado NOT IN ('cancelada','no_asistio') GROUP BY fecha", [$ini, $fin]) as $r) $cnt[$r['fecha']] = [(int)$r['n'], (int)$r['p']];
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $out = [];
        for ($i = -3; $i <= 3; $i++) {
            $d = date('Y-m-d', strtotime("$fecha $i days"));
            $out[] = ['f' => $d, 'dia' => $dias[(int)date('w', strtotime($d))], 'n' => (int)date('j', strtotime($d)), 'c' => $cnt[$d][0] ?? 0, 'p' => $cnt[$d][1] ?? 0, 'hoy' => $d === date('Y-m-d'), 'sel' => $d === $fecha];
        }
        return $out;
    }

    public function diaAction(): void
    {
        $f = $this->fecha();
        $this->json(['ok' => true, 'fecha' => $f, 'rows' => Reservas::dia($f), 'semana' => $this->semana($f), 'pend' => Reservas::pendientes()]);
    }

    public function disponibilidadAction(): void
    {
        $f = $this->fecha();
        $h = substr((string)($_GET['hora'] ?? '19:00'), 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $h)) $h = '19:00';
        $this->json(['ok' => true, 'mesas' => Reservas::mesas($f, $h, max(1, (int)($_GET['personas'] ?? 1)), (int)($_GET['excluir'] ?? 0))]);
    }

    public function guardarAction(): void
    {
        $d = $this->body();
        try {
            $r = Reservas::limpiar($d);
            $id = (int)($d['id'] ?? 0);
            $mesa = (int)($d['mesa_id'] ?? 0) ?: null;
            if ($mesa) {
                $m = DB::one("SELECT id, nombre, capacidad FROM mesas WHERE id = ? AND estado <> 'inactiva'", [$mesa]);
                if (!$m) throw new RuntimeException('La mesa elegida no existe.');
                foreach (Reservas::mesas($r['fecha'], substr($r['hora'], 0, 5), $r['personas'], $id) as $x) {
                    if ((int)$x['id'] === $mesa && !$x['libre']) throw new RuntimeException($m['nombre'] . ' ya está reservada en ese horario (' . $x['conflicto'] . ').');
                }
            }
            $r['mesa_id'] = $mesa;
            $r['cliente_id'] = Reservas::clientePorTelefono($r['telefono']);
            $origen = in_array($d['origen'] ?? '', ['local', 'telefono', 'whatsapp', 'web'], true) ? $d['origen'] : 'local';
            if ($id) {
                $old = DB::one('SELECT id FROM reservas WHERE id = ?', [$id]);
                if (!$old) throw new RuntimeException('La reserva no existe.');
                $est = $d['estado'] ?? null;
                if ($est && isset(Reservas::ESTADOS[$est])) $r['estado'] = $est;
                $r['origen'] = $origen; $r['updated_at'] = date('Y-m-d H:i:s');
                DB::update('reservas', $r, 'id = ?', [$id]);
                Log::add('reservas', 'Reserva ' . $id . ' modificada');
            } else {
                $r['estado'] = ($d['estado'] ?? 'confirmada') === 'pendiente' ? 'pendiente' : 'confirmada';
                $r['origen'] = $origen; $r['usuario_id'] = Auth::id();
                $id = DB::insert('reservas', $r);
                DB::update('reservas', ['codigo' => 'R' . str_pad((string)$id, 5, '0', STR_PAD_LEFT)], 'id = ?', [$id]);
                Log::add('reservas', 'Reserva R' . $id . ' creada para ' . $r['nombre'] . ' (' . $r['fecha'] . ' ' . $r['hora'] . ')');
            }
            $this->json(['ok' => true, 'msg' => 'Reserva guardada', 'id' => $id]);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function estadoAction(): void
    {
        $d = $this->body();
        $id = (int)($d['id'] ?? 0); $est = (string)($d['estado'] ?? '');
        $r = DB::one('SELECT * FROM reservas WHERE id = ?', [$id]);
        if (!$r || !isset(Reservas::ESTADOS[$est])) $this->json(['ok' => false, 'msg' => 'Reserva no válida.']);
        DB::update('reservas', ['estado' => $est, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Log::add('reservas', 'Reserva ' . ($r['codigo'] ?: $id) . ' → ' . Reservas::ESTADOS[$est][1]);
        $go = null;
        if ($est === 'sentada' && $r['mesa_id'] && Auth::can('pos')) $go = url('pos/index', ['mesa' => $r['mesa_id']]);
        $this->json(['ok' => true, 'msg' => 'Reserva: ' . Reservas::ESTADOS[$est][1], 'go' => $go]);
    }

    public function eliminarAction(): void
    {
        $id = (int)($this->body()['id'] ?? 0);
        DB::run('DELETE FROM reservas WHERE id = ?', [$id]);
        Log::add('reservas', 'Reserva ' . $id . ' eliminada');
        $this->json(['ok' => true, 'msg' => 'Reserva eliminada']);
    }

    public function configAction(): void
    {
        if (!Auth::isAdmin()) $this->json(['ok' => false, 'msg' => 'Solo el administrador puede cambiar la configuración.'], 403);
        $d = $this->body();
        $ap = Reservas::hhmm($d['apertura'] ?? '', ''); $ci = Reservas::hhmm($d['cierre'] ?? '', '');
        if (!$ap || !$ci || $ap >= $ci) $this->json(['ok' => false, 'msg' => 'Revise el horario de atención (apertura menor que cierre).']);
        Setting::setMany([
            'reserva_apertura' => $ap, 'reserva_cierre' => $ci,
            'reserva_duracion' => (string)max(30, min(360, (int)($d['duracion'] ?? 90))),
            'reserva_intervalo' => in_array((int)($d['intervalo'] ?? 30), [15, 30, 60], true) ? (string)(int)$d['intervalo'] : '30',
            'reserva_max_personas' => (string)max(1, min(200, (int)($d['max'] ?? 20))),
            'reserva_dias' => (string)max(1, min(365, (int)($d['dias'] ?? 30))),
            'reserva_horas_min' => (string)max(0, min(72, (int)($d['horas_min'] ?? 2))),
            'reserva_web' => !empty($d['web']) ? '1' : '0',
        ]);
        $this->json(['ok' => true, 'msg' => 'Configuración guardada']);
    }
}
