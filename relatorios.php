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

$titulo_pagina = 'Relatórios · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Relatórios</h1></div>
<div class="card">
  <p class="muted">Selecione uma medição para gerar o relatório mensal de atividades, pronto para anexar ao processo de cobrança (use "Imprimir → Salvar como PDF" na página do relatório).</p>
  <?php if ($medicoes): ?>
  <table>
    <tr><th>Medição</th><th>Órgão</th><th>Contrato</th><th>Período</th><th></th></tr>
    <?php foreach ($medicoes as $m): ?>
    <tr>
      <td><?= e($m['numero']) ?></td>
      <td><?= e($m['orgao_nome']) ?></td>
      <td><?= e($m['contrato_numero']) ?></td>
      <td><?= formatar_data_br($m['periodo_inicio']) ?> a <?= formatar_data_br($m['periodo_fim']) ?></td>
      <td><a href="medicao_relatorio.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm" target="_blank">Ver relatório</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhuma medição gerada ainda.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
