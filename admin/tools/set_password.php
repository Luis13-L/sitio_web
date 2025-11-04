<?php
require __DIR__ . '/../bd.php'; // ajusta si tu bd.php está en otro lugar

$usuario = 'Jgomez';
$nueva   = 'MiClave123!';

$hash = password_hash($nueva, PASSWORD_DEFAULT);
$st = $conexion->prepare("UPDATE tbl_usuarios SET password_hash = :h WHERE usuario = :u");
$st->execute([':h' => $hash, ':u' => $usuario]);

echo "Listo. Hash actualizado para $usuario";
