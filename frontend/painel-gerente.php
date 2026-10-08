<?php

require_once __DIR__ . '/componentes/layout.php';

$usuario = exigir_login('GERENTE');
$pdo = obter_conexao();

$receitaTotal = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM Receita')->fetchColumn();
$despesaTotal = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM Despesa')->fetchColumn();
$totalRelatorios = (int) $pdo->query('SELECT COUNT(*) FROM RelatorioFinanceiro')->fetchColumn();
$movimentos = $pdo->query(
    "(SELECT 'Receita' AS tipo, descricao, valor, dataReceita AS dataMovimento FROM Receita)
     UNION ALL
     (SELECT 'Despesa' AS tipo, descricao, valor, dataDespesa AS dataMovimento FROM Despesa)
     ORDER BY dataMovimento DESC
     LIMIT 6"
)->fetchAll();
$movimentoMensal = $pdo->query(
    "SELECT YEAR(periodo) AS ano, MONTH(periodo) AS mes, SUM(receita) AS receita, SUM(despesa) AS despesa
     FROM (
       SELECT dataReceita AS periodo, valor AS receita, 0 AS despesa
       FROM Receita
       WHERE dataReceita >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
       UNION ALL
       SELECT dataDespesa AS periodo, 0 AS receita, valor AS despesa
       FROM Despesa
       WHERE dataDespesa >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     ) movimentos
     GROUP BY YEAR(periodo), MONTH(periodo)
     ORDER BY MIN(periodo)"
)->fetchAll();
$receitasPorAcademia = $pdo->query(
    'SELECT ac.nome, COALESCE(SUM(r.valor), 0) AS total
     FROM Academia ac
     LEFT JOIN Receita r ON r.idAcademia = ac.idAcademia
     GROUP BY ac.idAcademia, ac.nome
     ORDER BY total DESC, ac.nome'
)->fetchAll();

cabecalho_pagina('Dashboard Financeiro', 'dashboard', $usuario);
?>
<section>
  <div class="section-kicker">Centro Financeiro</div>
  <div class="stats-grid">
    <article class="stat-card"><div><p class="label">Receita total</p><p class="value"><?= h(dinheiro($receitaTotal)) ?></p><span class="detail">Entradas registradas</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">trending_up</span></div></article>
    <article class="stat-card"><div><p class="label">Despesa total</p><p class="value"><?= h(dinheiro($despesaTotal)) ?></p><span class="detail">Custos operacionais</span></div><div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">trending_down</span></div></article>
    <article class="stat-card"><div><p class="label">Saldo final</p><p class="value"><?= h(dinheiro($receitaTotal - $despesaTotal)) ?></p><span class="detail">Receitas menos despesas</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">account_balance_wallet</span></div></article>
    <article class="stat-card"><div><p class="label">Relatórios</p><p class="value"><?= h($totalRelatorios) ?></p><span class="detail">Resumos gerados</span></div><div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">monitoring</span></div></article>
  </div>
</section>

<section class="finance-grid" style="margin-top:20px">
  <article class="panel">
    <div class="panel-header"><h2>Receitas x Despesas</h2></div>
    <?php if (!$movimentoMensal): ?>
      <div class="empty-state">Nenhum movimento financeiro encontrado.</div>
    <?php else: ?>
      <?php $maximoFinanceiro = max(array_map(fn ($linha) => max((float) $linha['receita'], (float) $linha['despesa']), $movimentoMensal)); ?>
      <div class="chart-bars" style="grid-template-columns: repeat(<?= h(count($movimentoMensal)) ?>, minmax(76px, 1fr));">
        <?php foreach ($movimentoMensal as $linha): ?>
          <div class="chart-group">
            <div class="chart-pair">
              <div class="chart-bar" style="height:<?= h(altura_grafico($linha['receita'], $maximoFinanceiro)) ?>px" title="<?= h('Receitas: ' . dinheiro($linha['receita'])) ?>">R</div>
              <div class="chart-bar expense" style="height:<?= h(altura_grafico($linha['despesa'], $maximoFinanceiro)) ?>px" title="<?= h('Despesas: ' . dinheiro($linha['despesa'])) ?>">D</div>
            </div>
            <span class="chart-label"><?= h(mes_ano_curto($linha['mes'], $linha['ano'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-legend"><span>Receitas</span><span class="expense">Despesas</span></div>
    <?php endif; ?>
  </article>
  <article class="panel">
    <div class="panel-header"><h2>Receitas por Academia</h2></div>
    <?php $totalReceitasAcademiaGrafico = total_grafico($receitasPorAcademia); ?>
    <div class="pie-layout">
      <div class="pie-chart" style="<?= h(estilo_grafico_pizza($receitasPorAcademia)) ?>" aria-label="Receitas por academia">
        <span class="pie-total money"><?= h(dinheiro($totalReceitasAcademiaGrafico)) ?></span>
      </div>
      <div class="pie-legend" aria-label="Legenda de receitas por academia">
      <?php foreach ($receitasPorAcademia as $indice => $linha): ?>
        <div class="pie-legend-item">
          <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
          <span><?= h($linha['nome']) ?></span>
          <strong><?= h(dinheiro($linha['total'])) ?> / <?= h(percentual_grafico($linha['total'], $totalReceitasAcademiaGrafico)) ?></strong>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </article>
</section>

<section class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Movimentos Recentes</h2></div>
  <div class="table-wrap compact-table">
    <table>
      <thead><tr><th>Tipo</th><th>Descrição</th><th>Data</th><th>Valor</th></tr></thead>
      <tbody>
        <?php foreach ($movimentos as $movimento): ?>
          <tr><td><?= badge_status($movimento['tipo'] === 'Receita' ? 'Pago' : 'Pendente') ?></td><td><?= h($movimento['descricao']) ?></td><td><?= h(data_br($movimento['dataMovimento'])) ?></td><td><?= h(dinheiro($movimento['valor'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php rodape_pagina(); ?>
