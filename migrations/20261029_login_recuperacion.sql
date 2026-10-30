-- Ejecutar una sola vez en cada base de datos del proyecto.
-- contrasena almacena hashes generados con password_hash().
-- token_recuperacion almacena el SHA-256 del token enviado por correo.

ALTER TABLE usuario
    ADD COLUMN contrasena VARCHAR(255) NULL AFTER correo,
    ADD COLUMN token_recuperacion CHAR(64) NULL,
    ADD COLUMN expiracion_token DATETIME NULL,
    ADD INDEX idx_usuario_token_recuperacion (token_recuperacion);
