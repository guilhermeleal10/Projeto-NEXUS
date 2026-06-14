<?php
require_once __DIR__ . '/configuracao/autenticacao.php';

sair_usuario();
header('Location: entrar.php');
exit;
