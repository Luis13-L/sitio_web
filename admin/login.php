<?php
// admin/login.php

// --- endurecer cookies de sesión ANTES de session_start() ---
$secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => $cookieParams['path'],
  'domain'   => $cookieParams['domain'],
  'secure'   => $secure,      // true si usas HTTPS
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

require __DIR__ . '/bd.php'; // lo cargamos siempre para poder leer logo/hero

// ===== CSRF para el formulario de login =====
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ===== Cargar recursos de portada/logo desde tbl_inicioo =====
$logo = $hero = null;
try {
  $q1 = $conexion->prepare("SELECT imagen FROM tbl_inicioo WHERE componente='logo' ORDER BY id DESC LIMIT 1");
  $q1->execute(); $logo = $q1->fetchColumn();

  $q2 = $conexion->prepare("SELECT imagen FROM tbl_inicioo WHERE componente='hero' ORDER BY id DESC LIMIT 1");
  $q2->execute(); $hero = $q2->fetchColumn();
} catch (\Throwable $e) {
  // si falla, seguimos con defaults
}
$logoUrl = $logo ? "../assets/img/{$logo}" : "https://dummyimage.com/240x80/ffffff/0d6efd.png&text=DIDEDUC+Solol%C3%A1";
$heroUrl = $hero ? "../assets/img/{$hero}" : "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1600";

$mensaje = '';

// ===== Proceso de login (tu lógica original) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF (si quieres desactivarlo, comenta este bloque)
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensaje = "Sesión inválida. Recarga la página e inténtalo de nuevo.";
    } else {
        $usuario  = trim($_POST['usuario'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        // 1) Traer por usuario (nunca password en WHERE)
        $sql = "SELECT ID AS id, usuario, correo, password_hash, rol, IFNULL(is_active,1) AS is_active
                FROM tbl_usuarios
                WHERE usuario = :usuario
                LIMIT 1";
        $st = $conexion->prepare($sql);
        $st->execute([':usuario' => $usuario]);
        $u = $st->fetch(PDO::FETCH_ASSOC);

        // 2) Verificar hash
        $ok = $u && !empty($u['password_hash']) && password_verify($password, $u['password_hash']);

        if ($ok) {
            if ((int)$u['is_active'] === 0) {
                $mensaje = "Tu usuario está inactivo. Contacta al administrador.";
            } else {
                // 3) Rol y sesión
                $rol = $u['rol'] ?? 'user';

                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$u['id'];
                $_SESSION['usuario']  = $u['usuario'];
                $_SESSION['correo']   = $u['correo'];
                $_SESSION['rol']      = $rol;           // 'admin' | 'user'
                $_SESSION['logueado'] = true;

                // 4) Redirección por rol (igual que tu código)
                if ($rol === 'admin') {
                    header('Location: /Proyecto/Proyecto/admin/index.php');
                } else {
                    header('Location: /Proyecto/Proyecto/admin/index.php');
                }
                exit;
            }
        } else {
            $mensaje = "Usuario o contraseña son incorrectos";
        }
    }
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <title>Ingreso | DIDEDUC Sololá</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root{
      --brand:#0d6efd;        /* azul institucional */
      --brand-dark:#0a58ca;
      --ink:#1f2937;
    }
/* antes: min-height:100vh; background: ... fixed; */
    body{
      min-height: 100svh;                           /* mejor en iOS/Android */
      background: linear-gradient(180deg, rgba(13,110,253,.10), rgba(13,110,253,.10)),
                  url('<?= htmlspecialchars($heroUrl) ?>') center/cover no-repeat;
    }

    .backdrop{ position:fixed; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.35), rgba(0,0,0,.35)); z-index:0; }
    .login-wrapper{ position:relative; z-index:1; min-height:100vh; display:grid; place-items:center; padding:32px 16px; }
    .card-login{
      width:min(960px, 96vw);
      border:0; overflow:hidden;
      box-shadow:0 12px 40px rgba(0,0,0,.25);
      border-radius:16px;
      background:rgba(255,255,255,.9);
      backdrop-filter: blur(6px);
    }
    .brand-pane{ background:linear-gradient(180deg, rgba(19,62,135,.85), rgba(13,110,253,.85)); color:#fff; }
    /* LOGO RECTANGULAR */
    .brand-pane .logo{
      width: 220px; height: auto;    /* rectangular, respeta proporción */
      object-fit: contain;
      background: transparent;       /* sin caja blanca */
      border-radius: 6px;            /* esquinas muy leves; pon 0 si lo quieres 100% recto */
      display:block;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,.25));
    }
    .btn-brand{ background:var(--brand); border:0; }
    .btn-brand:hover{ background:var(--brand-dark); }
    .form-control:focus{ box-shadow: 0 0 0 .25rem rgba(13,110,253,.25); }
    .caps-hint{ display:none; }
    .small-muted{ color:#6b7280; font-size:.9rem; }

    /* ===== Ajustes MOBILE (≤ 576px) ===== */
@media (max-width: 576px){
  /* Evita jank en scroll por fondos fijos */
  body{
    /* 100svh corrige el "100vh" en iOS/Android con barras */
    min-height: 100svh;
    background-attachment: scroll !important;
    background-position: center top !important;
    background-size: cover !important;
  }

  .login-wrapper{
    padding: 16px 10px;
    place-items: start center;     /* sube la tarjeta un poco */
  }

  .card-login{
    width: 100%;
    max-width: 420px;              /* buen ancho en iPhone grandes */
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.18);
    background: rgba(255,255,255,.95);
  }

  /* Panel institucional más compacto */
  .brand-pane{
    padding: 16px 16px !important;
    line-height: 1.3;
  }
  .brand-pane .logo{
    width: 180px;                  /* LOGO rectangular más pequeño en móvil */
    height: auto;                  /* mantiene proporción */
    border-radius: 4px;            /* si lo quieres 100% recto, usa 0 */
  }
  .brand-pane h1,
  .brand-pane .h4{                 /* título largo en 2 líneas sin desbordar */
    font-size: 1rem;
    margin-bottom: .25rem;
  }
  .brand-pane .small{
    font-size: .78rem;
  }
  .brand-pane ul{                  /* lista compacta (3 bullets) */
    margin: .5rem 0 0;
    padding-left: 20px;
  }
  .brand-pane ul li{
    margin-bottom: .25rem;
  }

  /* Formulario: inputs y botón “tap-friendly” */
  .form-label{
    font-size: .9rem;
  }
  .input-group-text{
    padding: .5rem .6rem;
  }
  .form-control{
    padding: .6rem .75rem;
    min-height: 44px;              /* altura cómoda de toque */
    font-size: .95rem;
  }
  #togglePass{
    padding: .5rem .7rem;
  }
  .btn-brand{
    min-height: 46px;
    font-weight: 600;
    letter-spacing: .2px;
  }

  /* Mensajes/ayudas */
  .form-text, .small-muted{
    font-size: .78rem;
  }

  /* Reduce separación general */
  .p-lg-5{ padding: 1rem !important; }
  .mb-3{ margin-bottom: .75rem !important; }
  .mb-4{ margin-bottom: 1rem !important; }
}

/* ===== Ajustes SMALL–MEDIUM (577–768px) ===== */
@media (min-width: 577px) and (max-width: 768px){
  .card-login{ max-width: 560px; }
  .brand-pane .logo{ width: 200px; }
}

  </style>
</head>
<body>
  <div class="backdrop"></div>

  <div class="login-wrapper">
    <div class="card card-login">
      <div class="row g-0">
        <!-- Panel institucional -->
        <div class="col-lg-5 brand-pane p-4 p-lg-5 d-flex flex-column justify-content-between">
          <div>
            <img class="logo mb-3" src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo DIDEDUC Sololá">
            <h1 class="h4 fw-semibold mb-1">Dirección Departamental de Educación de Sololá</h1>
            <p class="small mb-0">Portal de Administración</p>
          </div>
          <ul class="mt-4 small">
            <li>Acceso para personal autorizado.</li>
            <li>Usa tu usuario asignado.</li>
            <li>Puedes solicitar tu usuario a: <a class="text-white fw-semibold" href="mailto:engarcia@mineduc.gob.gt">engarcia@mineduc.gob.gt</a></li>
          </ul>
        </div>

        <!-- Formulario -->
        <div class="col-lg-7 p-4 p-lg-5 bg-white">
          <h2 class="h5 fw-semibold mb-3">Iniciar sesión</h2>

          <?php if (!empty($mensaje)): ?>
            <div class="alert alert-danger py-2">
              <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($mensaje) ?>
            </div>
          <?php endif; ?>

          <form method="post" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="mb-3">
              <label for="usuario" class="form-label">Usuario</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                <input type="text" class="form-control" id="usuario" name="usuario" required autofocus autocomplete="username">
              </div>
            </div>

            <div class="mb-2">
              <label for="password" class="form-label">Contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" aria-describedby="capsHint">
                <button class="btn btn-outline-secondary" type="button" id="togglePass" aria-label="Mostrar u ocultar contraseña">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
              <div id="capsHint" class="form-text text-warning caps-hint">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>Bloq Mayús activado.
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 mt-1">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                <label class="form-check-label" for="remember">Recordarme</label>
              </div>
            </div>

            <button class="btn btn-brand w-100 py-2" type="submit">
              <i class="fa-solid fa-right-to-bracket me-2"></i>Entrar
            </button>

            <p class="small-muted mt-3 mb-0">
              Acceso restringido a personal de la DIDEDUC Sololá. Uso bajo políticas institucionales.
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Mostrar / ocultar contraseña + aviso de Bloq Mayús
    (function(){
      const pass = document.getElementById('password');
      const btn  = document.getElementById('togglePass');
      const icon = btn.querySelector('i');
      const hint = document.getElementById('capsHint');

      btn.addEventListener('click', () => {
        const isText = pass.type === 'text';
        pass.type = isText ? 'password' : 'text';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      });

      pass.addEventListener('keyup', (e) => {
        const caps = e.getModifierState && e.getModifierState('CapsLock');
        hint.style.display = caps ? 'block' : 'none';
      });
    })();
  </script>
</body>
</html>
