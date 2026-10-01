<?php
function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

date_default_timezone_set('America/Mexico_City');
$hoyServidor   = date('Y-m-d');
$ahoraServidor = date('H:i');

$titulo = $fecha = $hora = $categoria = $descripcion = '';
$errores = [];
$categoriasOK = ['trabajo' => 'Trabajo', 'personal' => 'Personal', 'estudio' => 'Estudio', 'ocio' => 'Ocio'];

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
    elseif ($fecha < $hoyServidor)
      $errores['fecha'] = 'La fecha no puede ser anterior a hoy.';
  }

  if ($hora !== '') {
    $h = DateTime::createFromFormat('H:i', $hora);
    if (!$h || $h->format('H:i') !== $hora)
      $errores['hora'] = 'La hora no es válida.';
    elseif ($fecha === $hoyServidor && $hora < $ahoraServidor)
      $errores['hora'] = 'La hora ya pasó. Elige una a partir de las ' . $ahoraServidor . '.';
  }

  if (!array_key_exists($categoria, $categoriasOK))
    $errores['categoria'] = 'Elige una categoría válida.';

  if (mb_strlen($descripcion) > 500)
    $errores['descripcion'] = 'Máximo 500 caracteres.';

  if (empty($errores)) {
    require_once 'conexion.php';

    $horaDB = ($hora === '') ? null : $hora;
    $descDB = ($descripcion === '') ? null : $descripcion;

    try {
      $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria, descripcion)
              VALUES (?, ?, ?, ?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("sssss", $titulo, $fecha, $horaDB, $categoria, $descDB);
      $stmt->execute();
      $nuevoId = $stmt->insert_id;
      $stmt->close();
      $mysqli->close();

      header('Location: index.php?ok=1');
      exit;
    } catch (mysqli_sql_exception $ex) {
      $errores['general'] = 'No se pudo guardar el evento. Intenta de nuevo.';
    }
  }
}
$eventosDB = [];
if (file_exists(__DIR__ . '/conexion.php')) {
  require_once 'conexion.php';
  try {
    $res = $mysqli->query("SELECT id, titulo, fecha, hora, categoria, descripcion FROM eventos ORDER BY fecha, hora LIMIT 500");
    while ($r = $res->fetch_assoc()) {
      $eventosDB[] = [
        'id'        => (int)$r['id'],
        'titulo'    => $r['titulo'],
        'fecha'     => $r['fecha'],
        'hora'      => $r['hora'] ? substr($r['hora'], 0, 5) : '',
        'categoria' => $r['categoria'],
        'notas'     => (string)$r['descripcion'],
      ];
    }
  } catch (mysqli_sql_exception $ex) { }
}
?>
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
  <link rel="stylesheet" href="css/estilos.css">
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
        <?php if (isset($errores['general'])): ?>
          <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
        <?php elseif (!empty($errores)): ?>
          <div class="alert alert--error" role="alert">Revisa los campos marcados.</div>
        <?php endif; ?>
        <form method="post" action="" id="formulario">
          <div class="campo <?= isset($errores['titulo']) ? 'campo--error' : '' ?>">
            <label for="titulo">Título</label>
            <input id="titulo" name="titulo" type="text" maxlength="120" placeholder="Ej. Cita con el dentista" value="<?= e($titulo) ?>" required>
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
            <input id="fecha" name="fecha" type="date" min="<?= e($hoyServidor) ?>" value="<?= e($fecha) ?>" required>
            <?php if (isset($errores['fecha'])): ?><p class="campo__error"><?= e($errores['fecha']) ?></p><?php endif; ?>
          </div>
          <div class="campo <?= isset($errores['hora']) ? 'campo--error' : '' ?>">
            <label for="hora">Hora (opcional)</label>
            <input id="hora" name="hora" type="time" value="<?= e($hora) ?>">
            <?php if (isset($errores['hora'])): ?><p class="campo__error"><?= e($errores['hora']) ?></p><?php endif; ?>
          </div>
          <div class="campo <?= isset($errores['descripcion']) ? 'campo--error' : '' ?>">
            <label for="descripcion">Descripción (opcional)</label>
            <textarea id="descripcion" name="descripcion" maxlength="500"
                      placeholder="Lugar, recordatorios…"><?= e($descripcion) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?><p class="campo__error"><?= e($errores['descripcion']) ?></p><?php endif; ?>
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
    const eventos = <?= json_encode($eventosDB, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const $ = id => document.getElementById(id);
    const pad = n => String(n).padStart(2, "0");
    const aTexto = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
    const hoy = new Date();
    const hoyTxt = aTexto(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    let vista = { y: hoy.getFullYear(), m: hoy.getMonth() };
    let seleccionado = null;
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
        if (f < hoyTxt) b.disabled = true;
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
      visibles = [...visibles].sort((a, b) => (a.fecha + (a.hora || "")).localeCompare(b.fecha + (b.hora || "")));
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
          item.append(el("div", "evento__hora", ev.hora || "Sin hora"));
          const cuerpo = el("div", "evento__cuerpo");
          cuerpo.append(el("p", "evento__titulo", ev.titulo));
          if (ev.notas) cuerpo.append(el("p", "evento__notas", ev.notas));
          item.append(cuerpo);
          dia.append(item);
        });
        lista.append(dia);
      });
    }
    function pintar() { pintarCalendario(); pintarLista(); }
    function ajustarMinimos() {
      const ahora = new Date();
      const hoyLocal = aTexto(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
      const horaLocal = pad(ahora.getHours()) + ":" + pad(ahora.getMinutes());
      $("fecha").min = hoyLocal;
      if ($("fecha").value === hoyLocal) {
        $("hora").min = horaLocal;
      } else {
        $("hora").removeAttribute("min");
      }
    }
    $("fecha").addEventListener("change", ajustarMinimos);
    $("hora").addEventListener("focus", ajustarMinimos);
    setInterval(ajustarMinimos, 30000);
    $("fecha").value = <?= json_encode($fecha) ?> || hoyTxt;
    ajustarMinimos();
    actualizarBotonTema();
    pintar();
  </script>
</body>
</html>