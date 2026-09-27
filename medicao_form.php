<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin');

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contratoId = (int) $_POST['contrato_id'];
    $periodoInicio = $_POST['periodo_inicio'];
    $periodoFim = $_POST['periodo_fim'];

    $stmt = $db->prepare(
        "SELECT a.* FROM atendimentos a JOIN chamados ch ON ch.id=a.chamado_id
         WHERE ch.contrato_id=? AND a.data BETWEEN ? AND ? AND a.faturavel=1
         AND a.id NOT IN (SELECT atendimento_id FROM medicao_atendimentos)"
    );
    $stmt->execute([$contratoId, $periodoInicio, $periodoFim]);
    $atendimentos = $stmt->fetchAll();

    if (!$atendimentos) {
        redirecionar_com_flash('medicao_form.php', 'Nenhum atendimento faturável encontrado nesse período.', 'error');
    }

    $totalHoras = array_sum(array_column($atendimentos, 'horas_trabalhadas'));

    $stmt = $db->prepare('SELECT * FROM contratos WHERE id=?');
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();

    $valorHora = $contrato['horas_contratadas'] > 0 ? $contrato['valor'] / $contrato['horas_contratadas'] : 0;
    $valorTotal = round($totalHoras * $valorHora, 2);
    $numero = gerar_numero_medicao($db);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO medicoes (numero, contrato_id, periodo_inicio, periodo_fim, total_horas,
             total_atendimentos, valor_total, status, criado_por)
             VALUES (?,?,?,?,?,?,?, 'Em aberto', ?)"
        );
        $stmt->execute([
            $numero, $contratoId, $periodoInicio, $periodoFim, $totalHoras,
            count($atendimentos), $valorTotal, usuario_id_atual(),
        ]);
        $medicaoId = (int) $db->lastInsertId();

        $stmtRel = $db->prepare('INSERT INTO medicao_atendimentos (medicao_id, atendimento_id) VALUES (?,?)');
        foreach ($atendimentos as $a) {
            $stmtRel->execute([$medicaoId, $a['id']]);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    redirecionar_com_flash(
        'medicao_detalhe.php?id=' . $medicaoId,
        "Medição $numero gerada com " . count($atendimentos) . ' atendimento(s).',
        'success'
    );
}

$contratos = $db->query(
    "SELECT c.*, o.nome AS orgao_nome FROM contratos c JOIN orgaos o ON o.id=c.orgao_id
     WHERE c.status='Ativo' ORDER BY o.nome"
)->fetchAll();

$titulo_pagina = 'Gerar medição · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Gerar medição</h1></div>
<div class="card" style="max-width:640px;">
  <p class="muted">O sistema reunirá automaticamente os atendimentos faturáveis do contrato dentro do período informado.</p>
  <form method="POST">
    <div class="form-group">
      <label>Contrato</label>
      <select name="contrato_id" required>
        <?php foreach ($contratos as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['numero']) ?> — <?= e($c['orgao_nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Período — início</label><input type="date" name="periodo_inicio" required></div>
      <div class="form-group"><label>Período — fim</label><input type="date" name="periodo_fim" required></div>
    </div>
    <button class="btn" type="submit">Gerar medição</button>
    <a href="medicoes.php" class="btn btn-secundario">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
