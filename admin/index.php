<?php
// Si tu header ya exige login/rol, basta con incluirlo.
// Si no, descomenta las 2 líneas siguientes:
// require_once __DIR__ . '/secciones/auth_guard.php';
// require_login();

require __DIR__ . '/bd.php'; ;
include(__DIR__ . "/templates/header.php");

/* === Cargar logo/hero desde tbl_inicioo (opcional) === */
$logoUrl = null;
$heroUrl = null;
try {
  $q = $conexion->prepare("SELECT componente, imagen FROM tbl_inicioo ORDER BY id DESC");
  $q->execute();
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    if (!$logoUrl && isset($r['componente']) && $r['componente'] === 'logo' && !empty($r['imagen'])) {
      $logoUrl = "assets/img/" . $r['imagen'];
    }
    if (!$heroUrl && isset($r['componente']) && $r['componente'] === 'hero' && !empty($r['imagen'])) {
      $heroUrl = "assets/img/" . $r['imagen'];
    }
  }
} catch (\Throwable $e) {
  // silencioso
}

/* === Conteos seguros === */
function safe_count(PDO $cx, string $table): string {
  try {
    $st = $cx->query("SELECT COUNT(*) AS c FROM `$table`");
    $r  = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;
    return $r ? (string)$r['c'] : '0';
  } catch (\Throwable $e) {
    return '0';
  }
}
$counts = [
  'servicios' => safe_count($conexion, 'tbl_servicios'),
  'noticias'  => safe_count($conexion, 'tbl_portafolio'),
  'equipo'    => safe_count($conexion, 'tbl_equipo'),
  'entradas'  => safe_count($conexion, 'tbl_entradas'),
  'usuarios'  => safe_count($conexion, 'tbl_usuarios'),
  'config'    => safe_count($conexion, 'tbl_confifiguraciones'),
];

/* === Info sistema (segura) === */
$phpVersion = PHP_VERSION;
$dbVersion  = '';
try { $dbVersion = $conexion->getAttribute(PDO::ATTR_SERVER_VERSION) ?: ''; } catch (\Throwable $e) {}

/* === Usuario/rol de sesión === */
$nombre   = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario');
$rol      = htmlspecialchars($_SESSION['rol'] ?? 'user');
?>

<style>
  /* ===== Dashboard look ===== */
  .dash-hero{
    position: relative;
    border-radius: 14px;
    overflow: hidden;
    background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);
    color: #fff;
  }
  .dash-hero .overlay{
    position:absolute; inset:0;
    background:
      radial-gradient(1200px 300px at 10% 0%, rgba(255,255,255,.12), transparent 60%),
      radial-gradient(800px 260px at 90% 0%, rgba(0,0,0,.12), transparent 60%);
    pointer-events:none;
  }
  .dash-hero .hero-img{
    position:absolute; inset:0; opacity:.18;
    background-size: cover; background-position:center;
    filter: saturate(1.05) contrast(1.05);
  }
  .dash-hero .content{ position:relative; z-index:2; }

  .brand-badge{
    display:flex; align-items:center; gap:14px;
    padding:12px 14px; background: rgba(255,255,255,.12);
    border-radius:10px; backdrop-filter: blur(3px);
    width:max-content;
  }
  .brand-badge img{
    height:42px; width:auto; border-radius:6px; /* rectangular */
    background:#fff;
    padding:6px;
  }
  .brand-badge .text h6{ font-weight:700; letter-spacing:.2px; margin:0; }
  .brand-badge .text small{ opacity:.9; }

  .stat-card{
    border:1px solid #eef1f5; border-left:4px solid var(--bs-primary);
    border-radius:12px; padding:16px; background:#fff;
  }
  .stat-card .icon{
    width:40px; height:40px; display:grid; place-items:center;
    border-radius:10px; background: rgba(13,110,253,.1); color:#0d6efd;
  }
  .stat-card h5{ margin:0; font-size:1.75rem; }
  .stat-card small{ color:#6c757d; }

  .qa-card{
    background:#fff; border:1px solid #eef1f5; border-radius:12px;
    transition:.15s transform, .15s box-shadow;
  }
  .qa-card:hover{ transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }
  .qa-card .icon{
    width:38px; height:38px; border-radius:8px; display:grid; place-items:center;
    color:#fff;
  }
  .icon-blue{  background:#0d6efd; }
  .icon-green{ background:#198754; }
  .icon-orange{ background:#fd7e14; }
  .icon-cyan{   background:#0aa2c0; }
  .icon-indigo{ background:#6610f2; }
  .icon-pink{   background:#d63384; }

  @media (max-width: 576px){
    .brand-badge img{ height:36px; border-radius:4px; }
    .dash-hero h2{ font-size:1.35rem; }
    .dash-hero p{  font-size:.95rem; }
  }
</style>

<div class="container-xxl my-4">

  <!-- ===== HERO ===== -->
  <div class="dash-hero p-4 p-md-5 mb-4">
    <?php if ($heroUrl): ?>
      <div class="hero-img" style="background-image:url('<?= htmlspecialchars($heroUrl) ?>');"></div>
    <?php endif; ?>
    <div class="overlay"></div>

    <div class="content">
      <div class="brand-badge mb-3">
        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logotipo">
        <div class="text">
          <h6 class="mb-1">Dirección Departamental de Educación de Sololá</h6>
          <small>Portal de Administración</small>
        </div>
      </div>

      <h2 class="fw-bold mb-2">Hola, <?= $nombre ?> 👋</h2>
      <p class="mb-3">Has iniciado sesión como <span class="badge bg-light text-dark"><?= $rol ?></span>. 
        Usa los accesos rápidos o revisa el estado general del sitio.</p>

      <div class="d-flex flex-wrap gap-2">
        <a href="secciones/portafolio/index.php" class="btn btn-light btn-sm">
          <i class="fa-solid fa-bullhorn me-1"></i> Gestionar Noticias
        </a>
        <a href="secciones/servicios/index.php" class="btn btn-outline-light btn-sm">
          <i class="fa-solid fa-list-check me-1"></i> Servicios
        </a>
        <a href="secciones/configuraciones/index.php" class="btn btn-outline-light btn-sm">
          <i class="fa-solid fa-gear me-1"></i> Configuraciones
        </a>
      </div>
    </div>
  </div>

  <!-- ===== RESUMEN / ESTADÍSTICAS ===== -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon"><i class="fa-solid fa-bullhorn"></i></div>
          <div>
            <h5><?= $counts['noticias'] ?></h5>
            <small>Noticias</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100" style="border-left-color:#198754;">
        <div class="d-flex align-items-center gap-3">
          <div class="icon" style="background:rgba(25,135,84,.12); color:#198754;"><i class="fa-solid fa-list-check"></i></div>
          <div>
            <h5><?= $counts['servicios'] ?></h5>
            <small>Servicios</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100" style="border-left-color:#fd7e14;">
        <div class="d-flex align-items-center gap-3">
          <div class="icon" style="background:rgba(253,126,20,.12); color:#fd7e14;"><i class="fa-solid fa-people-group"></i></div>
          <div>
            <h5><?= $counts['equipo'] ?></h5>
            <small>Equipo</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100" style="border-left-color:#0aa2c0;">
        <div class="d-flex align-items-center gap-3">
          <div class="icon" style="background:rgba(10,162,192,.12); color:#0aa2c0;"><i class="fa-solid fa-timeline"></i></div>
          <div>
            <h5><?= $counts['entradas'] ?></h5>
            <small>Entradas</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100" style="border-left-color:#6610f2;">
        <div class="d-flex align-items-center gap-3">
          <div class="icon" style="background:rgba(102,16,242,.12); color:#6610f2;"><i class="fa-solid fa-user-shield"></i></div>
          <div>
            <h5><?= $counts['usuarios'] ?></h5>
            <small>Usuarios</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="stat-card h-100" style="border-left-color:#d63384;">
        <div class="d-flex align-items-center gap-3">
          <div class="icon" style="background:rgba(214,51,132,.12); color:#d63384;"><i class="fa-solid fa-sliders"></i></div>
          <div>
            <h5><?= $counts['config'] ?></h5>
            <small>Config.</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== ACCESOS RÁPIDOS ===== -->
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="row g-3">
        <!-- Noticias -->
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/portafolio/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-blue"><i class="fa-solid fa-bullhorn"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Noticias</h6>
                <small class="text-muted">Publica y edita comunicados y notas informativas.</small>
              </div>
            </div>
          </a>
        </div>
        <!-- Servicios -->
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/servicios/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-green"><i class="fa-solid fa-list-check"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Servicios</h6>
                <small class="text-muted">Actualiza enlaces y documentos de apoyo.</small>
              </div>
            </div>
          </a>
        </div>
        <!-- Equipo -->
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/equipo/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-orange"><i class="fa-solid fa-people-group"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Equipo</h6>
                <small class="text-muted">Gestión de personal y contactos.</small>
              </div>
            </div>
          </a>
        </div>
        <!-- Entradas / Historia -->
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/historia/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-cyan"><i class="fa-solid fa-timeline"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Historia</h6>
                <small class="text-muted">Cronología de hitos y actividades.</small>
              </div>
            </div>
          </a>
        </div>
        <!-- Portada/Logo -->
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/inicio/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-indigo"><i class="fa-solid fa-image"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Portada y Logo</h6>
                <small class="text-muted">Actualiza hero y logotipo institucional.</small>
              </div>
            </div>
          </a>
        </div>
        <!-- Usuarios (solo admin) -->
        <?php if (($GLOBALS['_SESSION']['rol'] ?? '') === 'admin'): ?>
        <div class="col-12 col-md-6">
          <a class="text-decoration-none d-block qa-card p-3 h-100" href="secciones/usuarios/index.php">
            <div class="d-flex align-items-start gap-3">
              <div class="icon icon-pink"><i class="fa-solid fa-user-shield"></i></div>
              <div>
                <h6 class="mb-1 text-dark">Usuarios</h6>
                <small class="text-muted">Altas, permisos y seguridad.</small>
              </div>
            </div>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Columna lateral: Sistema -->
    
  </div>
</div>

<?php include(__DIR__ . "/templates/footer.php"); ?>
