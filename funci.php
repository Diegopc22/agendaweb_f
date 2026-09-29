<?php
$conexion = new mysqli("localhost", "root", "123456", "agenda");
if ($conexion->connect_error) { die("Error de conexión"); }
$conexion->set_charset("utf8mb4");
?>