-- ============================================================
-- migracion_p5.sql · normalizar categorías SIN perder datos
-- Ejecuta un bloque a la vez y revisa el resultado antes de seguir.
-- LOCAL: antes escribe  USE agenda;   |   DOMCLOUD: selecciona tu base en phpMyAdmin (sin USE)
-- ============================================================

-- ---------- Paso 0 · Respaldo ----------
CREATE TABLE eventos_respaldo AS SELECT * FROM eventos;

-- ---------- Paso 1 · Tabla de categorías ----------
-- (Solo LOCAL: si ya existía una tabla categorias vieja y vacía de sentido, bórrala antes:
--    DROP TABLE categorias;   )
CREATE TABLE categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'),
  (2, 'Personal'),
  (3, 'Estudio'),
  (4, 'Ocio / Deporte');

SELECT * FROM categorias;

-- ---------- Paso 2 · Migrar los eventos ----------
-- a) Nueva columna, vacía por ahora
ALTER TABLE eventos ADD COLUMN categoria_id INT NULL AFTER categoria;

-- b) Traducir el texto viejo a su número
UPDATE eventos
   SET categoria_id = CASE categoria
         WHEN 'trabajo'  THEN 1
         WHEN 'personal' THEN 2
         WHEN 'estudio'  THEN 3
         WHEN 'ocio'     THEN 4
       END
 WHERE id > 0;

-- c) ¿Quedó alguno sin traducir? Debe salir VACÍO
SELECT id, titulo, categoria FROM eventos WHERE categoria_id IS NULL;
-- Si sale alguno, asígnale una a mano y repite c):
--   UPDATE eventos SET categoria_id = 2 WHERE id = 7;

-- d) Obligatoria y conectada con categorias (llave foránea) — solo cuando c) salga vacío
ALTER TABLE eventos
  MODIFY categoria_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_categoria
      FOREIGN KEY (categoria_id) REFERENCES categorias(id)
      ON DELETE RESTRICT;

-- e) La columna de texto ya no hace falta
ALTER TABLE eventos DROP COLUMN categoria;

DESCRIBE eventos;

-- ---------- Al terminar y comprobar que todo funciona ----------
-- DROP TABLE eventos_respaldo;
