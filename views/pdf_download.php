<?php
$file = (string)($_GET['file'] ?? '');
$file = str_replace(['\\', "\0"], ['/', ''], $file);
$file = ltrim($file, '/');

$base = realpath(__DIR__.'/../uploads');
$real = realpath(__DIR__.'/../'.$file);

if(
    $file === '' ||
    str_contains($file, '..') ||
    !$base || !$real ||
    !is_file($real) ||
    !str_starts_with($real, $base.DIRECTORY_SEPARATOR) ||
    strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf'
){
    http_response_code(404);
    echo 'Fichier introuvable ou non autorisé';
    exit;
}

ge_send_file_download($real, basename($real), 'application/pdf');
