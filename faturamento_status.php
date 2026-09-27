<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: faturamento.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('UPDATE faturas SET status=? WHERE id=?');
$stmt->execute([$_POST['status'], (int) $_POST['fatura_id']]);

redirecionar_com_flash('faturamento.php', 'Status da fatura atualizado.', 'success');
