<?php

require_once __DIR__ . '/../componentes/area-cliente.php';

$contexto = carregar_area_cliente();
$usuario = $contexto['usuario'];
$aluno = $contexto['aluno'];
$mensalidades = $contexto['mensalidades'];

cabecalho_pagina('Minhas Mensalidades', 'mensalidades', $usuario);
?>
<section>
  <div class="section-kicker">Mensalidades do aluno</div>
  <div class="stats-grid">
    <article class="stat-card">
      <div><p class="label">Em aberto</p><p class="value"><?= h($contexto['mensalidadesAbertas']) ?></p><span class="detail"><?= h(dinheiro($contexto['valorEmAberto'])) ?> pendente(s)</span></div>
      <div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">payments</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Pagas</p><p class="value"><?= h($contexto['mensalidadesPagas']) ?></p><span class="detail">Baixas registradas</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">check_circle</span></div>
    </article>
    <article class="stat-card">
      <div><p class="label">Proximo vencimento</p><p class="value"><?= h($contexto['proximoVencimento'] ? data_br($contexto['proximoVencimento']) : '-') ?></p><span class="detail">Pendencias e atrasos</span></div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">event</span></div>
    </article>
  </div>
</section>

<?php aviso_aluno_nao_vinculado($aluno); ?>

<section class="panel" style="margin-top:20px">
  <div class="panel-header"><h2>Historico de mensalidades</h2></div>
  <?php tabela_cliente_mensalidades($mensalidades); ?>
</section>
<?php rodape_pagina(); ?>
