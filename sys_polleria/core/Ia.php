<?php
/**
 * Asistente de reportes + Chat con IA · Pollería POS
 * La IA NUNCA toca las tablas reales: solo ve vistas de lectura (v_ia_*) y su SQL se valida y se ejecuta
 * en una transacción de solo lectura, con límite de tiempo y de filas.
 */
class Ia
{
    const MAX_FILAS = 500;
    const PRESETS = [
        'gemini'     => ['nombre' => 'Google Gemini (gratis)', 'url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'modelo' => 'gemini-3.5-flash-lite', 'clave' => true,  'ayuda' => 'Clave gratis en aistudio.google.com/apikey'],
        'groq'       => ['nombre' => 'Groq (gratis)', 'url' => 'https://api.groq.com/openai/v1', 'modelo' => 'llama-3.3-70b-versatile', 'clave' => true, 'ayuda' => 'Clave gratis en console.groq.com/keys'],
        'openrouter' => ['nombre' => 'OpenRouter (modelos :free)', 'url' => 'https://openrouter.ai/api/v1', 'modelo' => 'meta-llama/llama-3.3-70b-instruct:free', 'clave' => true, 'ayuda' => 'Clave gratis en openrouter.ai/keys'],
        'ollama'     => ['nombre' => 'Ollama (local, 100 % gratis y privado)', 'url' => 'http://localhost:11434/v1', 'modelo' => 'qwen2.5-coder:7b', 'clave' => false, 'ayuda' => 'Instala Ollama en tu PC y ejecuta: ollama pull qwen2.5-coder:7b'],
        'otro'       => ['nombre' => 'Otro (compatible con OpenAI)', 'url' => '', 'modelo' => '', 'clave' => true, 'ayuda' => 'Cualquier servicio con API /chat/completions (OpenAI, LM Studio…)'],
    ];

    /* ---------- Vistas de solo lectura ---------- */
    public static function vistas(): array
    {
        $fv = "COALESCE(p.fecha_pago, p.fecha_pedido)";
        $dl = "FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id LEFT JOIN categorias ca ON ca.id = pr.categoria_id LEFT JOIN mesas m ON m.id = p.mesa_id";
        return [
        'v_ia_ventas' => ["Una fila por pedido/comprobante. estado: pagado (venta válida y cobrada), anulado o abierto (en curso, aún sin cobrar). tipo: mesa, llevar o delivery. comprobante_tipo: ticket, boleta o factura. metodos_pago = métodos usados (efectivo, tarjeta, yape, plin, transferencia). hora = hora del día (0-23).",
          "SELECT p.id AS pedido_id, $fv AS fecha, DATE($fv) AS dia, HOUR($fv) AS hora, p.tipo, m.nombre AS mesa, COALESCE(c.nombre,'Público general') AS cliente, u.nombre AS cajero, p.comprobante AS comprobante_tipo, CONCAT(p.serie,'-',LPAD(p.numero,6,'0')) AS comprobante, p.subtotal, p.descuento, p.costo_delivery, p.igv, p.total, p.estado, (SELECT GROUP_CONCAT(DISTINCT pg.metodo SEPARATOR ', ') FROM pagos pg WHERE pg.pedido_id = p.id) AS metodos_pago, p.delivery_estado, rp.nombre AS repartidor, p.motivo_anulacion FROM pedidos p LEFT JOIN mesas m ON m.id = p.mesa_id LEFT JOIN clientes c ON c.id = p.cliente_id LEFT JOIN usuarios u ON u.id = p.usuario_id LEFT JOIN repartidores rp ON rp.id = p.repartidor_id"],
        'v_ia_detalle_ventas' => ["Una fila por plato/producto vendido en cada pedido. Úsala para platos más vendidos, categorías y utilidad. importe = ingreso de la línea; utilidad_estimada = importe - cantidad*costo_unitario (costo actual del producto). Filtra estado_pedido='pagado' para ventas válidas.",
          "SELECT d.pedido_id, $fv AS fecha, DATE($fv) AS dia, HOUR($fv) AS hora, p.tipo, m.nombre AS mesa, pr.id AS producto_id, pr.codigo, d.nombre AS producto, COALESCE(ca.nombre,'Sin categoría') AS categoria, d.cantidad, d.precio AS precio_unitario, pr.costo AS costo_unitario, d.subtotal AS importe, (d.subtotal - d.cantidad*pr.costo) AS utilidad_estimada, p.estado AS estado_pedido $dl"],
        'v_ia_pagos' => ["Pagos recibidos por método (efectivo, tarjeta, yape, plin, transferencia). Una fila por pago. Solo de pedidos cobrados salvo que se indique estado_pedido.",
          "SELECT pg.pedido_id, $fv AS fecha, DATE($fv) AS dia, pg.metodo, pg.monto, p.tipo, p.estado AS estado_pedido FROM pagos pg JOIN pedidos p ON p.id = pg.pedido_id"],
        'v_ia_productos' => ["Carta de platos, combos y bebidas. activo=1 son los vigentes. margen_unitario = precio - costo. controla_stock=1 para productos con inventario propio (bebidas); stock_bajo=1 si stock <= stock_minimo.",
          "SELECT pr.id AS producto_id, pr.codigo, pr.nombre AS producto, COALESCE(ca.nombre,'Sin categoría') AS categoria, pr.descripcion, pr.precio, pr.costo, (pr.precio - pr.costo) AS margen_unitario, pr.stock_control AS controla_stock, pr.stock, pr.stock_minimo, (pr.stock_control = 1 AND pr.stock <= pr.stock_minimo) AS stock_bajo, pr.para_cocina, pr.estado AS activo FROM productos pr LEFT JOIN categorias ca ON ca.id = pr.categoria_id"],
        'v_ia_insumos' => ["Insumos de cocina (pollo fresco, papa, aceite, carbón, arroz…) con su stock actual. stock_bajo=1 si stock <= stock_minimo. activo=1 vigentes.",
          "SELECT i.id AS insumo_id, i.nombre AS insumo, i.unidad, i.stock, i.stock_minimo, (i.stock <= i.stock_minimo) AS stock_bajo, i.costo, (i.stock * i.costo) AS valor_stock, i.estado AS activo FROM insumos i"],
        'v_ia_recetas' => ["Receta de cada plato: cuánto insumo consume una unidad vendida. costo_insumo = cantidad_por_unidad * costo del insumo.",
          "SELECT pr.nombre AS producto, i.nombre AS insumo, i.unidad, r.cantidad AS cantidad_por_unidad, (r.cantidad * i.costo) AS costo_insumo FROM recetas r JOIN productos pr ON pr.id = r.producto_id JOIN insumos i ON i.id = r.insumo_id"],
        'v_ia_movimientos_insumos' => ["Kardex de insumos: tipo entrada (compras, ajustes positivos), salida (consumo por ventas, mermas) o ajuste.",
          "SELECT mv.fecha, DATE(mv.fecha) AS dia, i.nombre AS insumo, i.unidad, mv.tipo, mv.cantidad, mv.motivo, mv.pedido_id FROM insumo_movimientos mv JOIN insumos i ON i.id = mv.insumo_id"],
        'v_ia_clientes' => ["Clientes con su historial de compras (solo pedidos pagados). activo=1 vigentes.",
          "SELECT c.id AS cliente_id, c.nombre, c.tipo_doc, c.documento, c.telefono, c.email, c.direccion, c.estado AS activo, (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id AND p.estado = 'pagado') AS num_compras, (SELECT COALESCE(SUM(p.total),0) FROM pedidos p WHERE p.cliente_id = c.id AND p.estado = 'pagado') AS total_comprado, (SELECT MAX(p.fecha_pago) FROM pedidos p WHERE p.cliente_id = c.id AND p.estado = 'pagado') AS ultima_compra FROM clientes c"],
        'v_ia_compras' => ["Compras de insumos a proveedores (cabecera).",
          "SELECT co.id AS compra_id, co.fecha, pv.razon_social AS proveedor, co.comprobante, co.total, co.notas FROM compras co LEFT JOIN proveedores pv ON pv.id = co.proveedor_id"],
        'v_ia_detalle_compras' => ["Insumos comprados (detalle de cada compra).",
          "SELECT cd.compra_id, co.fecha, pv.razon_social AS proveedor, i.nombre AS insumo, i.unidad, cd.cantidad, cd.costo AS costo_unitario, cd.subtotal AS importe FROM compra_detalle cd JOIN compras co ON co.id = cd.compra_id JOIN insumos i ON i.id = cd.insumo_id LEFT JOIN proveedores pv ON pv.id = co.proveedor_id"],
        'v_ia_proveedores' => ["Proveedores con total comprado.",
          "SELECT pv.id AS proveedor_id, pv.ruc, pv.razon_social, pv.contacto, pv.telefono, pv.estado AS activo, (SELECT COUNT(*) FROM compras co WHERE co.proveedor_id = pv.id) AS num_compras, (SELECT COALESCE(SUM(co.total),0) FROM compras co WHERE co.proveedor_id = pv.id) AS total_comprado FROM proveedores pv"],
        'v_ia_gastos' => ["Gastos operativos del local (alquiler, servicios, sueldos, etc.).",
          "SELECT g.id AS gasto_id, g.fecha, g.categoria, g.descripcion, g.monto, g.metodo, u.nombre AS registrado_por FROM gastos g LEFT JOIN usuarios u ON u.id = g.usuario_id"],
        'v_ia_cajas' => ["Aperturas y cierres de caja por cajero. estado: abierta o cerrada. diferencia = contado - esperado.",
          "SELECT ca.id AS caja_id, u.nombre AS cajero, ca.fecha_apertura, ca.fecha_cierre, ca.monto_inicial, ca.monto_esperado, ca.monto_contado, ca.diferencia, ca.estado, (SELECT COALESCE(SUM(x.monto),0) FROM caja_movimientos x WHERE x.caja_id = ca.id AND x.tipo = 'ingreso') AS ingresos_extra, (SELECT COALESCE(SUM(x.monto),0) FROM caja_movimientos x WHERE x.caja_id = ca.id AND x.tipo = 'egreso') AS egresos FROM cajas ca LEFT JOIN usuarios u ON u.id = ca.usuario_id"],
        'v_ia_mesas' => ["Mesas del local con su uso histórico (pedidos pagados).",
          "SELECT m.id AS mesa_id, m.nombre AS mesa, m.zona, m.capacidad, m.estado, (SELECT COUNT(*) FROM pedidos p WHERE p.mesa_id = m.id AND p.estado = 'pagado') AS veces_usada, (SELECT COALESCE(SUM(p.total),0) FROM pedidos p WHERE p.mesa_id = m.id AND p.estado = 'pagado') AS total_vendido FROM mesas m"],
        ];
    }

    public static function instalar(bool $force = false): void
    {
        $pdo = DB::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ia_consultas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, pregunta VARCHAR(500) NOT NULL, titulo VARCHAR(160) NULL, sql_texto TEXT NOT NULL, grafico VARCHAR(255) NULL, favorito TINYINT(1) NOT NULL DEFAULT 0, veces INT NOT NULL DEFAULT 1, creado_en DATETIME DEFAULT CURRENT_TIMESTAMP, ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (usuario_id, favorito)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ok = !$force;
        if ($ok) { try { foreach (array_keys(self::vistas()) as $v) $pdo->query("SELECT 1 FROM `$v` LIMIT 1")->fetchAll(); } catch (Throwable $e) { $ok = false; } }
        if ($ok) return;
        foreach (self::vistas() as $v => [, $sql]) $pdo->exec("CREATE OR REPLACE VIEW `$v` AS $sql");
    }

    public static function esquemaTexto(): string
    {
        $t = '';
        foreach (self::vistas() as $v => [$desc]) {
            $cols = [];
            foreach (DB::pdo()->query("SHOW COLUMNS FROM `$v`")->fetchAll() as $c) $cols[] = $c['Field'] . ' ' . strtolower(preg_replace('/\(.*/', '', $c['Type']));
            $t .= "$v: $desc\n  columnas: " . implode(', ', $cols) . "\n";
        }
        return $t;
    }

    /* ---------- Clave cifrada ---------- */
    private static function secret(): string
    {
        $f = BASE_PATH . '/config/secret.php';
        if (!is_file($f)) @file_put_contents($f, "<?php return '" . base64_encode(random_bytes(32)) . "';\n");
        $k = is_file($f) ? (string)(include $f) : '';
        return base64_decode($k) ?: str_repeat('x', 32);
    }
    public static function cifrar(string $s): string
    {
        if ($s === '') return '';
        if (!function_exists('openssl_encrypt')) return 'p:' . base64_encode($s);
        $iv = random_bytes(12); $tag = '';
        $c = openssl_encrypt($s, 'aes-256-gcm', self::secret(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'g:' . base64_encode($iv . $tag . $c);
    }
    public static function descifrar(string $s): string
    {
        if ($s === '') return '';
        if (str_starts_with($s, 'p:')) return (string)base64_decode(substr($s, 2));
        if (!str_starts_with($s, 'g:') || !function_exists('openssl_decrypt')) return '';
        $b = base64_decode(substr($s, 2)); if (strlen($b) < 29) return '';
        return (string)openssl_decrypt(substr($b, 28), 'aes-256-gcm', self::secret(), OPENSSL_RAW_DATA, substr($b, 0, 12), substr($b, 12, 16));
    }
    public static function cfg(): array
    {
        $p = cfg('ia_proveedor', 'gemini'); if (!isset(self::PRESETS[$p])) $p = 'gemini';
        return ['proveedor' => $p, 'url' => cfg('ia_url', self::PRESETS[$p]['url']) ?: self::PRESETS[$p]['url'], 'modelo' => cfg('ia_modelo', self::PRESETS[$p]['modelo']) ?: self::PRESETS[$p]['modelo'],
                'clave' => self::descifrar((string)cfg('ia_clave', '')), 'resumen' => cfg('ia_resumen', '1') === '1'];
    }
    public static function listo(): bool
    {
        $c = self::cfg();
        return $c['url'] !== '' && $c['modelo'] !== '' && (!self::PRESETS[$c['proveedor']]['clave'] || $c['clave'] !== '');
    }

    /* ---------- Llamada al modelo (API compatible con OpenAI) ---------- */
    public static function llamar(array $mensajes, int $maxTokens = 900): string
    {
        $c = self::cfg();
        if ($c['url'] === '' || $c['modelo'] === '') throw new RuntimeException('Configure el proveedor de IA (URL y modelo).');
        if (self::PRESETS[$c['proveedor']]['clave'] && $c['clave'] === '') throw new RuntimeException('Falta la clave (API key) del proveedor de IA. Configúrela con el botón «Configurar IA».');
        $url = rtrim($c['url'], '/') . '/chat/completions';
        if (!preg_match('#^https?://#i', $url)) throw new RuntimeException('La URL del proveedor debe empezar por http:// o https://');
        $payload = json_encode(['model' => $c['modelo'], 'messages' => $mensajes, 'temperature' => 0, 'max_tokens' => $maxTokens], JSON_UNESCAPED_UNICODE);
        $h = ['Content-Type: application/json', 'Accept: application/json']; if ($c['clave'] !== '') $h[] = 'Authorization: Bearer ' . $c['clave'];
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS]);
            $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $h), 'content' => $payload, 'timeout' => 60, 'ignore_errors' => true]]);
            $res = @file_get_contents($url, false, $ctx); $err = $res === false ? 'sin respuesta' : ''; $code = 0;
            foreach ($http_response_header ?? [] as $l) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $l, $m)) $code = (int)$m[1];
        }
        if ($res === false || $res === null) throw new RuntimeException('No se pudo conectar con el proveedor de IA (' . $err . '). Revise su internet y la URL.');
        $j = json_decode((string)$res, true);
        if ($code === 401 || $code === 403) throw new RuntimeException('El proveedor rechazó la clave (API key). Revise que sea correcta.');
        if ($code === 429) throw new RuntimeException('Se alcanzó el límite del plan gratuito. Espere un minuto e inténtelo de nuevo.');
        if ($code === 404) throw new RuntimeException('Modelo o URL no encontrados (' . $c['modelo'] . '). Google renueva sus modelos: pruebe con gemini-3.5-flash-lite o gemini-3.5-flash.');
        if ($code >= 400 || !is_array($j)) { $m = is_array($j) ? ($j['error']['message'] ?? ($j[0]['error']['message'] ?? '')) : ''; throw new RuntimeException('Error del proveedor de IA (' . $code . ')' . ($m ? ': ' . mb_substr($m, 0, 200) : '.')); }
        $txt = $j['choices'][0]['message']['content'] ?? '';
        if (is_array($txt)) $txt = implode('', array_map(fn($p) => $p['text'] ?? '', $txt));
        if (trim((string)$txt) === '') throw new RuntimeException('La IA no devolvió respuesta. Intente reformular la pregunta.');
        return (string)$txt;
    }

    public static function extraerJson(string $t): ?array
    {
        $t = trim($t);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $t, $m)) $t = trim($m[1]);
        $j = json_decode($t, true); if (is_array($j)) return $j;
        $a = strpos($t, '{'); $b = strrpos($t, '}');
        if ($a !== false && $b > $a) { $j = json_decode(substr($t, $a, $b - $a + 1), true); if (is_array($j)) return $j; }
        return null;
    }

    /* ---------- Validación del SQL ---------- */
    public static function validarSql(string $sql): string
    {
        $sql = rtrim(trim($sql), "; \t\r\n");
        if ($sql === '' || strlen($sql) > 4000) throw new InvalidArgumentException('La consulta está vacía o es demasiado larga.');
        $s = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", ' 0 ', $sql);
        $s = preg_replace('/"(?:[^"\\\\]|\\\\.|"")*"/s', ' 0 ', $s);
        if (strpbrk($s, "'\"") !== false) throw new InvalidArgumentException('Comillas sin cerrar en la consulta.');
        if (strpos($s, ';') !== false) throw new InvalidArgumentException('Solo se permite una consulta.');
        if (preg_match('/--|\/\*|\*\/|#|@/', $s)) throw new InvalidArgumentException('No se permiten comentarios ni variables.');
        $l = strtolower(str_replace('`', ' ', $s));
        if (!preg_match('/^\s*(select|with)\b/', $l)) throw new InvalidArgumentException('Solo se permiten consultas SELECT.');
        $prohib = 'insert|update|delete|drop|alter|create|truncate|grant|revoke|call|execute|prepare|handler|load|outfile|dumpfile|into|lock|unlock|set|rename|use|show|describe|explain|optimize|analyze|sleep|benchmark|load_file|get_lock|release_lock|information_schema|performance_schema|mysql|sys|user|current_user|session_user|system_user|database|schema|version|connection_id|row_count|found_rows|last_insert_id|uuid|procedure|kill|shutdown|flush|reset|purge|install|uninstall|import|binlog|xa|savepoint|commit|rollback|begin|start|recursive';
        if (preg_match('/\b(' . $prohib . ')\b/', $l, $m)) throw new InvalidArgumentException('Palabra no permitida en la consulta: ' . strtoupper($m[1]));
        if (preg_match('/for\s+update|lock\s+in\s+share/', $l)) throw new InvalidArgumentException('Bloqueos no permitidos.');
        static $reales = null;
        if ($reales === null) {
            $reales = [];
            foreach (DB::pdo()->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as $r) $reales[strtolower($r[0])] = true;
            $reales[strtolower(DB_NAME)] = true;
        }
        if (preg_match_all('/(?<![\w.])(?:(as)\s+)?([a-z_][a-z0-9_]*)\b/', $l, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $t) if (isset($reales[$t[2]]) && $t[1] === '') throw new InvalidArgumentException('Solo puede consultar las vistas v_ia_* (no la tabla «' . $t[2] . '»). Si es un alias, use otro nombre.');
        }
        if (preg_match('/\b[a-z_][a-z0-9_]*\s*\.\s*[a-z_][a-z0-9_]*\s*\./', $l)) throw new InvalidArgumentException('No se permiten nombres con base de datos.');
        return $sql;
    }

    /** Ejecuta en solo lectura con límite de tiempo y de filas */
    public static function ejecutar(string $sql): array
    {
        $sql = self::validarSql($sql); $pdo = DB::pdo(); $t0 = microtime(true);
        foreach (["SET SESSION MAX_EXECUTION_TIME=8000", "SET SESSION max_statement_time=8"] as $x) { try { $pdo->exec($x); } catch (Throwable $e) {} }
        $buf = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            $pdo->exec('START TRANSACTION READ ONLY');
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $pdo->query($sql);
            $n = $st->columnCount(); $cols = $tipos = [];
            for ($i = 0; $i < $n; $i++) { $m = $st->getColumnMeta($i); $cols[] = $m['name'] ?? ('col' . $i); $tipos[] = strtoupper((string)($m['native_type'] ?? '')); }
            $filas = []; $trunc = false;
            while ($r = $st->fetch(PDO::FETCH_NUM)) {
                if (count($filas) >= self::MAX_FILAS) { $trunc = true; break; }
                foreach ($r as $i => $v) if ($v !== null && is_string($v) && in_array($tipos[$i], ['NEWDECIMAL', 'DECIMAL', 'LONG', 'LONGLONG', 'SHORT', 'TINY', 'INT24', 'FLOAT', 'DOUBLE'], true) && is_numeric($v)) $r[$i] = $v + 0;
                $filas[] = $r;
            }
            $st->closeCursor();
        } catch (PDOException $e) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
            throw new RuntimeException('Error SQL: ' . preg_replace('/^SQLSTATE\[[^\]]*\]:?\s*/', '', $e->getMessage()));
        }
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
        return ['columnas' => $cols, 'filas' => $filas, 'truncado' => $trunc, 'ms' => (int)round((microtime(true) - $t0) * 1000), 'sql' => $sql];
    }

    /* ---------- Reglas y prompts ---------- */
    public static function reglas(): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        return "REGLAS:\n- Genera un único SELECT (o WITH ... SELECT) usando SOLO las vistas v_ia_*. Sin punto y coma, sin comentarios, sin variables.\n"
        . "- Hoy es " . $dias[(int)date('w')] . " " . date('Y-m-d') . " (usa CURDATE()/NOW() para fechas relativas). La semana empieza el lunes. «Este mes» = desde el día 1 del mes actual hasta hoy; «mes pasado» = mes calendario anterior.\n"
        . "- Ventas válidas: estado='pagado' (en v_ia_detalle_ventas y v_ia_pagos: estado_pedido='pagado'). Los pedidos 'abierto' están en curso y los 'anulado' no cuentan como venta.\n"
        . "- «Más vendidos» se mide por SUM(cantidad) salvo que pidan por monto. Productos/insumos vigentes: activo=1. Tipos de pedido: mesa, llevar (para llevar), delivery.\n"
        . "- «Pollos» o «pollo a la brasa» se refiere a productos de la categoría 'Pollos a la brasa' (1/4, 1/2, entero…). El consumo de pollo fresco está en v_ia_movimientos_insumos (insumo 'Pollo fresco', tipo salida).\n"
        . "- Para buscar por nombre usa LOWER(columna) LIKE '%texto%' en minúsculas.\n"
        . "- Moneda: " . cfg('moneda_simbolo', 'S/') . ". Redondea importes con ROUND(x,2). Pon alias de columnas legibles en español (sin espacios: usa guion bajo), por ejemplo producto, unidades_vendidas, total_ventas.\n"
        . "- Si piden un ranking sin cantidad, usa LIMIT 10; nunca más de " . self::MAX_FILAS . " filas. Ordena de forma útil.\n"
        . "- Si la pregunta no se puede responder con estas vistas, no inventes.";
    }

    public static function promptSistema(): string
    {
        return "Eres el analista de datos de una pollería / restaurante de pollos a la brasa con punto de venta (mesas, para llevar y delivery). Conviertes preguntas en español en UNA consulta SQL (MySQL/MariaDB) de solo lectura.\n\n"
        . "VISTAS DISPONIBLES (no existe nada más):\n" . self::esquemaTexto() . "\n"
        . self::reglas() . " Si no se puede responder, devuelve sql null y explica en «mensaje».\n\n"
        . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown:\n"
        . '{"titulo":"título corto del reporte","sql":"SELECT ...","grafico":{"tipo":"bar|line|pie|none","x":"alias_columna_etiquetas","y":"alias_columna_numerica"},"mensaje":null}' . "\n"
        . "grafico.tipo: line para series por fecha u hora, bar para rankings/comparaciones, pie para participación (máx. 8 categorías), none si no tiene sentido graficar.";
    }

    public static function preguntar(string $pregunta, ?array $previo = null): array
    {
        $m = [['role' => 'system', 'content' => self::promptSistema()]];
        if ($previo && !empty($previo['pregunta']) && !empty($previo['sql'])) {
            $m[] = ['role' => 'user', 'content' => mb_substr((string)$previo['pregunta'], 0, 300)];
            $m[] = ['role' => 'assistant', 'content' => json_encode(['titulo' => '', 'sql' => mb_substr((string)$previo['sql'], 0, 1500), 'grafico' => ['tipo' => 'none'], 'mensaje' => null], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $pregunta];
        $ultimoError = '';
        for ($intento = 0; $intento < 2; $intento++) {
            $raw = self::llamar($m);
            $j = self::extraerJson($raw);
            if (!$j) { $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 800)]; $m[] = ['role' => 'user', 'content' => 'Tu respuesta no era JSON válido. Responde SOLO el objeto JSON indicado.']; $ultimoError = 'La IA respondió en un formato inesperado.'; continue; }
            if (empty($j['sql'])) return ['mensaje' => (string)(($j['mensaje'] ?? '') ?: 'No puedo responder esa pregunta con los datos disponibles. Pruebe con ventas, platos, clientes, compras, gastos o inventario.')];
            try {
                $r = self::ejecutar((string)$j['sql']);
                $g = is_array($j['grafico'] ?? null) ? $j['grafico'] : [];
                return $r + ['titulo' => mb_substr(trim((string)($j['titulo'] ?? '')) ?: $pregunta, 0, 160), 'grafico' => ['tipo' => in_array($g['tipo'] ?? '', ['bar', 'line', 'pie', 'none'], true) ? $g['tipo'] : 'none', 'x' => (string)($g['x'] ?? ''), 'y' => (string)($g['y'] ?? '')]];
            } catch (Throwable $e) {
                $ultimoError = $e->getMessage();
                $m[] = ['role' => 'assistant', 'content' => $raw];
                $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $ultimoError . '. Corrígela usando solo las vistas y columnas listadas y responde de nuevo SOLO el JSON.'];
            }
        }
        throw new RuntimeException('No pude generar una consulta válida para esa pregunta. Reformúlela con más detalle. (' . $ultimoError . ')');
    }

    public static function resumir(string $pregunta, array $cols, array $filas): string
    {
        $muestra = array_slice($filas, 0, 25);
        $txt = self::llamar([
            ['role' => 'system', 'content' => 'Eres un analista de una pollería. Responde en español, en 1 o 2 frases claras, mencionando las cifras más relevantes. No uses listas, ni markdown, ni inventes datos que no estén en la tabla. Moneda: ' . cfg('moneda_simbolo', 'S/') . '.'],
            ['role' => 'user', 'content' => "Pregunta: $pregunta\nColumnas: " . implode(', ', $cols) . "\nFilas (" . count($filas) . " en total, se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
        ], 250);
        return trim(strip_tags($txt));
    }

    /* ---------- Chat ---------- */
    const GUIA = "Punto de Venta: cobrar pedidos de mesa, para llevar o delivery (antes hay que abrir caja en el módulo Caja); se puede cobrar con efectivo, tarjeta, Yape, Plin o transferencia, y emitir ticket, boleta o factura. Mesas: plano del salón, abrir pedido, reservar y códigos QR para que el cliente pida desde su celular (el mozo confirma los pedidos QR). Cocina: tablero de pedidos por preparar. Delivery: asignar repartidor y seguir entregas. Historial de ventas: reimprimir y anular. Caja: apertura, ingresos/egresos y cierre. Catálogo: Productos, Categorías, Clientes, Repartidores. Inventario: Insumos (kardex y ajustes), Recetas (cuánto insumo consume cada plato), Compras y Proveedores. Finanzas: Gastos, Reportes y Facturación electrónica SUNAT (modo simulado o Nubefact). Administración: Usuarios y roles, Configuración de la empresa (datos, logo, moneda, IGV, series), Copia de seguridad (crear, restaurar, resetear para empresa nueva), Asistente IA y Chat IA.";

    public static function promptChat(): string
    {
        return "Eres el chatbot de «" . cfg('empresa_nombre', 'la pollería') . "», un sistema de punto de venta para pollería. Conversas en español con el administrador, con tono cercano y breve.\n"
        . "Puedes: (1) consultar datos del negocio (ventas, platos, mesas, clientes, insumos, compras, gastos, cajas); (2) explicar cómo usar el sistema; (3) saludar o aclarar. NO puedes registrar, modificar ni borrar nada: si te lo piden, explica en qué módulo se hace.\n\n"
        . "VISTAS DISPONIBLES (no existe nada más):\n" . self::esquemaTexto() . "\n" . self::reglas() . "\n"
        . "- Para datos de un producto, cliente o proveedor concretos devuelve pocas columnas útiles y LIMIT 20.\n"
        . "- Si el mensaje es una continuación («¿y el mes pasado?», «¿y por delivery?»), reutiliza y adapta la consulta anterior.\n\n"
        . "GUÍA DEL SISTEMA: " . self::GUIA . "\n\n"
        . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown, en una de dos formas:\n"
        . '{"accion":"consulta","sql":"SELECT ..."}  → cuando necesites datos.' . "\n"
        . '{"accion":"respuesta","texto":"..."}  → cuando no necesites datos (saludo, ayuda de uso, aclaración, o si algo no es posible).';
    }

    /** Un turno de chat. $hist = [['q'=>..., 'sql'=>?, 'a'=>...], ...] */
    public static function chat(string $msg, array $hist): array
    {
        $m = [['role' => 'system', 'content' => self::promptChat()]];
        foreach (array_slice($hist, -6) as $h) {
            $m[] = ['role' => 'user', 'content' => (string)$h['q']];
            $m[] = ['role' => 'assistant', 'content' => json_encode(!empty($h['sql']) ? ['accion' => 'consulta', 'sql' => $h['sql']] : ['accion' => 'respuesta', 'texto' => mb_substr((string)$h['a'], 0, 300)], JSON_UNESCAPED_UNICODE)];
        }
        $m[] = ['role' => 'user', 'content' => $msg];
        $err = '';
        for ($i = 0; $i < 2; $i++) {
            $raw = self::llamar($m, 700); $j = self::extraerJson($raw);
            if (!$j) { $t = trim(strip_tags($raw)); if ($t !== '' && $t[0] !== '{') return ['texto' => $t]; $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 500)]; $m[] = ['role' => 'user', 'content' => 'Responde SOLO con el objeto JSON indicado.']; continue; }
            if (($j['accion'] ?? '') !== 'consulta' || empty($j['sql'])) return ['texto' => trim((string)($j['texto'] ?? $j['respuesta'] ?? '')) ?: 'No entendí bien. ¿Puede darme más detalle?'];
            try { $r = self::ejecutar((string)$j['sql']); }
            catch (Throwable $e) { $err = $e->getMessage(); $m[] = ['role' => 'assistant', 'content' => $raw]; $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $err . '. Corrígela usando solo las vistas y columnas listadas y responde SOLO el JSON.']; continue; }
            $muestra = array_slice($r['filas'], 0, 25);
            $texto = self::llamar([
                ['role' => 'system', 'content' => 'Eres el chatbot de una pollería. Responde en español con tono cercano y breve (máximo 4 frases o una lista corta con guiones). Usa las cifras EXACTAS de los datos, sin inventar nada. Importes con el símbolo ' . cfg('moneda_simbolo', 'S/') . ' (ejemplo de formato: ' . money(1234.5) . '). Si no hay filas, dilo claramente. No menciones SQL, vistas ni columnas técnicas.'],
                ['role' => 'user', 'content' => "Pregunta: $msg\nColumnas: " . implode(', ', $r['columnas']) . "\nFilas (" . count($r['filas']) . " en total" . ($r['truncado'] ? ', hay más' : '') . ", se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
            ], 450);
            return ['texto' => trim(strip_tags($texto)), 'sql' => $r['sql'], 'tabla' => ['columnas' => $r['columnas'], 'filas' => array_slice($r['filas'], 0, 10), 'total' => count($r['filas'])]];
        }
        throw new RuntimeException('No pude consultar eso con los datos disponibles. Pruebe reformulando la pregunta. (' . $err . ')');
    }
}
