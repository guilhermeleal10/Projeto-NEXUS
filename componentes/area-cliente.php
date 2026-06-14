<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';

function carregar_area_cliente(): array
{
    $usuario = exigir_login('CLIENTE');
    $pdo = obter_conexao();

    $consultaAluno = $pdo->prepare(
        'SELECT a.*, ac.nome AS academiaNome, ac.endereco AS academiaEndereco
         FROM Aluno a
         JOIN Academia ac ON ac.idAcademia = a.idAcademia
         WHERE a.idUsuario = :idUsuario
         LIMIT 1'
    );
    $consultaAluno->execute(['idUsuario' => $usuario['idUsuario']]);
    $aluno = $consultaAluno->fetch() ?: null;

    if (!$aluno) {
        $vincularAluno = $pdo->prepare(
            'UPDATE Aluno
             SET idUsuario = :idUsuario
             WHERE idUsuario IS NULL AND (email = :email OR cpf = :cpf)
             LIMIT 1'
        );
        $vincularAluno->execute([
            'idUsuario' => $usuario['idUsuario'],
            'email' => $usuario['email'],
            'cpf' => $usuario['cpf'],
        ]);

        $consultaAluno->execute(['idUsuario' => $usuario['idUsuario']]);
        $aluno = $consultaAluno->fetch() ?: null;
    }

    $idAluno = (int) ($aluno['idAluno'] ?? 0);

    $matriculas = [];
    $mensalidades = [];

    if ($idAluno > 0) {
        $consultaMatriculas = $pdo->prepare(
            "SELECT m.*, ac.nome AS academiaNome, CONCAT(u.nome, ' ', u.sobrenome) AS atendenteNome
             FROM Matricula m
             JOIN Academia ac ON ac.idAcademia = m.idAcademia
             LEFT JOIN Usuario u ON u.idUsuario = m.idAtendente
             WHERE m.idAluno = :idAluno
             ORDER BY m.idMatricula DESC"
        );
        $consultaMatriculas->execute(['idAluno' => $idAluno]);
        $matriculas = $consultaMatriculas->fetchAll();

        $consultaMensalidades = $pdo->prepare(
            'SELECT me.*
             FROM Mensalidade me
             JOIN Matricula ma ON ma.idMatricula = me.idMatricula
             WHERE ma.idAluno = :idAluno
             ORDER BY me.dataVencimento DESC'
        );
        $consultaMensalidades->execute(['idAluno' => $idAluno]);
        $mensalidades = $consultaMensalidades->fetchAll();
    }

    $consultaSuporte = $pdo->prepare(
        'SELECT *
         FROM SolicitacaoSuporte
         WHERE idUsuarioSolicitante = :idUsuario
         ORDER BY idSolicitacao DESC'
    );
    $consultaSuporte->execute(['idUsuario' => $usuario['idUsuario']]);
    $suportes = $consultaSuporte->fetchAll();

    $mensalidadesAbertas = count(array_filter(
        $mensalidades,
        fn ($mensalidade) => in_array($mensalidade['status'], ['Pendente', 'Atrasado'], true)
    ));
    $mensalidadesPagas = count(array_filter($mensalidades, fn ($mensalidade) => $mensalidade['status'] === 'Pago'));
    $valorEmAberto = array_sum(array_map(
        fn ($mensalidade) => in_array($mensalidade['status'], ['Pendente', 'Atrasado'], true) ? (float) $mensalidade['valor'] : 0,
        $mensalidades
    ));
    $proximoVencimento = null;

    foreach (array_reverse($mensalidades) as $mensalidade) {
        if (in_array($mensalidade['status'], ['Pendente', 'Atrasado'], true)) {
            $proximoVencimento = $mensalidade['dataVencimento'];
            break;
        }
    }

    return [
        'usuario' => $usuario,
        'aluno' => $aluno,
        'matriculas' => $matriculas,
        'mensalidades' => $mensalidades,
        'suportes' => $suportes,
        'statusMatricula' => (string) ($matriculas[0]['status'] ?? 'Sem matricula'),
        'mensalidadesAbertas' => $mensalidadesAbertas,
        'mensalidadesPagas' => $mensalidadesPagas,
        'valorEmAberto' => $valorEmAberto,
        'proximoVencimento' => $proximoVencimento,
        'matriculaAtual' => $matriculas[0] ?? null,
    ];
}

function aviso_aluno_nao_vinculado(?array $aluno): void
{
    if ($aluno) {
        return;
    }
    ?>
    <section class="panel" style="margin-top:20px" role="status">
      <div class="panel-header"><h2>Cadastro de aluno nao vinculado</h2></div>
      <p class="text-muted">Seu usuario CLIENTE ainda nao esta ligado a um registro em Aluno. Peca ao atendimento para vincular seu usuario ao cadastro do aluno.</p>
    </section>
    <?php
}

function tabela_cliente_matriculas(array $matriculas): void
{
    if (!$matriculas) {
        echo '<div class="empty-state">Nenhuma matricula encontrada.</div>';
        return;
    }
    ?>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>Codigo</th><th>Academia</th><th>Plano</th><th>Valor</th><th>Data</th><th>Atendente</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($matriculas as $matricula): ?>
            <tr>
              <td class="mono"><?= h($matricula['codigoMatricula'] ?? '#' . $matricula['idMatricula']) ?></td>
              <td><?= h($matricula['academiaNome']) ?></td>
              <td><?= h(rotulo_plano($matricula['plano'] ?? 'Basico')) ?></td>
              <td><?= h(dinheiro($matricula['valorPlano'] ?? 0)) ?></td>
              <td><?= h(data_br($matricula['dataMatricula'])) ?></td>
              <td><?= h($matricula['atendenteNome'] ?? 'Atendente removido') ?></td>
              <td><?= badge_status($matricula['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

function tabela_cliente_mensalidades(array $mensalidades): void
{
    if (!$mensalidades) {
        echo '<div class="empty-state">Nenhuma mensalidade encontrada.</div>';
        return;
    }
    ?>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>ID</th><th>Vencimento</th><th>Pagamento</th><th>Valor</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($mensalidades as $mensalidade): ?>
            <tr>
              <td class="mono">#<?= h($mensalidade['idMensalidade']) ?></td>
              <td><?= h(data_br($mensalidade['dataVencimento'])) ?></td>
              <td><?= h(data_br($mensalidade['dataPagamento'])) ?></td>
              <td><?= h(dinheiro($mensalidade['valor'])) ?></td>
              <td><?= badge_status($mensalidade['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

function tabela_cliente_suportes(array $suportes): void
{
    if (!$suportes) {
        echo '<div class="empty-state">Nenhuma solicitacao encontrada.</div>';
        return;
    }
    ?>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>ID</th><th>Descricao</th><th>Abertura</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($suportes as $suporte): ?>
            <tr>
              <td class="mono">#<?= h($suporte['idSolicitacao']) ?></td>
              <td><?= h($suporte['descricao']) ?></td>
              <td><?= h(data_br($suporte['dataAbertura'])) ?></td>
              <td><?= badge_status($suporte['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}
