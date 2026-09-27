<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
exigir_perfil('admin', 'tecnico');

$db = get_db();
$chamadoId = (int) ($_GET['chamado_id'] ?? $_POST['chamado_id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM chamados WHERE id=?');
$stmt->execute([$chamadoId]);
$chamado = $stmt->fetch();
if (!$chamado) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $horaIni = $_POST['hora_inicial'];
    $horaFim = $_POST['hora_final'];
    $ini = DateTime::createFromFormat('H:i', $horaIni);
    $fim = DateTime::createFromFormat('H:i', $horaFim);
    $horas = round(($fim->getTimestamp() - $ini->getTimestamp()) / 3600, 2);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO atendimentos (chamado_id, tecnico_id, data, hora_inicial, hora_final,
             horas_trabalhadas, descricao_servico, solucao, faturavel)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $chamadoId, usuario_id_atual(), $_POST['data'], $horaIni, $horaFim, $horas,
            $_POST['descricao_servico'], $_POST['solucao'] ?: null,
            isset($_POST['faturavel']) ? 1 : 0,
        ]);
        $atendimentoId = (int) $db->lastInsertId();

        // Upload de evidências
        if (!empty($_FILES['evidencias']['name'][0])) {
            $total = count($_FILES['evidencias']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['evidencias']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $nomeOriginal = $_FILES['evidencias']['name'][$i];
                if (!extensao_permitida($nomeOriginal)) {
                    continue;
                }
                $nomeSeguro = nome_arquivo_seguro($atendimentoId, $nomeOriginal);
                $destino = UPLOAD_DIR . $nomeSeguro;
                if (move_uploaded_file($_FILES['evidencias']['tmp_name'][$i], $destino)) {
                    $stmt2 = $db->prepare(
                        'INSERT INTO evidencias (atendimento_id, nome_arquivo, caminho) VALUES (?,?,?)'
                    );
                    $stmt2->execute([$atendimentoId, $nomeOriginal, $nomeSeguro]);
                }
            }
        }

        // Consome saldo de horas do contrato
        if ($chamado['contrato_id']) {
            $stmt = $db->prepare('UPDATE contratos SET horas_utilizadas = horas_utilizadas + ? WHERE id=?');
            $stmt->execute([$horas, $chamado['contrato_id']]);
        }

        $stmt = $db->prepare("UPDATE chamados SET status='Aguardando cliente' WHERE id=?");
        $stmt->execute([$chamadoId]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    redirecionar_com_flash('chamado_detalhe.php?id=' . $chamadoId, 'Atendimento registrado com sucesso.', 'success');
}

$hoje = date('Y-m-d');
$titulo_pagina = 'Registrar atendimento · Contratos 360';
require __DIR__ . '/includes/header_admin.php';
?>
<div class="topbar"><h1>Registrar atendimento — <?= e($chamado['numero']) ?></h1></div>

<div class="card" style="max-width:680px;">
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="chamado_id" value="<?= $chamadoId ?>">
    <div class="form-row">
      <div class="form-group"><label>Data</label><input type="date" name="data" value="<?= $hoje ?>" required></div>
      <div class="form-group"><label>Hora inicial</label><input type="time" name="hora_inicial" required></div>
      <div class="form-group"><label>Hora final</label><input type="time" name="hora_final" required></div>
    </div>
    <div class="form-group">
      <label>Descrição do serviço realizado</label>
      <textarea name="descricao_servico" rows="4" required></textarea>
    </div>
    <div class="form-group">
      <label>Solução aplicada</label>
      <textarea name="solucao" rows="3"></textarea>
    </div>
    <div class="form-group">
      <label>Evidências / anexos (fotos, documentos)</label>
      <input type="file" name="evidencias[]" multiple>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="faturavel" checked style="width:auto; display:inline-block; margin-right:6px;"> Atendimento faturável (consome saldo de horas do contrato)</label>
    </div>
    <button class="btn" type="submit">Salvar atendimento</button>
    <a href="chamado_detalhe.php?id=<?= $chamadoId ?>" class="btn btn-secundario">Cancelar</a>
  </form>
</div>
<?php require __DIR__ . '/includes/footer_admin.php'; ?>
