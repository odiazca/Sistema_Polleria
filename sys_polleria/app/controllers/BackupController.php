<?php
class BackupController extends Controller
{
    protected string $module = 'backup';

    public function __construct()
    {
        parent::__construct();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }

    private function verifyPassword(): void
    {
        $hash = DB::val('SELECT clave FROM usuarios WHERE id = ?', [Auth::id()]);
        if (!password_verify((string)($_POST['password'] ?? ''), (string)$hash)) {
            throw new RuntimeException('La contraseña de administrador no es correcta.');
        }
    }

    public function indexAction(): void
    {
        $files = Backup::listFiles();
        $dbInfo = DB::one('SELECT ROUND(SUM(data_length + index_length)) AS bytes, COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema = ?', [DB_NAME]);
        $contenido = [];
        $labels = [
            'pedidos' => ['Pedidos y ventas', 'bi-receipt'], 'productos' => ['Productos', 'bi-basket3'], 'clientes' => ['Clientes', 'bi-people'],
            'usuarios' => ['Usuarios', 'bi-person-gear'], 'insumos' => ['Insumos', 'bi-box-seam'], 'compras' => ['Compras', 'bi-cart'],
            'gastos' => ['Gastos', 'bi-wallet2'], 'mesas' => ['Mesas', 'bi-grid-3x3-gap'], 'cajas' => ['Cajas', 'bi-cash-coin'],
        ];
        foreach ($labels as $t => [$l, $i]) $contenido[] = ['label' => $l, 'icon' => $i, 'n' => (int)DB::val("SELECT COUNT(*) FROM `$t`")];

        $this->view('backup/index', [
            'title' => 'Copia de seguridad', 'files' => $files,
            'total' => array_sum(array_column($files, 'size')),
            'dbBytes' => (int)($dbInfo['bytes'] ?? 0), 'dbTablas' => (int)($dbInfo['tablas'] ?? 0),
            'contenido' => $contenido, 'zipOk' => class_exists('ZipArchive'),
        ]);
    }

    public function crearAction(): void
    {
        try {
            $name = Backup::create(!empty($_POST['con_imagenes']), 'manual');
            Log::add('backup', 'Copia creada: ' . $name);
            flash('success', 'Copia de seguridad creada: ' . $name);
        } catch (Throwable $e) {
            flash('danger', 'No se pudo crear la copia: ' . $e->getMessage());
        }
        redirect('backup/index');
    }

    public function descargarAction(): void
    {
        $p = Backup::path($_GET['f'] ?? '');
        if (!$p) { http_response_code(404); exit('Archivo no encontrado'); }
        Log::add('backup', 'Descarga: ' . basename($p));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($p) . '"');
        header('Content-Length: ' . filesize($p));
        readfile($p);
        exit;
    }

    public function eliminarAction(): void
    {
        $p = Backup::path($_POST['f'] ?? '');
        if ($p && @unlink($p)) { Log::add('backup', 'Eliminada: ' . basename($p)); flash('success', 'Copia eliminada.'); }
        else flash('danger', 'No se pudo eliminar el archivo.');
        redirect('backup/index');
    }

    public function restaurarAction(): void
    {
        try {
            $this->verifyPassword();
            if (strtoupper(trim($_POST['confirmacion'] ?? '')) !== 'RESTAURAR') throw new RuntimeException('Escriba RESTAURAR para confirmar.');

            $path = null; $tmpUp = null;
            if (!empty($_POST['f'])) {
                $path = Backup::path($_POST['f']);
                if (!$path) throw new RuntimeException('El archivo seleccionado no existe.');
            } elseif (!empty($_FILES['archivo']['tmp_name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['sql', 'zip'], true)) throw new RuntimeException('Solo se permiten archivos .sql o .zip.');
                $tmpUp = Backup::dir() . '/subido_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $tmpUp)) throw new RuntimeException('No se pudo recibir el archivo.');
                $path = $tmpUp;
                if (!Backup::looksValid($path)) { @unlink($tmpUp); throw new RuntimeException('El archivo no parece ser un respaldo válido.'); }
            } else {
                throw new RuntimeException('Seleccione una copia existente o suba un archivo.');
            }

            $seguridad = Backup::create(false, 'pre-restauracion');   // copia de seguridad previa
            Backup::restore($path);
            if ($tmpUp) @unlink($tmpUp);
            // La sesión puede ya no ser válida con los datos restaurados
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Restauración completada. Se guardó una copia previa (' . $seguridad . '). Inicie sesión nuevamente.');
            redirect('auth/login');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        } catch (Throwable $e) {
            flash('danger', 'Error al restaurar: ' . $e->getMessage());
        }
        redirect('backup/index');
    }

    public function resetearAction(): void
    {
        try {
            $this->verifyPassword();
            if (strtoupper(trim($_POST['confirmacion'] ?? '')) !== 'RESETEAR') throw new RuntimeException('Escriba RESETEAR para confirmar.');
            $seguridad = Backup::create(true, 'pre-reseteo');
            Backup::reset([
                'catalogo' => in_array($_POST['catalogo'] ?? '', ['mantener', 'base', 'vacio'], true) ? $_POST['catalogo'] : 'base',
                'usuarios' => !empty($_POST['mantener_usuarios']),
                'mantener_config' => !empty($_POST['mantener_config']),
            ]);
            Log::add('reset', 'Sistema reseteado para nueva empresa');
            flash('success', 'Sistema reseteado. Se guardó una copia previa (' . $seguridad . '). Configure ahora los datos de la nueva empresa.');
            redirect('empresa/index');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        } catch (Throwable $e) {
            flash('danger', 'Error al resetear: ' . $e->getMessage());
        }
        redirect('backup/index');
    }
}
