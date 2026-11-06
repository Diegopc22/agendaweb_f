-- ============================================================
-- migracion_p8.sql · cuentas de usuario y eventos con dueño
-- Ejecuta un bloque a la vez y revisa el resultado antes de seguir.
-- LOCAL: antes escribe  USE agenda;   |   DOMCLOUD: selecciona tu base en phpMyAdmin (sin USE)
-- ============================================================

-- ---------- Paso 1 · Respaldo y tabla de usuarios ----------
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM usuarios;

-- ------------------------------------------------------------
-- ANTES DE SEGUIR: abre registro.php y crea TU cuenta.
-- Luego averigua tu id con:  SELECT id, correo FROM usuarios;
-- Cambia el 1 de los UPDATE por ESE id.
-- ------------------------------------------------------------

-- ---------- Paso 2 · Cada evento con dueño ----------
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

UPDATE eventos SET usuario_id = 1 WHERE id > 0;   -- cambia 1 por tu id

-- ¿Quedó alguno sin dueño? Debe salir VACÍO
SELECT id, titulo FROM eventos WHERE usuario_id IS NULL;

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
      ON DELETE CASCADE;

DESCRIBE eventos;

-- ---------- Al terminar y comprobar que todo funciona ----------
-- DROP TABLE eventos_respaldo_p8;
