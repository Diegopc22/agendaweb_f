<?php
function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

date_default_timezone_set('America/Mexico_City');
$hoyServidor   = date('Y-m-d');
$ahoraServidor = date('H:i');

$categoriasOK = ['trabajo' => 'Trabajo', 'personal' => 'Personal', 'estudio' => 'Estudio', 'ocio' => 'Ocio'];
$errores = [];

$id = filter_var($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? ''), FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
  header('Location: index.php?error=1');
  exit;
}

require_once 'conexion.php';

$stmt = $mysqli->prepare("SELECT titulo, fecha, hora, categoria, descripcion FROM eventos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$orig = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orig) {
  header('Location: index.php?error=1');
  exit;
}
$fechaOrig = (string)$orig['fecha'];
$horaOrig  = $orig['hora'] ? substr($orig['hora'], 0, 5) : '';

$titulo      = $orig['titulo'];
$fecha       = $fechaOrig;
$hora        = $horaOrig;
$categoria   = $orig['categoria'];
$descripcion = (string)$orig['descripcion'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo      = trim($_POST['titulo']      ?? '');
  $fecha       = trim($_POST['fecha']       ?? '');
  $hora        = trim($_POST['hora']        ?? '');
  $categoria   = trim($_POST['categoria']   ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');

  if ($titulo === '')
    $errores['titulo'] = 'El título es obligatorio.';
  elseif (mb_strlen($titulo) > 120)
    $errores['titulo'] = 'Máximo 120 caracteres.';

  if ($fecha === '')
    $errores['fecha'] = 'La fecha es obligatoria.';
  else {
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$f || $f->format('Y-m-d') !== $fecha)
      $errores['fecha'] = 'La fecha no es válida.';
    elseif ($fecha !== $fechaOrig && $fecha < $hoyServidor)
      $errores['fecha'] = 'La fecha no puede ser anterior a hoy.';
  }

  if ($hora !== '') {
    $h = DateTime::createFromFormat('H:i', $hora);
    if (!$h || $h->format('H:i') !== $hora)
      $errores['hora'] = 'La hora no es válida.';
    elseif (($fecha !== $fechaOrig || $hora !== $horaOrig) && $fecha === $hoyServidor && $hora < $ahoraServidor)
      $errores['hora'] = 'La hora ya pasó. Elige una a partir de las ' . $ahoraServidor . '.';
  }

  if (!array_key_exists($categoria, $categoriasOK))
    $errores['categoria'] = 'Elige una categoría válida.';

  if (mb_strlen($descripcion) > 500)
    $errores['descripcion'] = 'Máximo 500 caracteres.';

  if (empty($errores)) {
    $horaDB = ($hora === '') ? null : $hora;
    $descDB = ($descripcion === '') ? null : $descripcion;
    try {
      $stmt = $mysqli->prepare("UPDATE eventos
                                SET titulo = ?, fecha = ?, hora = ?, categoria = ?, descripcion = ?
                                WHERE id = ?");
      $stmt->bind_param("sssssi", $titulo, $fecha, $horaDB, $categoria, $descDB, $id);
      $stmt->execute();
      $stmt->close();
      $mysqli->close();

      header('Location: index.php?editado=1');
      exit;
    } catch (mysqli_sql_exception $ex) {
      $errores['general'] = 'No se pudo actualizar el evento. Intenta de nuevo.';
    }
  }
}
$fechaMin = ($fechaOrig !== '' && $fechaOrig < $hoyServidor) ? $fechaOrig : $hoyServidor;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar evento · AgendaWeb</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    (function () {
      var t = null;
      try { t = localStorage.getItem("agendaweb:tema"); } catch (e) {}
      if (!t) t = window.matchMedia && matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
      document.documentElement.setAttribute("data-theme", t);
    })();
  </script>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="layout">

  <header class="site-header">
    <div class="contenedor site-header__inner">
      <a href="index.php" class="logo">Agenda<span>Web</span></a>
      <div class="header__right">
        <nav class="nav">
          <a href="index.php" class="nav__link is-active">Mis eventos</a>
          <a href="registrar.php" class="nav__link">Nuevo evento</a>
        </nav>
        <button class="btn-tema" id="btnTema" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
          <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
          <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>
      </div>
    </div>
  </header>

  <main class="contenedor">
    <div class="page__header">
      <div>
        <h1 class="page__title">Editar evento</h1>
        <p class="page__subtitle">Cambia los datos y guarda.</p>
      </div>
    </div>

    <section class="tarjeta form-wrap" aria-labelledby="titulo-form">
      <h2 id="titulo-form">Datos del evento</h2>

      <?php if (isset($errores['general'])): ?>
        <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
      <?php elseif (!empty($errores)): ?>
        <div class="alert alert--error" role="alert">Revisa los campos marcados.</div>
      <?php endif; ?>

      <form method="post" action="editar.php?id=<?= (int)$id ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="campo <?= isset($errores['titulo']) ? 'campo--error' : '' ?>">
          <label for="titulo">Título</label>
          <input id="titulo" name="titulo" type="text" maxlength="120" value="<?= e($titulo) ?>" required>
          <?php if (isset($errores['titulo'])): ?><p class="campo__error"><?= e($errores['titulo']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['categoria']) ? 'campo--error' : '' ?>">
          <label for="categoria">Categoría</label>
          <select id="categoria" name="categoria" required>
            <option value="">Elige una categoría…</option>
            <?php foreach ($categoriasOK as $valor => $texto): ?>
              <option value="<?= e($valor) ?>" <?= $categoria === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['categoria'])): ?><p class="campo__error"><?= e($errores['categoria']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['fecha']) ? 'campo--error' : '' ?>">
          <label for="fecha">Fecha</label>
          <input id="fecha" name="fecha" type="date" min="<?= e($fechaMin) ?>" value="<?= e($fecha) ?>" required>
          <?php if (isset($errores['fecha'])): ?><p class="campo__error"><?= e($errores['fecha']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['hora']) ? 'campo--error' : '' ?>">
          <label for="hora">Hora (opcional)</label>
          <input id="hora" name="hora" type="time" value="<?= e($hora) ?>">
          <?php if (isset($errores['hora'])): ?><p class="campo__error"><?= e($errores['hora']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['descripcion']) ? 'campo--error' : '' ?>">
          <label for="descripcion">Descripción (opcional)</label>
          <textarea id="descripcion" name="descripcion" maxlength="500"><?= e($descripcion) ?></textarea>
          <?php if (isset($errores['descripcion'])): ?><p class="campo__error"><?= e($errores['descripcion']) ?></p><?php endif; ?>
        </div>

        <div class="acciones">
          <button class="boton boton--bloque" type="submit">Guardar cambios</button>
          <a class="boton boton--bloque boton--secundario" href="index.php">Cancelar</a>
        </div>
      </form>
    </section>
  </main>

  <footer class="site-footer">
    AgendaWeb · Diego Plascencia Camarena · 2026
  </footer>

  <script>
    (function () {
      var btn = document.getElementById("btnTema");
      function etiqueta() {
        var oscuro = document.documentElement.dataset.theme === "dark";
        btn.setAttribute("aria-label", oscuro ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
      }
      btn.addEventListener("click", function () {
        var nuevo = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        document.documentElement.dataset.theme = nuevo;
        try { localStorage.setItem("agendaweb:tema", nuevo); } catch (e) {}
        etiqueta();
      });
      etiqueta();
    })();
  </script>
</body>
</html>
