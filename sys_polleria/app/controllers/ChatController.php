<?php
/** Chat con IA para consultar el sistema (solo administrador). El historial vive en la sesión. */
class ChatController extends Controller
{
    protected string $module = 'chat';

    private function out(array $d = []): void { $this->json($d + ['ok' => true]); }

    public function indexAction(): void
    {
        $this->view('chat/index', ['title' => 'Chat IA', 'scripts' => []]);
    }

    public function estadoAction(): void
    {
        $c = Ia::cfg();
        $this->out(['listo' => Ia::listo(), 'proveedor' => Ia::PRESETS[$c['proveedor']]['nombre'], 'empresa' => cfg('empresa_nombre', 'su pollería'),
            'historial' => array_map(fn($h) => ['q' => $h['q'], 'a' => $h['a']], $_SESSION['chat'] ?? [])]);
    }

    public function enviarAction(): void
    {
        $msg = trim((string)($this->body()['mensaje'] ?? ''));
        if (mb_strlen($msg) < 2) $this->json(['ok' => false, 'msg' => 'Escriba su mensaje.']);
        if (mb_strlen($msg) > 400) $this->json(['ok' => false, 'msg' => 'El mensaje es demasiado largo (máx. 400 caracteres).']);
        $now = time(); $_SESSION['chat_t'] = array_values(array_filter($_SESSION['chat_t'] ?? [], fn($t) => $t > $now - 600));
        if (count($_SESSION['chat_t']) >= 40) $this->json(['ok' => false, 'msg' => 'Demasiados mensajes seguidos. Espere unos minutos.'], 429);
        $_SESSION['chat_t'][] = $now;
        $hist = $_SESSION['chat'] ?? [];
        try { Ia::instalar(); $r = Ia::chat($msg, $hist); } catch (Throwable $e) { $this->json(['ok' => false, 'msg' => $e->getMessage()]); }
        $hist[] = ['q' => $msg, 'sql' => $r['sql'] ?? null, 'a' => mb_substr($r['texto'], 0, 800)];
        $_SESSION['chat'] = array_slice($hist, -12);
        if (!empty($r['sql'])) Log::add('ia', 'Chat: ' . mb_substr($msg, 0, 200));
        $this->out(['texto' => $r['texto'], 'tabla' => $r['tabla'] ?? null, 'sql' => $r['sql'] ?? null]);
    }

    public function limpiarAction(): void
    {
        $_SESSION['chat'] = [];
        $this->out();
    }
}
