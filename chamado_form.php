<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgaoId = $perfil === 'cliente' ? orgao_id_atual() : (int) $_POST['orgao_id'];

    $stmt = $db->prepare(
        "SELECT id FROM contratos WHERE orgao_id=? AND status='Ativo' ORDER BY data_inicio DESC LIMIT 1"
    );
    $stmt->execute([$orgaoId]);
    $contrato = $stmt->fetch();

    $numero = gerar_numero_chamado($db);
    $stmt = $db->prepare(
        "INSERT INTO chamados (numero, orgao_id, contrato_id, solicitante_id, assunto, descricao, categoria, prioridade, status)
         VALUES (?,?,?,?,?,?,?,?, 'Aberto')"
    );
    $stmt->execute([
        $numero, $orgaoId, $contrato['id'] ?? null, usuario_id_atual(),
        $_POST['assunto'], $_POST['descricao'], $_POST['categoria'] ?? 'Suporte', $_POST['prioridade'] ?? 'Media',
    ]);
    redirecionar_com_flash('chamados.php', "Chamado $numero aberto com sucesso.", 'success');
}

$orgaos = [];
if ($perfil === 'admin') {
    $orgaos = $db->query('SELECT * FROM orgaos ORDER BY nome')->fetchAll();
}

$titulo_pagina = 'Novo chamado · Contratos 360';
require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'header_client.php' : 'header_admin.php');
?>
<?php if ($perfil !== 'cliente'): ?><div class="topbar"><h1>Abrir chamado</h1></div><?php endif; ?>

<div class="card" style="max-width:640px;">
  <form method="POST">
    <?php if ($perfil === 'admin'): ?>
    <div class="form-group">
      <label>Órgão</label>
      <select name="orgao_id" required>
        <?php foreach ($orgaos as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['nome']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label>Assunto</label>
      <input type="text" name="assunto" required placeholder="Ex: Sistema de folha de pagamento fora do ar">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Categoria</label>
        <select name="categoria">
          <option>Suporte</option>
          <option>Manutenção</option>
          <option>Infraestrutura</option>
          <option>Sistema</option>
          <option>Outro</option>
        </select>
      </div>
      <div class="form-group">
        <label>Prioridade</label>
        <select name="prioridade">
          <option value="Baixa">Baixa</option>
          <option value="Media" selected>Média</option>
          <option value="Alta">Alta</option>
          <option value="Urgente">Urgente</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Descreva o problema ou solicitação</label>
      <textarea name="descricao" rows="5" required></textarea>
    </div>
    <button class="btn" type="submit">Abrir chamado</button>
  </form>
</div>
<?php require __DIR__ . '/includes/' . ($perfil === 'cliente' ? 'footer_client.php' : 'footer_admin.php'); ?>
