<?php
class ClientesController extends CrudController
{
    protected string $module = 'clientes';

    protected function config(): array
    {
        return [
            'table' => 'clientes', 'title' => 'Clientes', 'singular' => 'cliente', 'icon' => 'bi-people-fill', 'route' => 'clientes',
            'subtitle' => 'Datos para boletas, facturas y entregas a domicilio.', 'order' => 'nombre ASC',
            'columns' => [
                ['key' => 'nombre', 'label' => 'Cliente', 'type' => 'strong', 'sub' => 'email'],
                ['key' => 'tipo_doc', 'label' => 'Doc.', 'type' => 'badge', 'class' => 'gray'],
                ['key' => 'documento', 'label' => 'Número', 'type' => 'muted'],
                ['key' => 'telefono', 'label' => 'Teléfono', 'type' => 'muted'],
                ['key' => 'direccion', 'label' => 'Dirección', 'type' => 'muted'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'tipo_doc', 'label' => 'Tipo de documento', 'type' => 'select', 'options' => ['DNI' => 'DNI', 'RUC' => 'RUC', 'CE' => 'Carné de extranjería', 'OTRO' => 'Otro'], 'required' => 1, 'default' => 'DNI', 'col' => 4],
                ['name' => 'documento', 'label' => 'Número de documento', 'col' => 8],
                ['name' => 'nombre', 'label' => 'Nombre / Razón social', 'required' => 1, 'col' => 12],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'tel', 'col' => 6],
                ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'col' => 6],
                ['name' => 'direccion', 'label' => 'Dirección', 'col' => 12],
                ['name' => 'referencia', 'label' => 'Referencia de entrega', 'col' => 12, 'placeholder' => 'Ej: Frente al parque, casa azul'],
                ['name' => 'estado', 'label' => 'Cliente activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
}
