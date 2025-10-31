<?php

    include("admin/bd.php");

    //Seleccionar registros inicio
            if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

            // Traer último logo y hero
            $st = $conexion->prepare("
            SELECT t1.componente, t1.imagen
            FROM tbl_inicioo t1
            JOIN (
                SELECT componente, MAX(ID) AS max_id
                FROM tbl_inicioo
                WHERE componente IN ('logo','hero')
                GROUP BY componente
            ) t2 ON t1.componente = t2.componente AND t1.ID = t2.max_id
            ");
            $st->execute();
            $inicioo = ['logo'=>null, 'hero'=>null];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $inicioo[$row['componente']] = $row['imagen'];
            }

            $logoUrl = $inicioo['logo'] ? "assets/img/".rawurlencode($inicioo['logo']) : "assets/img/logo.png";
            $heroUrl = $inicioo['hero'] ? "assets/img/".rawurlencode($inicioo['hero']) : "assets/img/header-bg.jpg";


        // $sentencia=$conexion->prepare("SELECT * FROM `tbl_inicioo`");
        // $sentencia->execute();
        // $lista_inicioo=$sentencia->fetchAll(PDO::FETCH_ASSOC);





      //Obtener los registros

      $sentencia=$conexion->prepare("SELECT * FROM `tbl_servicios`");
      $sentencia->execute();
      $lista_servicios=$sentencia->fetchAll(PDO::FETCH_ASSOC);

      //obtener registros de portafolio



        // Paginación Portafolio/Noticias

        $per_page = 4; // SIEMPRE define cuántos por página (mínimo 1)
        if (!is_int($per_page) || $per_page < 1) { $per_page = 4; }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $per_page;

        // Total de registros
        $total = (int)$conexion->query("SELECT COUNT(*) FROM tbl_portafolio")->fetchColumn();
        $total_pages = max(1, (int)ceil($total / $per_page));

        // Traer solo los de la página actual
        $st = $conexion->prepare("
            SELECT id, titulo, subtitulo, descripcion, imagen, cliente, categoria, url
            FROM tbl_portafolio
            ORDER BY id DESC
            LIMIT :lim OFFSET :off
        ");
        $st->bindValue(':lim', $per_page, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $lista_portafolio = $st->fetchAll(PDO::FETCH_ASSOC);

        // Helper
        if (!function_exists('h')) {
        function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
        }

        //   $sentencia=$conexion->prepare("SELECT * FROM `tbl_portafolio`");
        //   $sentencia->execute();
        //   $lista_portafolio=$sentencia->fetchAll(PDO::FETCH_ASSOC);



        //  Fin Paginación Portafolio/Noticias



        //Seleccionar registros entradas
        $sentencia=$conexion->prepare("SELECT * FROM `tbl_entradas`");
        $sentencia->execute();
        $lista_entradas=$sentencia->fetchAll(PDO::FETCH_ASSOC);

         //Seleccionar registros equipo
        $sentencia=$conexion->prepare("SELECT * FROM `tbl_equipo`");
        $sentencia->execute();
        $lista_equipo=$sentencia->fetchAll(PDO::FETCH_ASSOC);

        //Seleccionar registros configuraciones
        $sentencia=$conexion->prepare("SELECT * FROM `tbl_confifiguraciones`");
        $sentencia->execute();
        $lista_configuraciones=$sentencia->fetchAll(PDO::FETCH_ASSOC);

        



?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Sitio Web</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link rel="stylesheet" type="text/css" href="css/styles.css?v=3"/>
    </head>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
        const nav = document.getElementById('mainNav');
        if (!nav) return;

        function toggleShrink(){
        if (window.scrollY > 10) nav.classList.add('navbar-shrink');
        else nav.classList.remove('navbar-shrink');
        }

        toggleShrink();                 // estado correcto al cargar
        window.addEventListener('scroll', toggleShrink);
        });
    </script>

  

    <body id="page-top">
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
            <div class="container">
                <a class="navbar-brand" href="#page-top"> <img class="logo-nav" src="<?= h($logoUrl) ?>" alt="Logo" /></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    Menu
                    <i class="fas fa-bars ms-1"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav text-uppercase ms-auto py-4 py-lg-0">
                        <li class="nav-item"><a class="nav-link" href="#services">Servicios</a></li>
                        <li class="nav-item"><a class="nav-link" href="#portfolio">Noticias</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about">Historia</a></li>
                        <li class="nav-item"><a class="nav-link" href="#team">Equipo</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contact">Contacto</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Masthead-->
        <header class="masthead"
            style="background-image:url('<?= h($heroUrl) ?>');
            background-repeat:no-repeat; background-attachment:scroll;
            background-position:center center; background-size:cover;">
            <div class="container">
                <div class="masthead-subheading"><?php echo $lista_configuraciones[0]['valor']; ?></div>
                <div class="masthead-heading text-uppercase"><?php echo $lista_configuraciones[1]['valor']; ?></div>
                <a class="btn btn-xl text-uppercase btn-empezar" href="<?php echo $lista_configuraciones[3]['valor']; ?>"><?php echo $lista_configuraciones[2]['valor']; ?></a>
            </div>
        </header>
        <!-- Services-->
        <section class="page-section" id="services">
          <div class="container">
            <div class="text-center">
              <h2 class="section-heading text-uppercase"><?= htmlspecialchars($lista_configuraciones[4]['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
              <h3 class="section-subheading text-muted"><?= htmlspecialchars($lista_configuraciones[5]['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            </div>

            <div class="row text-center g-4">
              <?php foreach ($lista_servicios as $s): 
                $img  = htmlspecialchars($s['icono'] ?? '', ENT_QUOTES, 'UTF-8');
                $tit  = htmlspecialchars($s['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                $desc = htmlspecialchars($s['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
              ?>
              <div class="col-12 col-md-6 col-lg-4">
                <div class="service-card h-100 d-flex flex-column align-items-center text-center">
                  <div class="service-icon mb-3">
                    <?php if ($img): ?>
                      <img
                        src="assets/img/services/<?= $img ?>"
                        alt="<?= $tit ?>"
                        loading="lazy" width="96" height="96"
                        class="img-fluid" />
                    <?php else: ?>
                      <!-- Fallback si no hay imagen -->
                      <span class="fa-stack fa-3x">
                        <i class="fas fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa-solid fa-image fa-stack-1x fa-inverse"></i>
                      </span>
                    <?php endif; ?>
                  </div>
                  <h4 class="my-2"><?= $tit ?></h4>
                  <p class="text-muted mb-0"><?= $desc ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>



        <!-- Portal de Noticias-->


<section class="page-section bg-light" id="portfolio">
  <div class="container">
    <div class="text-center">
      <h2 class="section-heading text-uppercase"><?= h($lista_configuraciones[6]['valor']) ?></h2>
      <h3 class="section-subheading text-muted"><?= h($lista_configuraciones[7]['valor']) ?></h3>
    </div>

    <div class="row g-4">
      <?php foreach ($lista_portafolio as $registros): $pid=(int)($registros['id'] ?? $registros['ID'] ?? 0); ?>
      <div class="col-12 col-md-6">
        <article class="card h-100 portfolio-card">
          <a class="text-decoration-none" data-bs-toggle="modal" href="#portfolioModal<?= $pid ?>">
            <!-- Cuadro 4:3 que obliga a la imagen a adaptarse -->
            <div class="thumb-4x3">
              <img class="thumb-img"
                   src="assets/img/portfolio/<?= h($registros['imagen']) ?>"
                   alt="<?= h($registros['titulo']) ?>" loading="lazy">
            </div>
          </a>
          <div class="card-body">
            <h3 class="h5 mb-1"><?= h($registros['titulo']) ?></h3>
            <p class="text-muted small mb-0"><?= h($registros['subtitulo']) ?></p>
          </div>
        </article>
      </div>

      <!-- MODAL -->
      <div class="portfolio-modal modal fade" id="portfolioModal<?= $pid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <button class="close-modal" data-bs-dismiss="modal" aria-label="Cerrar">
              <img src="assets/img/close-icon.svg" alt="Cerrar modal" />
            </button>
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <div class="modal-body">
                    <h2 class="text-uppercase"><?= h($registros['titulo']) ?></h2>
                    <p class="item-intro text-muted"><?= h($registros['subtitulo']) ?></p>
                    <img class="img-fluid d-block mx-auto modal-img" loading="lazy"
                         src="assets/img/portfolio/<?= h($registros['imagen']) ?>"
                         alt="<?= h($registros['titulo']) ?>"
                         width="1200" height="800" />
                    <p><?= nl2br(h($registros['descripcion'])) ?></p>
                    <ul class="list-inline">
                      <li><strong>Cliente:</strong> <?= h($registros['cliente']) ?></li>
                      <li><strong>Categoría:</strong> <?= h($registros['categoria']) ?></li>
                      <?php if (!empty($registros['url'])): ?>
                      <li><strong>URL:</strong>
                        <a href="<?= h($registros['url']) ?>" target="_blank" rel="noopener noreferrer">
                          <?= h($registros['url']) ?>
                        </a>
                      </li>
                      <?php endif; ?>
                    </ul>
                    <button class="btn btn-primary btn-xl text-uppercase" data-bs-dismiss="modal" type="button">
                      <i class="fas fa-xmark me-1" aria-hidden="true"></i> Cerrar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Paginador -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Paginación del portafolio" class="mt-4">
      <ul class="pagination justify-content-center">
        <!-- Anterior -->
        <li class="page-item <?= ($page<=1)?'disabled':'' ?>">
          <a class="page-link" href="?page=<?= max(1,$page-1) ?>#portfolio" aria-label="Anterior">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>

        <!-- Números -->
        <?php
          // Opcional: acotar para muchas páginas
          $start = max(1, $page-2);
          $end   = min($total_pages, $page+2);
          if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1#portfolio">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          }
          for ($i=$start; $i<=$end; $i++):
        ?>
          <li class="page-item <?= ($i===$page)?'active':'' ?>">
            <a class="page-link" href="?page=<?= $i ?>#portfolio"><?= $i ?></a>
          </li>
        <?php endfor;
          if ($end < $total_pages) {
            if ($end < $total_pages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'#portfolio">'.$total_pages.'</a></li>';
          }
        ?>

        <!-- Siguiente -->
        <li class="page-item <?= ($page>=$total_pages)?'disabled':'' ?>">
          <a class="page-link" href="?page=<?= min($total_pages,$page+1) ?>#portfolio" aria-label="Siguiente">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>

  </div>
</section>



      

        <!-- Historia-->
        <section class="page-section" id="about">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase"><?php echo $lista_configuraciones[8]['valor']; ?></h2>
                    <h3 class="section-subheading text-muted"><?php echo $lista_configuraciones[9]['valor']; ?></h3>
                </div>
                <ul class="timeline">

                <?php 
                
                $contador=1;
                
                foreach ($lista_entradas as $registros){ ?>



                    <li <?php echo (($contador%2)==0)?'class="timeline-inverted"':"";?>>
                        <div class="timeline-image"><img class="rounded-circle img-fluid" src="assets/img/about/<?php echo $registros['imagen'];?>" alt="..." /></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h4><?php echo $registros['fecha']; ?></h4>
                                <h4 class="subheading"><?php echo $registros['titulo'];?></h4>
                            </div>
                            <div class="timeline-body"><p class="text-muted"><?php echo $registros['descripcion'];?></p></div>
                        </div>
                    </li>

                <?php $contador++; } ?>

                    <li class="timeline-inverted">
                        <div class="timeline-image">
                            <h4>
                            <?php echo $lista_configuraciones[10]['valor']; ?>
                            </h4>
                        </div>
                    </li>
                </ul>
            </div>
        </section>


        <!-- Team-->


        

        <section class="page-section bg-light" id="team">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase"><?php echo $lista_configuraciones[11]['valor']; ?></h2>
                    <h3 class="section-subheading text-muted"><?php echo $lista_configuraciones[12]['valor']; ?></h3>
                </div>
                <div class="row">
                <?php foreach ($lista_equipo as $registros){ ?>
                    <div class="col-lg-4">
                        <div class="team-member text-center">
                          <img
                            class="team-avatar mx-auto"
                            src="assets/img/team/<?= htmlspecialchars($registros['imagen']) ?>"
                            alt="<?= htmlspecialchars($registros['nombrecompleto']) ?>"
                            loading="lazy" />

                          <h4 class="mt-4 mb-1"><?= htmlspecialchars($registros['nombrecompleto']) ?></h4>
                          <p class="text-muted mb-3"><?= htmlspecialchars($registros['puesto']) ?></p>

                          <!-- Corrijo: icono de LinkedIn para LinkedIn y sobre de email para correo -->
                          <?php $correo = trim($registros['correo']); $linkedin = trim($registros['linkedin']); ?>
                          <?php if (!empty($linkedin)): ?>
                            <a class="btn btn-dark btn-social mx-2" href="<?= htmlspecialchars($linkedin) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                              <i class="fab fa-linkedin-in"></i>
                            </a>
                          <?php endif; ?>

                          <?php if (!empty($correo)): ?>
                            <a class="btn btn-dark btn-social mx-2" href="mailto:<?= htmlspecialchars($correo) ?>" aria-label="Correo">
                              <i class="fa-solid fa-envelope"></i>
                            </a>
                          <?php endif; ?>
                        </div>
                      </div>

                <?php } ?>
                </div>
                <!--<div class="row">
                    <div class="col-lg-8 mx-auto text-center"><p class="large text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aut eaque, laboriosam veritatis, quos non quis ad perspiciatis, totam corporis ea, alias ut unde.</p></div>
                </div>-->
            </div>
        </section>
        <!--
         Clients
        <div class="py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-3 col-sm-6 my-3">
                        <a href="#!"><img class="img-fluid img-brand d-block mx-auto" src="assets/img/logos/microsoft.svg" alt="..." aria-label="Microsoft Logo" /></a>
                    </div>
                    <div class="col-md-3 col-sm-6 my-3">
                        <a href="#!"><img class="img-fluid img-brand d-block mx-auto" src="assets/img/logos/google.svg" alt="..." aria-label="Google Logo" /></a>
                    </div>
                    <div class="col-md-3 col-sm-6 my-3">
                        <a href="#!"><img class="img-fluid img-brand d-block mx-auto" src="assets/img/logos/facebook.svg" alt="..." aria-label="Facebook Logo" /></a>
                    </div>
                    <div class="col-md-3 col-sm-6 my-3">
                        <a href="#!"><img class="img-fluid img-brand d-block mx-auto" src="assets/img/logos/ibm.svg" alt="..." aria-label="IBM Logo" /></a>
                    </div>
                </div>
            </div>
        </div>-->
        <!-- Contact-->
       <section class="page-section contact-full" id="contact">
          <div class="contact-hero" role="img" aria-label="Contacto">
            <div class="contact-hero__inner">
              <div class="text-center text-white">
                <h2 class="ssection-heading text-uppercase headline-badge">
                  <?= htmlspecialchars($lista_configuraciones[13]['valor'] ?? '') ?>
                </h2>
                <h3 class="section-subheading headline-badge">
                  <?= htmlspecialchars($lista_configuraciones[14]['valor'] ?? '') ?>
                </h3>
              </div>
            </div>
          </div>
        </section>

        <!-- Footer-->
        <footer class="footer py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-start">Copyright &copy; Your Website 2023</div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-dark btn-social mx-2" href="<?php echo $lista_configuraciones[15]['valor']; ?>" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-dark btn-social mx-2" href="<?php echo $lista_configuraciones[16]['valor']; ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-dark btn-social mx-2" href="<?php echo $lista_configuraciones[17]['valor']; ?>" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a class="link-dark text-decoration-none me-3" href="#!">Privacy Policy</a>
                        <a class="link-dark text-decoration-none" href="#!">Terms of Use</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Portfolio Modals-->
        <!-- Portfolio item 1 modal popup-->
      

        
        <!-- Portfolio item 2 modal popup-->

        <!-- Portfolio item 3 modal popup-->

        <!-- Portfolio item 4 modal popup-->

        <!-- Portfolio item 5 modal popup-->

        <!-- Portfolio item 6 modal popup-->

        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
        <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
        <!-- * *                               SB Forms JS                               * *-->
        <!-- * * Activate your form at https://startbootstrap.com/solution/contact-forms * *-->
        <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
        <script src="https://cdn.startbootstrap.com/sb-forms-latest.js"></script>
    </body>
</html>
