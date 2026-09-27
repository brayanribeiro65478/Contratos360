<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin', 'tecnico');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chamados.php');
    exit;
}

$chamadoId = (int) $_POST['chamado_id'];
$db = get_db();
$stmt = $db->prepare('UPDATE chamados SET status=? WHERE id=?');
$stmt->execute([$_POST['status'], $chamadoId]);

redirecionar_com_flash('chamado_detalhe.php?id=' . $chamadoId, 'Status do chamado atualizado.', 'success');
