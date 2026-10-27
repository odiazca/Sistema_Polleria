<?php
/**
 * Controlador CRUD genérico dirigido por configuración.
 * Cada módulo define config() con tabla, columnas y campos.
 */
abstract class CrudController extends Controller
{
    abstract protected function config(): array;

    protected function cfgc(): array
    {
        $c = $this->config();
        $c += [
            'select' => null, 'order' => 'id DESC', 'icon' => 'bi-list', 'plural' => $c['title'] ?? '',
            'route' => $this->module, 'list_action' => 'index', 'columns' => [], 'fields' => [],
            'extra_actions' => [], 'can_delete' => true, 'subtitle' => '', 'header_actions' => '',
        ];
        return $c;
    }

    protected function listRows(array $c): array
    {
        $sql = $c['select'] ?: 'SELECT * FROM `' . $c['table'] . '`';
        return DB::all($sql . ' ORDER BY ' . $c['order']);
    }

    protected function renderList(): void
    {
        $c = $this->cfgc();
        $this->view('crud/index', [
            'title' => $c['title'], 'c' => $c, 'rows' => $this->listRows($c),
        ]);
    }

    public function indexAction(): void { $this->renderList(); }

    /** Hook: modificar datos antes de guardar. Lanzar RuntimeException para abortar. */
    protected function beforeSave(array $data, ?int $id): array { return $data; }
    protected function afterSave(int $id, bool $isNew): void {}
    protected function beforeDelete(int $id): void {}

    public function saveAction(): void
    {
        $c = $this->cfgc();
        $id = (int)($_POST['id'] ?? 0) ?: null;
        try {
            $data = [];
            foreach ($c['fields'] as $f) {
                $n = $f['name'];
                $type = $f['type'] ?? 'text';
                if ($type === 'image') {
                    $img = save_image($_FILES[$n] ?? [], $c['table']);
                    if ($img) {
                        if ($id) delete_upload(DB::val("SELECT `$n` FROM `{$c['table']}` WHERE id=?", [$id]));
                        $data[$n] = $img;
                    }
                    continue;
                }
                if ($type === 'switch') { $data[$n] = isset($_POST[$n]) ? 1 : 0; continue; }
                if ($type === 'password') {
                    $v = (string)($_POST[$n] ?? '');
                    if ($v !== '') $data[$n] = password_hash($v, PASSWORD_DEFAULT);
                    elseif (!$id && !empty($f['required'])) throw new RuntimeException('La contraseña es obligatoria.');
                    continue;
                }
                $v = $_POST[$n] ?? '';
                $v = is_string($v) ? trim($v) : $v;
                if (!empty($f['required']) && $v === '') throw new RuntimeException('El campo «' . $f['label'] . '» es obligatorio.');
                if (in_array($type, ['number', 'money'], true)) $v = $v === '' ? (isset($f['nullable']) ? null : 0) : (float)str_replace(',', '.', $v);
                elseif (in_array($type, ['select', 'date'], true) && $v === '') $v = null;
                elseif ($type === 'select' && !empty($f['int'])) $v = (int)$v;
                $data[$n] = $v;
            }
            $data = $this->beforeSave($data, $id);
            if ($id) {
                if ($data) DB::update($c['table'], $data, 'id = ?', [$id]);
                Log::add('editar', $c['singular'] . ' #' . $id);
                flash('success', ucfirst($c['singular']) . ' actualizado correctamente.');
            } else {
                $id = DB::insert($c['table'], $data);
                Log::add('crear', $c['singular'] . ' #' . $id);
                flash('success', ucfirst($c['singular']) . ' registrado correctamente.');
            }
            $this->afterSave($id, !isset($_POST['id']) || !$_POST['id']);
        } catch (PDOException $e) {
            flash('danger', $e->getCode() === '23000' ? 'Ya existe un registro con esos datos (valor duplicado).' : 'Error de base de datos: ' . (APP_DEBUG ? $e->getMessage() : ''));
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        redirect($c['route'] . '/' . $c['list_action']);
    }

    public function deleteAction(): void
    {
        $c = $this->cfgc();
        $id = (int)($_POST['id'] ?? 0);
        try {
            if (!$c['can_delete']) throw new RuntimeException('Este registro no se puede eliminar.');
            $this->beforeDelete($id);
            foreach ($c['fields'] as $f) {
                if (($f['type'] ?? '') === 'image') delete_upload(DB::val("SELECT `{$f['name']}` FROM `{$c['table']}` WHERE id=?", [$id]));
            }
            DB::run("DELETE FROM `{$c['table']}` WHERE id = ?", [$id]);
            Log::add('eliminar', $c['singular'] . ' #' . $id);
            flash('success', ucfirst($c['singular']) . ' eliminado.');
        } catch (PDOException $e) {
            flash('warning', 'No se puede eliminar porque tiene registros relacionados. Puede desactivarlo en su lugar.');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        redirect($c['route'] . '/' . $c['list_action']);
    }
}
