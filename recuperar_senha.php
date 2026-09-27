<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$enviado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Em um ambiente real, aqui seria disparado um e-mail com token de redefinição.
    $enviado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar senha · Contratos 360</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="brand">Contratos 360</div>
      <div class="subtitulo">Recuperação de senha</div>

      <?php if ($enviado): ?>
        <div class="flash flash-success">Se o e-mail existir em nossa base, enviaremos instruções de recuperação.</div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Informe seu e-mail cadastrado</label>
          <input type="email" name="email" required autofocus>
        </div>
        <button class="btn" type="submit">Enviar instruções</button>
      </form>
      <div style="text-align:center; margin-top:14px;">
        <a href="login.php">Voltar ao login</a>
      </div>
    </div>
  </div>
</body>
</html>
