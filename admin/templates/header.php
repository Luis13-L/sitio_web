<?php
require_once __DIR__.'/../auth_guard.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($conexion)) { require_once __DIR__ . '/../bd.php'; }

/* ===== URL bases sin hardcodear protocolo ni host ===== */
$SCHEME = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$HOST   = $_SERVER['HTTP_HOST'];
$ADMIN_BASE = $SCHEME.$HOST.'/Proyecto/Proyecto/admin/';

$ADMIN_PATH = rtrim(parse_url($ADMIN_BASE, PHP_URL_PATH), '/');    // /Proyecto/Proyecto/admin
$PROJ_PATH  = rtrim(preg_replace('#/admin$#','',$ADMIN_PATH), '/'); // /Proyecto/Proyecto

/* Posibles ubicaciones públicas de /assets/img/ */
$ASSET_CANDIDATES = [
  $PROJ_PATH.'/assets/img/',
  rtrim(dirname($PROJ_PATH), '/').'/assets/img/',
];

/* Resolver URL absoluta de un asset verificando que exista físicamente */
function resolve_asset_url(string $webDir, string $file): ?string {
  $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $fsPath  = $docRoot.$webDir.$file;
  if (is_file($fsPath)) {
    global $SCHEME, $HOST;
    return $SCHEME.$HOST.$webDir.rawurlencode($file);
  }
  return null;
}

/* Guardia de sesión */
if (empty($_SESSION['usuario'])) {
  header("Location: {$ADMIN_BASE}login.php");
  exit;
}
$isAdmin = (($_SESSION['rol'] ?? 'user') === 'admin');

/* Item activo */
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$basePath   = $ADMIN_PATH.'/';
function is_active(string $needle): string {
  global $currentUri, $basePath;
  return (strpos($currentUri, $basePath.ltrim($needle,'/')) !== false) ? 'active' : '';
}

/* ===== Logo del portal (último 'logo' en tbl_inicioo) ===== */
$logoFile = 'logo.png';
try {
  $q = $conexion->prepare("SELECT imagen FROM tbl_inicioo WHERE componente='logo' ORDER BY id DESC LIMIT 1");
  $q->execute();
  if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['imagen'])) { $logoFile = $row['imagen']; }
  }
} catch (Throwable $e) { /* ignore */ }

/* Armar URL pública del logo (o fallback a logo.png) */
$portalLogoUrl = null;
foreach ($ASSET_CANDIDATES as $webDir) {
  if ($url = resolve_asset_url($webDir, $logoFile)) { $portalLogoUrl = $url; break; }
}
if (!$portalLogoUrl) {
  foreach ($ASSET_CANDIDATES as $webDir) {
    if ($url = resolve_asset_url($webDir, 'logo.png')) { $portalLogoUrl = $url; break; }
  }
}
if (!$portalLogoUrl) {
  $portalLogoUrl = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
}

/* Fondo estático (opcional) */
$bgUrl = null;
foreach ($ASSET_CANDIDATES as $webDir) {
  if ($url = resolve_asset_url($webDir, 'fondo_panel.webp')) { $bgUrl = $url; break; }
}
echo "<!-- LOGO_URL_RESUELTA: {$portalLogoUrl} -->\n";
echo "<!-- BG_URL_RESUELTA: ".($bgUrl ?? 'no encontrado')." -->\n";
?>
<!doctype html>
<html lang="es">
<head>
  <title>Administrador del sitio web</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- jQuery + DataTables -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

  <!-- SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <style>
    :root{ --brand-h: 60px; }

    /* Fondo estático suave (se activa solo si $bgUrl existe) */
    <?php if ($bgUrl): ?>
    body{
      background: #f8fafc url('<?= htmlspecialchars($bgUrl) ?>') center top / cover fixed no-repeat;
    }
    body::before{
      content:""; position:fixed; inset:0;
      /* ligera veladura para que el contenido siga leyendo bien */
      background: rgba(255,255,255,0.75);
      pointer-events:none; z-index:-1;
    }
    <?php endif; ?>

    .navbar{
      box-shadow:0 2px 8px rgba(0,0,0,.05);
      min-height: calc(var(--brand-h) + 22px);
    }
    .navbar-brand{ display:flex; align-items:center; gap:0; }

    /* Logo (solo imagen, sin texto) */
    .admin-brand{
      height: var(--brand-h);
      width: auto;
      object-fit: contain;
      /* Si el logo es muy claro, puedes activar una sombra suave: */
      filter: drop-shadow(0 1px 2px rgba(0,0,0,.12));
      display:block;
    }

    .admin-nav .nav-link{
      font-weight:700; color:#2b2b2b;
      padding:.625rem 1rem;
      display:flex; align-items:center; gap:.5rem;
      transition: color .15s ease;
    }
    .admin-nav .nav-link i{
      width:1.25rem; text-align:center; font-size:1rem; color:#2b2b2b;
      transition: color .15s ease;
    }
    .admin-nav .nav-link:hover,
    .admin-nav .nav-link.active{ color:#0d6efd; text-decoration:none; }
    .admin-nav .nav-link:hover i,
    .admin-nav .nav-link.active i{ color:#0d6efd; }

    @media (max-width: 575.98px){
      :root{ --brand-h: 48px; }
    }
/* === DataTables 2.x === */
.dt-container .dt-length > label,
.dt-container .dt-search > label,
.dt-container .dt-info{
  font-weight: 700 !important;
  color: #111827 !important;
}
.dt-container .dt-length .dt-input,
.dt-container .dt-search input.dt-input{
  font-weight: 600 !important;
}

/* Paginación redondeada */
.dt-container .dt-paging .dt-paging-button{
  border-radius: .5rem !important;
  padding: .375rem .625rem !important;
}
.dt-container .dt-paging .dt-paging-button.current{
  background: #6b9be2ff !important;
  color: #fff !important;
  border-color: #0d6efd !important;
}

/* Azul más vivo */
:root{
  --brand-blue:#2563eb;     /* azul vivo */
  --brand-blue-hover:#1e40af;
}

/* Botón de icono consistente */
.btn-icon{
  display:inline-flex; align-items:center; justify-content:center;
  width:40px; height:40px; padding:0; border-radius:10px;
}
.btn-icon i{ font-size:1rem; }

/* Variantes azul vivo */
.btn-brand{
  color:#fff; background:var(--brand-blue); border-color:var(--brand-blue);
}
.btn-brand:hover,.btn-brand:focus{
  color:#fff; background:var(--brand-blue-hover); border-color:var(--brand-blue-hover);
}

.btn-brand-outline{
  color:var(--brand-blue); background:#fff; border-color:var(--brand-blue);
}
.btn-brand-outline:hover,.btn-brand-outline:focus{
  color:#fff; background:var(--brand-blue); border-color:var(--brand-blue);
}

/* Centrado perfecto en celdas de acciones/PDF */
th.cell-center, td.cell-center{ text-align:center; vertical-align:middle; }
.action-group{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; }

/* Centrado de columnas de íconos */
.table th.icon-col,
.table td.icon-col{
  text-align: center !important;
  vertical-align: middle;
}

/* Alinea y separa los botones */
.btn-icon-group{
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: .5rem;          /* espacio entre botones */
}


    
  </style>
</head>
<script src="/Proyecto/Proyecto/admin/assets/admin.js"></script>

<body class="<?= isset($BODY_CLASS) ? htmlspecialchars($BODY_CLASS) : '' ?>">
<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container-fluid">
      <!-- Brand: solo LOGO -->
      <a class="navbar-brand p-0 me-2" href="<?= htmlspecialchars($ADMIN_BASE); ?>" aria-label="Inicio admin">
        <img class="admin-brand"
             src="<?= htmlspecialchars($portalLogoUrl); ?>"
             alt="Logo"
             onerror="this.onerror=null; this.src='<?= $ADMIN_BASE; ?>../assets/img/logo.png';">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"
              aria-controls="adminNav" aria-expanded="false" aria-label="Alternar navegación">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 admin-nav">
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/servicios/');?>" href="<?= $ADMIN_BASE;?>secciones/servicios/">
              <i class="fa-solid fa-toolbox me-1"></i> Servicios
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/portafolio/');?>" href="<?= $ADMIN_BASE;?>secciones/portafolio/">
              <i class="fa-solid fa-newspaper me-1"></i> Noticias
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/entradas/');?>" href="<?= $ADMIN_BASE;?>secciones/entradas/">
              <i class="fa-solid fa-timeline me-1"></i> Historia
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/equipo/');?>" href="<?= $ADMIN_BASE;?>secciones/equipo/">
              <i class="fa-solid fa-people-group me-1"></i> Equipo
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/configuraciones/');?>" href="<?= $ADMIN_BASE;?>secciones/configuraciones/">
              <i class="fa-solid fa-sliders me-1"></i> Configuraciones
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/inicio/');?>" href="<?= $ADMIN_BASE;?>secciones/inicio/">
              <i class="fa-solid fa-image me-1"></i> Portada/Logo
            </a>
          </li>

          <?php if ($isAdmin): ?>
          <li class="nav-item">
            <a class="nav-link <?= is_active('secciones/usuarios/');?>" href="<?= $ADMIN_BASE;?>secciones/usuarios/">
              <i class="fa-solid fa-user-gear me-1"></i> Usuarios
            </a>
          </li>
          <?php endif; ?>
        </ul>

        <div class="d-flex">
          <a class="btn btn-sm btn-danger" href="<?= $ADMIN_BASE;?>cerrar.php" title="Cerrar sesión">
            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Cerrar sesión
          </a>
        </div>
      </div>
    </div>
  </nav>

  <link rel="stylesheet" href="/Proyecto/Proyecto/admin/assets/admin.css">

</header>

<main class="container">
  <br>

  <?php if (!empty($_GET['mensaje'])): ?>
    <script>Swal.fire({icon:"success", title:"<?= htmlspecialchars($_GET['mensaje']);?>"});</script>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
