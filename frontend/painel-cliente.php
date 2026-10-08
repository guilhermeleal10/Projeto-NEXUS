<?php

require_once __DIR__ . '/componentes/area-cliente.php';

$contexto = carregar_area_cliente();
$usuario = $contexto['usuario'];
$aluno = $contexto['aluno'];
$matriculas = $contexto['matriculas'];
$mensalidades = $contexto['mensalidades'];
$suportes = $contexto['suportes'];
$matriculaAtual = $contexto['matriculaAtual'];

cabecalho_pagina('Area do Cliente', 'dashboard', $usuario);
?>
<section>
  <div class="section-kicker">Painel do cliente</div>
  <div class="stats-grid">
    <?php if ($aluno): ?>
    <article class="stat-card">
      <div>
        <p class="label">Cliente</p>
        <p class="value"><?= h($aluno['nome']) ?></p>
        <span class="detail"><?= h($aluno['email']) ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">person</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Academia</p>
        <p class="value"><?= h($aluno['academiaNome']) ?></p>
        <span class="detail"><?= h($aluno['academiaEndereco']) ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">business</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Matricula</p>
        <p class="value"><?= h($contexto['statusMatricula']) ?></p>
        <span class="detail"><?= h($matriculaAtual['codigoMatricula'] ?? count($matriculas) . ' registro(s)') ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">badge</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Plano atual</p>
        <p class="value"><?= h($matriculaAtual['plano'] ?? 'Sem plano') ?></p>
        <span class="detail"><?= h($matriculaAtual ? dinheiro($matriculaAtual['valorPlano']) : 'Aguardando matricula') ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">workspace_premium</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Mensalidades abertas</p>
        <p class="value"><?= h($contexto['mensalidadesAbertas']) ?></p>
        <span class="detail"><?= h($contexto['proximoVencimento'] ? 'Vence em ' . data_br($contexto['proximoVencimento']) : 'Nenhuma pendencia') ?></span>
      </div>
      <div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">payments</span></div>
    </article>
    <?php else: ?>
    <article class="stat-card">
      <div>
        <p class="label">Conta cliente</p>
        <p class="value"><?= h($usuario['nome'] . ' ' . $usuario['sobrenome']) ?></p>
        <span class="detail"><?= h($usuario['email']) ?></span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">person</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Cadastro</p>
        <p class="value">Pendente</p>
        <span class="detail">Aguardando vinculo com aluno</span>
      </div>
      <div class="stat-icon hot"><span class="material-symbols-outlined" aria-hidden="true">pending_actions</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Matricula</p>
        <p class="value">Indisponivel</p>
        <span class="detail">Solicite o vinculo ao atendimento</span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">badge</span></div>
    </article>
    <article class="stat-card">
      <div>
        <p class="label">Suporte</p>
        <p class="value"><?= h(count($suportes)) ?></p>
        <span class="detail">Chamado(s) registrados</span>
      </div>
      <div class="stat-icon"><span class="material-symbols-outlined" aria-hidden="true">support_agent</span></div>
    </article>
    <?php endif; ?>
  </div>
</section>

<?php aviso_aluno_nao_vinculado($aluno); ?>

<section class="quick-grid" aria-label="Paginas do aluno" style="margin-top:20px">
  <a class="quick-card" href="paginas/meus-dados.php">
    <span class="material-symbols-outlined" aria-hidden="true">person</span>
    <strong>Meus dados</strong>
    <small>Cadastro, contato e academia vinculada.</small>
  </a>
  <a class="quick-card" href="paginas/minha-matricula.php">
    <span class="material-symbols-outlined" aria-hidden="true">badge</span>
    <strong>Minha matricula</strong>
    <small>Status, data e unidade da matricula.</small>
  </a>
  <a class="quick-card" href="paginas/minhas-mensalidades.php">
    <span class="material-symbols-outlined" aria-hidden="true">payments</span>
    <strong>Mensalidades</strong>
    <small>Pagamentos, vencimentos e pendencias.</small>
  </a>
  <a class="quick-card" href="paginas/meu-historico.php">
    <span class="material-symbols-outlined" aria-hidden="true">history</span>
    <strong>Historico</strong>
    <small>Resumo das atividades e solicitacoes.</small>
  </a>
</section>

<section class="two-column" style="margin-top:20px">
  <article class="panel">
    <div class="panel-header">
      <h2>Proximas acoes</h2>
      <a class="btn btn-ghost" href="paginas/solicitacoes-suporte.php">Abrir suporte</a>
    </div>
    <div class="list-stack">
      <div class="list-item"><strong>Mensalidades abertas</strong><span><?= h($contexto['mensalidadesAbertas']) ?> pendencia(s), totalizando <?= h(dinheiro($contexto['valorEmAberto'])) ?>.</span></div>
      <div class="list-item"><strong>Mensalidades pagas</strong><span><?= h($contexto['mensalidadesPagas']) ?> pagamento(s) registrado(s).</span></div>
      <div class="list-item"><strong>Solicitacoes</strong><span><?= h(count($suportes)) ?> chamado(s) no historico.</span></div>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header"><h2>Ultimas solicitacoes</h2></div>
    <?php tabela_cliente_suportes(array_slice($suportes, 0, 3)); ?>
  </article>
</section>
<?php rodape_pagina(); ?>
