<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (usuario_logado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        $_SESSION['user_id']  = $usuario['id'];
        $_SESSION['nome']     = $usuario['nome'];
        $_SESSION['perfil']   = $usuario['perfil'];
        $_SESSION['orgao_id'] = $usuario['orgao_id'];
        registrar_historico('login', 'Usuário ' . $usuario['email'] . ' autenticado');
        header('Location: dashboard.php');
        exit;
    }
    $erro = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · Contratos 360</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="brand">Contratos 360</div>
      <div class="subtitulo">Gestão de Contratos e Atendimento Público</div>

      <?php if ($erro): ?>
        <div class="flash flash-error"><?= e($erro) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" name="email" required autofocus>
        </div>
        <div class="form-group">
          <label>Senha</label>
          <input type="password" name="senha" required>
        </div>
        <button class="btn" type="submit">Entrar</button>
      </form>
      <div style="text-align:center; margin-top:14px;">
        <a href="recuperar_senha.php">Esqueci minha senha</a>
      </div>

      <div class="login-hint">
        <strong>Contas de demonstração:</strong><br>
        Admin: admin@cariri.com / admin123<br>
        Técnico: tecnico@cariri.com / tecnico123<br>
        Servidor público: servidor@crato.ce.gov.br / cliente123
      </div>
    </div>
  </div>
</body>
</html>
