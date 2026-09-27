<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$orgaoId = (int) ($_GET['id'] ?? 0);

// Cadastro de novo usuário (servidor público) vinculado a este órgão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil, orgao_id) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        $_POST['nome'],
        strtolower(trim($_POST['email'])),
        password_hash($_POST['senha'], PASSWORD_DEFAULT),
        'cliente',
        $orgaoId,
    ]);
    redirecionar_com_flash('orgao_detalhe.php?id=' . $orgaoId, 'Usuário do órgão cadastrado.', 'success');
}

$stmt = $db->prepare('SELECT * FROM orgaos WHERE id=?');
$stmt->execute([$orgaoId]);
$orgao = $stmt->fetch();
if (!$orgao) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$stmt = $db->prepare('SELECT * FROM usuarios WHERE orgao_id=?');
$stmt->execute([$orgaoId]);
$usuarios = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM contratos WHERE orgao_id=? ORDER BY data_inicio DESC');
$stmt->execute([$orgaoId]);
$contratos = $stmt->fetchAll();

$titulo_pagina = e($orgao['nome']) . ' · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar">
  <h1><?= e($orgao['nome']) ?></h1>
  <a href="contrato_form.php" class="btn">+ Novo contrato</a>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title" style="margin-top:0;">Dados do órgão</div>
    <p><strong>Tipo:</strong> <?= e($orgao['tipo']) ?></p>
    <p><strong>CNPJ:</strong> <?= e($orgao['cnpj']) ?: '-' ?></p>
    <p><strong>Cidade:</strong> <?= e($orgao['cidade']) ?: '-' ?><?= $orgao['estado'] ? '/' . e($orgao['estado']) : '' ?></p>
    <p><strong>Endereço:</strong> <?= e($orgao['endereco']) ?: '-' ?></p>
    <p><strong>Telefone:</strong> <?= e($orgao['telefone']) ?: '-' ?></p>
    <p><strong>E-mail:</strong> <?= e($orgao['email']) ?: '-' ?></p>
  </div>

  <div class="card">
    <div class="section-title" style="margin-top:0;">Usuários vinculados (servidores públicos)</div>
    <?php if ($usuarios): ?>
    <table>
      <tr><th>Nome</th><th>E-mail</th><th>Status</th></tr>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= e($u['nome']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <div class="empty-state">Nenhum usuário vinculado.</div>
    <?php endif; ?>

    <div class="section-title">Adicionar servidor público</div>
    <form method="POST">
      <div class="form-row">
        <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
      </div>
      <div class="form-group"><label>Senha provisória</label><input type="password" name="senha" required></div>
      <button class="btn btn-sm" type="submit">Adicionar usuário</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="section-title" style="margin-top:0;">Contratos</div>
  <?php if ($contratos): ?>
  <table>
    <tr><th>Número</th><th>Vigência</th><th>Horas</th><th>Status</th><th></th></tr>
    <?php foreach ($contratos as $c): ?>
    <tr>
      <td><?= e($c['numero']) ?></td>
      <td><?= formatar_data_br($c['data_inicio']) ?> a <?= formatar_data_br($c['data_fim']) ?></td>
      <td><?= $c['horas_utilizadas'] ?> / <?= $c['horas_contratadas'] ?>h</td>
      <td><span class="badge badge-<?= badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
      <td><a href="contrato_detalhe.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-secundario">Ver</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <div class="empty-state">Nenhum contrato cadastrado para este órgão.</div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
