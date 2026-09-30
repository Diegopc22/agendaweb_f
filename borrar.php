<?php
// ============================================================
// borrar.php · elimina un evento (solo acepta POST)
// Flujo: recibir id → validar → DELETE preparado → redirigir a index.php
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
  header('Location: index.php?error=1');
  exit;
}

require_once 'conexion.php';

try {
  $stmt = $mysqli->prepare("DELETE FROM eventos WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
  $mysqli->close();
} catch (mysqli_sql_exception $ex) {
  header('Location: index.php?error=1');
  exit;
}

header('Location: index.php?borrado=1');
exit;
