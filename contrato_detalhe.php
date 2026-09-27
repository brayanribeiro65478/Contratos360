<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$contratoId = (int) ($_GET['id'] ?? 0);

// Registro de novo aditivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_perfil('admin');
    $horasAdd = (float) ($_POST['horas_adicionais'] ?? 0);
    $valorAdd = (float) ($_POST['valor_adicional'] ?? 0);
    $novaData = $_POST['nova_data_fim'] ?: null;

    $stmt = $db->prepare(
        'INSERT INTO aditivos (contrato_id, descricao, horas_adicionais, valor_adicional, nova_data_fim)
         VALUES (?,?,?,?,?)'
    );
    $stmt->execute([$contratoId, $_POST['descricao'], $horasAdd, $valorAdd, $novaData]);

    if ($horasAdd || $valorAdd || $novaData) {
        $stmt = $db->prepare(
            'UPDATE contratos SET horas_contratadas = horas_contratadas + ?,
             valor = valor + ?, data_fim = COALESCE(?, data_fim) WHERE id=?'
        );
        $stmt->execute([$horasAdd, $valorAdd, $novaData, $contratoId]);
    }
    redirecionar_com_flash('contrato_detalhe.php?id=' . $contratoId, 'Aditivo registrado.', 'success');
}

$stmt = $db->prepare(
    'SELECT c.*, o.nome AS orgao_nome FROM contratos c JOIN orgaos o ON o.id=c.orgao_id WHERE c.id=?'
);
$stmt->execute([$contratoId]);
$contrato = $stmt->fetch();
if (!$contrato) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}
if (perfil_atual() === 'cliente' && (int)$contrato['orgao_id'] !== orgao_id_atual()) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

$stmt = $db->prepare('SELECT * FROM aditivos WHERE contrato_id=? ORDER BY criado_em DESC');
$stmt->execute([$contratoId]);
$aditivos = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM medicoes WHERE contrato_id=? ORDER BY periodo_inicio DESC');
$stmt->execute([$contratoId]);
$medicoes = $stmt->fetchAll();

$saldo = (float)$contrato['horas_contratadas'] - (float)$contrato['horas_utilizadas'];

$titulo_pagina = e($contrato['numero']) . ' · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Contrato <?= e($contrato['numero']) ?></h1></div>

<div class="grid grid-4">
  <div class="card stat-card"><div class="valor"><?= formatar_moeda((float)$contrato['valor']) ?></div><div class="label">Valor total</div></div>
  <div class="card stat-card"><div class="valor"><?= $contrato['horas_contratadas'] ?>h</div><div class="label">Horas contratadas</div></div>
  <div class="card stat-card alerta"><div class="valor"><?= $contrato['horas_utilizadas'] ?>h</div><div class="label">Horas utilizadas</div></div>
  <div class="card stat-card sucesso"><div class="valor"><?= number_format($saldo, 1, ',', '.') ?>h</div><div class="label">Saldo disponível</div></div>
</div>

<div class="card">
  <div class="section-title" style="margin-top:0;">Dados do contrato</div>
  <p><strong>Órgão:</strong> <?= e($contrato['orgao_nome']) ?></p>
  <p><strong>Vigência:</strong> <?= formatar_data_br($contrato['data_inicio']) ?> a <?= formatar_data_br($contrato['data_fim']) ?></p>
  <p><strong>Status:</strong> <span class="badge badge-<?= badge_class($contrato['status']) ?>"><?= e($contrato['status']) ?></span></p>
  <?php if ($contrato['observacoes']): ?><p><strong>Observações:</strong> <?= e($contrato['observacoes']) ?></p><?php endif; ?>
  <?php $pct = $contrato['horas_contratadas'] > 0 ? round($contrato['horas_utilizadas'] / $contrato['horas_contratadas'] * 100) : 0; ?>
  <div class="progress-bar"><div class="fill" style="width: <?= $pct ?>%;"></div></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title" style="margin-top:0;">Aditivos</div>
    <?php foreach ($aditivos as $a): ?>
    <p style="border-bottom:1px solid var(--cinza-200); padding-bottom:8px;">
      <strong><?= substr($a['criado_em'], 0, 10) ?>:</strong> <?= e($a['descricao']) ?>
      <?php if ($a['horas_adicionais']): ?> (+<?= $a['horas_adicionais'] ?>h)<?php endif; ?>
      <?php if ($a['valor_adicional']): ?> (+<?= formatar_moeda((float)$a['valor_adicional']) ?>)<?php endif; ?>
    </p>
    <?php endforeach; ?>
    <?php if (!$aditivos): ?><div class="empty-state">Nenhum aditivo registrado.</div><?php endif; ?>

    <?php if (perfil_atual() === 'admin'): ?>
    <form method="POST">
      <div class="form-group"><label>Descrição do aditivo</label><input type="text" name="descricao" required></div>
      <div class="form-row">
        <div class="form-group"><label>Horas adicionais</label><input type="number" step="0.5" name="horas_adicionais" value="0"></div>
        <div class="form-group"><label>Valor adicional (R$)</label><input type="number" step="0.01" name="valor_adicional" value="0"></div>
      </div>
      <div class="form-group"><label>Nova data de fim (opcional)</label><input type="date" name="nova_data_fim"></div>
      <button class="btn btn-sm" type="submit">Registrar aditivo</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title" style="margin-top:0;">Medições deste contrato</div>
    <?php if ($medicoes): ?>
    <table>
      <tr><th>Medição</th><th>Período</th><th>Valor</th><th>Status</th></tr>
      <?php foreach ($medicoes as $m): ?>
      <tr>
        <td><a href="medicao_detalhe.php?id=<?= (int)$m['id'] ?>"><?= e($m['numero']) ?></a></td>
        <td><?= formatar_data_br($m['periodo_inicio']) ?> a <?= formatar_data_br($m['periodo_fim']) ?></td>
        <td><?= formatar_moeda((float)$m['valor_total']) ?></td>
        <td><span class="badge badge-<?= badge_class($m['status']) ?>"><?= e($m['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <div class="empty-state">Nenhuma medição gerada para este contrato ainda.</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
