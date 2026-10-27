-- Esquema Pollería POS
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS configuraciones (
  clave VARCHAR(80) NOT NULL PRIMARY KEY,
  valor TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(150) NULL,
  permisos TEXT NOT NULL,
  UNIQUE KEY uq_rol_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL,
  clave VARCHAR(255) NOT NULL,
  email VARCHAR(120) NULL,
  telefono VARCHAR(30) NULL,
  avatar VARCHAR(255) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario (usuario),
  CONSTRAINT fk_usr_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  icono VARCHAR(40) NOT NULL DEFAULT 'bi-egg-fried',
  color VARCHAR(9) NOT NULL DEFAULT '#F97316',
  orden INT NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  codigo VARCHAR(30) NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo DECIMAL(12,2) NOT NULL DEFAULT 0,
  imagen VARCHAR(255) NULL,
  stock_control TINYINT(1) NOT NULL DEFAULT 0,
  stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
  para_cocina TINYINT(1) NOT NULL DEFAULT 1,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prod_cat (categoria_id),
  CONSTRAINT fk_prod_cat FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_doc VARCHAR(10) NOT NULL DEFAULT 'DNI',
  documento VARCHAR(20) NULL,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(200) NULL,
  referencia VARCHAR(200) NULL,
  fecha_nacimiento DATE NULL,
  etiquetas VARCHAR(200) NULL,
  acepta_marketing TINYINT(1) NOT NULL DEFAULT 1,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cli_doc (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS proveedores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ruc VARCHAR(20) NULL,
  razon_social VARCHAR(150) NOT NULL,
  contacto VARCHAR(100) NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(200) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS insumos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  unidad VARCHAR(20) NOT NULL DEFAULT 'kg',
  stock DECIMAL(12,3) NOT NULL DEFAULT 0,
  stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 0,
  costo DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS insumo_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  insumo_id INT UNSIGNED NOT NULL,
  tipo ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad DECIMAL(12,3) NOT NULL,
  motivo VARCHAR(150) NULL,
  usuario_id INT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pedido_id INT UNSIGNED NULL,
  KEY idx_mov_ins (insumo_id),
  KEY idx_mov_ped (pedido_id),
  CONSTRAINT fk_mov_ins FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recetas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  insumo_id INT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,4) NOT NULL,
  UNIQUE KEY uq_receta (producto_id, insumo_id),
  CONSTRAINT fk_rec_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_ins FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mesas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL,
  zona VARCHAR(40) NOT NULL DEFAULT 'Salón',
  capacidad INT NOT NULL DEFAULT 4,
  estado ENUM('libre','ocupada','reservada','inactiva') NOT NULL DEFAULT 'libre',
  qr_token VARCHAR(32) NULL,
  UNIQUE KEY uq_mesa_qr (qr_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(12) NULL,
  cliente_id INT UNSIGNED NULL,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  personas INT NOT NULL DEFAULT 2,
  mesa_id INT UNSIGNED NULL,
  estado ENUM('pendiente','confirmada','sentada','completada','cancelada','no_asistio') NOT NULL DEFAULT 'pendiente',
  ocasion VARCHAR(40) NULL,
  notas VARCHAR(300) NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'local',
  usuario_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_res_fecha (fecha, hora),
  KEY idx_res_estado (estado),
  KEY idx_res_mesa (mesa_id, fecha),
  CONSTRAINT fk_res_mesa FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE SET NULL,
  CONSTRAINT fk_res_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_solicitudes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT UNSIGNED NOT NULL,
  cliente_nombre VARCHAR(80) NULL,
  notas VARCHAR(250) NULL,
  items TEXT NOT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
  pedido_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_qr_estado (estado, mesa_id),
  CONSTRAINT fk_qr_mesa FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ia_consultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  pregunta VARCHAR(500) NOT NULL,
  titulo VARCHAR(160) NULL,
  sql_texto TEXT NOT NULL,
  grafico VARCHAR(255) NULL,
  favorito TINYINT(1) NOT NULL DEFAULT 0,
  veces INT NOT NULL DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (usuario_id, favorito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS repartidores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(30) NULL,
  vehiculo VARCHAR(40) NULL,
  placa VARCHAR(20) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cajas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  fecha_apertura DATETIME NOT NULL,
  fecha_cierre DATETIME NULL,
  monto_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
  monto_esperado DECIMAL(12,2) NULL,
  monto_contado DECIMAL(12,2) NULL,
  diferencia DECIMAL(12,2) NULL,
  observacion VARCHAR(250) NULL,
  estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  KEY idx_caja_usr (usuario_id, estado),
  CONSTRAINT fk_caja_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caja_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  caja_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  tipo ENUM('ingreso','egreso') NOT NULL,
  concepto VARCHAR(150) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cm_caja (caja_id),
  CONSTRAINT fk_cm_caja FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('mesa','llevar','delivery') NOT NULL DEFAULT 'mesa',
  mesa_id INT UNSIGNED NULL,
  cliente_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  caja_id INT UNSIGNED NULL,
  repartidor_id INT UNSIGNED NULL,
  estado ENUM('abierto','pagado','anulado') NOT NULL DEFAULT 'abierto',
  delivery_estado ENUM('pendiente','en_camino','entregado','cancelado') NULL,
  direccion_entrega VARCHAR(220) NULL,
  telefono_entrega VARCHAR(30) NULL,
  comprobante ENUM('ticket','boleta','factura') NULL,
  serie VARCHAR(10) NULL,
  numero INT UNSIGNED NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_delivery DECIMAL(12,2) NOT NULL DEFAULT 0,
  igv DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  notas VARCHAR(250) NULL,
  motivo_anulacion VARCHAR(200) NULL,
  fe_estado ENUM('pendiente','aceptado','rechazado','error','anulado') NULL,
  fe_mensaje VARCHAR(250) NULL,
  fe_hash VARCHAR(80) NULL,
  fe_enlace VARCHAR(255) NULL,
  fe_qr VARCHAR(500) NULL,
  fe_fecha DATETIME NULL,
  fecha_pedido DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_pago DATETIME NULL,
  KEY idx_ped_estado (estado, fecha_pago),
  KEY idx_ped_mesa (mesa_id),
  KEY idx_ped_tipo (tipo, delivery_estado),
  CONSTRAINT fk_ped_mesa FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE SET NULL,
  CONSTRAINT fk_ped_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_ped_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_ped_rep FOREIGN KEY (repartidor_id) REFERENCES repartidores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedido_detalle (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  nota VARCHAR(200) NULL,
  estado ENUM('pendiente','preparando','listo','entregado') NOT NULL DEFAULT 'pendiente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_det_ped (pedido_id),
  KEY idx_det_prod (producto_id),
  CONSTRAINT fk_det_ped FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_det_prod FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  metodo ENUM('efectivo','tarjeta','yape','plin','transferencia') NOT NULL DEFAULT 'efectivo',
  monto DECIMAL(12,2) NOT NULL,
  referencia VARCHAR(80) NULL,
  KEY idx_pago_ped (pedido_id),
  CONSTRAINT fk_pago_ped FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compras (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proveedor_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  fecha DATE NOT NULL,
  comprobante VARCHAR(40) NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  notas VARCHAR(250) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comp_prov FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compra_detalle (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  compra_id INT UNSIGNED NOT NULL,
  insumo_id INT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,3) NOT NULL,
  costo DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_cd_compra FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  CONSTRAINT fk_cd_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gastos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  fecha DATE NOT NULL,
  categoria VARCHAR(60) NOT NULL DEFAULT 'Otros',
  descripcion VARCHAR(200) NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  metodo VARCHAR(20) NOT NULL DEFAULT 'efectivo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(40) NOT NULL,
  detalle VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aud_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS crm_notas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'nota',
  texto VARCHAR(500) NOT NULL,
  seguimiento DATE NULL,
  hecho TINYINT(1) NOT NULL DEFAULT 0,
  usuario_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_nota_cli (cliente_id, created_at),
  KEY idx_nota_seg (seguimiento, hecho),
  CONSTRAINT fk_nota_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_puntos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  puntos INT NOT NULL,
  motivo VARCHAR(160) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pts_cli (cliente_id),
  CONSTRAINT fk_pts_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_recompensas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  puntos INT NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_campanas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  segmento VARCHAR(30) NOT NULL DEFAULT 'todos',
  mensaje VARCHAR(700) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_envios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campana_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  enviado TINYINT(1) NOT NULL DEFAULT 0,
  enviado_en DATETIME NULL,
  UNIQUE KEY uq_env (campana_id, cliente_id),
  CONSTRAINT fk_env_cam FOREIGN KEY (campana_id) REFERENCES crm_campanas(id) ON DELETE CASCADE,
  CONSTRAINT fk_env_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

