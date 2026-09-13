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

function coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :coluna'
    );
    $consulta->execute(['tabela' => $tabela, 'coluna' => $coluna]);

    return (int) $consulta->fetchColumn() > 0;
}

function indice_existe(PDO $pdo, string $tabela, string $indice): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND INDEX_NAME = :indice'
    );
    $consulta->execute(['tabela' => $tabela, 'indice' => $indice]);

    return (int) $consulta->fetchColumn() > 0;
}

function indice_unico_coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :tabela
           AND COLUMN_NAME = :coluna
           AND NON_UNIQUE = 0'
    );
    $consulta->execute(['tabela' => $tabela, 'coluna' => $coluna]);

    return (int) $consulta->fetchColumn() > 0;
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

function ajustar_matricula_planos(PDO $pdo): void
{
    if (!tabela_existe($pdo, 'Matricula')) {
        return;
    }

    if (!coluna_existe($pdo, 'Matricula', 'codigoMatricula')) {
        $pdo->exec('ALTER TABLE Matricula ADD codigoMatricula VARCHAR(24) NULL AFTER idMatricula');
    }

    $pdo->exec(
        "UPDATE Matricula
         SET codigoMatricula = CONCAT('NX-', DATE_FORMAT(COALESCE(dataMatricula, CURDATE()), '%Y%m'), '-', LPAD(idAluno, 4, '0'), '-', LPAD(idMatricula, 4, '0'))
         WHERE codigoMatricula IS NULL OR codigoMatricula = ''"
    );

    $pdo->exec('ALTER TABLE Matricula MODIFY codigoMatricula VARCHAR(24) NOT NULL');

    if (!indice_existe($pdo, 'Matricula', 'idx_matricula_codigo') && !indice_unico_coluna_existe($pdo, 'Matricula', 'codigoMatricula')) {
        $pdo->exec('CREATE UNIQUE INDEX idx_matricula_codigo ON Matricula (codigoMatricula)');
    }

    $planoCriado = false;

    if (!coluna_existe($pdo, 'Matricula', 'plano')) {
        $pdo->exec("ALTER TABLE Matricula ADD plano ENUM('Basico', 'Maromba', 'Shape') NOT NULL DEFAULT 'Basico' AFTER status");
        $planoCriado = true;
    }

    if (!coluna_existe($pdo, 'Matricula', 'valorPlano')) {
        $pdo->exec('ALTER TABLE Matricula ADD valorPlano DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER plano');
        $planoCriado = true;
    }

    if ($planoCriado) {
        $pdo->exec(
            "UPDATE Matricula m
             LEFT JOIN (
               SELECT idMatricula, MAX(valor) AS maiorValor
               FROM Mensalidade
               GROUP BY idMatricula
             ) mensalidade ON mensalidade.idMatricula = m.idMatricula
             SET
               m.plano = CASE
                 WHEN COALESCE(mensalidade.maiorValor, 0) >= 90 THEN 'Shape'
                 WHEN COALESCE(mensalidade.maiorValor, 0) >= 40 THEN 'Maromba'
                 ELSE 'Basico'
               END,
               m.valorPlano = CASE
                 WHEN COALESCE(mensalidade.maiorValor, 0) >= 90 THEN 99.99
                 WHEN COALESCE(mensalidade.maiorValor, 0) >= 40 THEN 49.90
                 ELSE 0.00
               END"
        );
    }
}

function ajustar_responsavel_academia(PDO $pdo): void
{
    if (!tabela_existe($pdo, 'Academia') || !tabela_existe($pdo, 'Usuario')) {
        return;
    }

    if (!coluna_existe($pdo, 'Academia', 'idResponsavel')) {
        $pdo->exec('ALTER TABLE Academia ADD idResponsavel INT NULL AFTER endereco');
    }

    $pdo->exec(
        "UPDATE Academia
         SET idResponsavel = (SELECT idUsuario FROM Usuario WHERE perfil = 'GERENTE' ORDER BY idUsuario LIMIT 1)
         WHERE idResponsavel IS NULL"
    );

    if (regra_exclusao_chave($pdo, 'Academia', 'fk_academia_responsavel') === null) {
        $pdo->exec(
            'ALTER TABLE Academia
             ADD CONSTRAINT fk_academia_responsavel
             FOREIGN KEY (idResponsavel) REFERENCES Usuario(idUsuario)
             ON UPDATE CASCADE
             ON DELETE SET NULL'
        );
    }
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
    ajustar_responsavel_academia($pdo);
    ajustar_matricula_planos($pdo);
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
