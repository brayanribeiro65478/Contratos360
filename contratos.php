<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$contratos = $db->query(
    "SELECT c.*, o.nome AS orgao_nome FROM contratos c JOIN orgaos o ON o.id=c.orgao_id ORDER BY c.data_fim ASC"
)->fetchAll();
$hoje = date('Y-m-d');

$titulo_pagina = 'Contratos · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar">
  <h1>Contratos</h1>
  <a href="contrato_form.php" class="btn">+ Novo contrato</a>
</div>

<div class="card">
  <?php if ($contratos): ?>
  <table>
    <tr><th>Número</th><th>Órgão</th><th>Vigência</th><th>Saldo de horas</th><th>Valor</th><th>Status</th><th></th></tr>
    <?php foreach ($contratos as $c): ?>
    <tr>
      <td><?= e($c['numero']) ?></td>
      <td><?= e($c['orgao_nome']) ?></td>
      <td>
        <?= formatar_data_br($c['data_inicio']) ?> a <?= formatar_data_br($c['data_fim']) ?>
        <?php if ($c['data_fim'] < $hoje && $c['status'] === 'Ativo'): ?>
          <span class="badge badge-vencido">Vencido</span>
        <?php endif; ?>
      </td>
      <td><?= number_format($c['horas_contratadas'] - $c['horas_utilizadas'], 1, ',', '.') ?>h / <?= $c['horas_contratadas'] ?>h</td>
      <td><?= formatar_moeda((float)$c['valor']) ?></td>
      <td><span class="badge badge-<?= badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
      <td><a href="contrato_detalhe.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-secundario">Ver</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhum contrato cadastrado ainda.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
