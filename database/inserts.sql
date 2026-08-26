-- ============================
-- CARGA INICIAL: ZONAS COMUNES
-- ============================
INSERT INTO zona_comun (nombre, descripcion, capacidad) VALUES 
('Salón Social', 'Espacio para eventos privados, incluye mesas, sillas y cocineta.', 50),
('Gimnasio', 'Equipado con máquinas de cardio y pesas. Uso exclusivo residentes.', 15),
('Piscina de Adultos', 'Piscina climatizada. Se requiere traje de baño adecuado.', 30),
('Piscina de Niños', 'Piscina de baja profundidad con supervisión requerida.', 12),
('Cancha Sintética', 'Cancha de fútbol 5 con iluminación nocturna.', 10),
('Zona BBQ', 'Kiosko con parrilla a carbón y lavaplatos.', 8),
('Sala de Cine', 'Proyector HD y sonido envolvente. Reservar con antelación.', 12),
('Parque Infantil', 'Juegos de madera y arenero para menores de 12 años.', 25),
('Coworking', 'Sala con Wi-Fi de alta velocidad y conexiones eléctricas.', 10),
('Sauna', 'Zona húmeda para relajación. Uso máximo 30 min por persona.', 6);

-- ============================
-- CARGA INICIAL: HORARIOS
-- ============================
INSERT INTO horario_zona (id_zona, dia_semana, hora_inicio, hora_fin) VALUES

-- 1. Salón Social
(1, 1, '08:00:00', '22:00:00'),
(1, 2, '08:00:00', '22:00:00'),
(1, 3, '08:00:00', '22:00:00'),
(1, 4, '08:00:00', '22:00:00'),
(1, 5, '08:00:00', '23:00:00'),
(1, 6, '09:00:00', '23:00:00'),
(1, 7, '09:00:00', '18:00:00'),

-- 2. Gimnasio
(2, 1, '05:00:00', '22:00:00'),
(2, 2, '05:00:00', '22:00:00'),
(2, 3, '05:00:00', '22:00:00'),
(2, 4, '05:00:00', '22:00:00'),
(2, 5, '05:00:00', '22:00:00'),
(2, 6, '07:00:00', '20:00:00'),
(2, 7, '07:00:00', '14:00:00'),

-- 3. Piscina de Adultos
(3, 1, '06:00:00', '21:00:00'),
(3, 2, '06:00:00', '21:00:00'),
(3, 3, '06:00:00', '21:00:00'),
(3, 4, '06:00:00', '21:00:00'),
(3, 5, '06:00:00', '21:00:00'),
(3, 6, '08:00:00', '20:00:00'),
(3, 7, '08:00:00', '18:00:00'),

-- 4. Piscina de Niños
(4, 1, '08:00:00', '18:00:00'),
(4, 2, '08:00:00', '18:00:00'),
(4, 3, '08:00:00', '18:00:00'),
(4, 4, '08:00:00', '18:00:00'),
(4, 5, '08:00:00', '18:00:00'),
(4, 6, '09:00:00', '18:00:00'),
(4, 7, '09:00:00', '17:00:00'),

-- 5. Cancha Sintética
(5, 1, '16:00:00', '22:00:00'),
(5, 2, '16:00:00', '22:00:00'),
(5, 3, '16:00:00', '22:00:00'),
(5, 4, '16:00:00', '22:00:00'),
(5, 5, '16:00:00', '23:00:00'),
(5, 6, '08:00:00', '23:00:00'),
(5, 7, '08:00:00', '20:00:00'),

-- 6. Zona BBQ
(6, 1, '10:00:00', '20:00:00'),
(6, 2, '10:00:00', '20:00:00'),
(6, 3, '10:00:00', '20:00:00'),
(6, 4, '10:00:00', '20:00:00'),
(6, 5, '10:00:00', '22:00:00'),
(6, 6, '09:00:00', '22:00:00'),
(6, 7, '09:00:00', '18:00:00'),

-- 7. Sala de Cine
(7, 1, '14:00:00', '22:00:00'),
(7, 2, '14:00:00', '22:00:00'),
(7, 3, '14:00:00', '22:00:00'),
(7, 4, '14:00:00', '22:00:00'),
(7, 5, '14:00:00', '23:00:00'),
(7, 6, '10:00:00', '23:00:00'),
(7, 7, '10:00:00', '20:00:00'),

-- 8. Parque Infantil
(8, 1, '08:00:00', '19:00:00'),
(8, 2, '08:00:00', '19:00:00'),
(8, 3, '08:00:00', '19:00:00'),
(8, 4, '08:00:00', '19:00:00'),
(8, 5, '08:00:00', '19:00:00'),
(8, 6, '08:00:00', '20:00:00'),
(8, 7, '08:00:00', '18:00:00'),

-- 9. Coworking
(9, 1, '07:00:00', '20:00:00'),
(9, 2, '07:00:00', '20:00:00'),
(9, 3, '07:00:00', '20:00:00'),
(9, 4, '07:00:00', '20:00:00'),
(9, 5, '07:00:00', '20:00:00'),
(9, 6, '08:00:00', '14:00:00'),

-- 10. Sauna
(10, 1, '10:00:00', '20:00:00'),
(10, 2, '10:00:00', '20:00:00'),
(10, 3, '10:00:00', '20:00:00'),
(10, 4, '10:00:00', '20:00:00'),
(10, 5, '10:00:00', '22:00:00'),
(10, 6, '09:00:00', '18:00:00');

-- ====================
-- CARGA INICIAL: ROLES
-- ====================
INSERT INTO rol (nombre) VALUES 
('Administrador'),
('Residente'),
('Propietario'),
('Vigilante');

-- =======================
-- CARGA INICIAL: USUARIOS
-- =======================
INSERT INTO usuario (nombre, apellido, correo, telefono, contrasena, rol_id, estado) VALUES 
-- Administrador: Acceso total
('Ana', 'Martinez', 'admin@convivium.com', '3001112233', 'admin2026', 1, TRUE),

-- Residente: Solo vive allí (arrendatario, por ejemplo)
('Luis', 'Torres', 'residente@gmail.com', '3114445566', 'residente123', 2, TRUE),

-- Propietario: Dueño del inmueble
('Elena', 'Rojas', 'propietario@gmail.com', '3227778899', 'propiedad456', 3, TRUE),

-- Vigilante: Acceso a portería y seguridad
('Marcos', 'Peña', 'vigilancia@convivium.com', '3150009988', 'seguridad789', 4, TRUE);

