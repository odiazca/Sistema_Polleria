<?php
class ProductosController extends CrudController
{
    protected string $module = 'productos';

    protected function config(): array
    {
        $cats = array_column(DB::all('SELECT id, nombre FROM categorias ORDER BY orden, nombre'), 'nombre', 'id');
        return [
            'table' => 'productos', 'title' => 'Productos', 'singular' => 'producto', 'icon' => 'bi-basket3-fill', 'route' => 'productos',
            'subtitle' => 'Platos, combos y bebidas que se venden en el punto de venta.', 'order' => 'p.categoria_id, p.nombre',
            'select' => 'SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON c.id = p.categoria_id',
            'columns' => [
                ['key' => 'imagen', 'label' => '', 'type' => 'img', 'icon' => 'bi-egg-fried'],
                ['key' => 'codigo', 'label' => 'Código', 'type' => 'muted'],
                ['key' => 'nombre', 'label' => 'Producto', 'type' => 'strong', 'sub' => 'descripcion'],
                ['key' => 'categoria', 'label' => 'Categoría', 'type' => 'badge'],
                ['key' => 'precio', 'label' => 'Precio', 'type' => 'money'],
                ['key' => 'costo', 'label' => 'Costo', 'type' => 'muted'],
                ['key' => 'stock', 'label' => 'Stock', 'type' => 'stock'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'bool'],
            ],
            'fields' => [
                ['name' => 'categoria_id', 'label' => 'Categoría', 'type' => 'select', 'options' => $cats, 'required' => 1, 'col' => 6],
                ['name' => 'codigo', 'label' => 'Código', 'col' => 6],
                ['name' => 'nombre', 'label' => 'Nombre del producto', 'required' => 1, 'col' => 12],
                ['name' => 'descripcion', 'label' => 'Descripción', 'col' => 12, 'placeholder' => 'Ej: Con papas fritas y ensalada'],
                ['name' => 'precio', 'label' => 'Precio de venta', 'type' => 'money', 'required' => 1, 'col' => 4],
                ['name' => 'costo', 'label' => 'Costo', 'type' => 'money', 'col' => 4],
                ['name' => 'imagen', 'label' => 'Imagen', 'type' => 'image', 'col' => 4],
                ['name' => 'stock_control', 'label' => 'Controlar stock (bebidas, envasados)', 'type' => 'switch', 'default' => 0, 'col' => 12],
                ['name' => 'stock', 'label' => 'Stock actual', 'type' => 'number', 'col' => 4, 'step' => '1'],
                ['name' => 'stock_minimo', 'label' => 'Stock mínimo', 'type' => 'number', 'col' => 4, 'step' => '1'],
                ['name' => 'para_cocina', 'label' => 'Se prepara en cocina', 'type' => 'switch', 'default' => 1, 'col' => 4],
                ['name' => 'estado', 'label' => 'Producto activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }

    protected function beforeSave(array $data, ?int $id): array
    {
        if (isset($data['codigo']) && $data['codigo'] === '') $data['codigo'] = null;
        return $data;
    }
}
