<?php
// ============================================================
// registro.php · crear una cuenta
// Recibir (POST) → validar → guardar hash → redirigir a login.php
// ============================================================
session_start();
if (isset($_SESSION['uid'])) {          // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}
require __DIR__ . '/conexion.php';

function e($texto) { return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'); }

$nombre = $correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));   // Ana@x.com = ana@x.com
    $clave  = $_POST['clave']  ?? '';                          // la contraseña NO se recorta
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);   // se guarda el HASH, nunca la clave
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {               // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta · AgendaWeb</title>

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
          <a href="login.php" class="nav__link">Entrar</a>
          <a href="registro.php" class="nav__link is-active">Crear cuenta</a>
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
        <h1 class="page__title">Crear cuenta</h1>
        <p class="page__subtitle">Tus eventos, solo tuyos.</p>
      </div>
    </div>

    <section class="tarjeta form-wrap" aria-labelledby="titulo-form">
      <h2 id="titulo-form">Datos de la cuenta</h2>

      <?php if (isset($errores['general'])): ?>
        <div class="alert alert--error" role="alert"><?= e($errores['general']) ?></div>
      <?php elseif (!empty($errores)): ?>
        <div class="alert alert--error" role="alert">Revisa los campos marcados.</div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="campo <?= isset($errores['nombre']) ? 'campo--error' : '' ?>">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" type="text" maxlength="80"
                 autocomplete="name" value="<?= e($nombre) ?>" required>
          <?php if (isset($errores['nombre'])): ?><p class="campo__error"><?= e($errores['nombre']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['correo']) ? 'campo--error' : '' ?>">
          <label for="correo">Correo</label>
          <input id="correo" name="correo" type="email" maxlength="120"
                 autocomplete="email" value="<?= e($correo) ?>" required>
          <?php if (isset($errores['correo'])): ?><p class="campo__error"><?= e($errores['correo']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['clave']) ? 'campo--error' : '' ?>">
          <label for="clave">Contraseña</label>
          <input id="clave" name="clave" type="password" minlength="8"
                 autocomplete="new-password" required>
          <?php if (isset($errores['clave'])): ?><p class="campo__error"><?= e($errores['clave']) ?></p><?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['clave2']) ? 'campo--error' : '' ?>">
          <label for="clave2">Repítela</label>
          <input id="clave2" name="clave2" type="password" minlength="8"
                 autocomplete="new-password" required>
          <?php if (isset($errores['clave2'])): ?><p class="campo__error"><?= e($errores['clave2']) ?></p><?php endif; ?>
        </div>

        <div class="acciones">
          <button class="boton boton--bloque" type="submit">Crear cuenta</button>
          <a class="boton boton--bloque boton--secundario" href="login.php">Ya tengo cuenta</a>
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
