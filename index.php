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
  <link rel="stylesheet" href="estilos.css">
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

    <!-- En P4 solo aparecerá si la URL trae ?ok=1 -->
    <div class="alert alert--ok" role="status">&#9989; Evento guardado.</div>

    <div class="page__header">
      <div>
        <h1 class="page__title">Mis eventos</h1>
        <p class="page__subtitle">3 eventos registrados</p>
      </div>
      <a href="registrar.php" class="btn-primary">+ Nuevo evento</a>
    </div>

    <section class="card-list">

      <!-- ▼ INICIO de UN evento (en P4 se repetirá con foreach) -->
      <article class="card">
        <span class="card__badge">Trabajo</span>
        <h2 class="card__title">Reunión de academia</h2>
        <p class="card__meta">
          <time datetime="2026-09-25T10:30">25/09/2026 · 10:30</time>
        </p>
        <p class="card__text">Revisar calificaciones del 1er parcial.</p>

        <div class="card__actions">
          <a href="editar.php?id=1" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline">
            <input type="hidden" name="id" value="1">
            <button type="submit" class="btn-danger btn-sm">Borrar</button>
          </form>
        </div>
      </article>
      <!-- ▲ FIN de un evento -->

      <!-- Evento 2: sin descripción -->
      <article class="card">
        <span class="card__badge">Personal</span>
        <h2 class="card__title">Cita con el dentista</h2>
        <p class="card__meta">
          <time datetime="2026-09-28T16:00">28/09/2026 · 16:00</time>
        </p>

        <div class="card__actions">
          <a href="editar.php?id=2" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline">
            <input type="hidden" name="id" value="2">
            <button type="submit" class="btn-danger btn-sm">Borrar</button>
          </form>
        </div>
      </article>

      <!-- Evento 3: sin hora y con título largo -->
      <article class="card">
        <span class="card__badge">Escuela</span>
        <h2 class="card__title">Entrega del proyecto final de Desarrollo de Aplicaciones Web con presentación al grupo</h2>
        <p class="card__meta">
          <time datetime="2026-10-02">02/10/2026</time>
        </p>
        <p class="card__text">Subir el repositorio y publicar en DomCloud.</p>

        <div class="card__actions">
          <a href="editar.php?id=3" class="btn-secondary btn-sm">Editar</a>
          <form method="post" action="borrar.php" class="form-inline">
            <input type="hidden" name="id" value="3">
            <button type="submit" class="btn-danger btn-sm">Borrar</button>
          </form>
        </div>
      </article>

    </section>

    <!-- En P4 solo aparecerá si no hay eventos -->
    <div class="empty-state">
      <p>Aún no tienes eventos registrados.</p>
      <a href="registrar.php" class="btn-primary">Registrar el primero</a>
    </div>

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