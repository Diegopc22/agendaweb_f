<?php
// ============================================================
// index.php · tablero de eventos (P4: arreglos y funciones)
// ============================================================
require __DIR__ . '/partes/sesion.php';

// ---------- Funciones de ayuda ----------

// Limpia un texto antes de mostrarlo (evita XSS)
function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

// '2026-10-08'  →  '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

// Recibe UN evento (arreglo asociativo) y devuelve el HTML de su tarjeta
function mostrarEvento(array $ev): string
{
    $html  = '<article class="card">';
    $html .= '<span class="card__badge">' . e($ev['categoria']) . '</span>';
    $html .= '<h2 class="card__title">' . e($ev['titulo']) . '</h2>';

    $cuando   = formatearFecha($ev['fecha']);
    $datetime = $ev['fecha'];
    if ($ev['hora']) {                                   // la hora es opcional
        $hora      = substr($ev['hora'], 0, 5);          // '10:30:00' → '10:30'
        $cuando   .= ' · ' . $hora;
        $datetime .= 'T' . $hora;
    }
    $html .= '<p class="card__meta"><time datetime="' . e($datetime) . '">' . e($cuando) . '</time></p>';

    if ($ev['descripcion']) {                            // la descripción también
        $html .= '<p class="card__text">' . e($ev['descripcion']) . '</p>';
    }

    $id = (int) $ev['id'];                               // el id SIEMPRE como número
    $html .= '<div class="card__actions">'
           . '<a href="editar.php?id=' . $id . '" class="btn-secondary btn-sm">Editar</a>'
           . '<form method="post" action="borrar.php" class="form-inline" '
           . 'onsubmit="return confirm(\'¿Borrar este evento? Esta acción no se puede deshacer.\');">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
           . '</form></div>';

    return $html . '</article>';
}

// ---------- Eventos desde MySQL (solo los del usuario) ----------
// ---------- Búsqueda (P9) ----------
$buscar          = trim($_GET['buscar'] ?? '');          // texto a buscar en el título
$categoriaFiltro = (int) ($_GET['categoria'] ?? 0);      // 0 = todas las categorías
$hayFiltro       = ($buscar !== '' || $categoriaFiltro > 0);

require_once 'conexion.php';
try {
  // Siempre solo los eventos del usuario; los filtros se agregan SOLO si hay búsqueda
  $sql   = 'SELECT e.id, e.titulo, e.fecha, e.hora, e.descripcion,
                   c.nombre AS categoria
              FROM eventos e
              JOIN categorias c ON c.id = e.categoria_id
             WHERE e.usuario_id = ?';
  $tipos = 'i';
  $datos = [$usuarioId];

  if ($buscar !== '') {
    $sql   .= ' AND e.titulo LIKE ?';
    $tipos .= 's';
    // El % va en la VARIABLE, no pegado en el SQL. addcslashes() hace que un % o _
    // escrito por el usuario se busque como letra normal y no como comodín.
    $datos[] = '%' . addcslashes($buscar, '%_\\') . '%';
  }
  if ($categoriaFiltro > 0) {
    $sql   .= ' AND e.categoria_id = ?';
    $tipos .= 'i';
    $datos[] = $categoriaFiltro;
  }
  $sql .= ' ORDER BY e.fecha, e.hora';

  // JOIN: une cada evento con su categoría para traer el NOMBRE
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param($tipos, ...$datos);
  $stmt->execute();
  $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);   // arreglo de arreglos asociativos
  $stmt->close();

  // GROUP BY: cuántos eventos tiene cada categoría (LEFT JOIN: incluye las de 0)
  // El filtro va en el ON para que también aparezcan las categorías con 0.
  $stmt = $mysqli->prepare(
    'SELECT c.id, c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
  );
  $stmt->bind_param('i', $usuarioId);
  $stmt->execute();
  $resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} catch (mysqli_sql_exception $ex) {
  $eventos = [];
  $resumen = [];
}
$mysqli->close();

$total = count($eventos);

// ---------- Avisos según la dirección ----------
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
          <span class="nav__link">Hola, <?= e($usuarioNombre) ?></span>
          <a href="index.php" class="nav__link is-active">Mis eventos</a>
          <a href="registrar.php" class="nav__link">Nuevo evento</a>
          <a href="logout.php" class="nav__link">Salir</a>
        </nav>
        <button class="btn-tema" id="btnTema" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
          <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
          <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>
      </div>
    </div>
  </header>

  <main class="contenedor">

    <!-- El aviso solo aparece si la URL trae ?ok=1, ?editado=1, ?borrado=1 o ?error=1 -->
    <?php if ($aviso): ?>
    <div class="alert <?= $avisoError ? 'alert--error' : 'alert--ok' ?>" role="<?= $avisoError ? 'alert' : 'status' ?>"><?= $avisoError ? '' : '&#9989; ' ?><?= e($aviso) ?></div>
    <?php endif; ?>

    <div class="page__header">
      <div>
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle">
          <?php if ($hayFiltro): ?>
            <?= $total ?> <?= $total === 1 ? 'resultado' : 'resultados' ?>
          <?php else: ?>
            <?= $total ?> <?= $total === 1 ? 'evento registrado' : 'eventos registrados' ?>
          <?php endif; ?>
        </p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <!-- Resumen: cuántos eventos hay por categoría -->
    <p class="resumen">
      <?php foreach ($resumen as $r): ?>
        <span class="card__badge"><?= e($r['nombre']) ?> · <?= (int) $r['total'] ?></span>
      <?php endforeach; ?>
    </p>

    <!-- Buscador: method="get" manda el texto a esta misma página -->
    <form method="get" action="index.php" class="buscador" role="search">
      <input type="search" name="buscar" maxlength="120"
             placeholder="Buscar evento por título..."
             value="<?= e($buscar) ?>" aria-label="Buscar evento por título">
      <select name="categoria" aria-label="Filtrar por categoría">
        <option value="0">Todas las categorías</option>
        <?php foreach ($resumen as $r): ?>
          <option value="<?= (int) $r['id'] ?>" <?= $categoriaFiltro === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary btn-sm">Buscar</button>
      <?php if ($hayFiltro): ?>
        <a href="index.php" class="btn-secondary btn-sm">Limpiar</a>
      <?php endif; ?>
    </form>

    <!-- Lista O estado vacío, nunca los dos -->
    <?php if (empty($eventos)): ?>
    <div class="empty-state">
      <?php if ($hayFiltro): ?>
        <p>Sin resultados para tu búsqueda.</p>
        <a href="index.php" class="btn-secondary">Ver todos mis eventos</a>
      <?php else: ?>
        <p>Aún no tienes eventos registrados.</p>
        <a href="registrar.php" class="btn-primary">Registrar el primero</a>
      <?php endif; ?>
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