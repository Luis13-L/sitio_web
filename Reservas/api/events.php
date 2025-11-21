<?php
// api/events.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// 1) Validar parámetro tipo
$tipo = $_GET['tipo'] ?? '';
if (!in_array($tipo, ['vehiculo', 'salon'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo inválido']);
    exit;
}

try {
    // 2) Conexión a tu BD usando config/db.php
    $pdo = db(); // ← función definida en config/db.php

    // 3) Consultar tu modelo real: reservas + recursos
    $sql = "
        SELECT
            r.id,
            r.titulo,
            r.descripcion,
            r.solicitante,
            r.inicio,
            r.fin,
            r.estado,
            rc.id     AS recurso_id,
            rc.nombre AS recurso_nombre
        FROM reservas r
        INNER JOIN recursos rc ON rc.id = r.recurso_id
        WHERE rc.tipo   = :tipo
          AND rc.activo = 1
          AND r.estado <> 'cancelada'
        ORDER BY r.inicio ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tipo' => $tipo]);
    $rows = $stmt->fetchAll();

    // 4) Adaptar al formato que FullCalendar espera
    $events = [];

    foreach ($rows as $row) {
        // Color por estado (ajústalo a gusto)
        switch ($row['estado']) {
            case 'confirmada':
                $color = '#198754'; // verde
                break;
            case 'cancelada':
                $color = '#dc3545'; // rojo
                break;
            default: // pendiente
                $color = '#0d6efd'; // azul
                break;
        }

        $events[] = [
            // IMPORTANTE: este id es el id de la tabla reservas
            'id'    => (int)$row['id'],
            'title' => $row['titulo'],
            'start' => $row['inicio'],
            'end'   => $row['fin'],
            'backgroundColor' => $color,
            'borderColor'     => $color,
            'extendedProps'   => [
                'descripcion'    => $row['descripcion'],
                'solicitante'    => $row['solicitante'],
                'estado'         => $row['estado'],
                'recurso_id'     => $row['recurso_id'],
                'recurso_nombre' => $row['recurso_nombre'],
            ],
        ];
    }

    echo json_encode($events);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

