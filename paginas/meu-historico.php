<?php

require_once __DIR__ . '/../componentes/area-cliente.php';

$contexto = carregar_area_cliente();
$usuario = $contexto['usuario'];
$aluno = $contexto['aluno'];
$matriculas = $contexto['matriculas'];
$mensalidades = $contexto['mensalidades'];
$suportes = $contexto['suportes'];

cabecalho_pagina('Meu Historico', 'historico', $usuario);
?>
<section>
  <div class="section-kicker">Historico do aluno</div>
  <div class="quick-grid">
    <article class="quick-card">
      <span class="material-symbols-outlined" aria-hidden="true">badge</span>
      <strong><?= h(count($matriculas)) ?> matricula(s)</strong>
      <small>Registros vinculados ao aluno.</small>
    </article>
    <article class="quick-card">
      <span class="material-symbols-outlined" aria-hidden="true">payments</span>
      <strong><?= h($contexto['mensalidadesPagas']) ?> mensalidade(s) pagas</strong>
      <small><?= h($contexto['mensalidadesAbertas']) ?> em aberto.</small>
    </article>
    <article class="quick-card">
      <span class="material-symbols-outlined" aria-hidden="true">support_agent</span>
      <strong><?= h(count($suportes)) ?> solicitacao(oes)</strong>
      <small>Chamados abertos no suporte.</small>
    </article>
  </div>
</section>

<?php aviso_aluno_nao_vinculado($aluno); ?>

<section class="two-column" style="margin-top:20px">
  <article class="panel">
    <div class="panel-header"><h2>Matriculas</h2></div>
    <?php tabela_cliente_matriculas($matriculas); ?>
  </article>
  <article class="panel">
    <div class="panel-header">
      <h2>Suporte</h2>
      <a class="btn btn-ghost" href="solicitacoes-suporte.php">Abrir suporte</a>
    </div>
    <?php tabela_cliente_suportes($suportes); ?>
  </article>
</section>

<section class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Mensalidades</h2></div>
  <?php tabela_cliente_mensalidades($mensalidades); ?>
</section>
<?php rodape_pagina(); ?>
