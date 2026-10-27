<?php
/** Campañas de WhatsApp por segmento + reglas del programa de fidelidad */
class CampanasController extends Controller
{
    protected string $module = 'campanas';

    private const EXTRA = ['todos' => 'Todos los clientes', 'cumple_mes' => 'Cumpleañeros del mes'];

    private function destinatarios(string $seg): array
    {
        $cl = Crm::clientes("AND c.acepta_marketing = 1 AND c.telefono IS NOT NULL AND c.telefono <> ''");
        $cl = array_filter($cl, fn($c) => strlen(preg_replace('/\D/', '', (string)$c['telefono'])) >= 9);
        if ($seg === 'cumple_mes') return array_values(array_filter($cl, fn($c) => $c['fecha_nacimiento'] && date('m', strtotime($c['fecha_nacimiento'])) === date('m')));
        if ($seg !== 'todos') return array_values(array_filter($cl, fn($c) => $c['seg'] === $seg));
        return array_values($cl);
    }

    public function indexAction(): void
    {
        $camp = DB::all("SELECT c.*, (SELECT COUNT(*) FROM crm_envios e WHERE e.campana_id = c.id) total, (SELECT COUNT(*) FROM crm_envios e WHERE e.campana_id = c.id AND e.enviado = 1) enviados FROM crm_campanas c ORDER BY c.id DESC LIMIT 50");
        $cnt = [];
        foreach (array_merge(array_keys(self::EXTRA), array_keys(Crm::SEG)) as $k) $cnt[$k] = count($this->destinatarios($k));
        $this->view('campanas/index', ['scripts' => ['js/crm.js'],'title' => 'Campañas y fidelidad', 'tab' => 'campanas', 'camp' => $camp, 'cnt' => $cnt, 'extra' => self::EXTRA,
            'r' => Crm::reglas(), 'premios' => DB::all('SELECT * FROM crm_recompensas ORDER BY puntos'), 'seg' => (string)($_GET['seg'] ?? '')]);
    }

    public function crearAction(): void
    {
        $d = $this->body();
        $nom = trim((string)($d['nombre'] ?? '')); $msg = trim((string)($d['mensaje'] ?? '')); $seg = (string)($d['segmento'] ?? 'todos');
        if (mb_strlen($nom) < 3) $this->json(['ok' => false, 'msg' => 'Escriba un nombre para la campaña.']);
        if (mb_strlen($msg) < 10) $this->json(['ok' => false, 'msg' => 'El mensaje es muy corto.']);
        if (!isset(self::EXTRA[$seg]) && !isset(Crm::SEG[$seg])) $this->json(['ok' => false, 'msg' => 'Segmento no válido.']);
        $dest = $this->destinatarios($seg);
        if (!$dest) $this->json(['ok' => false, 'msg' => 'No hay clientes con teléfono y permiso de contacto en ese segmento.']);
        DB::begin();
        $id = DB::insert('crm_campanas', ['nombre' => mb_substr($nom, 0, 120), 'segmento' => $seg, 'mensaje' => mb_substr($msg, 0, 700), 'usuario_id' => Auth::id() ?: null]);
        foreach ($dest as $c) DB::insert('crm_envios', ['campana_id' => $id, 'cliente_id' => (int)$c['id']]);
        DB::commit();
        Log::add('crm', 'Campaña «' . $nom . '» creada para ' . count($dest) . ' clientes');
        $this->json(['ok' => true, 'id' => $id, 'msg' => 'Campaña creada con ' . count($dest) . ' destinatarios']);
    }

    public function verAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $c = DB::one('SELECT * FROM crm_campanas WHERE id = ?', [$id]);
        if (!$c) { flash('danger', 'La campaña no existe.'); redirect('campanas/index'); }
        $ids = array_column(DB::all('SELECT cliente_id FROM crm_envios WHERE campana_id = ?', [$id]), 'cliente_id');
        $cl = [];
        foreach (Crm::clientes() as $x) $cl[$x['id']] = $x;
        $env = DB::all('SELECT * FROM crm_envios WHERE campana_id = ? ORDER BY enviado, id', [$id]);
        foreach ($env as &$e) {
            $x = $cl[$e['cliente_id']] ?? null;
            $e['c'] = $x; $e['link'] = $x ? Crm::wa($x['telefono'], Crm::plantilla($c['mensaje'], $x)) : '';
        }
        $this->view('campanas/ver', ['scripts' => ['js/crm.js'],'title' => 'Campaña', 'tab' => 'campanas', 'c' => $c, 'env' => $env]);
    }

    public function enviadoAction(): void
    {
        $d = $this->body();
        DB::update('crm_envios', ['enviado' => empty($d['enviado']) ? 0 : 1, 'enviado_en' => empty($d['enviado']) ? null : date('Y-m-d H:i:s')], 'id = ?', [(int)($d['id'] ?? 0)]);
        $this->json(['ok' => true]);
    }

    public function eliminarAction(): void
    {
        DB::run('DELETE FROM crm_campanas WHERE id = ?', [(int)($this->body()['id'] ?? 0)]);
        $this->json(['ok' => true, 'msg' => 'Campaña eliminada']);
    }

    public function reglasAction(): void
    {
        if (!Auth::isAdmin()) $this->json(['ok' => false, 'msg' => 'Solo el administrador puede cambiar las reglas.'], 403);
        $d = $this->body();
        $plata = max(1, (int)($d['plata'] ?? 200)); $oro = max(1, (int)($d['oro'] ?? 500));
        if ($oro <= $plata) $this->json(['ok' => false, 'msg' => 'El nivel Oro debe requerir más puntos que Plata.']);
        $riesgo = max(7, (int)($d['riesgo'] ?? 45)); $perdido = max(14, (int)($d['perdido'] ?? 120));
        if ($perdido <= $riesgo) $this->json(['ok' => false, 'msg' => 'Los días para «Perdido» deben ser mayores que para «En riesgo».']);
        Setting::setMany([
            'crm_soles_por_punto' => (string)max(1, (float)($d['soles'] ?? 10)), 'crm_nivel_plata' => (string)$plata, 'crm_nivel_oro' => (string)$oro,
            'crm_vip_gasto' => (string)max(1, (float)($d['vip'] ?? 1000)), 'crm_frecuente_visitas' => (string)max(2, (int)($d['frecuente'] ?? 4)),
            'crm_riesgo_dias' => (string)$riesgo, 'crm_perdido_dias' => (string)$perdido,
        ]);
        $this->json(['ok' => true, 'msg' => 'Reglas guardadas']);
    }

    public function premioAction(): void
    {
        $d = $this->body(); $id = (int)($d['id'] ?? 0);
        if (!empty($d['borrar'])) { DB::run('DELETE FROM crm_recompensas WHERE id = ?', [$id]); $this->json(['ok' => true, 'msg' => 'Recompensa eliminada']); }
        $n = trim((string)($d['nombre'] ?? '')); $p = (int)($d['puntos'] ?? 0);
        if (mb_strlen($n) < 3 || $p < 1) $this->json(['ok' => false, 'msg' => 'Indique el nombre y los puntos requeridos.']);
        $row = ['nombre' => mb_substr($n, 0, 100), 'puntos' => $p, 'estado' => empty($d['inactivo']) ? 1 : 0];
        if ($id) DB::update('crm_recompensas', $row, 'id = ?', [$id]); else DB::insert('crm_recompensas', $row);
        $this->json(['ok' => true, 'msg' => 'Recompensa guardada']);
    }
}
