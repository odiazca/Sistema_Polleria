<?php
class AuthController extends Controller
{
    protected bool $public = true;

    public function loginAction(): void
    {
        if (Auth::check()) redirect(Auth::home());
        $error = '';
        $usuario = '';
        if ($this->isPost()) {
            $usuario = trim($_POST['usuario'] ?? '');
            [$ok, $error] = Auth::attempt($usuario, (string)($_POST['clave'] ?? ''));
            if ($ok) redirect(Auth::home());
        }
        $this->view('auth/login', ['title' => 'Iniciar sesión', 'error' => $error, 'usuario' => $usuario], '');
    }

    public function logoutAction(): void
    {
        if (Auth::check()) Auth::logout();
        session_start();
        redirect('auth/login');
    }

    /** Mi cuenta: cambiar nombre y contraseña */
    public function cuentaAction(): void
    {
        if (!Auth::check()) redirect('auth/login');
        $uid = Auth::id();
        if ($this->isPost()) {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $nueva = (string)($_POST['nueva'] ?? '');
            try {
                if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
                $data = ['nombre' => $nombre, 'email' => $email ?: null];
                if ($nueva !== '') {
                    $actual = DB::val('SELECT clave FROM usuarios WHERE id=?', [$uid]);
                    if (!password_verify((string)($_POST['actual'] ?? ''), $actual)) throw new RuntimeException('La contraseña actual no es correcta.');
                    if (strlen($nueva) < 6) throw new RuntimeException('La nueva contraseña debe tener al menos 6 caracteres.');
                    if ($nueva !== ($_POST['repetir'] ?? '')) throw new RuntimeException('Las contraseñas nuevas no coinciden.');
                    $data['clave'] = password_hash($nueva, PASSWORD_DEFAULT);
                }
                $img = save_image($_FILES['avatar'] ?? [], 'avatars');
                if ($img) $data['avatar'] = $img;
                DB::update('usuarios', $data, 'id = ?', [$uid]);
                $_SESSION['user']['nombre'] = $nombre;
                if ($img) $_SESSION['user']['avatar'] = $img;
                flash('success', 'Cuenta actualizada.');
            } catch (RuntimeException $e) {
                flash('danger', $e->getMessage());
            }
            redirect('auth/cuenta');
        }
        $u = DB::one('SELECT * FROM usuarios WHERE id=?', [$uid]);
        $this->view('auth/cuenta', ['title' => 'Mi cuenta', 'u' => $u]);
    }
}
