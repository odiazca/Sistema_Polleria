<?php
/** Asistente de reportes con IA (solo administrador) */
class AsistenteController extends Controller
{
    protected string $module = 'asistente';

    public function __construct()
    {
        parent::__construct();
        try { Ia::instalar(); } catch (Throwable $e) {
            if ($this->isPost() || ($_GET['r'] ?? '') !== 'asistente/index') $this->json(['ok' => false, 'msg' => 'No se pudieron preparar las vistas del asistente: ' . $e->getMessage()], 500);
        }
    }

    private function out(array $d = []): void { $this->json($d + ['ok' => true]); }

    private function consulta(int $id): array
    {
        $c = DB::one('SELECT * FROM ia_consultas WHERE id = ? AND usuario_id = ?', [$id, Auth::id()]);
        if (!$c) $this->json(['ok' => false, 'msg' => 'Consulta no encontrada.'], 404);
        return $c;
    }

    public function indexAction(): void
    {
        $this->view('asistente/index', ['title' => 'Asistente de reportes', 'useCharts' => true, 'scripts' => ['js/asistente.js']]);
    }

    public function estadoAction(): void
    {
        $c = Ia::cfg(); $uid = Auth::id();
        $this->out([
            'cfg' => ['proveedor' => $c['proveedor'], 'url' => $c['url'], 'modelo' => $c['modelo'], 'clave_puesta' => $c['clave'] !== '', 'resumen' => $c['resumen']],
            'listo' => Ia::listo(), 'presets' => Ia::PRESETS,
            'favoritos' => DB::all('SELECT id, pregunta, titulo FROM ia_consultas WHERE usuario_id = ? AND favorito = 1 ORDER BY ultimo_uso DESC', [$uid]),
            'historial' => DB::all('SELECT id, pregunta, titulo, favorito FROM ia_consultas WHERE usuario_id = ? AND favorito = 0 ORDER BY ultimo_uso DESC LIMIT 15', [$uid]),
        ]);
    }

    public function guardarConfigAction(): void
    {
        $b = $this->body(); $p = (string)($b['proveedor'] ?? '');
        if (!isset(Ia::PRESETS[$p])) $this->json(['ok' => false, 'msg' => 'Proveedor no válido.']);
        $url = trim((string)($b['url'] ?? '')); $modelo = trim((string)($b['modelo'] ?? ''));
        if (!preg_match('#^https?://[^\s]+$#i', $url)) $this->json(['ok' => false, 'msg' => 'La URL debe empezar por http:// o https://']);
        if ($modelo === '' || mb_strlen($modelo) > 100) $this->json(['ok' => false, 'msg' => 'Indique el nombre del modelo.']);
        $k = trim((string)($b['clave'] ?? ''));
        if ($k !== '' && $k !== '__borrar__' && (mb_strlen($k) < 8 || mb_strlen($k) > 200 || preg_match('/\s/', $k))) $this->json(['ok' => false, 'msg' => 'La clave no parece válida.']);
        Setting::set('ia_proveedor', $p); Setting::set('ia_url', $url); Setting::set('ia_modelo', $modelo);
        Setting::set('ia_resumen', !empty($b['resumen']) ? '1' : '0');
        if ($k === '__borrar__') Setting::set('ia_clave', ''); elseif ($k !== '') Setting::set('ia_clave', Ia::cifrar($k));
        Log::add('config', 'Asistente IA: ' . $p . ' / ' . $modelo);
        $this->out();
    }

    public function probarAction(): void
    {
        try { $t = Ia::llamar([['role' => 'user', 'content' => 'Responde solo con la palabra OK.']], 20); }
        catch (Throwable $e) { $this->json(['ok' => false, 'msg' => $e->getMessage()]); }
        $this->out(['respuesta' => mb_substr(trim($t), 0, 60)]);
    }

    public function preguntarAction(): void
    {
        $b = $this->body(); $pregunta = trim((string)($b['pregunta'] ?? ''));
        if (mb_strlen($pregunta) < 4) $this->json(['ok' => false, 'msg' => 'Escriba su pregunta (mínimo 4 caracteres).']);
        if (mb_strlen($pregunta) > 500) $this->json(['ok' => false, 'msg' => 'La pregunta es demasiado larga (máx. 500 caracteres).']);
        $now = time(); $_SESSION['ia_hist'] = array_values(array_filter($_SESSION['ia_hist'] ?? [], fn($t) => $t > $now - 600));
        if (count($_SESSION['ia_hist']) >= 30) $this->json(['ok' => false, 'msg' => 'Demasiadas consultas seguidas. Espere unos minutos.'], 429);
        $_SESSION['ia_hist'][] = $now;
        $previo = is_array($b['previo'] ?? null) ? $b['previo'] : null;
        try { $r = Ia::preguntar($pregunta, $previo); } catch (Throwable $e) { $this->json(['ok' => false, 'msg' => $e->getMessage()]); }
        if (isset($r['mensaje'])) $this->out(['mensaje' => $r['mensaje']]);
        $uid = Auth::id();
        $id = DB::insert('ia_consultas', ['usuario_id' => $uid, 'pregunta' => $pregunta, 'titulo' => $r['titulo'], 'sql_texto' => $r['sql'], 'grafico' => json_encode($r['grafico'])]);
        DB::q('DELETE FROM ia_consultas WHERE usuario_id = ? AND favorito = 0 AND id NOT IN (SELECT id FROM (SELECT id FROM ia_consultas WHERE usuario_id = ? AND favorito = 0 ORDER BY id DESC LIMIT 60) x)', [$uid, $uid]);
        Log::add('ia', 'Consulta: ' . mb_substr($pregunta, 0, 200));
        $this->out(['id' => (int)$id] + $r);
    }

    public function ejecutarAction(): void
    {
        $c = $this->consulta((int)($this->body()['id'] ?? 0));
        try { $r = Ia::ejecutar($c['sql_texto']); } catch (Throwable $e) { $this->json(['ok' => false, 'msg' => $e->getMessage()]); }
        DB::q('UPDATE ia_consultas SET veces = veces + 1, ultimo_uso = NOW() WHERE id = ?', [$c['id']]);
        $this->out(['id' => (int)$c['id'], 'pregunta' => $c['pregunta'], 'titulo' => $c['titulo'] ?: $c['pregunta'], 'grafico' => json_decode((string)$c['grafico'], true) ?: ['tipo' => 'none'], 'favorito' => (int)$c['favorito']] + $r);
    }

    public function resumirAction(): void
    {
        if (!Ia::cfg()['resumen']) $this->json(['ok' => false, 'msg' => 'El resumen está desactivado.']);
        $c = $this->consulta((int)($this->body()['id'] ?? 0));
        try {
            $r = Ia::ejecutar($c['sql_texto']);
            if (!$r['filas']) $this->out(['resumen' => 'La consulta no devolvió resultados para ese período.']);
            $t = Ia::resumir($c['pregunta'], $r['columnas'], $r['filas']);
        } catch (Throwable $e) { $this->json(['ok' => false, 'msg' => $e->getMessage()]); }
        $this->out(['resumen' => $t]);
    }

    public function favoritoAction(): void
    {
        $b = $this->body(); $c = $this->consulta((int)($b['id'] ?? 0)); $v = !empty($b['valor']) ? 1 : 0;
        DB::update('ia_consultas', ['favorito' => $v], 'id = ?', [$c['id']]);
        $this->out(['favorito' => $v]);
    }

    public function borrarAction(): void
    {
        $c = $this->consulta((int)($this->body()['id'] ?? 0));
        DB::q('DELETE FROM ia_consultas WHERE id = ?', [$c['id']]);
        $this->out();
    }

    public function csvAction(): void
    {
        $c = $this->consulta((int)($_GET['id'] ?? 0));
        try { $r = Ia::ejecutar($c['sql_texto']); } catch (Throwable $e) { http_response_code(400); exit($e->getMessage()); }
        $name = 'consulta_' . preg_replace('/[^a-z0-9]+/i', '_', (string)(@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)($c['titulo'] ?: 'reporte')) ?: 'reporte')) . '_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $name . '"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $safe = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;
        fputcsv($o, $r['columnas'], ';'); foreach ($r['filas'] as $f) fputcsv($o, array_map($safe, $f), ';');
        Log::add('ia', 'Exportó CSV de consulta #' . $c['id']); exit;
    }

    public function esquemaAction(): void
    {
        header('Content-Type: text/plain; charset=utf-8'); echo Ia::esquemaTexto(); exit;
    }
}
