<?php
class UsuariosController extends CrudController
{
    protected string $module = 'usuarios';

    protected function config(): array
    {
        $roles = array_column(DB::all('SELECT id, nombre FROM roles ORDER BY id'), 'nombre', 'id');
        return [
            'table' => 'usuarios', 'title' => 'Usuarios', 'singular' => 'usuario', 'icon' => 'bi-person-gear', 'route' => 'usuarios',
            'subtitle' => 'Personal con acceso al sistema y su rol.', 'order' => 'u.id ASC', 'modal' => '',
            'select' => 'SELECT u.id, u.rol_id, u.nombre, u.usuario, u.email, u.telefono, u.estado, u.ultimo_acceso, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id',
            'header_actions' => '<a href="' . e(url('usuarios/roles')) . '" class="btn btn-ghost"><i class="bi bi-shield-check me-1"></i>Roles y permisos</a>',
            'columns' => [
                ['key' => 'nombre', 'label' => 'Usuario', 'type' => 'strong', 'sub' => 'email'],
                ['key' => 'usuario', 'label' => 'Acceso', 'type' => 'muted'],
                ['key' => 'rol', 'label' => 'Rol', 'type' => 'badge', 'class' => 'violet'],
                ['key' => 'ultimo_acceso', 'label' => 'Último acceso', 'type' => 'muted'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre completo', 'required' => 1, 'col' => 12],
                ['name' => 'usuario', 'label' => 'Usuario', 'required' => 1, 'col' => 6],
                ['name' => 'clave', 'label' => 'Contraseña', 'type' => 'password', 'required' => 1, 'col' => 6, 'help' => 'Déjela vacía al editar para no cambiarla'],
                ['name' => 'rol_id', 'label' => 'Rol', 'type' => 'select', 'options' => $roles, 'required' => 1, 'col' => 6],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'tel', 'col' => 6],
                ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'col' => 12],
                ['name' => 'estado', 'label' => 'Usuario activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }

    protected function beforeSave(array $data, ?int $id): array
    {
        if ($id === Auth::id()) {
            $data['estado'] = 1;
            if (isset($data['rol_id']) && !Auth::isAdmin()) unset($data['rol_id']);
        }
        if (!Auth::isAdmin()) throw new RuntimeException('Solo un administrador puede gestionar usuarios.');
        return $data;
    }

    protected function beforeDelete(int $id): void
    {
        if ($id === Auth::id()) throw new RuntimeException('No puede eliminar su propio usuario.');
        if (!Auth::isAdmin()) throw new RuntimeException('Solo un administrador puede eliminar usuarios.');
    }

    public function rolesAction(): void
    {
        $this->view('usuarios/roles', ['title' => 'Roles y permisos', 'roles' => DB::all('SELECT * FROM roles ORDER BY id'), 'mods' => Auth::modules()]);
    }

    public function guardarRolesAction(): void
    {
        if (!Auth::isAdmin()) { flash('danger', 'Solo un administrador puede cambiar permisos.'); redirect('usuarios/roles'); }
        $valid = array_keys(Auth::modules());
        foreach (DB::all('SELECT id FROM roles WHERE id <> 1') as $r) {
            $sel = array_values(array_intersect($valid, (array)($_POST['perm'][$r['id']] ?? [])));
            DB::update('roles', ['permisos' => implode(',', $sel)], 'id = ?', [$r['id']]);
        }
        Log::add('permisos', 'Permisos de roles actualizados');
        flash('success', 'Permisos actualizados.');
        redirect('usuarios/roles');
    }
}
