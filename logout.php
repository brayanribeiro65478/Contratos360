<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    registrar_historico('logout');
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
