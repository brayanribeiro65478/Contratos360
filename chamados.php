<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();
$filtroStatus = $_GET['status'] ?? '';

$sql = "SELECT ch.*, o.nome AS orgao_nome, u.nome AS tecnico_nome
        FROM chamados ch JOIN orgaos o ON o.id=ch.orgao_id
        LEFT JOIN usuarios u ON u.id=ch.tecnico_id WHERE 1=1 ";
$params = [];

if ($perfil === 'cliente') {
    $sql .= 'AND ch.orgao_id = ? ';
    $params[] = orgao_id_atual();
} elseif ($perfil === 'tecnico') {
    $sql .= 'AND ch.tecnico_id = ? ';
    $params[] = usuario_id_atual();
}

if ($filtroStatus) {
    $sql .= 'AND ch.status = ? ';
    $params[] = $filtroStatus;
}

$sql .= 'ORDER BY ch.criado_em DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$chamados = $stmt->fetchAll();

$statusOpcoes = ['Aberto', 'Em triagem', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];

$titulo_pagina = 'Chamados · Contratos 360';
require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'header_client.php' : 'header_admin.php');
?>

<?php if ($perfil !== 'cliente'): ?>
<div class="topbar">
  <h1>Chamados</h1>
  <?php if ($perfil === 'admin'): ?><a href="chamado_form.php" class="btn">+ Novo chamado</a><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($perfil === 'admin'): ?>
<div class="filters card">
  <form method="GET" style="display:flex; gap:10px; align-items:end;">
    <div class="form-group" style="margin-bottom:0;">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach ($statusOpcoes as $s): ?>
          <option value="<?= e($s) ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <?php if ($chamados): ?>
  <table>
    <tr>
      <th>Nº</th><th>Órgão</th><th>Assunto</th><th>Prioridade</th>
      <?php if ($perfil === 'admin'): ?><th>Técnico</th><?php endif; ?>
      <th>Status</th><th></th>
    </tr>
    <?php foreach ($chamados as $ch): ?>
    <tr>
      <td><?= e($ch['numero']) ?></td>
      <td><?= e($ch['orgao_nome']) ?></td>
      <td><?= e($ch['assunto']) ?></td>
      <td><span class="badge badge-<?= badge_class($ch['prioridade']) ?>"><?= e($ch['prioridade']) ?></span></td>
      <?php if ($perfil === 'admin'): ?><td><?= e($ch['tecnico_nome']) ?: '—' ?></td><?php endif; ?>
      <td><span class="badge badge-<?= badge_class($ch['status']) ?>"><?= e($ch['status']) ?></span></td>
      <td><a href="chamado_detalhe.php?id=<?= (int)$ch['id'] ?>" class="btn btn-sm btn-secundario">Ver</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhum chamado encontrado.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'footer_client.php' : 'footer_admin.php'); ?>
