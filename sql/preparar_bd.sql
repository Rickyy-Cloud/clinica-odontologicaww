-- Ejecutar en phpMyAdmin sobre la base de datos clinica_odontologica.
-- No modifica nombres de tablas ni columnas: solo asegura que `password` pueda guardar un hash seguro
-- y deja un usuario administrador de prueba.

USE clinica_odontologica;

-- 1) El hash de password_hash() ocupa 60 caracteres: la columna debe ser al menos VARCHAR(255)
ALTER TABLE usuarios MODIFY password VARCHAR(255) NOT NULL;

-- 2) Usuario de prueba (clave: Admin2026*). CÁMBIALA después de ingresar.
--    Si ya tienes un usuario en la tabla, puedes saltarte este paso: el login acepta
--    claves en texto plano y las convierte a hash en el primer ingreso.
INSERT INTO usuarios (usuario, password, rol)
VALUES ('admin', '$2y$10$00JFJWCbi7cBEydSU9r3X.OMk9uvQ3.vV2ReJ.OwiF.kqZiAjgHnm', 'admin');

-- 3) Procedimientos iniciales (omite los que ya tengas; en tu captura aparecen 5 filas)
-- INSERT INTO procedimientos (nombre_procedimiento, descripcion, costo) VALUES
--   ('Limpieza dental', 'Profilaxis y remoción de sarro', 30.00),
--   ('Blanqueamiento dental', 'Blanqueamiento de la dentadura', 120.00),
--   ('Extracción de muelas o cordales', 'Extracción de molares y terceros molares', 50.00),
--   ('Tratamiento de conductos', 'Endodoncia', 150.00),
--   ('Calzas de muelas', 'Restauración con resina', 40.00);
