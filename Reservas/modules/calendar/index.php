<?php
// modules/calendar/index.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../auth/auth_guard.php';
require_login(); // solo usuarios logueados

// tipo de recurso: vehiculo | salon
$tipo = $_GET['tipo'] ?? 'vehiculo';
if (!in_array($tipo, ['vehiculo', 'salon'], true)) {
    $tipo = 'vehiculo';
}

// modo embebido (iframe) o página normal
$embed = isset($_GET['embed']) && $_GET['embed'] == '1';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Calendario de reservas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            <?php if ($embed): ?>
            background: transparent;
            <?php else: ?>
            background: #0f2b3a;
            <?php endif; ?>
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        #wrapper {
            <?php if ($embed): ?>
            padding: 0;
            <?php else: ?>
            max-width: 1100px;
            margin: 20px auto;
            padding: 16px;
            background: #f6f9fc;
            border-radius: 12px;
            <?php endif; ?>
        }
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div id="wrapper">
    <?php if (!$embed): ?>
        <h4>Calendario de reservas — <?= $tipo === 'vehiculo' ? 'Vehículos' : 'Salones' ?></h4>
        <hr>
    <?php endif; ?>

    <div id="calendar"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: {
            url: '../../api/events.php',
            method: 'GET',
            extraParams: {
                tipo: '<?= $tipo ?>'
            },
            failure: () => alert('No se pudo cargar el calendario')
        },
        eventDidMount: (info) => {
            const estado = info.event.extendedProps.estado;
            if (estado === 'pendiente') info.el.style.opacity = 0.8;
            if (estado === 'cancelada') info.el.style.textDecoration = 'line-through';
        },
        dateClick: (info) => {
            <?php if (isset($_SESSION['rol'])): ?>
            // crear reserva en esa fecha
            window.top.location.href = '../reservas/nueva.php?fecha=' + info.dateStr + '&tipo=<?= $tipo ?>';
            <?php endif; ?>
        },
        eventClick: (info) => {
            <?php if (isset($_SESSION['rol'])): ?>
            // editar reserva existente
            window.top.location.href = '../reservas/nueva.php?id=' + info.event.id;
            <?php endif; ?>
        }
    });

    calendar.render();
});
</script>
</body>
</html>

