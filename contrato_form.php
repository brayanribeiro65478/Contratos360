<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare(
        "INSERT INTO contratos (numero, orgao_id, data_inicio, data_fim, valor, horas_contratadas, status, observacoes)
         VALUES (?,?,?,?,?,?, 'Ativo', ?)"
    );
    $stmt->execute([
        $_POST['numero'], $_POST['orgao_id'], $_POST['data_inicio'], $_POST['data_fim'],
        (float) ($_POST['valor'] ?? 0), (float) ($_POST['horas_contratadas'] ?? 0),
        $_POST['observacoes'] ?: null,
    ]);
    redirecionar_com_flash('contratos.php', 'Contrato cadastrado com sucesso.', 'success');
}

$orgaos = $db->query('SELECT * FROM orgaos ORDER BY nome')->fetchAll();

$titulo_pagina = 'Novo contrato · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Novo contrato</h1></div>
<div class="card" style="max-width:640px;">
  <form method="POST">
    <div class="form-group">
      <label>Órgão</label>
      <select name="orgao_id" required>
        <?php foreach ($orgaos as $o): ?>
          <option value="<?= (int)$o['id'] ?>"><?= e($o['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Número do contrato</label>
      <input type="text" name="numero" required placeholder="Ex: CT-2026-002">
    </div>
    <div class="form-row">
      <div class="form-group"><label>Data de início</label><input type="date" name="data_inicio" required></div>
      <div class="form-group"><label>Data de fim</label><input type="date" name="data_fim" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Valor total (R$)</label><input type="number" step="0.01" name="valor" required></div>
      <div class="form-group"><label>Horas contratadas</label><input type="number" step="0.5" name="horas_contratadas" required></div>
    </div>
    <div class="form-group"><label>Observações</label><textarea name="observacoes" rows="3"></textarea></div>
    <button class="btn" type="submit">Salvar contrato</button>
    <a href="contratos.php" class="btn btn-secundario">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
