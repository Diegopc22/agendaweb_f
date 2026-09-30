<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli("localhost", "enchanted_agency_juf_enchanted_agency_juf_", "1x(77Q1x)kYNG6C(yp", "enchanted_agency_juf_enchanted_agency_juf_");
    $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("No se pudo conectar a la base de datos.");
}