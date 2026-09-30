<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuevo evento · AgendaWeb</title>

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
          <a href="index.php" class="nav__link">Mis eventos</a>
          <a href="registrar.php" class="nav__link is-active">Nuevo evento</a>
        </nav>
        <button class="btn-tema" id="btnTema" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
          <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
          <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>
      </div>
    </div>
  </header>

  <main class="contenedor">
    <section class="introduccion">
      <h1>Tu agenda, sin ruido</h1>
      <p>Elige un día en el calendario, registra tu evento y míralo ordenado por fecha.</p>
    </section>

    <div class="rejilla">

      <section class="tarjeta" aria-labelledby="titulo-form">
        <h2 id="titulo-form">Nuevo evento</h2>
        <form id="formulario">
          <div class="campo">
            <label for="titulo">Título</label>
            <input id="titulo" name="titulo" type="text" placeholder="Ej. Cita con el dentista" required>
          </div>
          <div class="campo">
            <label for="fecha">Fecha</label>
            <input id="fecha" name="fecha" type="date" required>
          </div>
          <div class="campo">
            <label for="hora">Hora</label>
            <input id="hora" name="hora" type="time" required>
          </div>
          <div class="campo">
            <label for="notas">Notas (opcional)</label>
            <textarea id="notas" name="notas" placeholder="Lugar, recordatorios…"></textarea>
          </div>
          <div class="acciones">
            <button class="boton boton--bloque" type="submit">Guardar evento</button>
            <a class="boton boton--bloque boton--secundario" href="index.php">Cancelar</a>
          </div>
        </form>
      </section>

      <div class="columna">
        <section class="tarjeta" aria-label="Calendario">
          <div class="cal__cabecera">
            <h2 class="cal__mes" id="calMes" aria-live="polite"></h2>
            <div class="cal__nav">
              <button type="button" id="calPrev" aria-label="Mes anterior">‹</button>
              <button type="button" id="calHoy" class="cal__hoy">Hoy</button>
              <button type="button" id="calNext" aria-label="Mes siguiente">›</button>
            </div>
          </div>
          <div class="cal__grid" id="calGrid"></div>
        </section>

        <section class="tarjeta" aria-labelledby="titulo-lista">
          <div class="lista__cabecera">
            <h2 id="titulo-lista">Próximos eventos</h2>
            <button type="button" class="boton boton--suave" id="verTodos" hidden>Ver todos</button>
          </div>
          <div id="lista" aria-live="polite"></div>
        </section>
      </div>

    </div>
  </main>

  <footer class="site-footer">
    AgendaWeb · Diego Plascencia Camarena · 2026
  </footer>

  <script>
    const CLAVE = "agendaweb:eventos";
    const $ = id => document.getElementById(id);
    const pad = n => String(n).padStart(2, "0");
    const aTexto = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;

    const hoy = new Date();
    const hoyTxt = aTexto(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    let vista = { y: hoy.getFullYear(), m: hoy.getMonth() };
    let seleccionado = null;
    let eventos = [];

    function cargar() { try { eventos = JSON.parse(localStorage.getItem(CLAVE)) || []; } catch (e) { eventos = []; } }
    function guardar() { try { localStorage.setItem(CLAVE, JSON.stringify(eventos)); } catch (e) {} }

    function el(tag, clase, texto) {
      const n = document.createElement(tag);
      if (clase) n.className = clase;
      if (texto) n.textContent = texto;
      return n;
    }
    const nombreDia = f => new Date(f + "T00:00:00").toLocaleDateString("es-MX", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

    function actualizarBotonTema() {
      const oscuro = document.documentElement.dataset.theme === "dark";
      $("btnTema").setAttribute("aria-label", oscuro ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
    }
    $("btnTema").addEventListener("click", () => {
      const nuevo = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
      document.documentElement.dataset.theme = nuevo;
      try { localStorage.setItem("agendaweb:tema", nuevo); } catch (e) {}
      actualizarBotonTema();
    });

    function pintarCalendario() {
      const grid = $("calGrid");
      grid.replaceChildren();
      $("calMes").textContent = new Date(vista.y, vista.m, 1).toLocaleDateString("es-MX", { month: "long", year: "numeric" });

      ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"].forEach(d => grid.append(el("div", "cal__dia-sem", d)));

      const primero = (new Date(vista.y, vista.m, 1).getDay() + 6) % 7;
      const total = new Date(vista.y, vista.m + 1, 0).getDate();
      const conEvento = new Set(eventos.map(e => e.fecha));

      for (let i = 0; i < primero; i++) grid.append(el("div", "cal__vacio"));

      for (let d = 1; d <= total; d++) {
        const f = aTexto(vista.y, vista.m, d);
        let clase = "cal__celda";
        if (f === hoyTxt) clase += " cal__celda--hoy";
        if (conEvento.has(f)) clase += " cal__celda--evento";
        const b = el("button", clase, String(d));
        b.type = "button";
        b.setAttribute("aria-pressed", f === seleccionado ? "true" : "false");
        b.setAttribute("aria-label", nombreDia(f) + (conEvento.has(f) ? ", con eventos" : ""));
        b.addEventListener("click", () => {
          seleccionado = (seleccionado === f) ? null : f;
          if (seleccionado) $("fecha").value = seleccionado;
          pintar();
        });
        grid.append(b);
      }
    }
    $("calPrev").addEventListener("click", () => { vista.m--; if (vista.m < 0) { vista.m = 11; vista.y--; } pintarCalendario(); });
    $("calNext").addEventListener("click", () => { vista.m++; if (vista.m > 11) { vista.m = 0; vista.y++; } pintarCalendario(); });
    $("calHoy").addEventListener("click", () => {
      vista = { y: hoy.getFullYear(), m: hoy.getMonth() };
      seleccionado = hoyTxt; $("fecha").value = hoyTxt; pintar();
    });
    $("verTodos").addEventListener("click", () => { seleccionado = null; pintar(); });

    function pintarLista() {
      const lista = $("lista");
      lista.replaceChildren();
      $("titulo-lista").textContent = seleccionado ? "Eventos del día" : "Próximos eventos";
      $("verTodos").hidden = !seleccionado;

      let visibles = seleccionado ? eventos.filter(e => e.fecha === seleccionado) : eventos.filter(e => e.fecha >= hoyTxt);
      visibles = [...visibles].sort((a, b) => (a.fecha + a.hora).localeCompare(b.fecha + b.hora));

      if (visibles.length === 0) {
        const v = el("div", "vacio");
        v.append(el("strong", "", seleccionado ? "Sin eventos este día" : "Aún no tienes eventos próximos"), seleccionado ? "Agrega uno con el formulario." : "Agrega el primero con el formulario.");
        lista.append(v);
        return;
      }

      const porDia = {};
      visibles.forEach(ev => (porDia[ev.fecha] ||= []).push(ev));

      Object.keys(porDia).forEach(f => {
        const dia = el("div", "dia");
        dia.append(el("h3", "", nombreDia(f)));
        porDia[f].forEach(ev => {
          const item = el("article", "evento");
          item.append(el("div", "evento__hora", ev.hora));
          const cuerpo = el("div", "evento__cuerpo");
          cuerpo.append(el("p", "evento__titulo", ev.titulo));
          if (ev.notas) cuerpo.append(el("p", "evento__notas", ev.notas));
          item.append(cuerpo);
          const borrar = el("button", "boton boton--suave", "Eliminar");
          borrar.type = "button";
          borrar.setAttribute("aria-label", "Eliminar evento " + ev.titulo);
          borrar.addEventListener("click", () => { eventos = eventos.filter(x => x.id !== ev.id); guardar(); pintar(); });
          item.append(borrar);
          dia.append(item);
        });
        lista.append(dia);
      });
    }

    function pintar() { pintarCalendario(); pintarLista(); }

    $("formulario").addEventListener("submit", e => {
      e.preventDefault();
      const d = new FormData(e.target);
      const fecha = d.get("fecha");
      eventos.push({
        id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
        titulo: d.get("titulo").trim(), fecha, hora: d.get("hora"), notas: d.get("notas").trim()
      });
      guardar();
      const [y, m] = fecha.split("-").map(Number);
      vista = { y, m: m - 1 };
      seleccionado = fecha;
      e.target.reset();
      $("fecha").value = fecha;
      pintar();
      $("titulo").focus();
    });

    cargar();
    $("fecha").value = hoyTxt;
    actualizarBotonTema();
    pintar();
  </script>
</body>
</html>