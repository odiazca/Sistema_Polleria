<?php
class GastosController extends CrudController
{
    protected string $module = 'gastos';

    protected function config(): array
    {
        $cats = array_combine($l = ['Servicios', 'Insumos', 'Personal', 'Alquiler', 'Mantenimiento', 'Limpieza', 'Marketing', 'Impuestos', 'Otros'], $l);
        return [
            'table' => 'gastos', 'title' => 'Gastos', 'singular' => 'gasto', 'icon' => 'bi-wallet2', 'route' => 'gastos',
            'subtitle' => 'Registre los egresos del negocio para conocer su utilidad real.', 'order' => 'fecha DESC, id DESC', 'modal' => '',
            'columns' => [
                ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                ['key' => 'categoria', 'label' => 'Categoría', 'type' => 'badge', 'class' => 'warn'],
                ['key' => 'descripcion', 'label' => 'Descripción', 'type' => 'strong'],
                ['key' => 'metodo', 'label' => 'Pago', 'type' => 'badge', 'class' => 'gray'],
                ['key' => 'monto', 'label' => 'Monto', 'type' => 'money', 'th' => 'text-end', 'td' => 'text-end'],
            ],
            'fields' => [
                ['name' => 'fecha', 'label' => 'Fecha', 'type' => 'date', 'required' => 1, 'default' => date('Y-m-d'), 'col' => 6],
                ['name' => 'categoria', 'label' => 'Categoría', 'type' => 'select', 'options' => $cats, 'required' => 1, 'default' => 'Otros', 'col' => 6],
                ['name' => 'descripcion', 'label' => 'Descripción', 'required' => 1, 'col' => 12],
                ['name' => 'monto', 'label' => 'Monto', 'type' => 'money', 'required' => 1, 'col' => 6],
                ['name' => 'metodo', 'label' => 'Método de pago', 'type' => 'select', 'options' => ['efectivo' => 'efectivo', 'tarjeta' => 'tarjeta', 'yape' => 'yape', 'plin' => 'plin', 'transferencia' => 'transferencia'], 'required' => 1, 'default' => 'efectivo', 'col' => 6],
            ],
        ];
    }

    protected function beforeSave(array $data, ?int $id): array
    {
        if (!$id) $data['usuario_id'] = Auth::id();
        return $data;
    }
}
