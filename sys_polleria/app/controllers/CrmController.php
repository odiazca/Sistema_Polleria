<?php
/** CRM avanzado: panel, clientes 360, ficha, notas/seguimiento y puntos */
class CrmController extends Controller
{
    protected string $module = 'crm';

    public function indexAction(): void
    {
        $cl = Crm::clientes();
        $seg = array_fill_keys(array_keys(Crm::SEG), 0);
        $val = array_fill_keys(array_keys(Crm::SEG), 0.0);
        foreach ($cl as $c) { $seg[$c['seg']]++; $val[$c['seg']] += $c['total']; }
        $comp = array_filter($cl, fn($c) => $c['n'] > 0);
        $activos = count(array_filter($comp, fn($c) => $c['dias'] <= 30));
        $nuevosMes = (int)DB::val("SELECT COUNT(*) FROM clientes WHERE estado = 1 AND created_at >= ?", [date('Y-m-01')]);
        $ltv = $comp ? array_sum(array_column($comp, 'total')) / count($comp) : 0;
        $top = $cl; usort($top, fn($a, $b) => $b['total'] <=> $a['total']); $top = array_slice(array_filter($top, fn($c) => $c['total'] > 0), 0, 6);
        $riesgo = array_filter($cl, fn($c) => $c['seg'] === 'riesgo' && $c['telefono']);
        usort($riesgo, fn($a, $b) => $b['total'] <=> $a['total']);
        $meses = [];
        foreach (DB::all("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) n FROM clientes WHERE estado = 1 AND created_at >= ? GROUP BY m", [date('Y-m-01', strtotime('-5 months'))]) as $r) $meses[$r['m']] = (int)$r['n'];
        $serie = [];
        for ($i = 5; $i >= 0; $i--) { $k = date('Y-m', strtotime("-$i months", strtotime(date('Y-m-01')))); $serie[] = ['m' => $k, 'n' => $meses[$k] ?? 0]; }
        $this->view('crm/index', ['scripts' => ['js/crm.js'],
            'title' => 'Panel CRM', 'useCharts' => true, 'tab' => 'panel',
            'seg' => $seg, 'val' => $val, 'total' => count($cl), 'activos' => $activos, 'nuevosMes' => $nuevosMes, 'ltv' => $ltv,
            'top' => $top, 'riesgo' => array_slice($riesgo, 0, 5), 'serie' => $serie, 'cumple' => array_slice(Crm::cumpleanos(30), 0, 6), 'segs' => Crm::seguimientos(),
            'conTel' => count(array_filter($cl, fn($c) => $c['telefono'])), 'puntosTot' => array_sum(array_column($cl, 'puntos')),
        ]);
    }

    public function clientesAction(): void
    {
        $cl = Crm::clientes();
        $seg = array_fill_keys(array_keys(Crm::SEG), 0);
        foreach ($cl as $c) $seg[$c['seg']]++;
        $this->view('crm/clientes', ['scripts' => ['js/crm.js'],'title' => 'Clientes 360', 'tab' => 'clientes', 'rows' => array_slice($cl, 0, 1500), 'seg' => $seg, 'total' => count($cl), 'filtro' => (string)($_GET['seg'] ?? '')]);
    }

    public function fichaAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $c = Crm::cliente($id);
        if (!$c) { flash('danger', 'El cliente no existe.'); redirect('crm/clientes'); }
        $pedidos = DB::all("SELECT id, tipo, total, fecha_pago, comprobante, serie, numero FROM pedidos WHERE cliente_id = ? AND estado = 'pagado' ORDER BY fecha_pago DESC LIMIT 25", [$id]);
        $fav = DB::all("SELECT d.nombre, SUM(d.cantidad) cant, COUNT(DISTINCT d.pedido_id) veces FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id WHERE p.cliente_id = ? AND p.estado = 'pagado' GROUP BY d.producto_id, d.nombre ORDER BY cant DESC LIMIT 6", [$id]);
        $tipos = DB::all("SELECT tipo, COUNT(*) n FROM pedidos WHERE cliente_id = ? AND estado = 'pagado' GROUP BY tipo ORDER BY n DESC", [$id]);
        $meses = [];
        foreach (DB::all("SELECT DATE_FORMAT(fecha_pago,'%Y-%m') m, SUM(total) t FROM pedidos WHERE cliente_id = ? AND estado = 'pagado' AND fecha_pago >= ? GROUP BY m", [$id, date('Y-m-01', strtotime('-11 months'))]) as $r) $meses[$r['m']] = (float)$r['t'];
        $serie = [];
        for ($i = 11; $i >= 0; $i--) { $k = date('Y-m', strtotime("-$i months", strtotime(date('Y-m-01')))); $serie[] = ['m' => $k, 't' => $meses[$k] ?? 0]; }
        $this->view('crm/ficha', ['scripts' => ['js/crm.js'],
            'title' => 'Ficha del cliente', 'tab' => 'clientes', 'useCharts' => true, 'c' => $c, 'pedidos' => $pedidos, 'fav' => $fav, 'tipos' => $tipos, 'serie' => $serie,
            'notas' => DB::all('SELECT n.*, u.nombre AS autor FROM crm_notas n LEFT JOIN usuarios u ON u.id = n.usuario_id WHERE n.cliente_id = ? ORDER BY n.created_at DESC LIMIT 60', [$id]),
            'movs' => DB::all('SELECT * FROM crm_puntos WHERE cliente_id = ? ORDER BY id DESC LIMIT 30', [$id]),
            'reservas' => DB::all("SELECT codigo, fecha, hora, personas, estado FROM reservas WHERE cliente_id = ? OR (telefono IS NOT NULL AND telefono <> '' AND telefono = ?) ORDER BY fecha DESC, hora DESC LIMIT 10", [$id, (string)$c['telefono']]),
            'premios' => DB::all('SELECT * FROM crm_recompensas WHERE estado = 1 ORDER BY puntos'), 'r' => Crm::reglas(),
            'plantillas' => $this->plantillas(),
        ]);
    }

    private function plantillas(): array
    {
        return [
            'Agradecimiento' => '¡Hola {nombre}! Gracias por visitarnos en {empresa} 🍗. Esperamos verte pronto. Tienes {puntos} puntos acumulados.',
            'Te extrañamos' => 'Hola {nombre}, te extrañamos en {empresa} 🍗. Vuelve esta semana y te regalamos una gaseosa con tu pedido.',
            'Cumpleaños' => '¡Feliz cumpleaños {nombre}! 🎂 En {empresa} queremos celebrarlo contigo. Muéstranos este mensaje y recibe un postre de regalo.',
        ];
    }

    /** Crear o editar los datos CRM de un cliente */
    public function guardarAction(): void
    {
        $d = $this->body(); $id = (int)($d['id'] ?? 0);
        $nombre = trim((string)($d['nombre'] ?? ''));
        if (mb_strlen($nombre) < 2) $this->json(['ok' => false, 'msg' => 'Ingrese el nombre del cliente.']);
        $email = trim((string)($d['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->json(['ok' => false, 'msg' => 'El correo no es válido.']);
        $fn = (string)($d['fecha_nacimiento'] ?? '');
        if ($fn !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fn) || $fn > date('Y-m-d') || $fn < '1900-01-01')) $this->json(['ok' => false, 'msg' => 'La fecha de nacimiento no es válida.']);
        $tags = implode(', ', array_slice(array_filter(array_map(fn($t) => mb_substr(trim($t), 0, 24), explode(',', (string)($d['etiquetas'] ?? '')))), 0, 8));
        $row = [
            'nombre' => mb_substr($nombre, 0, 150), 'telefono' => mb_substr(trim(preg_replace('/[^0-9+ ()-]/', '', (string)($d['telefono'] ?? ''))), 0, 30) ?: null,
            'email' => $email ?: null, 'fecha_nacimiento' => $fn ?: null, 'etiquetas' => $tags ?: null, 'acepta_marketing' => empty($d['acepta_marketing']) ? 0 : 1,
        ];
        if ($id) {
            if (!DB::one('SELECT id FROM clientes WHERE id = ?', [$id])) $this->json(['ok' => false, 'msg' => 'El cliente no existe.']);
            DB::update('clientes', $row, 'id = ?', [$id]);
        } else {
            $row['tipo_doc'] = 'DNI'; $row['documento'] = trim((string)($d['documento'] ?? '')) ?: null;
            $id = DB::insert('clientes', $row);
        }
        Log::add('crm', 'Cliente ' . $id . ' guardado desde CRM');
        $this->json(['ok' => true, 'msg' => 'Cliente guardado', 'id' => $id]);
    }

    public function notaAction(): void
    {
        $d = $this->body(); $cid = (int)($d['cliente_id'] ?? 0);
        $texto = trim((string)($d['texto'] ?? ''));
        if (!DB::one('SELECT id FROM clientes WHERE id = ?', [$cid])) $this->json(['ok' => false, 'msg' => 'El cliente no existe.']);
        if (mb_strlen($texto) < 3) $this->json(['ok' => false, 'msg' => 'Escriba el detalle de la nota.']);
        $tipo = isset(Crm::TIPOS_NOTA[$d['tipo'] ?? '']) ? $d['tipo'] : 'nota';
        $seg = (string)($d['seguimiento'] ?? '');
        if ($seg !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $seg)) $this->json(['ok' => false, 'msg' => 'Fecha de seguimiento no válida.']);
        DB::insert('crm_notas', ['cliente_id' => $cid, 'tipo' => $tipo, 'texto' => mb_substr($texto, 0, 500), 'seguimiento' => $seg ?: null, 'usuario_id' => Auth::id() ?: null]);
        $this->json(['ok' => true, 'msg' => 'Nota registrada']);
    }

    public function notaHechoAction(): void
    {
        $d = $this->body();
        DB::update('crm_notas', ['hecho' => empty($d['hecho']) ? 0 : 1], 'id = ?', [(int)($d['id'] ?? 0)]);
        $this->json(['ok' => true, 'msg' => 'Seguimiento actualizado']);
    }

    public function notaBorrarAction(): void
    {
        DB::run('DELETE FROM crm_notas WHERE id = ?', [(int)($this->body()['id'] ?? 0)]);
        $this->json(['ok' => true, 'msg' => 'Nota eliminada']);
    }

    /** Canjear una recompensa o ajustar puntos manualmente */
    public function puntosAction(): void
    {
        $d = $this->body(); $cid = (int)($d['cliente_id'] ?? 0);
        $c = Crm::cliente($cid);
        if (!$c) $this->json(['ok' => false, 'msg' => 'El cliente no existe.']);
        if (($d['tipo'] ?? '') === 'canje') {
            $p = DB::one('SELECT * FROM crm_recompensas WHERE id = ? AND estado = 1', [(int)($d['recompensa_id'] ?? 0)]);
            if (!$p) $this->json(['ok' => false, 'msg' => 'Elija una recompensa.']);
            if ($c['puntos'] < (int)$p['puntos']) $this->json(['ok' => false, 'msg' => 'Puntos insuficientes (' . $c['puntos'] . ' disponibles, se requieren ' . (int)$p['puntos'] . ').']);
            DB::insert('crm_puntos', ['cliente_id' => $cid, 'puntos' => -(int)$p['puntos'], 'motivo' => 'Canje: ' . $p['nombre'], 'usuario_id' => Auth::id() ?: null]);
            Log::add('crm', 'Canje ' . $p['nombre'] . ' a cliente ' . $cid);
            $this->json(['ok' => true, 'msg' => 'Canje registrado: ' . $p['nombre'] . '. Aplique el beneficio en la venta.']);
        }
        $pts = (int)($d['puntos'] ?? 0); $mot = trim((string)($d['motivo'] ?? ''));
        if ($pts === 0 || abs($pts) > 100000) $this->json(['ok' => false, 'msg' => 'Ingrese una cantidad de puntos distinta de cero.']);
        if (mb_strlen($mot) < 3) $this->json(['ok' => false, 'msg' => 'Indique el motivo del ajuste.']);
        if ($pts < 0 && $c['puntos'] + $pts < 0) $this->json(['ok' => false, 'msg' => 'El cliente solo tiene ' . $c['puntos'] . ' puntos.']);
        DB::insert('crm_puntos', ['cliente_id' => $cid, 'puntos' => $pts, 'motivo' => 'Ajuste: ' . mb_substr($mot, 0, 140), 'usuario_id' => Auth::id() ?: null]);
        Log::add('crm', 'Ajuste de ' . $pts . ' puntos a cliente ' . $cid);
        $this->json(['ok' => true, 'msg' => 'Puntos actualizados']);
    }
}
