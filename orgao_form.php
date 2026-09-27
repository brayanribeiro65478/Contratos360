<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();
    $stmt = $db->prepare(
        'INSERT INTO orgaos (nome, tipo, cnpj, cidade, estado, endereco, telefone, email)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $_POST['nome'], $_POST['tipo'], $_POST['cnpj'] ?: null,
        $_POST['cidade'] ?: null, $_POST['estado'] ?: null,
        $_POST['endereco'] ?: null, $_POST['telefone'] ?: null, $_POST['email'] ?: null,
    ]);
    redirecionar_com_flash('orgaos.php', 'Órgão cadastrado com sucesso.', 'success');
}

$titulo_pagina = 'Novo órgão · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Cadastrar órgão público</h1></div>
<div class="card" style="max-width:640px;">
  <form method="POST">
    <div class="form-group">
      <label>Nome do órgão</label>
      <input type="text" name="nome" required placeholder="Ex: Prefeitura Municipal de Barbalha">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Tipo</label>
        <select name="tipo" required>
          <option value="Prefeitura">Prefeitura</option>
          <option value="Camara">Câmara de Vereadores</option>
          <option value="Outro">Outro</option>
        </select>
      </div>
      <div class="form-group">
        <label>CNPJ</label>
        <input type="text" name="cnpj" placeholder="00.000.000/0001-00">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Cidade</label><input type="text" name="cidade"></div>
      <div class="form-group"><label>Estado</label><input type="text" name="estado" maxlength="2" placeholder="CE"></div>
    </div>
    <div class="form-group"><label>Endereço</label><input type="text" name="endereco"></div>
    <div class="form-row">
      <div class="form-group"><label>Telefone</label><input type="text" name="telefone"></div>
      <div class="form-group"><label>E-mail</label><input type="email" name="email"></div>
    </div>
    <button class="btn" type="submit">Salvar órgão</button>
    <a href="orgaos.php" class="btn btn-secundario">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
