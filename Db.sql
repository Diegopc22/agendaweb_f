-- db.sql · esquema desde CERO (si ya tienes datos, usa migracion_p5.sql)
-- En DomCloud NO uses las dos primeras líneas (CREATE DATABASE / USE):
-- selecciona tu base en phpMyAdmin y ejecuta desde "CREATE TABLE IF NOT EXISTS categorias".
CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4;
USE agenda;

CREATE TABLE IF NOT EXISTS categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
) CHARACTER SET utf8mb4;

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'), (2, 'Personal'), (3, 'Estudio'), (4, 'Ocio / Deporte');

CREATE TABLE IF NOT EXISTS eventos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  titulo       VARCHAR(120) NOT NULL,
  fecha        DATE         NOT NULL,
  hora         TIME         NULL,
  categoria_id INT          NOT NULL,
  descripcion  VARCHAR(500) NULL,
  creado_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eventos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE RESTRICT
) CHARACTER SET utf8mb4;