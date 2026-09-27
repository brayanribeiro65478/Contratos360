<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('UPDATE usuarios SET ativo = 1 - ativo WHERE id=?');
$stmt->execute([(int) $_POST['usuario_id']]);

header('Location: usuarios.php');
exit;
