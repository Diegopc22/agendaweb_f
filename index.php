<?php
// ============================================================
// index.php · tablero de eventos (la "R" de CRUD: leer de MySQL)
// ============================================================
function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

$categorias = ['trabajo' => 'Trabajo', 'personal' => 'Personal', 'estudio' => 'Estudio', 'ocio' => 'Ocio'];
$eventos = [];

require_once 'conexion.php';
try {
  $res = $mysqli->query("SELECT id, titulo, fecha, hora, categoria, descripcion
                         FROM eventos ORDER BY fecha, hora");
  while ($fila = $res->fetch_assoc()) { $eventos[] = $fila; }
} catch (mysqli_sql_exception $ex) {
  $eventos = [];
}

$total = count($eventos);
$guardado = (($_GET['ok'] ?? '') === '1');
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

    <?php if ($guardado): ?>
    <!-- Solo aparece si la URL trae ?ok=1 -->
    <div class="alert alert--ok" role="status">&#9989; Evento guardado.</div>
    <?php endif; ?>

    <div class="page__header">
      <div>
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle"><?= $total ?> <?= $total === 1 ? 'evento registrado' : 'eventos registrados' ?></p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <?php if ($total > 0): ?>
    <section class="card-list">

      <?php foreach ($eventos as $ev):
        $tieneHora = !empty($ev['hora']);
        $hora      = $tieneHora ? substr($ev['hora'], 0, 5) : '';
        $fechaObj  = DateTime::createFromFormat('Y-m-d', (string)$ev['fecha']);
        $fechaTxt  = $fechaObj ? $fechaObj->format('d/m/Y') : '';
        $datetime  = $fechaObj ? ($ev['fecha'] . ($tieneHora ? 'T' . $hora : '')) : '';
        $catTxt    = $categorias[$ev['categoria']] ?? ucfirst((string)$ev['categoria']);
      ?>
      <!-- ▼ INICIO de UN evento (se repite con foreach) -->
      <article class="card">
        <span class="card__badge"><?= e($catTxt) ?></span>
        <h2 class="card__title"><?= e($ev['titulo']) ?></h2>
        <p class="card__meta">
          <time datetime="<?= e($datetime) ?>"><?= e($fechaTxt) ?><?= $tieneHora ? ' · ' . e($hora) : '' ?></time>
        </p>
        <?php if (!empty($ev['descripcion'])): ?>
        <p class="card__text"><?= e($ev['descripcion']) ?></p>
        <?php endif; ?>

        <div class="card__actions">
          <a href="editar.php?id=<?= (int)$ev['id'] ?>" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline">
            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
            <button type="submit" class="btn-danger btn-sm">Borrar</button>
          </form>
        </div>
      </article>
      <!-- ▲ FIN de un evento -->
      <?php endforeach; ?>

    </section>
    <?php else: ?>
    <!-- Solo aparece si no hay eventos -->
    <div class="empty-state">
      <p>Aún no tienes eventos registrados.</p>
      <a href="registrar.php" class="btn-primary">Registrar el primero</a>
    </div>
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