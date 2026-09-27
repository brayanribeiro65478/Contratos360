<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$arquivo = basename($_GET['arquivo'] ?? '');
$caminho = UPLOAD_DIR . $arquivo;

if ($arquivo === '' || !file_exists($caminho)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit;
