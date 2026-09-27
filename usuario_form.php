<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgaoId = $_POST['orgao_id'] ?: null;
    try {
        $stmt = $db->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, perfil, orgao_id) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([
            $_POST['nome'],
            strtolower(trim($_POST['email'])),
            password_hash($_POST['senha'], PASSWORD_DEFAULT),
            $_POST['perfil'],
            $orgaoId,
        ]);
        redirecionar_com_flash('usuarios.php', 'Usuário cadastrado com sucesso.', 'success');
    } catch (PDOException $e) {
        // Código 23000 = violação de restrição única (e-mail duplicado)
        if ($e->getCode() === '23000') {
            $erro = 'Já existe um usuário com esse e-mail.';
        } else {
            throw $e;
        }
    }
}

$orgaos = $db->query('SELECT * FROM orgaos ORDER BY nome')->fetchAll();

$titulo_pagina = 'Novo usuário · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Novo usuário</h1></div>
<div class="card" style="max-width:560px;">
  <?php if ($erro): ?><div class="flash flash-error"><?= e($erro) ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
    <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
    <div class="form-group"><label>Senha</label><input type="password" name="senha" required></div>
    <div class="form-group">
      <label>Perfil</label>
      <select name="perfil" id="perfil" required onchange="document.getElementById('orgao-wrap').style.display = this.value=='cliente' ? 'block' : 'none';">
        <option value="admin">Administrador / Gestor</option>
        <option value="tecnico">Técnico</option>
        <option value="cliente">Cliente (servidor público)</option>
      </select>
    </div>
    <div class="form-group" id="orgao-wrap" style="display:none;">
      <label>Órgão vinculado</label>
      <select name="orgao_id">
        <?php foreach ($orgaos as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">Salvar usuário</button>
    <a href="usuarios.php" class="btn btn-secundario">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
