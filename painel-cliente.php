<?php

require_once __DIR__ . '/componentes/layout.php';

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
$idAluno = (int) ($aluno['idAluno'] ?? 0);

$matriculas = [];
$mensalidades = [];
$suportes = [];
$mensalidadesAbertas = 0;
$proximoVencimento = null;
$statusMatricula = 'Sem matricula';

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
    $statusMatricula = (string) ($matriculas[0]['status'] ?? 'Sem matricula');

    $consultaMensalidades = $pdo->prepare(
        'SELECT me.*
         FROM Mensalidade me
         JOIN Matricula ma ON ma.idMatricula = me.idMatricula
         WHERE ma.idAluno = :idAluno
         ORDER BY me.dataVencimento DESC'
    );
    $consultaMensalidades->execute(['idAluno' => $idAluno]);
    $mensalidades = $consultaMensalidades->fetchAll();

    $mensalidadesAbertas = count(array_filter(
        $mensalidades,
        fn ($mensalidade) => in_array($mensalidade['status'], ['Pendente', 'Atrasado'], true)
    ));

    foreach (array_reverse($mensalidades) as $mensalidade) {
        if (in_array($mensalidade['status'], ['Pendente', 'Atrasado'], true)) {
            $proximoVencimento = $mensalidade['dataVencimento'];
            break;
        }
    }
}

$consultaSuporte = $pdo->prepare(
    'SELECT *
     FROM SolicitacaoSuporte
     WHERE idUsuarioSolicitante = :idUsuario
     ORDER BY idSolicitacao DESC
     LIMIT 5'
);
$consultaSuporte->execute(['idUsuario' => $usuario['idUsuario']]);
$suportes = $consultaSuporte->fetchAll();
$mensalidadesPagas = count(array_filter($mensalidades, fn ($mensalidade) => $mensalidade['status'] === 'Pago'));

cabecalho_pagina('Dashboard do Aluno', 'dashboard', $usuario);
?>
<section>
  <div class="section-kicker">Area do Aluno</div>
  <div class="stats-grid">
    <article class="stat-card">
      <div>
        <p class="label">Aluno</p>
        <p class="value"><?= h($aluno['nome'] ?? ($usuario['nome'] . ' ' . $usuario['sobrenome'])) ?></p>
        <span class="detail"><?= h($aluno['email'] ?? $usuario['email']) ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">person</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Academia</p>
        <p class="value"><?= h($aluno['academiaNome'] ?? 'Sem academia') ?></p>
        <span class="detail"><?= h($aluno['academiaEndereco'] ?? 'Procure o atendimento') ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">business</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Matricula</p>
        <p class="value"><?= h($statusMatricula) ?></p>
        <span class="detail"><?= h(count($matriculas)) ?> registro(s)</span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">badge</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Mensalidades abertas</p>
        <p class="value"><?= h($mensalidadesAbertas) ?></p>
        <span class="detail"><?= h($proximoVencimento ? 'Vence em ' . data_br($proximoVencimento) : 'Nenhuma pendencia') ?></span>
      </div>
      <div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">payments</span></div>
    </article>
  </div>
</section>

<?php if (!$aluno): ?>
  <section class="panel" style="margin-top:20px" role="status">
    <div class="panel-header"><h2>Cadastro de aluno nao vinculado</h2></div>
    <p class="text-muted">Seu usuario CLIENTE ainda nao esta ligado a um registro em Aluno. Peca ao atendimento para vincular seu usuario ao cadastro do aluno.</p>
  </section>
<?php endif; ?>

<?php if ($aluno): ?>
  <section id="dados-aluno" class="panel" style="margin-top:20px">
    <div class="panel-header"><h2>Dados do aluno</h2></div>
    <div class="list-stack">
      <div class="list-item"><strong>Nome</strong><span><?= h($aluno['nome']) ?></span></div>
      <div class="list-item"><strong>CPF</strong><span><?= h($aluno['cpf']) ?></span></div>
      <div class="list-item"><strong>Telefone</strong><span><?= h($aluno['telefone']) ?></span></div>
      <div class="list-item"><strong>E-mail</strong><span><?= h($aluno['email']) ?></span></div>
      <div class="list-item"><strong>Nascimento</strong><span><?= h(data_br($aluno['dataNascimento'])) ?></span></div>
    </div>
  </section>
<?php endif; ?>

<section id="matricula" class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Minha matricula</h2></div>
  <?php if (!$matriculas): ?>
    <div class="empty-state">Nenhuma matricula encontrada.</div>
  <?php else: ?>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>ID</th><th>Academia</th><th>Data</th><th>Atendente</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($matriculas as $matricula): ?>
            <tr>
              <td class="mono">#<?= h($matricula['idMatricula']) ?></td>
              <td><?= h($matricula['academiaNome']) ?></td>
              <td><?= h(data_br($matricula['dataMatricula'])) ?></td>
              <td><?= h($matricula['atendenteNome'] ?? 'Atendente removido') ?></td>
              <td><?= badge_status($matricula['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section id="mensalidades" class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Minhas mensalidades</h2></div>
  <?php if (!$mensalidades): ?>
    <div class="empty-state">Nenhuma mensalidade encontrada.</div>
  <?php else: ?>
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
  <?php endif; ?>
</section>

<section id="historico" class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Historico basico</h2></div>
  <div class="list-stack">
    <div class="list-item"><strong>Matriculas registradas</strong><span><?= h(count($matriculas)) ?></span></div>
    <div class="list-item"><strong>Mensalidades pagas</strong><span><?= h($mensalidadesPagas) ?></span></div>
    <div class="list-item"><strong>Mensalidades pendentes ou atrasadas</strong><span><?= h($mensalidadesAbertas) ?></span></div>
    <div class="list-item"><strong>Solicitacoes de suporte</strong><span><?= h(count($suportes)) ?></span></div>
  </div>
</section>

<section id="suporte" class="panel" style="margin-top:20px">
  <div class="panel-header">
    <h2>Minhas solicitacoes</h2>
    <a class="btn btn-ghost" href="paginas/solicitacoes-suporte.php">Abrir suporte</a>
  </div>
  <?php if (!$suportes): ?>
    <div class="empty-state">Nenhuma solicitacao encontrada.</div>
  <?php else: ?>
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
  <?php endif; ?>
</section>
<?php rodape_pagina(); ?>
