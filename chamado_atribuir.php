<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chamados.php');
    exit;
}

$chamadoId = (int) $_POST['chamado_id'];
$db = get_db();
$stmt = $db->prepare("UPDATE chamados SET tecnico_id=?, status='Em triagem' WHERE id=?");
$stmt->execute([$_POST['tecnico_id'], $chamadoId]);

redirecionar_com_flash('chamado_detalhe.php?id=' . $chamadoId, 'Técnico atribuído ao chamado.', 'success');
