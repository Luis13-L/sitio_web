<?php
declare(strict_types=1);

// api/reservas_save.php

require_once __DIR__ . '/../auth/auth_guard.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Obligamos login (ya tienes user_id y rol en sesión)
require_login();

header('Content-Type: application/json; charset=utf-8');

// ==== Solo POST ====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// ==== CSRF (usando helper del guard) ====
$csrfToken = $_POST['csrf_token'] ?? null;
verify_csrf_or_fail($csrfToken);

// ==== Datos del formulario ====
$accion       = $_POST['accion'] ?? 'agregar';   // agregar | actualizar | cancelar
$idReserva    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$recursoId    = (int)($_POST['recurso_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$solicitante  = trim($_POST['solicitante'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$fecha        = $_POST['fecha'] ?? '';
$horaInicio   = $_POST['hora'] ?? '';
$horaFin      = $_POST['hora_fin'] ?? '';

// Usuario logueado
$creadoPor = current_user_id();
if ($creadoPor === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

/**
 * Maneja el adjunto PDF para una reserva:
 * - Solo si se envió adjunto_pdf
 * - Valida tamaño (5 MB) y MIME real
 * - Guarda en assets/docs/reservas/
 * - Inserta o actualiza fila en tabla adjuntos
 */
function manejarAdjuntoPdf(PDO $pdo, int $reservaId): void
{
    if (
        !isset($_FILES['adjunto_pdf']) ||
        !is_array($_FILES['adjunto_pdf']) ||
        (int)$_FILES['adjunto_pdf']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        // No se envió archivo, salimos sin hacer nada.
        return;
    }

    $file = $_FILES['adjunto_pdf'];

    // Errores básicos de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo PDF.');
    }

    // Límite de tamaño (5 MB)
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('El archivo PDF supera el tamaño máximo permitido (5 MB).');
    }

    // Validar MIME real como PDF
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';

    if ($mime !== 'application/pdf') {
        throw new RuntimeException('El archivo adjunto debe ser un PDF válido.');
    }

    // Carpeta de destino (desde /api/ → ../assets/docs/reservas)
    $uploadBase = realpath(__DIR__ . '/../assets/docs/reservas');
    if ($uploadBase === false) {
        throw new RuntimeException('Carpeta de adjuntos no encontrada.');
    }

    if (!is_dir($uploadBase)) {
        if (!mkdir($uploadBase, 0775, true) && !is_dir($uploadBase)) {
            throw new RuntimeException('No se pudo crear la carpeta de adjuntos.');
        }
    }

    // Nombre aleatorio para el archivo
    $nuevoNombre = bin2hex(random_bytes(16)) . '.pdf';
    $rutaFisica  = $uploadBase . DIRECTORY_SEPARATOR . $nuevoNombre;

    if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
        throw new RuntimeException('No se pudo guardar el archivo PDF en el servidor.');
    }

    // Ruta relativa que se guarda en BD (desde la raíz del sitio)
    $rutaRelativa   = 'assets/docs/reservas/' . $nuevoNombre;
    $nombreOriginal = substr((string)$file['name'], 0, 180);

    // ¿Ya existe adjunto para esta reserva?
    $stmt = $pdo->prepare("SELECT id, archivo_pdf FROM adjuntos WHERE reserva_id = :rid");
    $stmt->execute([':rid' => $reservaId]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        // Opcional: borrar el archivo anterior
        $viejaRuta = realpath(__DIR__ . '/../' . $existente['archivo_pdf']);
        if ($viejaRuta && is_file($viejaRuta)) {
            @unlink($viejaRuta);
        }

        // Actualizar fila
        $upd = $pdo->prepare("
            UPDATE adjuntos
            SET archivo_pdf    = :archivo_pdf,
                nombre_original = :nombre_original,
                subido_en       = NOW()
            WHERE reserva_id   = :rid
        ");
        $upd->execute([
            ':archivo_pdf'     => $rutaRelativa,
            ':nombre_original' => $nombreOriginal,
            ':rid'             => $reservaId,
        ]);

    } else {
        // Insertar nuevo adjunto
        $ins = $pdo->prepare("
            INSERT INTO adjuntos (reserva_id, archivo_pdf, nombre_original, subido_en)
            VALUES (:rid, :archivo_pdf, :nombre_original, NOW())
        ");
        $ins->execute([
            ':rid'             => $reservaId,
            ':archivo_pdf'     => $rutaRelativa,
            ':nombre_original' => $nombreOriginal,
        ]);
    }
}

/**
 * Verifica si existe un conflicto de horario para un recurso dado:
 * Devuelve true si hay choque, false si está libre.
 */
function existeConflictoReserva(PDO $pdo, int $recursoId, string $inicio, string $fin, ?int $idActual = null): bool
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM reservas
        WHERE recurso_id = :recurso_id
          AND estado <> 'cancelada'
          AND :inicio < fin
          AND :fin    > inicio
    ";
    $params = [
        ':recurso_id' => $recursoId,
        ':inicio'     => $inicio,
        ':fin'        => $fin,
    ];

    if ($idActual !== null && $idActual > 0) {
        $sql .= " AND id <> :idActual";
        $params[':idActual'] = $idActual;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return isset($row['total']) && (int)$row['total'] > 0;
}

/*
 * VALIDACIONES
 * - Para cancelar: solo necesitamos un ID válido.
 * - Para agregar / actualizar: todos los campos obligatorios.
 */
if ($accion === 'cancelar') {

    if ($idReserva <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID de reserva inválido']);
        exit;
    }

} else { // agregar / actualizar

    if (
        $recursoId <= 0 ||
        $titulo === '' ||
        $solicitante === '' ||
        $fecha === '' ||
        $horaInicio === '' ||
        $horaFin === ''
    ) {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
        exit;
    }
}

$inicio = $fecha && $horaInicio ? $fecha . ' ' . $horaInicio . ':00' : null;
$fin    = $fecha && $horaFin    ? $fecha . ' ' . $horaFin   . ':00' : null;

// ==== Validación de orden de horarios (servidor) ====
if ($accion !== 'cancelar') {
    if (!$inicio || !$fin) {
        echo json_encode(['ok' => false, 'error' => 'Fecha y hora de inicio/fin no válidas']);
        exit;
    }

    $dtInicio = \DateTime::createFromFormat('Y-m-d H:i:s', $inicio);
    $dtFin    = \DateTime::createFromFormat('Y-m-d H:i:s', $fin);

    if (!$dtInicio || !$dtFin) {
        echo json_encode(['ok' => false, 'error' => 'Formato de fecha/hora no válido']);
        exit;
    }

    if ($dtInicio >= $dtFin) {
        echo json_encode(['ok' => false, 'error' => 'La hora de fin debe ser mayor que la hora de inicio.']);
        exit;
    }
}

try {
    $pdo = db();

    /* =======================
     *  AGREGAR NUEVA RESERVA
     * ======================= */
    if ($accion === 'agregar') {

        // Validar choque de horarios
        if (existeConflictoReserva($pdo, $recursoId, $inicio, $fin, null)) {
            echo json_encode([
                'ok'    => false,
                'error' => 'Ya existe otra reserva para este recurso en el horario indicado.'
            ]);
            exit;
        }

        $sql = "
            INSERT INTO reservas
                (recurso_id, titulo, solicitante, descripcion, inicio, fin, estado, creado_por, creado_en, actualizado_en)
            VALUES
                (:recurso_id, :titulo, :solicitante, :descripcion, :inicio, :fin, 'pendiente', :creado_por, NOW(), NOW())
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':recurso_id'  => $recursoId,
            ':titulo'      => $titulo,
            ':solicitante' => $solicitante,
            ':descripcion' => $descripcion,
            ':inicio'      => $inicio,
            ':fin'         => $fin,
            ':creado_por'  => $creadoPor,
        ]);

        $nuevoId = (int)$pdo->lastInsertId();

        // Manejo de adjunto PDF (si se envió)
        manejarAdjuntoPdf($pdo, $nuevoId);

        echo json_encode([
            'ok' => true,
            'id' => $nuevoId,
        ]);
        exit;
    }

    /* =======================
     *  ACTUALIZAR RESERVA
     * ======================= */
    if ($accion === 'actualizar') {

        if ($idReserva <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de reserva inválido']);
            exit;
        }

        // Validar choque de horarios (ignorando esta misma reserva)
        if (existeConflictoReserva($pdo, $recursoId, $inicio, $fin, $idReserva)) {
            echo json_encode([
                'ok'    => false,
                'error' => 'Ya existe otra reserva para este recurso en el horario indicado.'
            ]);
            exit;
        }

        $sql = "
            UPDATE reservas
            SET recurso_id     = :recurso_id,
                titulo         = :titulo,
                solicitante    = :solicitante,
                descripcion    = :descripcion,
                inicio         = :inicio,
                fin            = :fin,
                actualizado_en = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':recurso_id'  => $recursoId,
            ':titulo'      => $titulo,
            ':solicitante' => $solicitante,
            ':descripcion' => $descripcion,
            ':inicio'      => $inicio,
            ':fin'         => $fin,
            ':id'          => $idReserva,
        ]);

        // Manejo de adjunto PDF (si se envió uno nuevo)
        manejarAdjuntoPdf($pdo, $idReserva);

        echo json_encode(['ok' => true, 'id' => $idReserva]);
        exit;
    }

    /* =======================
     *  CANCELAR RESERVA
     * ======================= */
    if ($accion === 'cancelar') {

        $sql = "
            UPDATE reservas
            SET estado = 'cancelada',
                actualizado_en = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $idReserva]);

        echo json_encode(['ok' => true, 'id' => $idReserva]);
        exit;
    }

    // Si llega otra acción no soportada
    echo json_encode(['ok' => false, 'error' => 'Acción no soportada']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}


