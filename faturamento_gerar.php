<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: faturamento.php');
    exit;
}

$db = get_db();
$medicaoId = (int) $_POST['medicao_id'];

$stmt = $db->prepare('SELECT * FROM medicoes WHERE id=?');
$stmt->execute([$medicaoId]);
$medicao = $stmt->fetch();
if (!$medicao) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$numero = gerar_numero_fatura($db);

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        "INSERT INTO faturas (medicao_id, numero, valor, status, data_emissao, data_vencimento)
         VALUES (?,?,?, 'Emitida', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))"
    );
    $stmt->execute([$medicaoId, $numero, $medicao['valor_total']]);

    $stmt = $db->prepare("UPDATE medicoes SET status='Faturada' WHERE id=?");
    $stmt->execute([$medicaoId]);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}

redirecionar_com_flash('faturamento.php', "Fatura $numero gerada com sucesso.", 'success');
