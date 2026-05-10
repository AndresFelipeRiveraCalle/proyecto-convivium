-- ============================
-- CARGA INICIAL: ZONAS COMUNES
-- ============================
INSERT INTO zona_comun (nombre, descripcion, capacidad, horario_disponible) VALUES 
('Salón Social', 'Espacio para eventos privados, incluye mesas, sillas y cocineta.', 50, '08:00 - 22:00'),
('Gimnasio', 'Equipado con máquinas de cardio y pesas. Uso exclusivo residentes.', 15, '05:00 - 23:00'),
('Piscina de Adultos', 'Piscina climatizada. Se requiere traje de baño adecuado.', 30, '09:00 - 19:00'),
('Piscina de Niños', 'Piscina de baja profundidad con supervisión requerida.', 12, '09:00 - 19:00'),
('Cancha Sintética', 'Cancha de fútbol 5 con iluminación nocturna.', 10, '06:00 - 21:00'),
('Zona BBQ', 'Kiosko con parrilla a carbón y lavaplatos.', 8, '10:00 - 20:00'),
('Sala de Cine', 'Proyector HD y sonido envolvente. Reservar con antelación.', 12, '14:00 - 22:00'),
('Parque Infantil', 'Juegos de madera y arenero para menores de 12 años.', 25, '07:00 - 19:00'),
('Coworking', 'Sala con Wi-Fi de alta velocidad y conexiones eléctricas.', 10, '07:00 - 21:00'),
('Sauna', 'Zona húmeda para relajación. Uso máximo 30 min por persona.', 6, '16:00 - 21:00');

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

