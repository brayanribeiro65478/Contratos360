<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_login();

$db = get_db();
$perfil = perfil_atual();
$medicaoId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT m.*, c.numero AS contrato_numero, o.nome AS orgao_nome, o.id AS orgao_id, o.cnpj, o.endereco
     FROM medicoes m JOIN contratos c ON c.id=m.contrato_id JOIN orgaos o ON o.id=c.orgao_id
     WHERE m.id=?"
);
$stmt->execute([$medicaoId]);
$medicao = $stmt->fetch();

if (!$medicao) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}
if ($perfil === 'cliente') {
    if ((int)$medicao['orgao_id'] !== orgao_id_atual() || $medicao['status'] === 'Em aberto') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

$stmt = $db->prepare(
    "SELECT a.*, u.nome AS tecnico_nome, ch.numero AS chamado_numero, ch.assunto FROM atendimentos a
     JOIN medicao_atendimentos ma ON ma.atendimento_id=a.id
     JOIN usuarios u ON u.id=a.tecnico_id JOIN chamados ch ON ch.id=a.chamado_id
     WHERE ma.medicao_id=? ORDER BY a.data"
);
$stmt->execute([$medicaoId]);
$atendimentos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Relatório <?= e($medicao['numero']) ?> · Contratos 360</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="relatorio-acoes">
    <button class="btn" onclick="window.print()">Imprimir / Salvar como PDF</button>
  </div>

  <div class="relatorio-page">
    <div class="relatorio-topo">
      <div>
        <h1><?= e(EMPRESA_NOME) ?></h1>
        <p>CNPJ: <?= e(EMPRESA_CNPJ) ?> · <?= e(EMPRESA_ENDERECO) ?> · <?= e(EMPRESA_TELEFONE) ?></p>
      </div>
      <div style="text-align:right;">
        <strong>Relatório Mensal de Atividades</strong><br>
        <span class="muted">Emitido em <?= date('d/m/Y') ?></span>
      </div>
    </div>

    <div class="relatorio-info">
      <div><strong>Órgão:</strong> <?= e($medicao['orgao_nome']) ?></div>
      <div><strong>Contrato:</strong> <?= e($medicao['contrato_numero']) ?></div>
      <div><strong>Medição:</strong> <?= e($medicao['numero']) ?></div>
      <div><strong>Status:</strong> <?= e($medicao['status']) ?></div>
      <div><strong>Período:</strong> <?= formatar_data_br($medicao['periodo_inicio']) ?> a <?= formatar_data_br($medicao['periodo_fim']) ?></div>
    </div>

    <div class="section-title" style="margin-top:0;">Atendimentos realizados no período</div>
    <table>
      <tr><th>Data</th><th>Chamado</th><th>Assunto</th><th>Técnico</th><th>Horas</th><th>Descrição do serviço</th></tr>
      <?php foreach ($atendimentos as $a): ?>
      <tr>
        <td><?= formatar_data_br($a['data']) ?></td>
        <td><?= e($a['chamado_numero']) ?></td>
        <td><?= e($a['assunto']) ?></td>
        <td><?= e($a['tecnico_nome']) ?></td>
        <td><?= $a['horas_trabalhadas'] ?>h</td>
        <td><?= e($a['descricao_servico']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <table class="relatorio-resumo">
      <tr><td><strong>Total de atendimentos:</strong></td><td><?= $medicao['total_atendimentos'] ?></td></tr>
      <tr><td><strong>Total de horas:</strong></td><td><?= $medicao['total_horas'] ?>h</td></tr>
      <tr><td><strong>Valor total da medição:</strong></td><td><?= formatar_moeda((float)$medicao['valor_total']) ?></td></tr>
    </table>

    <p class="muted" style="margin-top:30px;">
      Documento gerado automaticamente pelo sistema Contratos 360, com base nos registros de
      atendimento vinculados aos chamados oficiais do órgão.
    </p>
  </div>
</body>
</html>
