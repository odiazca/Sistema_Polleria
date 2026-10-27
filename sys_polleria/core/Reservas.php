<?php
/** Lógica del módulo de reservaciones: disponibilidad, validación y utilidades */
class Reservas
{
    const ESTADOS = [
        'pendiente'  => ['warn', 'Por confirmar', 'bi-hourglass-split'],
        'confirmada' => ['info', 'Confirmada', 'bi-patch-check-fill'],
        'sentada'    => ['ok', 'En mesa', 'bi-cup-hot-fill'],
        'completada' => ['gray', 'Completada', 'bi-check-circle-fill'],
        'cancelada'  => ['bad', 'Cancelada', 'bi-x-circle-fill'],
        'no_asistio' => ['bad', 'No asistió', 'bi-person-x-fill'],
    ];
    const ACTIVAS = ['pendiente', 'confirmada', 'sentada'];
    const OCASIONES = ['Cumpleaños', 'Aniversario', 'Reunión familiar', 'Reunión de trabajo', 'Celebración', 'Otro'];

    public static function cfg(): array
    {
        return [
            'duracion'  => max(30, (int)cfg('reserva_duracion', 90)),
            'apertura'  => self::hhmm(cfg('reserva_apertura', '12:00'), '12:00'),
            'cierre'    => self::hhmm(cfg('reserva_cierre', '22:30'), '22:30'),
            'intervalo' => in_array((int)cfg('reserva_intervalo', 30), [15, 30, 60], true) ? (int)cfg('reserva_intervalo', 30) : 30,
            'max'       => max(1, (int)cfg('reserva_max_personas', 20)),
            'dias'      => max(1, (int)cfg('reserva_dias', 30)),
            'horas_min' => max(0, (int)cfg('reserva_horas_min', 2)),
            'web'       => cfg('reserva_web', '1') === '1',
        ];
    }

    public static function hhmm($v, string $def): string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string)$v) ? (string)$v : $def;
    }

    /** Horarios disponibles dentro del horario de atención */
    public static function franjas(): array
    {
        $c = self::cfg();
        $out = [];
        $t = strtotime('2000-01-01 ' . $c['apertura']);
        $fin = strtotime('2000-01-01 ' . $c['cierre']);
        while ($t <= $fin) { $out[] = date('H:i', $t); $t += $c['intervalo'] * 60; }
        return $out;
    }

    public static function pendientes(): int
    {
        return (int)DB::val("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente' AND fecha >= CURDATE()");
    }

    /** Mesas aptas y su disponibilidad para una fecha/hora */
    public static function mesas(string $fecha, string $hora, int $personas, int $excluir = 0): array
    {
        $dur = self::cfg()['duracion'];
        $ini = strtotime("$fecha $hora");
        $fin = $ini + $dur * 60;
        $mesas = DB::all("SELECT id, nombre, zona, capacidad FROM mesas WHERE estado <> 'inactiva' ORDER BY zona, capacidad, id");
        $res = DB::all("SELECT id, mesa_id, hora, nombre FROM reservas WHERE fecha = ? AND estado IN ('pendiente','confirmada','sentada') AND id <> ?", [$fecha, $excluir]);
        foreach ($mesas as &$m) {
            $m['apta'] = (int)$m['capacidad'] >= $personas;
            $m['libre'] = true; $m['conflicto'] = null;
            foreach ($res as $r) {
                if ((int)$r['mesa_id'] !== (int)$m['id']) continue;
                $ri = strtotime("$fecha " . $r['hora']);
                if ($ri < $fin && $ri + $dur * 60 > $ini) { $m['libre'] = false; $m['conflicto'] = substr($r['hora'], 0, 5) . ' · ' . $r['nombre']; break; }
            }
        }
        return $mesas;
    }

    /** ¿Queda lugar para una reserva sin mesa asignada (web)? */
    public static function hayLugar(string $fecha, string $hora, int $personas): bool
    {
        $dur = self::cfg()['duracion'];
        $ini = strtotime("$fecha $hora"); $fin = $ini + $dur * 60;
        $aptas = array_filter(self::mesas($fecha, $hora, $personas), fn($m) => $m['apta']);
        if (!$aptas) return false;
        $libres = count(array_filter($aptas, fn($m) => $m['libre']));
        $sin = 0;
        foreach (DB::all("SELECT hora FROM reservas WHERE fecha = ? AND mesa_id IS NULL AND estado IN ('pendiente','confirmada','sentada')", [$fecha]) as $r) {
            $ri = strtotime("$fecha " . $r['hora']);
            if ($ri < $fin && $ri + $dur * 60 > $ini) $sin++;
        }
        return $libres - $sin > 0;
    }

    /** Valida y normaliza los datos de una reserva. Lanza RuntimeException con el mensaje para el usuario. */
    public static function limpiar(array $d, bool $publico = false): array
    {
        $c = self::cfg();
        $nombre = trim(preg_replace('/\s+/', ' ', (string)($d['nombre'] ?? '')));
        if (mb_strlen($nombre) < 2) throw new RuntimeException('Ingrese el nombre del cliente.');
        $tel = preg_replace('/[^0-9+ ()-]/', '', (string)($d['telefono'] ?? ''));
        if ($publico && strlen(preg_replace('/\D/', '', $tel)) < 7) throw new RuntimeException('Ingrese un teléfono válido para confirmar su reserva.');
        $email = trim((string)($d['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('El correo electrónico no es válido.');
        $fecha = (string)($d['fecha'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int)substr($fecha, 5, 2), (int)substr($fecha, 8, 2), (int)substr($fecha, 0, 4))) throw new RuntimeException('Elija una fecha válida.');
        $hora = substr((string)($d['hora'] ?? ''), 0, 5);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) throw new RuntimeException('Elija una hora válida.');
        $p = (int)($d['personas'] ?? 0);
        if ($p < 1 || $p > $c['max']) throw new RuntimeException('El número de personas debe estar entre 1 y ' . $c['max'] . '.');
        if ($publico) {
            if (!in_array($hora, self::franjas(), true)) throw new RuntimeException('Esa hora está fuera del horario de atención.');
            $t = strtotime("$fecha $hora");
            if ($t < time() + $c['horas_min'] * 3600) throw new RuntimeException('Debe reservar con al menos ' . $c['horas_min'] . ' hora(s) de anticipación.');
            if ($t > strtotime('+' . $c['dias'] . ' days 23:59')) throw new RuntimeException('Solo se puede reservar hasta con ' . $c['dias'] . ' días de anticipación.');
        }
        $oc = trim((string)($d['ocasion'] ?? ''));
        return [
            'nombre' => mb_substr($nombre, 0, 120), 'telefono' => mb_substr(trim($tel), 0, 30) ?: null, 'email' => $email ?: null,
            'fecha' => $fecha, 'hora' => $hora . ':00', 'personas' => $p,
            'ocasion' => $oc !== '' ? mb_substr($oc, 0, 40) : null,
            'notas' => ($n = trim((string)($d['notas'] ?? ''))) !== '' ? mb_substr($n, 0, 300) : null,
        ];
    }

    /** Busca un cliente por teléfono para vincular la reserva */
    public static function clientePorTelefono(?string $tel): ?int
    {
        $n = preg_replace('/\D/', '', (string)$tel);
        if (strlen($n) < 7) return null;
        $id = DB::val("SELECT id FROM clientes WHERE estado = 1 AND REPLACE(REPLACE(REPLACE(telefono,' ',''),'-',''),'+','') LIKE ? LIMIT 1", ['%' . substr($n, -9)]);
        return $id ? (int)$id : null;
    }

    public static function dia(string $fecha): array
    {
        $rows = DB::all("SELECT r.*, m.nombre AS mesa, m.zona FROM reservas r LEFT JOIN mesas m ON m.id = r.mesa_id WHERE r.fecha = ? ORDER BY r.hora, r.id", [$fecha]);
        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id']; $r['personas'] = (int)$r['personas']; $r['mesa_id'] = $r['mesa_id'] ? (int)$r['mesa_id'] : null;
            $r['hora'] = substr($r['hora'], 0, 5);
        }
        return $rows;
    }

    /** Próxima reserva de hoy por mesa (para el plano de mesas) */
    public static function proximasPorMesa(): array
    {
        $out = [];
        $rows = DB::all("SELECT mesa_id, hora, nombre, personas FROM reservas WHERE fecha = CURDATE() AND mesa_id IS NOT NULL AND estado IN ('pendiente','confirmada') AND hora >= ? ORDER BY hora", [date('H:i:s', time() - 20 * 60)]);
        foreach ($rows as $r) if (!isset($out[$r['mesa_id']])) $out[$r['mesa_id']] = ['hora' => substr($r['hora'], 0, 5), 'nombre' => $r['nombre'], 'personas' => (int)$r['personas']];
        return $out;
    }
}
