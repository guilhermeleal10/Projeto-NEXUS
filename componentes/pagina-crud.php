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

function coletar_dados_formulario(PDO $pdo, array $entidade, ?array $registroAtual, array $usuario): array
{
    $dados = [];

    foreach ($entidade['campos'] as $campo) {
        if (campo_oculto_para_usuario($campo, $usuario)) {
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

        if ($nome === 'senhaCriptografada') {
            if ($editandoSenha) {
                continue;
            }
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

function salvar_registro(PDO $pdo, array $entidade, ?int $id, array $dados): void
{
    if ($id) {
        if (!$dados) {
            return;
        }

        $atribuicoes = array_map(fn ($campo) => "{$campo} = :{$campo}", array_keys($dados));
        $dados['idRegistro'] = $id;
        $sql = "UPDATE {$entidade['tabela']} SET " . implode(', ', $atribuicoes) . " WHERE {$entidade['id']} = :idRegistro";
        $pdo->prepare($sql)->execute($dados);
        return;
    }

    $colunas = array_keys($dados);
    $parametros = array_map(fn ($campo) => ":{$campo}", $colunas);
    $sql = "INSERT INTO {$entidade['tabela']} (" . implode(', ', $colunas) . ') VALUES (' . implode(', ', $parametros) . ')';
    $pdo->prepare($sql)->execute($dados);
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

    if ($campo['nome'] === 'status' || $campo['nome'] === 'perfil') {
        return badge_status((string) $valor);
    }

    return h($valor ?: 'Não informado');
}

function valor_padrao_campo(array $campo): string
{
    $hoje = date('Y-m-d');

    return match ($campo['nome']) {
        'dataMatricula', 'dataReceita', 'dataDespesa', 'dataAbertura' => $hoje,
        'mesReferencia' => (string) date('n'),
        'anoReferencia' => (string) date('Y'),
        'totalReceitas', 'totalDespesas', 'saldoFinal' => '0',
        default => '',
    };
}

function renderizar_campo(PDO $pdo, array $campo, ?array $registro, array $usuario): void
{
    if (campo_oculto_para_usuario($campo, $usuario)) {
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
        <select id="<?= h($nome) ?>" name="<?= h($nome) ?>" <?= $obrigatorio ?>>
          <option value="">Selecione...</option>
          <?php foreach (opcoes_campo($pdo, $campo) as $opcao): ?>
            <option value="<?= h($opcao['valor']) ?>" <?= (string) $opcao['valor'] === $valor ? 'selected' : '' ?>>
              <?= h($opcao['rotulo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php elseif ($tipo === 'textarea'): ?>
        <textarea id="<?= h($nome) ?>" name="<?= h($nome) ?>" <?= $obrigatorio ?>><?= h($valor) ?></textarea>
      <?php else: ?>
        <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="<?= h($tipo) ?>" value="<?= h($valor) ?>" <?= $obrigatorio ?> <?= $somenteLeitura ?> <?= $tipo === 'number' ? 'step="0.01"' : '' ?>>
      <?php endif; ?>
      <?php if ($editandoSenha): ?>
        <span class="field-help">Deixe em branco para manter a senha atual.</span>
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

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                $dados = coletar_dados_formulario($pdo, $entidade, $registroAtual, $usuario);
                salvar_registro($pdo, $entidade, $id ?: null, $dados);
                $texto = $id ? 'Registro atualizado com sucesso.' : 'Registro cadastrado com sucesso.';
                header('Location: ' . basename($_SERVER['PHP_SELF']) . '?mensagem=' . urlencode($texto) . '&tipo=success');
                exit;
            }
        }
    } catch (Throwable $erro) {
        $mensagem = $erro instanceof PDOException
            ? 'Não foi possível concluir a operação. Verifique duplicidade ou vínculos com outros registros.'
            : $erro->getMessage();
        $tipoMensagem = 'error';
    }

    $registroEdicao = $somenteLeitura ? null : buscar_registro($pdo, $entidade, $_GET['editar'] ?? null);
    if ($registroEdicao && !usuario_pode_acessar_registro($entidade, $usuario, $registroEdicao)) {
        $registroEdicao = null;
        $mensagem = 'Voce nao tem permissao para editar este registro.';
        $tipoMensagem = 'error';
    }

    $busca = trim((string) ($_GET['busca'] ?? ''));
    $registros = listar_registros($pdo, $entidade, $usuario);

    if ($busca !== '') {
        $buscaNormalizada = mb_strtolower($busca, 'UTF-8');
        $registros = array_filter($registros, fn ($registro) => str_contains(texto_busca_registro($pdo, $entidade, $registro), $buscaNormalizada));
    }

    cabecalho_pagina($entidade['titulo'], $entidade['secao'], $usuario);
    ?>
    <div class="section-kicker"><?= h($entidade['titulo']) ?></div>

    <section class="crud-layout" aria-label="Cadastro e listagem">
      <?php if (!$somenteLeitura): ?>
      <div class="panel">
        <div class="panel-header">
          <h2><?= $registroEdicao ? 'Editar' : 'Cadastrar' ?> <?= h($entidade['singular']) ?></h2>
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
            <?php if ($registroEdicao): ?>
              <a class="btn btn-ghost" href="<?= h(basename($_SERVER['PHP_SELF'])) ?>">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
                Cancelar
              </a>
            <?php endif; ?>
          </div>
        </form>

        <?php if ($mensagem): ?>
          <div class="feedback is-visible <?= h($tipoMensagem) ?>" role="status"><?= h($mensagem) ?></div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="panel">
        <div class="panel-header"><h2>Consulta</h2></div>
        <p class="text-muted">Seu perfil permite consultar estes registros. Cadastros e alteracoes ficam restritos ao perfil responsavel.</p>
        <?php if ($mensagem): ?>
          <div class="feedback is-visible <?= h($tipoMensagem) ?>" role="status"><?= h($mensagem) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="panel">
        <div class="crud-toolbar">
          <div class="panel-header" style="margin-bottom:0">
            <h2>Listagem</h2>
          </div>
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
    </section>
    <?php
    rodape_pagina();
}
