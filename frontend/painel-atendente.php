<?php

require_once __DIR__ . '/componentes/layout.php';

$usuario = exigir_login('ATENDENTE');
$pdo = obter_conexao();

$totalAlunos = (int) $pdo->query('SELECT COUNT(*) FROM Aluno')->fetchColumn();
$matriculasAtivas = (int) $pdo->query("SELECT COUNT(*) FROM Matricula WHERE status = 'Ativa'")->fetchColumn();
$mensalidadesPendentes = (int) $pdo->query("SELECT COUNT(*) FROM Mensalidade WHERE status IN ('Pendente', 'Atrasado')")->fetchColumn();
$solicitacoesAbertas = (int) $pdo->query("SELECT COUNT(*) FROM SolicitacaoSuporte WHERE status IN ('Aberta', 'Em andamento')")->fetchColumn();
$pendencias = $pdo->query(
    "SELECT m.*, a.nome AS alunoNome
     FROM Mensalidade m
     JOIN Matricula mt ON mt.idMatricula = m.idMatricula
     JOIN Aluno a ON a.idAluno = mt.idAluno
     WHERE m.status IN ('Pendente', 'Atrasado')
     ORDER BY m.dataVencimento ASC"
)->fetchAll();
$alunosRecentes = $pdo->query(
    "SELECT a.*, ac.nome AS academiaNome
     FROM Aluno a
     JOIN Academia ac ON ac.idAcademia = a.idAcademia
     ORDER BY a.idAluno DESC
     LIMIT 4"
)->fetchAll();
$alunosPorAcademia = $pdo->query(
    'SELECT ac.nome, COUNT(a.idAluno) AS total
     FROM Academia ac
     LEFT JOIN Aluno a ON a.idAcademia = ac.idAcademia
     GROUP BY ac.idAcademia, ac.nome
     ORDER BY total DESC, ac.nome'
)->fetchAll();
$matriculasPorStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM Matricula
     GROUP BY status
     ORDER BY total DESC, status'
)->fetchAll();
$mensalidadesPorStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM Mensalidade
     GROUP BY status
     ORDER BY total DESC, status'
)->fetchAll();

cabecalho_pagina('Dashboard de Atendimento', 'dashboard', $usuario);
?>
<section>
  <div class="section-kicker">Operação do Balcão</div>
  <div class="stats-grid">
    <article class="stat-card"><div><p class="label">Alunos cadastrados</p><p class="value"><?= h($totalAlunos) ?></p><span class="detail">Base ativa</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">fitness_center</span></div></article>
    <article class="stat-card"><div><p class="label">Matrículas ativas</p><p class="value"><?= h($matriculasAtivas) ?></p><span class="detail">Contratos vigentes</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">badge</span></div></article>
    <article class="stat-card"><div><p class="label">Mensalidades pendentes</p><p class="value"><?= h($mensalidadesPendentes) ?></p><span class="detail">Pendentes ou atrasadas</span></div><div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">payments</span></div></article>
    <article class="stat-card"><div><p class="label">Solicitacoes abertas</p><p class="value"><?= h($solicitacoesAbertas) ?></p><span class="detail">Demandas de suporte</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">support_agent</span></div></article>
  </div>
</section>

<section class="finance-grid" style="margin-top:20px" aria-label="Graficos operacionais">
  <article class="panel">
    <div class="panel-header"><h2>Alunos por Academia</h2></div>
    <?php $maximoAlunos = max(array_column($alunosPorAcademia ?: [['total' => 1]], 'total')); ?>
    <div class="chart-bars" style="grid-template-columns: repeat(<?= h(max(count($alunosPorAcademia), 1)) ?>, minmax(82px, 1fr));">
      <?php foreach ($alunosPorAcademia as $linha): ?>
        <div class="chart-group">
          <div class="chart-bar" style="height:<?= h(altura_grafico($linha['total'], $maximoAlunos)) ?>px" title="<?= h($linha['total'] . ' aluno(s)') ?>"><?= h($linha['total']) ?></div>
          <span class="chart-label"><?= h($linha['nome']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header"><h2>Matrículas por Status</h2></div>
    <?php $totalMatriculasGrafico = total_grafico($matriculasPorStatus); ?>
    <div class="pie-layout">
      <div class="pie-chart" style="<?= h(estilo_grafico_pizza($matriculasPorStatus)) ?>" aria-label="Matrículas por status">
        <span class="pie-total"><?= h((int) $totalMatriculasGrafico) ?></span>
      </div>
      <div class="pie-legend" aria-label="Legenda de matrículas por status">
      <?php foreach ($matriculasPorStatus as $indice => $linha): ?>
        <div class="pie-legend-item">
          <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
          <span><?= h($linha['status']) ?></span>
          <strong><?= h($linha['total']) ?> / <?= h(percentual_grafico($linha['total'], $totalMatriculasGrafico)) ?></strong>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header"><h2>Mensalidades por Status</h2></div>
    <?php $totalMensalidadesGrafico = total_grafico($mensalidadesPorStatus); ?>
    <div class="pie-layout">
      <div class="pie-chart" style="<?= h(estilo_grafico_pizza($mensalidadesPorStatus)) ?>" aria-label="Mensalidades por status">
        <span class="pie-total"><?= h((int) $totalMensalidadesGrafico) ?></span>
      </div>
      <div class="pie-legend" aria-label="Legenda de mensalidades por status">
      <?php foreach ($mensalidadesPorStatus as $indice => $linha): ?>
        <div class="pie-legend-item">
          <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
          <span><?= h($linha['status']) ?></span>
          <strong><?= h($linha['total']) ?> / <?= h(percentual_grafico($linha['total'], $totalMensalidadesGrafico)) ?></strong>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </article>
</section>

<section class="two-column" style="margin-top:20px">
  <article class="panel">
    <div class="panel-header"><h2>Mensalidades Pendentes</h2><a class="btn btn-ghost" href="paginas/mensalidades.php">Gerenciar</a></div>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>ID</th><th>Aluno</th><th>Vencimento</th><th>Valor</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($pendencias as $item): ?>
            <tr><td class="mono">#<?= h($item['idMensalidade']) ?></td><td><?= h($item['alunoNome']) ?></td><td><?= h(data_br($item['dataVencimento'])) ?></td><td><?= h(dinheiro($item['valor'])) ?></td><td><?= badge_status($item['status']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>
  <article class="panel">
    <div class="panel-header"><h2>Alunos Recentes</h2><a class="btn btn-ghost" href="paginas/alunos.php">Cadastrar</a></div>
    <div class="list-stack">
      <?php foreach ($alunosRecentes as $aluno): ?>
        <div class="list-item"><strong><?= h($aluno['nome']) ?></strong><span><?= h($aluno['academiaNome'] . ' - ' . $aluno['telefone']) ?></span></div>
      <?php endforeach; ?>
    </div>
  </article>
</section>
<?php rodape_pagina(); ?>
