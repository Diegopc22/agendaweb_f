<?php
function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

function nombreCategoria(string $clave): string
{
    $nombres = [
        'trabajo'  => 'Trabajo',
        'personal' => 'Personal',
        'estudio'  => 'Estudio',
        'ocio'     => 'Ocio / Deporte',
    ];
    return $nombres[$clave] ?? $clave;
}

function mostrarEvento(array $ev): string
{
    $html  = '<article class="card">';
    $html .= '<span class="card__badge">' . e(nombreCategoria($ev['categoria'])) . '</span>';
    $html .= '<h2 class="card__title">' . e($ev['titulo']) . '</h2>';

    $cuando   = formatearFecha($ev['fecha']);
    $datetime = $ev['fecha'];
    if ($ev['hora']) {                                   
        $hora      = substr($ev['hora'], 0, 5);          
        $cuando   .= ' · ' . $hora;
        $datetime .= 'T' . $hora;
    }
    $html .= '<p class="card__meta"><time datetime="' . e($datetime) . '">' . e($cuando) . '</time></p>';

    if ($ev['descripcion']) {                            
        $html .= '<p class="card__text">' . e($ev['descripcion']) . '</p>';
    }

    $id = (int) $ev['id'];                               
    $html .= '<div class="card__actions">'
           . '<a href="editar.php?id=' . $id . '" class="btn-secondary btn-sm">Editar</a>'
           . '<form method="post" action="borrar.php" class="form-inline" '
           . 'onsubmit="return confirm(\'¿Borrar este evento? Esta acción no se puede deshacer.\');">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
           . '</form></div>';

    return $html . '</article>';
}

require_once 'conexion.php';
try {
  $resultado = $mysqli->query(
    'SELECT id, titulo, fecha, hora, categoria, descripcion
       FROM eventos
      ORDER BY fecha, hora'
  );
  $eventos = $resultado->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $ex) {
  $eventos = [];
}
$mysqli->close();

$total = count($eventos);

// ?ok=1 (guardado), ?editado=1, ?borrado=1, ?error=1
$aviso = null; $avisoError = false;
if (($_GET['ok'] ?? '') === '1')           $aviso = 'Evento guardado.';
elseif (($_GET['editado'] ?? '') === '1')  $aviso = 'Evento actualizado.';
elseif (($_GET['borrado'] ?? '') === '1')  $aviso = 'Evento eliminado.';
elseif (($_GET['error'] ?? '') === '1')  { $aviso = 'No se pudo completar la acción.'; $avisoError = true; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mis eventos · AgendaWeb</title>

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

    <?php if ($aviso): ?>
    <div class="alert <?= $avisoError ? 'alert--error' : 'alert--ok' ?>" role="<?= $avisoError ? 'alert' : 'status' ?>"><?= $avisoError ? '' : '&#9989; ' ?><?= e($aviso) ?></div>
    <?php endif; ?>

    <div class="page__header">
      <div>
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle"><?= $total ?> <?= $total === 1 ? 'evento registrado' : 'eventos registrados' ?></p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <?php if (empty($eventos)): ?>
    <div class="empty-state">
      <p>Aún no tienes eventos registrados.</p>
      <a href="registrar.php" class="btn-primary">Registrar el primero</a>
    </div>
    <?php else: ?>
    <section class="card-list" aria-label="Lista de eventos">
      <?php foreach ($eventos as $ev): ?>
        <?= mostrarEvento($ev) ?>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

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