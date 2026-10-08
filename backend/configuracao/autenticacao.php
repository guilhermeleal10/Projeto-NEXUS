<?php

declare(strict_types=1);

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function usuario_atual(): ?array
{
    if (empty($_SESSION['idUsuario'])) {
        return null;
    }

    $pdo = obter_conexao();
    $consulta = $pdo->prepare('SELECT * FROM Usuario WHERE idUsuario = :idUsuario');
    $consulta->execute(['idUsuario' => $_SESSION['idUsuario']]);
    $usuario = $consulta->fetch();

    return $usuario ?: null;
}

function entrar_usuario(string $email, string $senha): ?array
{
    if (!senha_tem_formato_valido($senha)) {
        return null;
    }

    $pdo = obter_conexao();
    $consulta = $pdo->prepare('SELECT * FROM Usuario WHERE email = :email LIMIT 1');
    $consulta->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);
    $usuario = $consulta->fetch();

    if (!$usuario || !hash_equals((string) $usuario['senhaCriptografada'], senha_criptografada($senha))) {
        return null;
    }

    $_SESSION['idUsuario'] = $usuario['idUsuario'];
    $_SESSION['perfil'] = $usuario['perfil'];

    return $usuario;
}

function painel_por_perfil(string $perfil): string
{
    return match ($perfil) {
        'ADMIN' => 'painel-administrador.php',
        'GERENTE' => 'painel-gerente.php',
        'ATENDENTE' => 'painel-atendente.php',
        'CLIENTE' => 'painel-cliente.php',
        default => 'entrar.php',
    };
}

function verificarPerfil(array $perfisPermitidos): array
{
    $usuario = usuario_atual();

    if (!$usuario) {
        header('Location: ' . caminho_base() . 'entrar.php');
        exit;
    }

    if ($perfisPermitidos && !in_array($usuario['perfil'], $perfisPermitidos, true)) {
        header('Location: ' . caminho_base() . painel_por_perfil((string) $usuario['perfil']));
        exit;
    }

    return $usuario;
}

function exigir_login(string|array|null $perfil = null): array
{
    $perfis = $perfil === null ? [] : (array) $perfil;
    return verificarPerfil($perfis);
}

function sair_usuario(): void
{
    $_SESSION = [];
    session_destroy();
}
