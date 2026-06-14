<?php

declare(strict_types=1);

function exibir_erro_banco(string $titulo, string $mensagem): void
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_contains($script, '/paginas/') ? '../' : '';

    http_response_code(500);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>NEXUS | Banco de dados</title>';
    echo '<link rel="stylesheet" href="' . $base . 'recursos/css/estilo.css"></head><body class="login-body">';
    echo '<main class="login-card"><header><h1>NEXUS</h1><p>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</p></header>';
    echo '<p class="text-muted">' . $mensagem . '</p>';
    echo '</main></body></html>';
    exit;
}

function tabela_existe(PDO $pdo, string $tabela): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela'
    );
    $consulta->execute(['tabela' => $tabela]);

    return (int) $consulta->fetchColumn() > 0;
}

function coluna_permite_nulo(PDO $pdo, string $tabela, string $coluna): bool
{
    $consulta = $pdo->prepare(
        'SELECT IS_NULLABLE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :coluna'
    );
    $consulta->execute(['tabela' => $tabela, 'coluna' => $coluna]);

    return $consulta->fetchColumn() === 'YES';
}

function regra_exclusao_chave(PDO $pdo, string $tabela, string $chave): ?string
{
    $consulta = $pdo->prepare(
        'SELECT DELETE_RULE
         FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND CONSTRAINT_NAME = :chave'
    );
    $consulta->execute(['tabela' => $tabela, 'chave' => $chave]);
    $regra = $consulta->fetchColumn();

    return $regra === false ? null : (string) $regra;
}

function ajustar_chave_usuario_opcional(PDO $pdo, string $tabela, string $coluna, string $chave): void
{
    if (!tabela_existe($pdo, $tabela) || !tabela_existe($pdo, 'Usuario')) {
        return;
    }

    $regra = regra_exclusao_chave($pdo, $tabela, $chave);

    if ($regra === 'SET NULL' && coluna_permite_nulo($pdo, $tabela, $coluna)) {
        return;
    }

    if ($regra !== null) {
        $pdo->exec("ALTER TABLE {$tabela} DROP FOREIGN KEY {$chave}");
    }

    $pdo->exec("ALTER TABLE {$tabela} MODIFY {$coluna} INT NULL");
    $pdo->exec(
        "ALTER TABLE {$tabela}
         ADD CONSTRAINT {$chave}
         FOREIGN KEY ({$coluna}) REFERENCES Usuario(idUsuario)
         ON UPDATE CASCADE
         ON DELETE SET NULL"
    );
}

function aplicar_migracoes(PDO $pdo): void
{
    static $executado = false;

    if ($executado) {
        return;
    }

    $executado = true;
    ajustar_chave_usuario_opcional($pdo, 'Matricula', 'idAtendente', 'fk_matricula_atendente');
    ajustar_chave_usuario_opcional($pdo, 'RelatorioFinanceiro', 'idGerente', 'fk_relatorio_gerente');
    ajustar_chave_usuario_opcional($pdo, 'SolicitacaoSuporte', 'idUsuarioSolicitante', 'fk_suporte_usuario');
}

function obter_conexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = 'localhost';
    $banco = 'nexus';
    $usuario = 'root';
    $senha = '';

    $dsn = "mysql:host={$host};dbname={$banco};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->query('SELECT 1 FROM Usuario LIMIT 1');
        aplicar_migracoes($pdo);
    } catch (PDOException $erro) {
        exibir_erro_banco(
            'Banco de dados nao preparado',
            'Importe primeiro <strong>banco-de-dados/01_usuarios.sql</strong> e depois <strong>banco-de-dados/02_valores.sql</strong> no phpMyAdmin.'
        );
    }

    return $pdo;
}
