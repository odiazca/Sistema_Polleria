<?php
/** Controlador base */
abstract class Controller
{
    /** Clave de módulo requerida (config/modules.php). Vacío = solo sesión iniciada */
    protected string $module = '';
    protected bool $public = false;

    public function __construct()
    {
        if (!$this->public) {
            if (!Auth::check()) {
                if (is_ajax()) $this->json(['ok' => false, 'msg' => 'Sesión expirada'], 401);
                redirect('auth/login');
            }
            if ($this->module !== '' && !Auth::can($this->module)) {
                if (is_ajax()) $this->json(['ok' => false, 'msg' => 'Sin permiso'], 403);
                http_response_code(403);
                $this->view('errors/403', ['title' => 'Acceso denegado']);
                exit;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $t = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!hash_equals(csrf_token(), (string)$t)) {
                if (is_ajax()) $this->json(['ok' => false, 'msg' => 'Token de seguridad inválido. Recargue la página.'], 419);
                http_response_code(419);
                exit('Token de seguridad inválido. Vuelva atrás y recargue la página.');
            }
        }
    }

    protected function view(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $data += ['title' => APP_NAME, 'module' => $this->module, 'useCharts' => false];
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/views/' . $view . '.php';
        $content = ob_get_clean();
        if ($layout === '') { echo $content; return; }
        require BASE_PATH . '/app/views/' . $layout . '.php';
    }

    protected function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/views/' . $view . '.php';
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    protected function body(): array
    {
        $raw = file_get_contents('php://input');
        $d = json_decode($raw ?: '[]', true);
        return is_array($d) ? $d : [];
    }

    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
}
