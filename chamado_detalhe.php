<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();
$chamadoId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT ch.*, o.nome AS orgao_nome, u.nome AS tecnico_nome, c.numero AS contrato_numero
     FROM chamados ch JOIN orgaos o ON o.id=ch.orgao_id
     LEFT JOIN usuarios u ON u.id=ch.tecnico_id
     LEFT JOIN contratos c ON c.id = ch.contrato_id WHERE ch.id=?"
);
$stmt->execute([$chamadoId]);
$chamado = $stmt->fetch();

if (!$chamado) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}
if ($perfil === 'cliente' && (int)$chamado['orgao_id'] !== orgao_id_atual()) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}
if ($perfil === 'tecnico' && (int)$chamado['tecnico_id'] !== usuario_id_atual()) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

// Envio de mensagem no chat do chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $stmt = $db->prepare('INSERT INTO mensagens (chamado_id, usuario_id, mensagem) VALUES (?,?,?)');
    $stmt->execute([$chamadoId, usuario_id_atual(), $_POST['mensagem']]);

    if ($perfil === 'cliente') {
        $stmt = $db->prepare("UPDATE chamados SET status='Em atendimento' WHERE id=?");
        $stmt->execute([$chamadoId]);
    }
    header('Location: chamado_detalhe.php?id=' . $chamadoId);
    exit;
}

$stmt = $db->prepare(
    "SELECT m.*, u.nome AS usuario_nome, u.perfil AS usuario_perfil FROM mensagens m
     JOIN usuarios u ON u.id=m.usuario_id WHERE m.chamado_id=? ORDER BY m.criado_em ASC"
);
$stmt->execute([$chamadoId]);
$mensagens = $stmt->fetchAll();

$stmt = $db->prepare(
    "SELECT a.*, u.nome AS tecnico_nome FROM atendimentos a JOIN usuarios u ON u.id=a.tecnico_id
     WHERE a.chamado_id=? ORDER BY a.data DESC, a.hora_inicial DESC"
);
$stmt->execute([$chamadoId]);
$atendimentos = $stmt->fetchAll();

$tecnicos = [];
if ($perfil === 'admin') {
    $tecnicos = $db->query("SELECT * FROM usuarios WHERE perfil='tecnico' AND ativo=1")->fetchAll();
}

$statusOpcoes = ['Aberto', 'Em triagem', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];

$titulo_pagina = e($chamado['numero']) . ' · Contratos 360';
require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'header_client.php' : 'header_admin.php');
?>
<?php if ($perfil !== 'cliente'): ?>
  <div class="topbar"><h1>Chamado <?= e($chamado['numero']) ?></h1></div>
<?php else: ?>
  <h2 style="margin:0 0 16px;">Chamado <?= e($chamado['numero']) ?></h2>
<?php endif; ?>

<div class="card">
  <p><strong>Órgão:</strong> <?= e($chamado['orgao_nome']) ?><?php if ($chamado['contrato_numero']): ?> · <strong>Contrato:</strong> <?= e($chamado['contrato_numero']) ?><?php endif; ?></p>
  <p><strong>Assunto:</strong> <?= e($chamado['assunto']) ?></p>
  <p><strong>Descrição:</strong> <?= nl2br(e($chamado['descricao'])) ?></p>
  <p><strong>Categoria:</strong> <?= e($chamado['categoria']) ?> &nbsp; <strong>Prioridade:</strong> <span class="badge badge-<?= badge_class($chamado['prioridade']) ?>"><?= e($chamado['prioridade']) ?></span></p>
  <p><strong>Status:</strong> <span class="badge badge-<?= badge_class($chamado['status']) ?>"><?= e($chamado['status']) ?></span></p>
  <p><strong>Técnico responsável:</strong> <?= e($chamado['tecnico_nome']) ?: 'Ainda não atribuído' ?></p>

  <?php if ($perfil === 'admin' && !$chamado['tecnico_id']): ?>
  <form method="POST" action="chamado_atribuir.php" style="margin-top:12px;">
    <input type="hidden" name="chamado_id" value="<?= $chamadoId ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Atribuir técnico</label>
        <select name="tecnico_id" required>
          <?php foreach ($tecnicos as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn btn-sm" type="submit">Atribuir</button>
  </form>
  <?php endif; ?>

  <?php if (in_array($perfil, ['admin', 'tecnico'], true)): ?>
  <form method="POST" action="chamado_status.php" style="margin-top:12px; display:flex; gap:10px; align-items:end;">
    <input type="hidden" name="chamado_id" value="<?= $chamadoId ?>">
    <div class="form-group" style="margin-bottom:0; flex:1;">
      <label>Alterar status</label>
      <select name="status">
        <?php foreach ($statusOpcoes as $s): ?>
          <option value="<?= e($s) ?>" <?= $chamado['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-sm btn-secundario" type="submit">Atualizar</button>
  </form>
  <?php endif; ?>
</div>

<?php if (in_array($perfil, ['admin', 'tecnico'], true)): ?>
<div class="card">
  <div class="topbar" style="margin-bottom:10px;">
    <div class="section-title" style="margin:0;">Atendimentos registrados</div>
    <a href="atendimento_form.php?chamado_id=<?= $chamadoId ?>" class="btn btn-sm">+ Registrar atendimento</a>
  </div>
  <?php if ($atendimentos): ?>
  <table>
    <tr><th>Data</th><th>Técnico</th><th>Horário</th><th>Horas</th><th>Descrição</th></tr>
    <?php foreach ($atendimentos as $a): ?>
    <tr>
      <td><?= formatar_data_br($a['data']) ?></td>
      <td><?= e($a['tecnico_nome']) ?></td>
      <td><?= substr($a['hora_inicial'], 0, 5) ?> - <?= substr($a['hora_final'], 0, 5) ?></td>
      <td><?= $a['horas_trabalhadas'] ?>h</td>
      <td><?= e($a['descricao_servico']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhum atendimento registrado ainda.</div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="section-title" style="margin-top:0;">Histórico de mensagens</div>
  <div class="chat-box">
    <?php if ($mensagens): ?>
      <?php foreach ($mensagens as $m): ?>
      <div class="msg <?= (int)$m['usuario_id'] === usuario_id_atual() ? 'mine' : '' ?>">
        <div class="meta"><?= e($m['usuario_nome']) ?> (<?= e($m['usuario_perfil']) ?>) · <?= e($m['criado_em']) ?></div>
        <div class="texto"><?= nl2br(e($m['mensagem'])) ?></div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="muted">Nenhuma mensagem ainda.</div>
    <?php endif; ?>
  </div>
  <form method="POST">
    <div class="form-group">
      <textarea name="mensagem" rows="2" placeholder="Escreva uma mensagem..." required></textarea>
    </div>
    <button class="btn btn-sm" type="submit">Enviar</button>
  </form>
</div>
<?php require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'footer_client.php' : 'footer_admin.php'); ?>
