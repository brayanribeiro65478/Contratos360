<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$orgaos = $db->query(
    "SELECT o.*, (SELECT COUNT(*) FROM contratos c WHERE c.orgao_id=o.id AND c.status='Ativo') AS contratos_ativos
     FROM orgaos o ORDER BY o.nome"
)->fetchAll();

$titulo_pagina = 'Clientes · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar">
  <h1>Clientes / Órgãos Públicos</h1>
  <a href="orgao_form.php" class="btn">+ Novo órgão</a>
</div>

<div class="card">
  <?php if ($orgaos): ?>
  <table>
    <tr><th>Nome</th><th>Tipo</th><th>Cidade/UF</th><th>Contratos ativos</th><th></th></tr>
    <?php foreach ($orgaos as $o): ?>
    <tr>
      <td><?= e($o['nome']) ?></td>
      <td><?= e($o['tipo']) ?></td>
      <td><?= e($o['cidade']) ?><?= $o['estado'] ? '/' . e($o['estado']) : '' ?></td>
      <td><?= (int)$o['contratos_ativos'] ?></td>
      <td><a href="orgao_detalhe.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-secundario">Ver</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhum órgão cadastrado ainda.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
