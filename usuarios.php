<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$usuarios = $db->query(
    "SELECT u.*, o.nome AS orgao_nome FROM usuarios u LEFT JOIN orgaos o ON o.id=u.orgao_id
     ORDER BY u.perfil, u.nome"
)->fetchAll();

$titulo_pagina = 'Usuários · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar">
  <h1>Usuários</h1>
  <a href="usuario_form.php" class="btn">+ Novo usuário</a>
</div>
<div class="card">
  <table>
    <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Órgão</th><th>Status</th><th></th></tr>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= e($u['nome']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e(ucfirst($u['perfil'])) ?></td>
      <td><?= e($u['orgao_nome']) ?: '—' ?></td>
      <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
      <td>
        <form method="POST" action="usuario_toggle.php">
          <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>">
          <button class="btn btn-sm btn-secundario" type="submit"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
