<?php
class CategoriasController extends CrudController
{
    protected string $module = 'categorias';

    protected function config(): array
    {
        $icons = ['bi-fire' => 'Fuego / brasa', 'bi-stars' => 'Combos', 'bi-egg-fried' => 'Huevo / platos', 'bi-cup-hot' => 'Plato caliente', 'bi-basket' => 'Canasta',
            'bi-cup-straw' => 'Bebidas', 'bi-cake2' => 'Postres', 'bi-cookie' => 'Galletas', 'bi-droplet' => 'Salsas', 'bi-heart' => 'Favoritos', 'bi-lightning' => 'Rápidos', 'bi-gift' => 'Promociones', 'bi-basket3' => 'Extras'];
        return [
            'table' => 'categorias', 'title' => 'Categorías', 'singular' => 'categoría', 'icon' => 'bi-tags-fill', 'route' => 'categorias',
            'subtitle' => 'Agrupe sus productos para encontrarlos rápido en el punto de venta.', 'order' => 'orden ASC, id ASC', 'modal' => '',
            'select' => 'SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id) AS productos FROM categorias c',
            'columns' => [
                ['key' => 'orden', 'label' => 'Orden', 'type' => 'muted'],
                ['key' => 'nombre', 'label' => 'Categoría', 'type' => 'strong'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'color'],
                ['key' => 'productos', 'label' => 'Productos', 'type' => 'number'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => 1, 'col' => 12],
                ['name' => 'icono', 'label' => 'Icono', 'type' => 'select', 'options' => $icons, 'required' => 1, 'default' => 'bi-egg-fried', 'col' => 6],
                ['name' => 'color', 'label' => 'Color', 'type' => 'color', 'default' => '#F97316', 'col' => 3],
                ['name' => 'orden', 'label' => 'Orden', 'type' => 'number', 'default' => 0, 'col' => 3, 'step' => '1'],
                ['name' => 'estado', 'label' => 'Categoría activa', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }
}
