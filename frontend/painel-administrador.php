<?php

require_once __DIR__ . '/componentes/layout.php';

$usuario = exigir_login('ADMIN');
$pdo = obter_conexao();

$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM Usuario')->fetchColumn();
$suporteAberto = (int) $pdo->query("SELECT COUNT(*) FROM SolicitacaoSuporte WHERE status = 'Aberta'")->fetchColumn();
$totalAcademias = (int) $pdo->query('SELECT COUNT(*) FROM Academia')->fetchColumn();
$solicitacoes = $pdo->query(
    "SELECT s.*, COALESCE(CONCAT(u.nome, ' ', u.sobrenome, ' (', u.perfil, ')'), 'Usuario removido') AS usuarioSolicitante
     FROM SolicitacaoSuporte s
     LEFT JOIN Usuario u ON u.idUsuario = s.idUsuarioSolicitante
     ORDER BY s.idSolicitacao DESC
     LIMIT 5"
)->fetchAll();
$usuariosRecentes = $pdo->query('SELECT * FROM Usuario ORDER BY idUsuario DESC LIMIT 4')->fetchAll();
$usuariosPorPerfil = $pdo->query(
    'SELECT perfil, COUNT(*) AS total
     FROM Usuario
     GROUP BY perfil
     ORDER BY FIELD(perfil, "ADMIN", "GERENTE", "ATENDENTE", "CLIENTE")'
)->fetchAll();
$suportePorStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM SolicitacaoSuporte
     GROUP BY status
     ORDER BY total DESC, status'
)->fetchAll();

cabecalho_pagina('Dashboard Administrador', 'dashboard', $usuario);
?>
<section aria-labelledby="admin-titulo">
  <div class="section-kicker" id="admin-titulo">Painel Administrativo</div>
  <div class="stats-grid">
    <article class="stat-card">
      <div><p class="label">Total de usuários</p><p class="value"><?= h($totalUsuarios) ?></p><span class="detail">Perfis ativos no sistema</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">group</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Suporte aberto</p><p class="value"><?= h($suporteAberto) ?></p><span class="detail">Solicitações aguardando ação</span></div>
      <div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">support_agent</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Academias</p><p class="value"><?= h($totalAcademias) ?></p><span class="detail">Unidades cadastradas</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">business</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Status do sistema</p><p class="value">Online</p><span class="detail">Operação PHP/MySQL</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">check_circle</span></div>
    </article>
  </div>
</section>

<section class="finance-grid" style="margin-top:20px" aria-label="Graficos administrativos">
  <article class="panel">
    <div class="panel-header"><h2>Usuários por Perfil</h2></div>
    <?php $totalPerfisGrafico = total_grafico($usuariosPorPerfil); ?>
    <div class="pie-layout">
      <div class="pie-chart" style="<?= h(estilo_grafico_pizza($usuariosPorPerfil)) ?>" aria-label="Usuários por perfil">
        <span class="pie-total"><?= h((int) $totalPerfisGrafico) ?></span>
      </div>
      <div class="pie-legend" aria-label="Legenda de usuários por perfil">
      <?php foreach ($usuariosPorPerfil as $indice => $linha): ?>
        <div class="pie-legend-item">
          <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
          <span><?= h($linha['perfil']) ?></span>
          <strong><?= h($linha['total']) ?> / <?= h(percentual_grafico($linha['total'], $totalPerfisGrafico)) ?></strong>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header"><h2>Suporte por Status</h2></div>
    <?php $totalSuporteGrafico = total_grafico($suportePorStatus); ?>
    <div class="pie-layout">
      <div class="pie-chart" style="<?= h(estilo_grafico_pizza($suportePorStatus)) ?>" aria-label="Suporte por status">
        <span class="pie-total"><?= h((int) $totalSuporteGrafico) ?></span>
      </div>
      <div class="pie-legend" aria-label="Legenda de suporte por status">
      <?php foreach ($suportePorStatus as $indice => $linha): ?>
        <div class="pie-legend-item">
          <span class="pie-swatch" style="background: <?= h(cor_grafico($indice)) ?>" aria-hidden="true"></span>
          <span><?= h($linha['status']) ?></span>
          <strong><?= h($linha['total']) ?> / <?= h(percentual_grafico($linha['total'], $totalSuporteGrafico)) ?></strong>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </article>
</section>

<section class="two-column" aria-label="Operação administrativa" style="margin-top:20px">
  <article class="panel">
    <div class="panel-header">
      <h2>Solicitações de Suporte</h2>
      <a class="btn btn-ghost" href="paginas/solicitacoes-suporte.php">Ver tudo</a>
    </div>
    <div class="table-wrap compact-table">
      <table>
        <thead><tr><th>ID</th><th>Descrição</th><th>Usuário</th><th>Data</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($solicitacoes as $solicitacao): ?>
            <tr>
              <td class="mono">#<?= h($solicitacao['idSolicitacao']) ?></td>
              <td><?= h($solicitacao['descricao']) ?></td>
              <td><?= h($solicitacao['usuarioSolicitante']) ?></td>
              <td><?= h(data_br($solicitacao['dataAbertura'])) ?></td>
              <td><?= badge_status($solicitacao['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header">
      <h2>Novos Usuários</h2>
      <a class="btn btn-ghost" href="paginas/usuarios.php">Gerenciar</a>
    </div>
    <div class="list-stack">
      <?php foreach ($usuariosRecentes as $item): ?>
        <div class="list-item">
          <strong><?= h($item['nome'] . ' ' . $item['sobrenome']) ?></strong>
          <span><?= h($item['perfil'] . ' - ' . $item['email']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<?php rodape_pagina(); ?>
