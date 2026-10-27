<?php
/**
 * Registro de módulos del sistema (menú vertical + permisos).
 * clave => [etiqueta, icono, grupo, ruta]
 */
return [
    'dashboard'    => ['Dashboard',            'bi-speedometer2',      'Principal',      'dashboard/index'],

    'pos'          => ['Punto de Venta',       'bi-shop-window',       'Ventas',         'pos/index'],
    'mesas'        => ['Mesas',                'bi-grid-3x3-gap-fill', 'Ventas',         'mesas/index'],
    'reservas'     => ['Reservaciones',        'bi-calendar-heart-fill', 'Ventas',       'reservas/index'],
    'cocina'       => ['Cocina',               'bi-fire',              'Ventas',         'cocina/index'],
    'delivery'     => ['Delivery',             'bi-bicycle',           'Ventas',         'delivery/index'],
    'ventas'       => ['Historial de ventas',  'bi-receipt-cutoff',    'Ventas',         'ventas/index'],
    'caja'         => ['Caja',                 'bi-cash-coin',         'Ventas',         'caja/index'],

    'productos'    => ['Productos',            'bi-basket3-fill',      'Catálogo',       'productos/index'],
    'categorias'   => ['Categorías',           'bi-tags-fill',         'Catálogo',       'categorias/index'],
    'clientes'     => ['Clientes',             'bi-people-fill',       'Catálogo',       'clientes/index'],
    'repartidores' => ['Repartidores',         'bi-person-badge-fill', 'Catálogo',       'repartidores/index'],

    'insumos'      => ['Insumos',              'bi-box-seam-fill',     'Inventario',     'insumos/index'],
    'recetas'      => ['Recetas',              'bi-journal-richtext',  'Inventario',     'recetas/index'],
    'compras'      => ['Compras',              'bi-cart-plus-fill',    'Inventario',     'compras/index'],
    'proveedores'  => ['Proveedores',          'bi-truck',             'Inventario',     'proveedores/index'],

    'gastos'       => ['Gastos',               'bi-wallet2',           'Finanzas',       'gastos/index'],
    'facturacion'  => ['Facturación electrónica', 'bi-file-earmark-check-fill', 'Finanzas', 'facturacion/index'],
    'reportes'     => ['Reportes',             'bi-graph-up-arrow',    'Finanzas',       'reportes/index'],

    'crm'          => ['CRM de clientes',      'bi-heart-pulse-fill',  'CRM',            'crm/index'],
    'campanas'     => ['Campañas y fidelidad', 'bi-megaphone-fill',    'CRM',            'campanas/index'],

    'asistente'    => ['Asistente IA',         'bi-stars',             'Inteligencia',   'asistente/index', true],
    'chat'         => ['Chat IA',              'bi-chat-dots-fill',    'Inteligencia',   'chat/index', true],

    'usuarios'     => ['Usuarios y roles',     'bi-person-gear',       'Administración', 'usuarios/index'],
    'empresa'      => ['Configuración',        'bi-sliders',           'Administración', 'empresa/index'],
    'backup'       => ['Copia de seguridad',   'bi-database-fill-check', 'Administración', 'backup/index'],
];
