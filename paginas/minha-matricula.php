<?php

require_once __DIR__ . '/../componentes/area-cliente.php';

$contexto = carregar_area_cliente();
$usuario = $contexto['usuario'];
$aluno = $contexto['aluno'];
$matriculas = $contexto['matriculas'];
$matriculaAtual = $contexto['matriculaAtual'];

cabecalho_pagina('Minha Matricula', 'matricula', $usuario);
?>
<section>
  <div class="section-kicker">Matricula do aluno</div>
  <div class="stats-grid">
    <article class="stat-card">
      <div><p class="label">Status atual</p><p class="value"><?= h($contexto['statusMatricula']) ?></p><span class="detail">Ultimo registro encontrado</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">badge</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Codigo</p><p class="value"><?= h($matriculaAtual['codigoMatricula'] ?? '-') ?></p><span class="detail">Identificador unico da matricula</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">tag</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Plano</p><p class="value"><?= h($matriculaAtual['plano'] ?? 'Sem plano') ?></p><span class="detail"><?= h($matriculaAtual ? dinheiro($matriculaAtual['valorPlano']) : 'Sem valor') ?></span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">workspace_premium</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Registros</p><p class="value"><?= h(count($matriculas)) ?></p><span class="detail">Historico de matriculas</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">history</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Aluno</p><p class="value"><?= h($aluno['nome'] ?? $usuario['nome']) ?></p><span class="detail"><?= h($aluno['academiaNome'] ?? 'Sem academia') ?></span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">person</span></div>
    </article>
  </div>
</section>

<?php aviso_aluno_nao_vinculado($aluno); ?>

<section class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Historico de matriculas</h2></div>
  <?php tabela_cliente_matriculas($matriculas); ?>
</section>
<?php rodape_pagina(); ?>
