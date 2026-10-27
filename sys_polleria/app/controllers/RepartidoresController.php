<?php
class RepartidoresController extends CrudController
{
    protected string $module = 'repartidores';

    protected function config(): array
    {
        return [
            'table' => 'repartidores', 'title' => 'Repartidores', 'singular' => 'repartidor', 'icon' => 'bi-person-badge-fill', 'route' => 'repartidores',
            'subtitle' => 'Personal de reparto para pedidos delivery.', 'order' => 'nombre ASC', 'modal' => '',
            'columns' => [
                ['key' => 'nombre', 'label' => 'Repartidor', 'type' => 'strong'],
                ['key' => 'telefono', 'label' => 'Teléfono', 'type' => 'muted'],
                ['key' => 'vehiculo', 'label' => 'Vehículo', 'type' => 'badge', 'class' => 'info'],
                ['key' => 'placa', 'label' => 'Placa', 'type' => 'muted'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre completo', 'required' => 1, 'col' => 12],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'tel', 'col' => 6],
                ['name' => 'vehiculo', 'label' => 'Vehículo', 'type' => 'select', 'options' => ['Moto' => 'Moto', 'Bicicleta' => 'Bicicleta', 'Auto' => 'Auto', 'A pie' => 'A pie'], 'col' => 3],
                ['name' => 'placa', 'label' => 'Placa', 'col' => 3],
                ['name' => 'estado', 'label' => 'Repartidor activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
}
