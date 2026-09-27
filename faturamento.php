<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$medicoes = $db->query(
    "SELECT m.*, c.numero AS contrato_numero, o.nome AS orgao_nome,
     f.id AS fatura_id, f.status AS fatura_status, f.numero AS fatura_numero
     FROM medicoes m JOIN contratos c ON c.id=m.contrato_id JOIN orgaos o ON o.id=c.orgao_id
     LEFT JOIN faturas f ON f.medicao_id=m.id
     WHERE m.status IN ('Aprovada','Faturada') ORDER BY m.criado_em DESC"
)->fetchAll();

$titulo_pagina = 'Faturamento · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Faturamento</h1></div>
<div class="card">
  <?php if ($medicoes): ?>
  <table>
    <tr><th>Medição</th><th>Órgão</th><th>Contrato</th><th>Valor</th><th>Fatura</th><th>Status</th><th></th></tr>
    <?php foreach ($medicoes as $m): ?>
    <tr>
      <td><?= e($m['numero']) ?></td>
      <td><?= e($m['orgao_nome']) ?></td>
      <td><?= e($m['contrato_numero']) ?></td>
      <td><?= formatar_moeda((float)$m['valor_total']) ?></td>
      <td><?= e($m['fatura_numero']) ?: '—' ?></td>
      <td>
        <?php if ($m['fatura_status']): ?>
          <span class="badge badge-<?= badge_class($m['fatura_status']) ?>"><?= e($m['fatura_status']) ?></span>
        <?php else: ?>
          <span class="badge badge-pendente">Aguardando emissão</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if (!$m['fatura_id']): ?>
        <form method="POST" action="faturamento_gerar.php">
          <input type="hidden" name="medicao_id" value="<?= (int)$m['id'] ?>">
          <button class="btn btn-sm" type="submit">Gerar fatura</button>
        </form>
        <?php else: ?>
        <form method="POST" action="faturamento_status.php" style="display:flex; gap:6px;">
          <input type="hidden" name="fatura_id" value="<?= (int)$m['fatura_id'] ?>">
          <select name="status" style="width:auto;">
            <option value="Emitida" <?= $m['fatura_status'] === 'Emitida' ? 'selected' : '' ?>>Emitida</option>
            <option value="Paga" <?= $m['fatura_status'] === 'Paga' ? 'selected' : '' ?>>Paga</option>
            <option value="Atrasada" <?= $m['fatura_status'] === 'Atrasada' ? 'selected' : '' ?>>Atrasada</option>
          </select>
          <button class="btn btn-sm btn-secundario" type="submit">Atualizar</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhuma medição aprovada aguardando faturamento.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
