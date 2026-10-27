<?php
/** Migraciones de esquema para instalaciones existentes */
class Migrate
{
    const VERSION = 7;

    public static function run(): void
    {
        if ((int)cfg('schema_version', 1) >= self::VERSION) return;
        $pdo = DB::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS recetas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, producto_id INT UNSIGNED NOT NULL, insumo_id INT UNSIGNED NOT NULL, cantidad DECIMAL(12,4) NOT NULL,
            UNIQUE KEY uq_receta (producto_id, insumo_id),
            CONSTRAINT fk_rec_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
            CONSTRAINT fk_rec_ins FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $has = (int)DB::val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'insumo_movimientos' AND column_name = 'pedido_id'", [DB_NAME]);
        if (!$has) {
            $pdo->exec('ALTER TABLE insumo_movimientos ADD COLUMN pedido_id INT UNSIGNED NULL, ADD KEY idx_mov_ped (pedido_id)');
        }
        if (!(int)DB::val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'mesas' AND column_name = 'qr_token'", [DB_NAME])) {
            $pdo->exec('ALTER TABLE mesas ADD COLUMN qr_token VARCHAR(32) NULL, ADD UNIQUE KEY uq_mesa_qr (qr_token)');
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS qr_solicitudes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mesa_id INT UNSIGNED NOT NULL, cliente_nombre VARCHAR(80) NULL, notas VARCHAR(250) NULL, items TEXT NOT NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0, estado ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente', pedido_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_qr_estado (estado, mesa_id),
            CONSTRAINT fk_qr_mesa FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (cfg('qr_activo', null) === null) Setting::set('qr_activo', '1');
        if (cfg('qr_base_url', null) === null) Setting::set('qr_base_url', '');
        if (!(int)DB::val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'pedidos' AND column_name = 'fe_estado'", [DB_NAME])) {
            $pdo->exec("ALTER TABLE pedidos ADD COLUMN fe_estado ENUM('pendiente','aceptado','rechazado','error','anulado') NULL, ADD COLUMN fe_mensaje VARCHAR(250) NULL, ADD COLUMN fe_hash VARCHAR(80) NULL, ADD COLUMN fe_enlace VARCHAR(255) NULL, ADD COLUMN fe_qr VARCHAR(500) NULL, ADD COLUMN fe_fecha DATETIME NULL");
        }
        foreach (['fe_activo' => '0', 'fe_modo' => 'simulado', 'fe_url' => '', 'fe_token' => '', 'fe_auto' => '1'] as $k => $v) if (cfg($k, null) === null) Setting::set($k, $v);
        $pdo->exec("CREATE TABLE IF NOT EXISTS ia_consultas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, pregunta VARCHAR(500) NOT NULL, titulo VARCHAR(160) NULL, sql_texto TEXT NOT NULL, grafico VARCHAR(255) NULL, favorito TINYINT(1) NOT NULL DEFAULT 0, veces INT NOT NULL DEFAULT 1, creado_en DATETIME DEFAULT CURRENT_TIMESTAMP, ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (usuario_id, favorito)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach (['ia_proveedor' => 'gemini', 'ia_url' => '', 'ia_modelo' => '', 'ia_clave' => '', 'ia_resumen' => '1'] as $k => $v) if (cfg($k, null) === null) Setting::set($k, $v);
        $pdo->exec("CREATE TABLE IF NOT EXISTS reservas (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   codigo VARCHAR(12) NULL,   cliente_id INT UNSIGNED NULL,   nombre VARCHAR(120) NOT NULL,   telefono VARCHAR(30) NULL,   email VARCHAR(120) NULL,   fecha DATE NOT NULL,   hora TIME NOT NULL,   personas INT NOT NULL DEFAULT 2,   mesa_id INT UNSIGNED NULL,   estado ENUM('pendiente','confirmada','sentada','completada','cancelada','no_asistio') NOT NULL DEFAULT 'pendiente',   ocasion VARCHAR(40) NULL,   notas VARCHAR(300) NULL,   origen VARCHAR(20) NOT NULL DEFAULT 'local',   usuario_id INT UNSIGNED NULL,   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   updated_at DATETIME NULL,   KEY idx_res_fecha (fecha, hora),   KEY idx_res_estado (estado),   KEY idx_res_mesa (mesa_id, fecha),   CONSTRAINT fk_res_mesa FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE SET NULL,   CONSTRAINT fk_res_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach (['reserva_duracion' => '90', 'reserva_apertura' => '12:00', 'reserva_cierre' => '22:30', 'reserva_intervalo' => '30', 'reserva_max_personas' => '20', 'reserva_dias' => '30', 'reserva_horas_min' => '2', 'reserva_web' => '1'] as $k => $v) if (cfg($k, null) === null) Setting::set($k, $v);
        $pdo->exec("UPDATE roles SET permisos = CONCAT(permisos, ',reservas') WHERE nombre IN ('Cajero','Mozo') AND permisos <> '*' AND FIND_IN_SET('reservas', permisos) = 0");
        foreach (['fecha_nacimiento' => 'DATE NULL', 'etiquetas' => 'VARCHAR(200) NULL', 'acepta_marketing' => 'TINYINT(1) NOT NULL DEFAULT 1'] as $col => $def) {
            if (!(int)DB::val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'clientes' AND column_name = ?", [DB_NAME, $col])) $pdo->exec("ALTER TABLE clientes ADD COLUMN $col $def");
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_notas (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   cliente_id INT UNSIGNED NOT NULL,   tipo VARCHAR(20) NOT NULL DEFAULT 'nota',   texto VARCHAR(500) NOT NULL,   seguimiento DATE NULL,   hecho TINYINT(1) NOT NULL DEFAULT 0,   usuario_id INT UNSIGNED NULL,   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   KEY idx_nota_cli (cliente_id, created_at),   KEY idx_nota_seg (seguimiento, hecho),   CONSTRAINT fk_nota_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_puntos (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   cliente_id INT UNSIGNED NOT NULL,   puntos INT NOT NULL,   motivo VARCHAR(160) NOT NULL,   usuario_id INT UNSIGNED NULL,   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   KEY idx_pts_cli (cliente_id),   CONSTRAINT fk_pts_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_recompensas (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   nombre VARCHAR(100) NOT NULL,   puntos INT NOT NULL,   estado TINYINT(1) NOT NULL DEFAULT 1 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_campanas (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   nombre VARCHAR(120) NOT NULL,   segmento VARCHAR(30) NOT NULL DEFAULT 'todos',   mensaje VARCHAR(700) NOT NULL,   usuario_id INT UNSIGNED NULL,   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS crm_envios (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   campana_id INT UNSIGNED NOT NULL,   cliente_id INT UNSIGNED NOT NULL,   enviado TINYINT(1) NOT NULL DEFAULT 0,   enviado_en DATETIME NULL,   UNIQUE KEY uq_env (campana_id, cliente_id),   CONSTRAINT fk_env_cam FOREIGN KEY (campana_id) REFERENCES crm_campanas(id) ON DELETE CASCADE,   CONSTRAINT fk_env_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach (['crm_soles_por_punto' => '10', 'crm_nivel_plata' => '200', 'crm_nivel_oro' => '500', 'crm_vip_gasto' => '1000', 'crm_frecuente_visitas' => '4', 'crm_riesgo_dias' => '45', 'crm_perdido_dias' => '120'] as $k => $v) if (cfg($k, null) === null) Setting::set($k, $v);
        if (!(int)DB::val('SELECT COUNT(*) FROM crm_recompensas')) $pdo->exec("INSERT INTO crm_recompensas (nombre, puntos) VALUES ('Gaseosa personal gratis', 40), ('Porción de papas gratis', 80), ('1/4 de pollo gratis', 150), ('S/ 20 de descuento', 200)");
        Setting::set('schema_version', (string)self::VERSION);
    }
}
