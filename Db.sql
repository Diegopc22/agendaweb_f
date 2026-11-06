-- ============================================================
-- db.sql · esquema COMPLETO desde cero (P3 + P5 + P8)
-- Tablas: categorias, usuarios, eventos
--
-- LOCAL:    ejecuta todo, incluidas las dos primeras líneas.
-- DOMCLOUD: NO uses CREATE DATABASE ni USE. Selecciona tu base en
--           phpMyAdmin y pega desde "DROP TABLE IF EXISTS eventos;"
-- ============================================================
CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4;
USE agenda;

-- 1) Borrar lo anterior (primero eventos, porque depende de las otras)
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS eventos_respaldo;
DROP TABLE IF EXISTS eventos_respaldo_p8;

-- 2) Categorías
CREATE TABLE categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
) CHARACTER SET utf8mb4;

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'), (2, 'Personal'), (3, 'Estudio'), (4, 'Ocio / Deporte');

-- 3) Usuarios
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4;

-- 4) Eventos: cada uno con dueño (usuario) y categoría
CREATE TABLE eventos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT          NOT NULL,
  titulo       VARCHAR(120) NOT NULL,
  fecha        DATE         NOT NULL,
  hora         TIME         NULL,
  categoria_id INT          NOT NULL,
  descripcion  VARCHAR(500) NULL,
  creado_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eventos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_eventos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE RESTRICT
) CHARACTER SET utf8mb4;
