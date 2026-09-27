<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$medicoes = $db->query(
    "SELECT m.*, c.numero AS contrato_numero, o.nome AS orgao_nome FROM medicoes m
     JOIN contratos c ON c.id=m.contrato_id JOIN orgaos o ON o.id=c.orgao_id
     ORDER BY m.criado_em DESC"
)->fetchAll();

$titulo_pagina = 'Medições · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar">
  <h1>Medições</h1>
  <a href="medicao_form.php" class="btn">+ Gerar medição</a>
</div>
<div class="card">
  <?php if ($medicoes): ?>
  <table>
    <tr><th>Número</th><th>Contrato</th><th>Órgão</th><th>Período</th><th>Horas</th><th>Valor</th><th>Status</th><th></th></tr>
    <?php foreach ($medicoes as $m): ?>
    <tr>
      <td><?= e($m['numero']) ?></td>
      <td><?= e($m['contrato_numero']) ?></td>
      <td><?= e($m['orgao_nome']) ?></td>
      <td><?= formatar_data_br($m['periodo_inicio']) ?> a <?= formatar_data_br($m['periodo_fim']) ?></td>
      <td><?= $m['total_horas'] ?>h</td>
      <td><?= formatar_moeda((float)$m['valor_total']) ?></td>
      <td><span class="badge badge-<?= badge_class($m['status']) ?>"><?= e($m['status']) ?></span></td>
      <td><a href="medicao_detalhe.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-secundario">Ver</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhuma medição gerada ainda.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
