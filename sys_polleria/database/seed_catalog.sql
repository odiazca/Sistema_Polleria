-- Catálogo inicial para pollería (editable desde el sistema)
INSERT INTO categorias (id, nombre, icono, color, orden) VALUES
(1, 'Pollos a la brasa', 'bi-fire', '#F97316', 1),
(2, 'Combos y promociones', 'bi-stars', '#EAB308', 2),
(3, 'Parrillas y carnes', 'bi-egg-fried', '#EF4444', 3),
(4, 'Platos criollos', 'bi-cup-hot', '#14B8A6', 4),
(5, 'Guarniciones', 'bi-basket', '#84CC16', 5),
(6, 'Bebidas', 'bi-cup-straw', '#3B82F6', 6),
(7, 'Postres', 'bi-cake2', '#EC4899', 7);

INSERT INTO productos (categoria_id, codigo, nombre, descripcion, precio, costo, stock_control, stock, stock_minimo, para_cocina) VALUES
(1, 'P001', 'Pollo entero', 'Con papas fritas y ensalada', 68.00, 32.00, 0, 0, 0, 1),
(1, 'P002', '1/2 Pollo', 'Con papas fritas y ensalada', 36.00, 17.00, 0, 0, 0, 1),
(1, 'P003', '1/4 Pollo', 'Con papas fritas y ensalada', 19.00, 9.00, 0, 0, 0, 1),
(1, 'P004', '1/8 Pollo', 'Con papas fritas y ensalada', 11.00, 5.00, 0, 0, 0, 1),
(1, 'P005', 'Mostrito', '1/4 pollo, chaufa y papas', 24.00, 11.00, 0, 0, 0, 1),
(2, 'C001', 'Combo Familiar', '1 pollo + papas grandes + 1.5L gaseosa', 85.00, 42.00, 0, 0, 0, 1),
(2, 'C002', 'Combo Pareja', '1/2 pollo + papas + 2 chichas', 48.00, 23.00, 0, 0, 0, 1),
(2, 'C003', 'Combo Personal', '1/4 pollo + papas + gaseosa personal', 25.00, 12.00, 0, 0, 0, 1),
(2, 'C004', 'Combo Amigos', '2 pollos + papas + 2 gaseosas 1.5L', 150.00, 76.00, 0, 0, 0, 1),
(3, 'G001', 'Parrilla Mixta', 'Pollo, chorizo, anticucho y papas', 62.00, 30.00, 0, 0, 0, 1),
(3, 'G002', 'Anticuchos (2 palos)', 'Corazón de res con papas', 22.00, 10.00, 0, 0, 0, 1),
(3, 'G003', 'Chorizo parrillero', 'Con ensalada y papas', 16.00, 7.00, 0, 0, 0, 1),
(4, 'E001', 'Salchipollo', 'Papas, salchicha y pollo broaster', 18.00, 8.00, 0, 0, 0, 1),
(4, 'E002', 'Arroz chaufa de pollo', 'Clásico chaufa con pollo', 17.00, 7.50, 0, 0, 0, 1),
(4, 'E003', 'Chicharrón de pollo', 'Con camote y salsa criolla', 21.00, 9.50, 0, 0, 0, 1),
(4, 'E004', 'Alitas BBQ (8 und)', 'Con papas fritas', 24.00, 11.00, 0, 0, 0, 1),
(5, 'A001', 'Papas fritas porción', 'Porción grande', 10.00, 3.50, 0, 0, 0, 1),
(5, 'A002', 'Ensalada fresca', 'Lechuga, tomate y pepino', 8.00, 2.50, 0, 0, 0, 1),
(5, 'A003', 'Ensalada de palta', '', 12.00, 5.00, 0, 0, 0, 1),
(5, 'A004', 'Cremas extras (3)', 'Ají, mayonesa, mostaza', 3.00, 0.80, 0, 0, 0, 1),
(6, 'B001', 'Inca Kola 1.5 L', '', 10.00, 6.50, 1, 48, 12, 0),
(6, 'B002', 'Coca Cola 1.5 L', '', 10.00, 6.50, 1, 48, 12, 0),
(6, 'B003', 'Inca Kola personal', '', 4.00, 2.20, 1, 60, 15, 0),
(6, 'B004', 'Chicha morada (jarra 1L)', '', 12.00, 3.00, 0, 0, 0, 0),
(6, 'B005', 'Limonada frozen', '', 9.00, 2.50, 0, 0, 0, 0),
(6, 'B006', 'Agua mineral 625 ml', '', 3.00, 1.40, 1, 60, 15, 0),
(6, 'B007', 'Cerveza Cusqueña 620 ml', '', 11.00, 6.50, 1, 36, 12, 0),
(7, 'D001', 'Gelatina', '', 4.00, 1.20, 0, 0, 0, 0),
(7, 'D002', 'Flan de vainilla', '', 6.00, 2.00, 0, 0, 0, 0),
(7, 'D003', 'Mazamorra morada', '', 5.00, 1.50, 0, 0, 0, 0);

INSERT INTO mesas (nombre, zona, capacidad) VALUES
('Mesa 1', 'Salón', 4), ('Mesa 2', 'Salón', 4), ('Mesa 3', 'Salón', 4), ('Mesa 4', 'Salón', 6),
('Mesa 5', 'Salón', 6), ('Mesa 6', 'Salón', 2), ('Mesa 7', 'Terraza', 4), ('Mesa 8', 'Terraza', 4),
('Mesa 9', 'Terraza', 6), ('Mesa 10', 'Terraza', 8);

INSERT INTO insumos (nombre, unidad, stock, stock_minimo, costo) VALUES
('Pollo fresco', 'kg', 80, 30, 8.50),
('Papa amarilla', 'kg', 120, 40, 2.20),
('Aceite vegetal', 'lt', 40, 15, 7.00),
('Carbón', 'saco', 12, 5, 18.00),
('Arroz', 'kg', 50, 20, 3.80),
('Lechuga', 'kg', 10, 4, 3.00),
('Ají amarillo', 'kg', 8, 3, 6.00),
('Bolsas / empaques', 'und', 400, 150, 0.30);

INSERT INTO repartidores (nombre, telefono, vehiculo, placa) VALUES
('Carlos Mendoza', '987 654 321', 'Moto', 'M1A-234'),
('Luis Quispe', '986 111 222', 'Moto', 'M2B-518');

INSERT INTO recetas (producto_id, insumo_id, cantidad)
SELECT p.id, i.id, x.c FROM (
  SELECT 'P001' cod, 'Pollo fresco' ins, 1.6 c UNION ALL SELECT 'P001','Papa amarilla',0.8 UNION ALL SELECT 'P001','Aceite vegetal',0.1 UNION ALL SELECT 'P001','Carbón',0.05 UNION ALL SELECT 'P001','Lechuga',0.1 UNION ALL SELECT 'P001','Ají amarillo',0.05
  UNION ALL SELECT 'P002','Pollo fresco',0.8 UNION ALL SELECT 'P002','Papa amarilla',0.4 UNION ALL SELECT 'P002','Aceite vegetal',0.05 UNION ALL SELECT 'P002','Carbón',0.025 UNION ALL SELECT 'P002','Lechuga',0.05
  UNION ALL SELECT 'P003','Pollo fresco',0.4 UNION ALL SELECT 'P003','Papa amarilla',0.25 UNION ALL SELECT 'P003','Aceite vegetal',0.03 UNION ALL SELECT 'P003','Carbón',0.0125 UNION ALL SELECT 'P003','Lechuga',0.03
  UNION ALL SELECT 'P004','Pollo fresco',0.2 UNION ALL SELECT 'P004','Papa amarilla',0.15 UNION ALL SELECT 'P004','Carbón',0.00625
  UNION ALL SELECT 'P005','Pollo fresco',0.4 UNION ALL SELECT 'P005','Arroz',0.15 UNION ALL SELECT 'P005','Papa amarilla',0.2 UNION ALL SELECT 'P005','Aceite vegetal',0.04
  UNION ALL SELECT 'E001','Pollo fresco',0.25 UNION ALL SELECT 'E001','Papa amarilla',0.3 UNION ALL SELECT 'E001','Aceite vegetal',0.08
  UNION ALL SELECT 'E002','Arroz',0.25 UNION ALL SELECT 'E002','Pollo fresco',0.15 UNION ALL SELECT 'E002','Aceite vegetal',0.03
  UNION ALL SELECT 'A001','Papa amarilla',0.35 UNION ALL SELECT 'A001','Aceite vegetal',0.06
  UNION ALL SELECT 'A002','Lechuga',0.12
) x JOIN productos p ON p.codigo = x.cod JOIN insumos i ON i.nombre = x.ins;
