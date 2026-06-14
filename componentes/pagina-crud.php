<?php

declare(strict_types=1);

require_once __DIR__ . '/../configuracao/autenticacao.php';
require_once __DIR__ . '/../configuracao/entidades.php';
require_once __DIR__ . '/layout.php';

function opcoes_campo(PDO $pdo, array $campo): array
{
    if (isset($campo['opcoes'])) {
        return array_map(function ($opcao) {
            if (is_array($opcao)) {
                return ['valor' => $opcao['valor'], 'rotulo' => $opcao['rotulo']];
            }

            return ['valor' => $opcao, 'rotulo' => $opcao];
        }, $campo['opcoes']);
    }

    if (empty($campo['relacao'])) {
        return [];
    }

    $relacao = $campo['relacao'];
    $filtro = !empty($relacao['filtro']) ? ' WHERE ' . $relacao['filtro'] : '';
    $sql = "SELECT {$relacao['id']} AS valor, {$relacao['rotulo']} AS rotulo FROM {$relacao['tabela']}{$filtro} ORDER BY rotulo";

    return $pdo->query($sql)->fetchAll();
}

function campo_oculto_para_usuario(array $campo, array $usuario): bool
{
    return in_array($usuario['perfil'], $campo['ocultar_para_perfis'] ?? [], true);
}

function campo_disponivel_formulario(array $campo, array $usuario, ?array $registroAtual): bool
{
    if (campo_oculto_para_usuario($campo, $usuario)) {
        return false;
    }

    if (!empty($campo['apenas_novo']) && $registroAtual) {
        return false;
    }

    return true;
}

function perfil_somente_leitura(array $entidade, array $usuario): bool
{
    return in_array($usuario['perfil'], $entidade['somente_leitura_perfis'] ?? [], true);
}

function usuario_pode_acessar_registro(array $entidade, array $usuario, ?array $registro): bool
{
    if (!$registro) {
        return false;
    }

    if ($entidade['tabela'] !== 'SolicitacaoSuporte' || $usuario['perfil'] === 'ADMIN') {
        return true;
    }

    return (int) $registro['idUsuarioSolicitante'] === (int) $usuario['idUsuario'];
}

function rotulo_relacao(PDO $pdo, array $campo, mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return 'Não informado';
    }

    if (empty($campo['relacao'])) {
        return (string) $valor;
    }

    $relacao = $campo['relacao'];
    $consulta = $pdo->prepare("SELECT {$relacao['rotulo']} AS rotulo FROM {$relacao['tabela']} WHERE {$relacao['id']} = :valor LIMIT 1");
    $consulta->execute(['valor' => $valor]);
    $linha = $consulta->fetch();

    return $linha ? (string) $linha['rotulo'] : 'Não encontrado';
}

function buscar_registro(PDO $pdo, array $entidade, mixed $id): ?array
{
    if (!$id) {
        return null;
    }

    $consulta = $pdo->prepare("SELECT * FROM {$entidade['tabela']} WHERE {$entidade['id']} = :id");
    $consulta->execute(['id' => $id]);
    $registro = $consulta->fetch();

    return $registro ?: null;
}

function calcular_relatorio(PDO $pdo, array $dados): array
{
    $parametros = [
        'academia' => $dados['idAcademia'] ?? 0,
        'mes' => $dados['mesReferencia'] ?? 0,
        'ano' => $dados['anoReferencia'] ?? 0,
    ];

    $receitas = $pdo->prepare(
        'SELECT COALESCE(SUM(valor), 0) FROM Receita
         WHERE idAcademia = :academia AND MONTH(dataReceita) = :mes AND YEAR(dataReceita) = :ano'
    );
    $receitas->execute($parametros);

    $despesas = $pdo->prepare(
        'SELECT COALESCE(SUM(valor), 0) FROM Despesa
         WHERE idAcademia = :academia AND MONTH(dataDespesa) = :mes AND YEAR(dataDespesa) = :ano'
    );
    $despesas->execute($parametros);

    $totalReceitas = (float) $receitas->fetchColumn();
    $totalDespesas = (float) $despesas->fetchColumn();

    return [
        'totalReceitas' => $totalReceitas,
        'totalDespesas' => $totalDespesas,
        'saldoFinal' => $totalReceitas - $totalDespesas,
    ];
}

function gerar_codigo_matricula(PDO $pdo, int $idAluno): string
{
    for ($tentativa = 0; $tentativa < 20; $tentativa++) {
        $codigo = sprintf('NX-%s-%04d-%04d', date('Ym'), $idAluno, random_int(0, 9999));
        $consulta = $pdo->prepare('SELECT COUNT(*) FROM Matricula WHERE codigoMatricula = :codigo');
        $consulta->execute(['codigo' => $codigo]);

        if ((int) $consulta->fetchColumn() === 0) {
            return $codigo;
        }
    }

    throw new RuntimeException('Nao foi possivel gerar um codigo unico de matricula. Tente novamente.');
}

function valor_plano_matricula(PDO $pdo, int $idMatricula): float
{
    $consulta = $pdo->prepare('SELECT valorPlano FROM Matricula WHERE idMatricula = :idMatricula LIMIT 1');
    $consulta->execute(['idMatricula' => $idMatricula]);
    $valor = $consulta->fetchColumn();

    return $valor === false ? 0.00 : (float) $valor;
}

function coletar_dados_formulario(PDO $pdo, array $entidade, ?array $registroAtual, array $usuario): array
{
    $dados = [];

    foreach ($entidade['campos'] as $campo) {
        if (!campo_disponivel_formulario($campo, $usuario, $registroAtual)) {
            continue;
        }

        $nome = $campo['nome'];
        $valor = trim((string) ($_POST[$nome] ?? ''));
        $editandoSenha = $nome === 'senhaCriptografada' && $registroAtual && $valor === '';

        if (!empty($campo['obrigatorio']) && !$editandoSenha && $valor === '') {
            throw new RuntimeException('Preencha o campo: ' . $campo['rotulo'] . '.');
        }

        if (!empty($campo['somente_leitura'])) {
            continue;
        }

        if (!empty($campo['virtual'])) {
            continue;
        }

        if ($nome === 'senhaCriptografada') {
            if ($editandoSenha) {
                continue;
            }
            validar_senha_sistema($valor);
            $dados[$nome] = senha_criptografada($valor);
            continue;
        }

        if ($nome === 'email') {
            $dados[$nome] = $valor === '' ? null : mb_strtolower($valor, 'UTF-8');
            continue;
        }

        if (($campo['tipo'] ?? '') === 'number' && $valor !== '') {
            $dados[$nome] = (float) str_replace(',', '.', $valor);
        } else {
            $dados[$nome] = $valor === '' ? null : $valor;
        }
    }

    if ($entidade['tabela'] === 'RelatorioFinanceiro') {
        $dados = array_merge($dados, calcular_relatorio($pdo, $dados));
    }

    if ($entidade['tabela'] === 'Matricula') {
        $dados['plano'] = (string) ($dados['plano'] ?? 'Basico');
        $dados['valorPlano'] = valor_plano($dados['plano']);

        if (!$registroAtual || empty($registroAtual['codigoMatricula'])) {
            $dados['codigoMatricula'] = gerar_codigo_matricula($pdo, (int) ($dados['idAluno'] ?? 0));
        }
    }

    if ($entidade['tabela'] === 'Mensalidade' && !empty($dados['idMatricula'])) {
        $dados['valor'] = valor_plano_matricula($pdo, (int) $dados['idMatricula']);
    }

    if ($entidade['tabela'] === 'SolicitacaoSuporte') {
        if ($usuario['perfil'] !== 'ADMIN') {
            if (!$registroAtual) {
                $dados['status'] = 'Aberta';
                $dados['dataAbertura'] = date('Y-m-d');
                $dados['dataFechamento'] = null;
                $dados['idUsuarioSolicitante'] = (int) $usuario['idUsuario'];
                $dados['idAdminResponsavel'] = null;
            }
        } else {
            if (($dados['status'] ?? '') === 'Resolvida' && empty($dados['dataFechamento'])) {
                $dados['dataFechamento'] = date('Y-m-d');
            }

            if (($dados['status'] ?? '') !== 'Resolvida') {
                $dados['dataFechamento'] = null;
            }
        }
    }

    return $dados;
}

function salvar_registro(PDO $pdo, array $entidade, ?int $id, array $dados): int
{
    if ($id) {
        if (!$dados) {
            return $id;
        }

        $atribuicoes = array_map(fn ($campo) => "{$campo} = :{$campo}", array_keys($dados));
        $dados['idRegistro'] = $id;
        $sql = "UPDATE {$entidade['tabela']} SET " . implode(', ', $atribuicoes) . " WHERE {$entidade['id']} = :idRegistro";
        $pdo->prepare($sql)->execute($dados);
        return $id;
    }

    $colunas = array_keys($dados);
    $parametros = array_map(fn ($campo) => ":{$campo}", $colunas);
    $sql = "INSERT INTO {$entidade['tabela']} (" . implode(', ', $colunas) . ') VALUES (' . implode(', ', $parametros) . ')';
    $pdo->prepare($sql)->execute($dados);

    return (int) $pdo->lastInsertId();
}

function criar_matricula_inicial_aluno(PDO $pdo, int $idAluno, array $dadosAluno, array $usuario, string $plano): int
{
    $planos = planos_matricula();

    if (!isset($planos[$plano])) {
        throw new RuntimeException('Selecione um plano valido.');
    }

    $idAcademia = (int) ($dadosAluno['idAcademia'] ?? 0);

    if ($idAluno <= 0 || $idAcademia <= 0) {
        throw new RuntimeException('Nao foi possivel criar a matricula inicial do aluno.');
    }

    $inserir = $pdo->prepare(
        'INSERT INTO Matricula
         (codigoMatricula, dataMatricula, status, plano, valorPlano, idAluno, idAcademia, idAtendente)
         VALUES
         (:codigoMatricula, :dataMatricula, :status, :plano, :valorPlano, :idAluno, :idAcademia, :idAtendente)'
    );
    $inserir->execute([
        'codigoMatricula' => gerar_codigo_matricula($pdo, $idAluno),
        'dataMatricula' => date('Y-m-d'),
        'status' => 'Ativa',
        'plano' => $plano,
        'valorPlano' => valor_plano($plano),
        'idAluno' => $idAluno,
        'idAcademia' => $idAcademia,
        'idAtendente' => (int) $usuario['idUsuario'],
    ]);

    $idMatricula = (int) $pdo->lastInsertId();
    $valorPlano = valor_plano($plano);

    if ($valorPlano > 0) {
        $mensalidade = $pdo->prepare(
            'INSERT INTO Mensalidade (valor, dataVencimento, dataPagamento, status, idMatricula)
             VALUES (:valor, :dataVencimento, NULL, :status, :idMatricula)'
        );
        $mensalidade->execute([
            'valor' => $valorPlano,
            'dataVencimento' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'Pendente',
            'idMatricula' => $idMatricula,
        ]);
    }

    return $idMatricula;
}

function executar_em_transacao(PDO $pdo, callable $acao): void
{
    $jaEmTransacao = $pdo->inTransaction();

    if (!$jaEmTransacao) {
        $pdo->beginTransaction();
    }

    try {
        $acao();

        if (!$jaEmTransacao) {
            $pdo->commit();
        }
    } catch (Throwable $erro) {
        if (!$jaEmTransacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }
}

function excluir_usuario_acesso(PDO $pdo, int $id): void
{
    executar_em_transacao($pdo, function () use ($pdo, $id): void {
        $comandos = [
            'UPDATE Aluno SET idUsuario = NULL WHERE idUsuario = :id',
            'UPDATE Matricula SET idAtendente = NULL WHERE idAtendente = :id',
            'UPDATE RelatorioFinanceiro SET idGerente = NULL WHERE idGerente = :id',
            'UPDATE SolicitacaoSuporte SET idUsuarioSolicitante = NULL WHERE idUsuarioSolicitante = :id',
            'UPDATE SolicitacaoSuporte SET idAdminResponsavel = NULL WHERE idAdminResponsavel = :id',
            'DELETE FROM Usuario WHERE idUsuario = :id',
        ];

        foreach ($comandos as $sql) {
            $pdo->prepare($sql)->execute(['id' => $id]);
        }
    });
}

function excluir_matricula_com_dependencias(PDO $pdo, int $id): void
{
    executar_em_transacao($pdo, function () use ($pdo, $id): void {
        $pdo->prepare('DELETE FROM Mensalidade WHERE idMatricula = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM Matricula WHERE idMatricula = :id')->execute(['id' => $id]);
    });
}

function excluir_aluno_com_dependencias(PDO $pdo, int $id): void
{
    executar_em_transacao($pdo, function () use ($pdo, $id): void {
        $pdo->prepare(
            'DELETE me FROM Mensalidade me
             INNER JOIN Matricula ma ON ma.idMatricula = me.idMatricula
             WHERE ma.idAluno = :id'
        )->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM Matricula WHERE idAluno = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM Aluno WHERE idAluno = :id')->execute(['id' => $id]);
    });
}

function excluir_registro(PDO $pdo, array $entidade, int $id): void
{
    if ($entidade['tabela'] === 'Usuario') {
        excluir_usuario_acesso($pdo, $id);
        return;
    }

    if ($entidade['tabela'] === 'Aluno') {
        excluir_aluno_com_dependencias($pdo, $id);
        return;
    }

    if ($entidade['tabela'] === 'Matricula') {
        excluir_matricula_com_dependencias($pdo, $id);
        return;
    }

    $consulta = $pdo->prepare("DELETE FROM {$entidade['tabela']} WHERE {$entidade['id']} = :id");
    $consulta->execute(['id' => $id]);
}

function mensagem_erro_operacao(Throwable $erro): string
{
    if (!$erro instanceof PDOException) {
        return $erro->getMessage();
    }

    $detalhe = $erro->getMessage();

    if (str_contains($detalhe, 'Duplicate entry')) {
        return 'Ja existe um registro com CPF, e-mail, codigo ou usuario de acesso informado.';
    }

    if (str_contains($detalhe, 'foreign key constraint')) {
        return 'Selecione registros vinculados validos antes de salvar.';
    }

    return 'Nao foi possivel concluir a operacao. Verifique os dados informados.';
}

function listar_registros(PDO $pdo, array $entidade, array $usuario): array
{
    if ($entidade['tabela'] === 'SolicitacaoSuporte' && $usuario['perfil'] !== 'ADMIN') {
        $consulta = $pdo->prepare(
            "SELECT * FROM {$entidade['tabela']}
             WHERE idUsuarioSolicitante = :idUsuario
             ORDER BY {$entidade['ordem']}"
        );
        $consulta->execute(['idUsuario' => $usuario['idUsuario']]);
        return $consulta->fetchAll();
    }

    return $pdo->query("SELECT * FROM {$entidade['tabela']} ORDER BY {$entidade['ordem']}")->fetchAll();
}

function texto_busca_registro(PDO $pdo, array $entidade, array $registro): string
{
    $partes = [$registro[$entidade['id']] ?? ''];

    foreach ($entidade['campos'] as $campo) {
        if (($campo['tabela_listagem'] ?? true) === false) {
            continue;
        }

        $valor = $registro[$campo['nome']] ?? '';
        $partes[] = !empty($campo['relacao']) ? rotulo_relacao($pdo, $campo, $valor) : $valor;
    }

    return mb_strtolower(implode(' ', $partes), 'UTF-8');
}

function formatar_valor_listagem(PDO $pdo, array $campo, array $registro): string
{
    $valor = $registro[$campo['nome']] ?? null;

    if (!empty($campo['relacao'])) {
        return h(rotulo_relacao($pdo, $campo, $valor));
    }

    if (!empty($campo['dinheiro'])) {
        return h(dinheiro($valor));
    }

    if (($campo['tipo'] ?? '') === 'date') {
        return h(data_br($valor));
    }

    if ($campo['nome'] === 'mesReferencia') {
        return h(nome_mes($valor));
    }

    if ($campo['nome'] === 'plano') {
        return '<span class="status-badge">' . h(rotulo_plano($valor)) . '</span>';
    }

    if ($campo['nome'] === 'status' || $campo['nome'] === 'perfil') {
        return badge_status((string) $valor);
    }

    return h($valor ?: 'Não informado');
}

function configuracao_tela_crud(array $entidade, array $usuario): array
{
    $perfil = (string) $usuario['perfil'];
    $tabela = $entidade['tabela'];
    $chave = "{$perfil}:{$tabela}";
    $padrao = [
        'titulo' => $entidade['titulo'],
        'descricao' => 'Gerencie os registros desta area conforme as permissoes do seu perfil.',
        'formulario' => 'Cadastrar ' . $entidade['singular'],
        'consulta' => 'Consulta',
        'lista' => 'Listagem',
    ];

    $configuracoes = [
        'ADMIN:Aluno' => [
            'titulo' => 'Auditoria e manutencao de alunos',
            'descricao' => 'O ADMIN acompanha toda a base e pode corrigir vinculos de acesso, academia e dados cadastrais quando necessario.',
            'formulario' => 'Manutencao de aluno',
            'consulta' => 'Painel administrativo',
            'lista' => 'Base completa de alunos',
        ],
        'GERENTE:Aluno' => [
            'titulo' => 'Visao gerencial da base de alunos',
            'descricao' => 'Consulta focada em unidade, vinculo de acesso e distribuicao de alunos. Alteracoes ficam com o atendimento.',
            'consulta' => 'Leitura gerencial',
            'lista' => 'Alunos para acompanhamento',
        ],
        'ATENDENTE:Aluno' => [
            'titulo' => 'Cadastro operacional de alunos',
            'descricao' => 'Tela de atendimento para criar, atualizar e organizar os dados de alunos vinculados as academias.',
            'formulario' => 'Cadastrar aluno',
            'lista' => 'Fila de atendimento e cadastros',
        ],
        'ADMIN:Matricula' => [
            'titulo' => 'Governanca de matriculas',
            'descricao' => 'O ADMIN pode revisar contratos, corrigir responsaveis e manter o historico consistente.',
            'formulario' => 'Manutencao de matricula',
            'consulta' => 'Painel administrativo',
            'lista' => 'Matriculas do sistema',
        ],
        'GERENTE:Matricula' => [
            'titulo' => 'Carteira de matriculas',
            'descricao' => 'Visao para acompanhar contratos ativos, trancados e cancelados sem alterar a operacao do balcao.',
            'consulta' => 'Indicadores de contratos',
            'lista' => 'Matriculas para acompanhamento',
        ],
        'ATENDENTE:Matricula' => [
            'titulo' => 'Operacao de matriculas',
            'descricao' => 'Tela para abrir, atualizar e encerrar matriculas de alunos durante o atendimento.',
            'formulario' => 'Cadastrar matricula',
            'lista' => 'Matriculas em atendimento',
        ],
        'ADMIN:Mensalidade' => [
            'titulo' => 'Auditoria de mensalidades',
            'descricao' => 'Acesso administrativo para revisar cobrancas, vencimentos e baixas financeiras registradas.',
            'formulario' => 'Ajustar mensalidade',
            'consulta' => 'Painel administrativo',
            'lista' => 'Mensalidades do sistema',
        ],
        'GERENTE:Mensalidade' => [
            'titulo' => 'Leitura financeira de mensalidades',
            'descricao' => 'Consulta gerencial para acompanhar inadimplencia, pagamentos e previsao de recebimento.',
            'consulta' => 'Resumo financeiro',
            'lista' => 'Mensalidades para analise',
        ],
        'ATENDENTE:Mensalidade' => [
            'titulo' => 'Baixa de mensalidades',
            'descricao' => 'Tela operacional para registrar vencimentos, pagamentos e status de cobrancas.',
            'formulario' => 'Cadastrar mensalidade',
            'lista' => 'Cobrancas em atendimento',
        ],
        'ADMIN:RelatorioFinanceiro' => [
            'titulo' => 'Auditoria de relatorios financeiros',
            'descricao' => 'O ADMIN consulta os relatorios gerados pela gerencia e confere receitas, despesas e saldo final.',
            'consulta' => 'Auditoria administrativa',
            'lista' => 'Relatorios para revisao',
        ],
        'GERENTE:RelatorioFinanceiro' => [
            'titulo' => 'Geracao de relatorios financeiros',
            'descricao' => 'Tela da gerencia para gerar resumos mensais por academia com calculo automatico de receitas, despesas e saldo.',
            'formulario' => 'Gerar relatorio financeiro',
            'lista' => 'Relatorios gerados',
        ],
    ];

    return array_merge($padrao, $configuracoes[$chave] ?? []);
}

function metricas_crud(array $entidade, array $registros): array
{
    $total = count($registros);

    if ($entidade['tabela'] === 'Aluno') {
        $comAcesso = count(array_filter($registros, fn ($registro) => !empty($registro['idUsuario'])));
        $academias = array_unique(array_filter(array_column($registros, 'idAcademia')));

        return [
            ['rotulo' => 'Alunos', 'valor' => (string) $total],
            ['rotulo' => 'Com usuario', 'valor' => (string) $comAcesso],
            ['rotulo' => 'Academias', 'valor' => (string) count($academias)],
        ];
    }

    if ($entidade['tabela'] === 'Matricula') {
        $ativas = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Ativa'));
        $trancadas = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Trancada'));
        $canceladas = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Cancelada'));

        return [
            ['rotulo' => 'Matriculas', 'valor' => (string) $total],
            ['rotulo' => 'Ativas', 'valor' => (string) $ativas],
            ['rotulo' => 'Trancadas', 'valor' => (string) $trancadas],
            ['rotulo' => 'Canceladas', 'valor' => (string) $canceladas],
        ];
    }

    if ($entidade['tabela'] === 'Mensalidade') {
        $valorTotal = array_sum(array_map(fn ($registro) => (float) ($registro['valor'] ?? 0), $registros));
        $pendentes = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Pendente'));
        $atrasadas = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Atrasado'));
        $pagas = count(array_filter($registros, fn ($registro) => ($registro['status'] ?? '') === 'Pago'));

        return [
            ['rotulo' => 'Valor total', 'valor' => dinheiro($valorTotal)],
            ['rotulo' => 'Pendentes', 'valor' => (string) $pendentes],
            ['rotulo' => 'Atrasadas', 'valor' => (string) $atrasadas],
            ['rotulo' => 'Pagas', 'valor' => (string) $pagas],
        ];
    }

    if ($entidade['tabela'] === 'RelatorioFinanceiro') {
        $receitas = array_sum(array_map(fn ($registro) => (float) ($registro['totalReceitas'] ?? 0), $registros));
        $despesas = array_sum(array_map(fn ($registro) => (float) ($registro['totalDespesas'] ?? 0), $registros));
        $saldo = array_sum(array_map(fn ($registro) => (float) ($registro['saldoFinal'] ?? 0), $registros));

        return [
            ['rotulo' => 'Relatorios', 'valor' => (string) $total],
            ['rotulo' => 'Receitas', 'valor' => dinheiro($receitas)],
            ['rotulo' => 'Despesas', 'valor' => dinheiro($despesas)],
            ['rotulo' => 'Saldo', 'valor' => dinheiro($saldo)],
        ];
    }

    return [
        ['rotulo' => 'Registros', 'valor' => (string) $total],
    ];
}

function campo_entidade(array $entidade, string $nome): ?array
{
    foreach ($entidade['campos'] as $campo) {
        if (($campo['nome'] ?? '') === $nome) {
            return $campo;
        }
    }

    return null;
}

function agrupar_financeiro_por_periodo(array $registros, string $campoData): array
{
    $agrupados = [];

    foreach ($registros as $registro) {
        $data = (string) ($registro[$campoData] ?? '');
        $timestamp = $data !== '' ? strtotime($data) : false;

        if (!$timestamp) {
            continue;
        }

        $chave = date('Y-m', $timestamp);

        if (!isset($agrupados[$chave])) {
            $agrupados[$chave] = [
                'ano' => (int) date('Y', $timestamp),
                'mes' => (int) date('n', $timestamp),
                'nome' => mes_ano_curto(date('n', $timestamp), date('Y', $timestamp)),
                'total' => 0.0,
            ];
        }

        $agrupados[$chave]['total'] += (float) ($registro['valor'] ?? 0);
    }

    ksort($agrupados);
    return array_slice(array_values($agrupados), -8);
}

function agrupar_financeiro_por_campo(PDO $pdo, array $entidade, array $registros, string $nomeCampo): array
{
    $campo = campo_entidade($entidade, $nomeCampo) ?? ['nome' => $nomeCampo];
    $agrupados = [];

    foreach ($registros as $registro) {
        $valor = $registro[$nomeCampo] ?? null;

        if (!empty($campo['relacao'])) {
            $rotulo = rotulo_relacao($pdo, $campo, $valor);
        } else {
            $rotulo = trim((string) $valor);
            $rotulo = $rotulo !== '' ? $rotulo : 'Nao informado';
        }

        if (!isset($agrupados[$rotulo])) {
            $agrupados[$rotulo] = [
                'nome' => $rotulo,
                'total' => 0.0,
            ];
        }

        $agrupados[$rotulo]['total'] += (float) ($registro['valor'] ?? 0);
    }

    uasort($agrupados, fn (array $a, array $b): int => ((float) $b['total'] <=> (float) $a['total']) ?: strcmp($a['nome'], $b['nome']));

    return array_values($agrupados);
}

function renderizar_graficos_financeiros_crud(PDO $pdo, array $entidade, array $usuario, array $registros): void
{
    if ($usuario['perfil'] !== 'GERENTE' || !in_array($entidade['tabela'], ['Receita', 'Despesa'], true)) {
        return;
    }

    $ehDespesa = $entidade['tabela'] === 'Despesa';
    $campoData = $ehDespesa ? 'dataDespesa' : 'dataReceita';
    $campoGrupo = $ehDespesa ? 'categoria' : 'idAcademia';
    $tituloBarras = $ehDespesa ? 'Despesas por mes' : 'Receitas por mes';
    $tituloPizza = $ehDespesa ? 'Despesas por categoria' : 'Receitas por academia';
    $classeBarra = $ehDespesa ? ' expense' : '';
    $linhasPeriodo = agrupar_financeiro_por_periodo($registros, $campoData);
    $linhasGrupo = agrupar_financeiro_por_campo($pdo, $entidade, $registros, $campoGrupo);
    ?>
    <section class="finance-grid crud-charts" aria-label="Graficos financeiros">
      <article class="panel">
        <div class="panel-header"><h2><?= h($tituloBarras) ?></h2></div>
        <?php if (!$linhasPeriodo): ?>
          <div class="empty-state">Nenhum registro encontrado.</div>
        <?php else: ?>
          <?php $maximoPeriodo = max(array_map(fn (array $linha): float => (float) $linha['total'], $linhasPeriodo)); ?>
          <div class="chart-bars" style="grid-template-columns: repeat(<?= h(count($linhasPeriodo)) ?>, minmax(86px, 1fr));">
            <?php foreach ($linhasPeriodo as $linha): ?>
              <div class="chart-group">
                <div class="chart-bar<?= h($classeBarra) ?>" style="height:<?= h(altura_grafico($linha['total'], $maximoPeriodo)) ?>px" title="<?= h(dinheiro($linha['total'])) ?>"></div>
                <span class="chart-value"><?= h(dinheiro($linha['total'])) ?></span>
                <span class="chart-label"><?= h($linha['nome']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

      <article class="panel">
        <div class="panel-header"><h2><?= h($tituloPizza) ?></h2></div>
        <?php if (!$linhasGrupo): ?>
          <div class="empty-state">Nenhum registro encontrado.</div>
        <?php else: ?>
          <?php $totalGrupo = total_grafico($linhasGrupo); ?>
          <div class="pie-layout">
            <div class="pie-chart" style="<?= h(estilo_grafico_pizza($linhasGrupo)) ?>" aria-label="<?= h($tituloPizza) ?>">
              <span class="pie-total money"><?= h(dinheiro($totalGrupo)) ?></span>
            </div>
            <div class="pie-legend" aria-label="Legenda">
              <?php foreach ($linhasGrupo as $indice => $linha): ?>
                <div class="pie-legend-item">
                  <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
                  <span><?= h($linha['nome']) ?></span>
                  <strong><?= h(dinheiro($linha['total'])) ?> / <?= h(percentual_grafico($linha['total'], $totalGrupo)) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </article>
    </section>
    <?php
}

function renderizar_visao_crud(array $entidade, array $usuario, array $registros, array $configuracao): void
{
    $classePerfil = 'role-' . mb_strtolower((string) $usuario['perfil'], 'UTF-8');
    ?>
    <section class="role-overview <?= h($classePerfil) ?>" aria-label="Resumo da tela">
      <div class="role-copy">
        <span class="role-chip"><?= h($usuario['perfil']) ?></span>
        <h2><?= h($configuracao['titulo']) ?></h2>
        <p><?= h($configuracao['descricao']) ?></p>
      </div>
      <div class="mini-metrics" aria-label="Indicadores">
        <?php foreach (metricas_crud($entidade, $registros) as $metrica): ?>
          <div class="mini-metric">
            <span><?= h($metrica['rotulo']) ?></span>
            <strong><?= h($metrica['valor']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}

function valor_padrao_campo(array $campo): string
{
    $hoje = date('Y-m-d');

    return match ($campo['nome']) {
        'dataMatricula', 'dataReceita', 'dataDespesa', 'dataAbertura' => $hoje,
        'mesReferencia' => (string) date('n'),
        'anoReferencia' => (string) date('Y'),
        'codigoMatricula' => 'Gerado automaticamente',
        'totalReceitas', 'totalDespesas', 'saldoFinal' => '0',
        default => '',
    };
}

function renderizar_campo(PDO $pdo, array $campo, ?array $registro, array $usuario): void
{
    if (!campo_disponivel_formulario($campo, $usuario, $registro)) {
        return;
    }

    $nome = $campo['nome'];
    $tipo = $campo['tipo'] ?? 'text';
    $editandoSenha = $nome === 'senhaCriptografada' && $registro !== null;
    $valor = $editandoSenha ? '' : (string) ($registro[$nome] ?? valor_padrao_campo($campo));
    $obrigatorio = !empty($campo['obrigatorio']) && !$editandoSenha ? 'required' : '';
    $somenteLeitura = !empty($campo['somente_leitura']) ? 'readonly' : '';
    $classe = !empty($campo['completo']) || $tipo === 'textarea' ? 'field full' : 'field';
    ?>
    <div class="<?= h($classe) ?>">
      <label for="<?= h($nome) ?>"><?= h($campo['rotulo']) ?><?= $obrigatorio ? ' *' : '' ?></label>
      <?php if ($tipo === 'select'): ?>
        <?php
        $opcoes = opcoes_campo($pdo, $campo);
        $valorExisteNasOpcoes = $valor === '';

        foreach ($opcoes as $opcao) {
            if ((string) $opcao['valor'] === $valor) {
                $valorExisteNasOpcoes = true;
                break;
            }
        }

        if (!$valorExisteNasOpcoes) {
            $opcoes[] = ['valor' => $valor, 'rotulo' => rotulo_relacao($pdo, $campo, $valor)];
        }
        ?>
        <select id="<?= h($nome) ?>" name="<?= h($nome) ?>" <?= $obrigatorio ?>>
          <option value="">Selecione...</option>
          <?php foreach ($opcoes as $opcao): ?>
            <option value="<?= h($opcao['valor']) ?>" <?= (string) $opcao['valor'] === $valor ? 'selected' : '' ?>>
              <?= h($opcao['rotulo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php elseif ($tipo === 'textarea'): ?>
        <textarea id="<?= h($nome) ?>" name="<?= h($nome) ?>" <?= $obrigatorio ?>><?= h($valor) ?></textarea>
      <?php elseif ($tipo === 'password'): ?>
        <div class="input-shell password-shell">
          <span class="material-symbols-outlined" aria-hidden="true">lock</span>
          <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="password" value="<?= h($valor) ?>" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" data-password-input <?= $obrigatorio ?>>
          <button class="password-toggle" type="button" data-password-toggle aria-controls="<?= h($nome) ?>" aria-label="Mostrar senha" aria-pressed="false">
            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
          </button>
        </div>
      <?php else: ?>
        <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="<?= h($tipo) ?>" value="<?= h($valor) ?>" <?= $obrigatorio ?> <?= $somenteLeitura ?> <?= $tipo === 'number' ? 'step="0.01"' : '' ?>>
      <?php endif; ?>
      <?php if ($editandoSenha): ?>
        <span class="field-help">Deixe em branco para manter a senha atual.</span>
      <?php endif; ?>
      <?php if (!empty($campo['ajuda'])): ?>
        <span class="field-help"><?= h($campo['ajuda']) ?></span>
      <?php endif; ?>
    </div>
    <?php
}

function executar_pagina_crud(array $entidade): void
{
    $usuario = verificarPerfil($entidade['perfis'] ?? []);
    $pdo = obter_conexao();
    $mensagem = $_GET['mensagem'] ?? '';
    $tipoMensagem = $_GET['tipo'] ?? 'success';
    $somenteLeitura = perfil_somente_leitura($entidade, $usuario);
    $cadastroManual = (bool) ($entidade['cadastro_manual'] ?? true);
    $configuracaoTela = configuracao_tela_crud($entidade, $usuario);

    try {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if ($somenteLeitura) {
                throw new RuntimeException('Seu perfil pode consultar estes registros, mas nao pode alterar dados nesta tela.');
            }

            $acao = $_POST['acao'] ?? '';

            if ($acao === 'excluir') {
                $id = (int) ($_POST['id'] ?? 0);
                $registroAtual = buscar_registro($pdo, $entidade, $id);

                if (!usuario_pode_acessar_registro($entidade, $usuario, $registroAtual)) {
                    throw new RuntimeException('Voce nao tem permissao para excluir este registro.');
                }

                excluir_registro($pdo, $entidade, $id);
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?mensagem=' . urlencode('Registro excluído com sucesso.') . '&tipo=success');
                exit;
            }

            if ($acao === 'salvar') {
                $id = (int) ($_POST['id'] ?? 0);
                $registroAtual = $id ? buscar_registro($pdo, $entidade, $id) : null;

                if ($id && !usuario_pode_acessar_registro($entidade, $usuario, $registroAtual)) {
                    throw new RuntimeException('Voce nao tem permissao para alterar este registro.');
                }

                if (!$id && !$cadastroManual) {
                    throw new RuntimeException('Este modulo nao possui cadastro manual. Use o cadastro de aluno e o plano escolhido.');
                }

                $dados = coletar_dados_formulario($pdo, $entidade, $registroAtual, $usuario);
                $novoAlunoAtendente = !$id && $entidade['tabela'] === 'Aluno' && $usuario['perfil'] === 'ATENDENTE';
                $planoAluno = (string) ($_POST['planoAluno'] ?? 'Basico');

                executar_em_transacao($pdo, function () use ($pdo, $entidade, $id, $dados, $usuario, $novoAlunoAtendente, $planoAluno): void {
                    $idSalvo = salvar_registro($pdo, $entidade, $id ?: null, $dados);

                    if ($novoAlunoAtendente) {
                        criar_matricula_inicial_aluno($pdo, $idSalvo, $dados, $usuario, $planoAluno);
                    }
                });
                $texto = $id
                    ? 'Registro atualizado com sucesso.'
                    : ($novoAlunoAtendente ? 'Aluno cadastrado e matricula criada com sucesso.' : 'Registro cadastrado com sucesso.');
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?mensagem=' . urlencode($texto) . '&tipo=success');
                exit;
            }
        }
    } catch (Throwable $erro) {
        $mensagem = $erro instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique duplicidade ou vínculos com outros registros.'
            : $erro->getMessage();
        $mensagem = mensagem_erro_operacao($erro);
        $tipoMensagem = 'error';
    }

    $idEdicao = trim((string) ($_GET['editar'] ?? ''));
    $postSalvar = (($_POST['acao'] ?? '') === 'salvar');
    $postId = (int) ($_POST['id'] ?? 0);
    $modoFormulario = !$somenteLeitura && (($cadastroManual && isset($_GET['novo'])) || $idEdicao !== '' || ($postSalvar && ($cadastroManual || $postId > 0)));
    $registroEdicao = null;

    if (!$somenteLeitura && $idEdicao !== '') {
        $registroEdicao = buscar_registro($pdo, $entidade, $idEdicao);

        if (!$registroEdicao) {
            $modoFormulario = false;
            $mensagem = 'Registro nao encontrado.';
            $tipoMensagem = 'error';
        } elseif (!usuario_pode_acessar_registro($entidade, $usuario, $registroEdicao)) {
            $registroEdicao = null;
            $modoFormulario = false;
            $mensagem = 'Voce nao tem permissao para editar este registro.';
            $tipoMensagem = 'error';
        }
    }

    $busca = trim((string) ($_GET['busca'] ?? ''));
    $registros = listar_registros($pdo, $entidade, $usuario);
    $todosRegistros = $registros;

    if ($busca !== '') {
        $buscaNormalizada = mb_strtolower($busca, 'UTF-8');
        $registros = array_filter($registros, fn ($registro) => str_contains(texto_busca_registro($pdo, $entidade, $registro), $buscaNormalizada));
    }

    cabecalho_pagina($entidade['titulo'], $entidade['secao'], $usuario);
    ?>
    <div class="section-kicker"><?= h($entidade['titulo']) ?></div>
    <?php if ($mensagem): ?>
      <div class="feedback is-visible <?= h($tipoMensagem) ?>" role="status" style="margin:0 0 18px"><?= h($mensagem) ?></div>
    <?php endif; ?>
    <?php if (!$modoFormulario): ?>
      <?php renderizar_graficos_financeiros_crud($pdo, $entidade, $usuario, $todosRegistros); ?>
    <?php endif; ?>

    <section class="crud-layout <?= $modoFormulario ? 'is-form-page' : 'is-list-page' ?>" aria-label="Cadastro e listagem">
      <?php if ($modoFormulario): ?>
      <div class="panel">
        <div class="panel-header">
          <h2><?= h($registroEdicao ? 'Editar ' . $entidade['singular'] : $configuracaoTela['formulario']) ?></h2>
        </div>

        <form class="crud-form" method="post" novalidate>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?= h($registroEdicao[$entidade['id']] ?? '') ?>">

          <div class="form-grid">
            <?php foreach ($entidade['campos'] as $campo): ?>
              <?php renderizar_campo($pdo, $campo, $registroEdicao, $usuario); ?>
            <?php endforeach; ?>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">
              <span class="material-symbols-outlined" aria-hidden="true"><?= $registroEdicao ? 'save' : 'add' ?></span>
              <?= $registroEdicao ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <a class="btn btn-ghost" href="<?= h(basename($_SERVER['PHP_SELF'])) ?>">
              <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
              Voltar para listagem
            </a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <?php if (!$modoFormulario): ?>
      <div class="panel">
        <div class="crud-toolbar">
          <div class="panel-header" style="margin-bottom:0">
            <h2><?= h($configuracaoTela['lista']) ?></h2>
          </div>
          <?php if (!$somenteLeitura && $cadastroManual): ?>
            <a class="btn btn-primary" href="<?= h(basename($_SERVER['PHP_SELF'])) ?>?novo=1">
              <span class="material-symbols-outlined" aria-hidden="true">add</span>
              Cadastrar <?= h($entidade['singular']) ?>
            </a>
          <?php endif; ?>
          <form class="field search-field" method="get">
            <label for="busca">Busca</label>
            <input id="busca" name="busca" type="search" value="<?= h($busca) ?>" placeholder="Buscar registros...">
          </form>
        </div>

        <?php if (!$registros): ?>
          <div class="empty-state">Nenhum registro encontrado.</div>
        <?php else: ?>
          <div class="table-wrap crud-table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <?php foreach ($entidade['campos'] as $campo): ?>
                    <?php if (($campo['tabela_listagem'] ?? true) !== false): ?>
                      <th><?= h($campo['rotulo']) ?></th>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  <?php if (!$somenteLeitura): ?>
                  <th>Ações</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($registros as $registro): ?>
                  <tr>
                    <td class="mono">#<?= h($registro[$entidade['id']]) ?></td>
                    <?php foreach ($entidade['campos'] as $campo): ?>
                      <?php if (($campo['tabela_listagem'] ?? true) !== false): ?>
                        <td><?= formatar_valor_listagem($pdo, $campo, $registro) ?></td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$somenteLeitura): ?>
                    <td>
                      <div class="actions">
                        <a class="btn btn-ghost" href="<?= h(basename($_SERVER['PHP_SELF'])) ?>?editar=<?= h($registro[$entidade['id']]) ?>">
                          <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                          Editar
                        </a>
                        <form method="post" data-confirmar-exclusao="Deseja excluir este registro?">
                          <input type="hidden" name="acao" value="excluir">
                          <input type="hidden" name="id" value="<?= h($registro[$entidade['id']]) ?>">
                          <button class="btn btn-danger" type="submit">
                            <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                            Excluir
                          </button>
                        </form>
                      </div>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php
    rodape_pagina();
}
