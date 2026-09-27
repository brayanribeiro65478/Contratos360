<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();
$titulo_pagina = 'Dashboard · Contratos 360';

if ($perfil === 'cliente') {
    $orgaoId = orgao_id_atual();

    $stmt = $db->prepare('SELECT * FROM chamados WHERE orgao_id = ? ORDER BY criado_em DESC LIMIT 10');
    $stmt->execute([$orgaoId]);
    $chamados = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT COUNT(*) FROM chamados WHERE orgao_id=? AND status NOT IN ('Resolvido','Encerrado')");
    $stmt->execute([$orgaoId]);
    $abertos = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM chamados WHERE orgao_id=? AND status IN ('Resolvido','Encerrado')");
    $stmt->execute([$orgaoId]);
    $resolvidos = (int) $stmt->fetchColumn();

    require __DIR__ . '/includes/header_client.php';
    ?>
    <div class="grid grid-2" style="margin-bottom:20px;">
      <div class="card stat-card alerta">
        <div class="valor"><?= $abertos ?></div>
        <div class="label">Chamados em andamento</div>
      </div>
      <div class="card stat-card sucesso">
        <div class="valor"><?= $resolvidos ?></div>
        <div class="label">Chamados resolvidos</div>
      </div>
    </div>

    <a href="chamado_form.php" class="btn" style="width:100%; text-align:center; padding:14px; margin-bottom:20px; display:block;">
      + Abrir novo chamado
    </a>

    <div class="card">
      <div class="section-title" style="margin-top:0;">Últimos chamados</div>
      <?php if ($chamados): ?>
        <?php foreach ($chamados as $ch): ?>
          <a href="chamado_detalhe.php?id=<?= (int)$ch['id'] ?>" style="display:block; padding:12px 0; border-bottom:1px solid var(--cinza-200); color:inherit;">
            <strong><?= e($ch['numero']) ?></strong> — <?= e($ch['assunto']) ?>
            <span class="badge badge-<?= badge_class($ch['status']) ?>" style="float:right;"><?= e($ch['status']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">Você ainda não abriu nenhum chamado.</div>
      <?php endif; ?>
    </div>
    <?php
    require __DIR__ . '/includes/footer_client.php';
    exit;
}

if ($perfil === 'tecnico') {
    $userId = usuario_id_atual();

    $stmt = $db->prepare(
        "SELECT c.*, o.nome AS orgao_nome FROM chamados c
         JOIN orgaos o ON o.id = c.orgao_id
         WHERE c.tecnico_id = ? AND c.status NOT IN ('Resolvido','Encerrado')
         ORDER BY FIELD(c.prioridade,'Urgente','Alta','Media','Baixa'), c.criado_em ASC"
    );
    $stmt->execute([$userId]);
    $chamados = $stmt->fetchAll();

    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(horas_trabalhadas),0) FROM atendimentos
         WHERE tecnico_id = ? AND YEAR(data)=YEAR(CURDATE()) AND MONTH(data)=MONTH(CURDATE())"
    );
    $stmt->execute([$userId]);
    $horasMes = (float) $stmt->fetchColumn();

    require __DIR__ . '/includes/header_admin.php';
    ?>
    <div class="topbar"><h1>Meus chamados</h1></div>
    <div class="grid grid-3">
      <div class="card stat-card">
        <div class="valor"><?= count($chamados) ?></div>
        <div class="label">Chamados em andamento</div>
      </div>
      <div class="card stat-card sucesso">
        <div class="valor"><?= number_format($horasMes, 1, ',', '.') ?>h</div>
        <div class="label">Horas registradas este mês</div>
      </div>
    </div>

    <div class="card">
      <div class="section-title" style="margin-top:0;">Chamados atribuídos a você</div>
      <?php if ($chamados): ?>
      <table>
        <tr><th>Nº</th><th>Órgão</th><th>Assunto</th><th>Prioridade</th><th>Status</th><th></th></tr>
        <?php foreach ($chamados as $ch): ?>
        <tr>
          <td><?= e($ch['numero']) ?></td>
          <td><?= e($ch['orgao_nome']) ?></td>
          <td><?= e($ch['assunto']) ?></td>
          <td><span class="badge badge-<?= badge_class($ch['prioridade']) ?>"><?= e($ch['prioridade']) ?></span></td>
          <td><span class="badge badge-<?= badge_class($ch['status']) ?>"><?= e($ch['status']) ?></span></td>
          <td><a href="chamado_detalhe.php?id=<?= (int)$ch['id'] ?>" class="btn btn-sm">Abrir</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
        <div class="empty-state">Nenhum chamado atribuído no momento.</div>
      <?php endif; ?>
    </div>
    <?php
    require __DIR__ . '/includes/footer_admin.php';
    exit;
}

// --- admin / gestor ---
$totalAbertos = (int) $db->query("SELECT COUNT(*) FROM chamados WHERE status='Aberto'")->fetchColumn();
$totalPendentes = (int) $db->query(
    "SELECT COUNT(*) FROM chamados WHERE status IN ('Em triagem','Em atendimento','Aguardando cliente')"
)->fetchColumn();
$totalResolvidos = (int) $db->query(
    "SELECT COUNT(*) FROM chamados WHERE status IN ('Resolvido','Encerrado')"
)->fetchColumn();
$contratosAtivos = (int) $db->query("SELECT COUNT(*) FROM contratos WHERE status='Ativo'")->fetchColumn();

$contratosVencendo = $db->query(
    "SELECT co.*, o.nome AS orgao_nome FROM contratos co JOIN orgaos o ON o.id = co.orgao_id
     WHERE co.status='Ativo' AND co.data_fim <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY co.data_fim ASC"
)->fetchAll();

$chamadosRecentes = $db->query(
    "SELECT ch.*, o.nome AS orgao_nome FROM chamados ch JOIN orgaos o ON o.id = ch.orgao_id
     ORDER BY ch.criado_em DESC LIMIT 8"
)->fetchAll();

require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Dashboard</h1></div>

<div class="grid grid-4">
  <div class="card stat-card">
    <div class="valor"><?= $totalAbertos ?></div>
    <div class="label">Chamados abertos</div>
  </div>
  <div class="card stat-card alerta">
    <div class="valor"><?= $totalPendentes ?></div>
    <div class="label">Chamados pendentes</div>
  </div>
  <div class="card stat-card sucesso">
    <div class="valor"><?= $totalResolvidos ?></div>
    <div class="label">Chamados resolvidos</div>
  </div>
  <div class="card stat-card">
    <div class="valor"><?= $contratosAtivos ?></div>
    <div class="label">Contratos ativos</div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title" style="margin-top:0;">Contratos próximos do vencimento (30 dias)</div>
    <?php if ($contratosVencendo): ?>
    <table>
      <tr><th>Contrato</th><th>Órgão</th><th>Vencimento</th></tr>
      <?php foreach ($contratosVencendo as $c): ?>
      <tr>
        <td><a href="contrato_detalhe.php?id=<?= (int)$c['id'] ?>"><?= e($c['numero']) ?></a></td>
        <td><?= e($c['orgao_nome']) ?></td>
        <td><?= formatar_data_br($c['data_fim']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <div class="empty-state">Nenhum contrato vencendo em breve.</div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title" style="margin-top:0;">Chamados recentes</div>
    <?php if ($chamadosRecentes): ?>
    <table>
      <tr><th>Nº</th><th>Órgão</th><th>Assunto</th><th>Status</th></tr>
      <?php foreach ($chamadosRecentes as $ch): ?>
      <tr>
        <td><a href="chamado_detalhe.php?id=<?= (int)$ch['id'] ?>"><?= e($ch['numero']) ?></a></td>
        <td><?= e($ch['orgao_nome']) ?></td>
        <td><?= e($ch['assunto']) ?></td>
        <td><span class="badge badge-<?= badge_class($ch['status']) ?>"><?= e($ch['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <div class="empty-state">Nenhum chamado registrado ainda.</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
