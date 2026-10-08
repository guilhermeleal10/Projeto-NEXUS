<?php
require_once __DIR__ . '/../backend/configuracao/autenticacao.php';

sair_usuario();
header('Location: entrar.php');
exit;
