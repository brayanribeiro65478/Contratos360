<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();
$medicaoId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT m.*, c.numero AS contrato_numero, o.nome AS orgao_nome, o.id AS orgao_id
     FROM medicoes m JOIN contratos c ON c.id=m.contrato_id JOIN orgaos o ON o.id=c.orgao_id
     WHERE m.id=?"
);
$stmt->execute([$medicaoId]);
$medicao = $stmt->fetch();

if (!$medicao) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}
if ($perfil === 'cliente' && (int)$medicao['orgao_id'] !== orgao_id_atual()) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}
if ($perfil === 'cliente') {
    // Cliente só pode ver medições já aprovadas/faturadas
    if ($medicao['status'] === 'Em aberto') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

$stmt = $db->prepare(
    "SELECT a.*, u.nome AS tecnico_nome, ch.numero AS chamado_numero, ch.assunto FROM atendimentos a
     JOIN medicao_atendimentos ma ON ma.atendimento_id=a.id
     JOIN usuarios u ON u.id=a.tecnico_id JOIN chamados ch ON ch.id=a.chamado_id
     WHERE ma.medicao_id=? ORDER BY a.data"
);
$stmt->execute([$medicaoId]);
$atendimentos = $stmt->fetchAll();

$titulo_pagina = e($medicao['numero']) . ' · Contratos 360';
require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'header_client.php' : 'header_admin.php');
?>
<?php if ($perfil !== 'cliente'): ?><div class="topbar"><h1>Medição <?= e($medicao['numero']) ?></h1></div><?php endif; ?>

<div class="card">
  <p><strong>Órgão:</strong> <?= e($medicao['orgao_nome']) ?> &nbsp; <strong>Contrato:</strong> <?= e($medicao['contrato_numero']) ?></p>
  <p><strong>Período:</strong> <?= formatar_data_br($medicao['periodo_inicio']) ?> a <?= formatar_data_br($medicao['periodo_fim']) ?></p>
  <p><strong>Status:</strong> <span class="badge badge-<?= badge_class($medicao['status']) ?>"><?= e($medicao['status']) ?></span></p>
  <p><strong>Total de atendimentos:</strong> <?= $medicao['total_atendimentos'] ?> &nbsp; <strong>Total de horas:</strong> <?= $medicao['total_horas'] ?>h</p>
  <p><strong>Valor total:</strong> <?= formatar_moeda((float)$medicao['valor_total']) ?></p>

  <a href="medicao_relatorio.php?id=<?= $medicaoId ?>" class="btn" target="_blank">Ver relatório mensal</a>
  <?php if ($perfil === 'admin' && $medicao['status'] === 'Em aberto'): ?>
  <form method="POST" action="medicao_aprovar.php" style="display:inline;">
    <input type="hidden" name="medicao_id" value="<?= $medicaoId ?>">
    <button class="btn btn-sucesso" type="submit">Aprovar medição</button>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="section-title" style="margin-top:0;">Atendimentos incluídos</div>
  <table>
    <tr><th>Data</th><th>Chamado</th><th>Técnico</th><th>Horas</th><th>Descrição</th></tr>
    <?php foreach ($atendimentos as $a): ?>
    <tr>
      <td><?= formatar_data_br($a['data']) ?></td>
      <td><?= e($a['chamado_numero']) ?> — <?= e($a['assunto']) ?></td>
      <td><?= e($a['tecnico_nome']) ?></td>
      <td><?= $a['horas_trabalhadas'] ?>h</td>
      <td><?= e($a['descricao_servico']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'footer_client.php' : 'footer_admin.php'); ?>
