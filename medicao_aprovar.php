<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medicoes.php');
    exit;
}

$medicaoId = (int) $_POST['medicao_id'];
$db = get_db();
$stmt = $db->prepare("UPDATE medicoes SET status='Aprovada' WHERE id=?");
$stmt->execute([$medicaoId]);

redirecionar_com_flash('medicao_detalhe.php?id=' . $medicaoId, 'Medição aprovada.', 'success');
