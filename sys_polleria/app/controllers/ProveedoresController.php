<?php
class ProveedoresController extends CrudController
{
    protected string $module = 'proveedores';

    protected function config(): array
    {
        return [
            'table' => 'proveedores', 'title' => 'Proveedores', 'singular' => 'proveedor', 'icon' => 'bi-truck', 'route' => 'proveedores',
            'subtitle' => 'Empresas y personas que le abastecen de insumos.', 'order' => 'razon_social ASC',
            'columns' => [
                ['key' => 'razon_social', 'label' => 'Proveedor', 'type' => 'strong', 'sub' => 'email'],
                ['key' => 'ruc', 'label' => 'RUC', 'type' => 'muted'],
                ['key' => 'contacto', 'label' => 'Contacto', 'type' => 'muted'],
                ['key' => 'telefono', 'label' => 'Teléfono', 'type' => 'muted'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'razon_social', 'label' => 'Razón social', 'required' => 1, 'col' => 8],
                ['name' => 'ruc', 'label' => 'RUC', 'col' => 4],
                ['name' => 'contacto', 'label' => 'Persona de contacto', 'col' => 6],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'tel', 'col' => 6],
                ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'col' => 6],
                ['name' => 'direccion', 'label' => 'Dirección', 'col' => 6],
                ['name' => 'estado', 'label' => 'Proveedor activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
}
