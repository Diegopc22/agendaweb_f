<?php
// ============================================================
// login.php · iniciar sesión
// Recibir (POST) → buscar usuario → verificar hash → sesión
// ============================================================
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    // 1 · Buscar al usuario por su correo
    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();   // null si no existe
    $stmt->close();

    // 2 · ¿Existe y la contraseña coincide con su hash?
    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);                  // id de sesión nuevo al entrar
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $mysqli->close();
        header('Location: index.php');
        exit;
    }

    // 3 · Mismo mensaje en los dos casos: no revelamos si el correo existe
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar · AgendaWeb</title>

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
          <a href="login.php" class="nav__link is-active">Entrar</a>
          <a href="registro.php" class="nav__link">Crear cuenta</a>
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
        <h1 class="page__title">Entrar</h1>
        <p class="page__subtitle">Tu agenda te está esperando.</p>
      </div>
    </div>

    <section class="tarjeta form-wrap" aria-labelledby="titulo-form">
      <h2 id="titulo-form">Inicia sesión</h2>

      <?php if (isset($_GET['registrado'])): ?>
        <div class="alert alert--ok" role="status">Cuenta creada. Ya puedes iniciar sesión.</div>
      <?php endif; ?>
      <?php if (isset($_GET['salio'])): ?>
        <div class="alert alert--ok" role="status">Cerraste sesión. ¡Hasta pronto!</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert--error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="campo">
          <label for="correo">Correo</label>
          <input id="correo" name="correo" type="email" maxlength="120"
                 autocomplete="email" value="<?= e($correo) ?>" required>
        </div>

        <div class="campo">
          <label for="clave">Contraseña</label>
          <input id="clave" name="clave" type="password"
                 autocomplete="current-password" required>
        </div>

        <div class="acciones">
          <button class="boton boton--bloque" type="submit">Entrar</button>
          <a class="boton boton--bloque boton--secundario" href="registro.php">Crear cuenta</a>
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
