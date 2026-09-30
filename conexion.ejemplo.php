<?php
// Copia este archivo como conexion.php y pon tus datos reales.
// conexion.php NO se sube a Git (está en .gitignore).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $mysqli = new mysqli("localhost", "USUARIO", "CONTRASEÑA", "agenda");
  $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  die("No se pudo conectar a la base de datos.");
}
