<?php

require_once __DIR__ . '/../componentes/area-cliente.php';

$contexto = carregar_area_cliente();
$usuario = $contexto['usuario'];
$aluno = $contexto['aluno'];

cabecalho_pagina('Meus Dados', 'dados-aluno', $usuario);
?>
<section>
  <div class="section-kicker">Cadastro do aluno</div>
  <?php aviso_aluno_nao_vinculado($aluno); ?>

  <?php if ($aluno): ?>
    <div class="settings-grid" style="margin-top:20px">
      <article class="panel">
        <div class="panel-header"><h2>Dados pessoais</h2></div>
        <div class="list-stack">
          <div class="list-item"><strong>Nome</strong><span><?= h($aluno['nome']) ?></span></div>
          <div class="list-item"><strong>CPF</strong><span><?= h($aluno['cpf']) ?></span></div>
          <div class="list-item"><strong>Nascimento</strong><span><?= h(data_br($aluno['dataNascimento'])) ?></span></div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-header"><h2>Contato e unidade</h2></div>
        <div class="list-stack">
          <div class="list-item"><strong>Telefone</strong><span><?= h($aluno['telefone']) ?></span></div>
          <div class="list-item"><strong>E-mail</strong><span><?= h($aluno['email']) ?></span></div>
          <div class="list-item"><strong>Academia</strong><span><?= h($aluno['academiaNome']) ?></span></div>
          <div class="list-item"><strong>Endereco</strong><span><?= h($aluno['academiaEndereco']) ?></span></div>
        </div>
      </article>
    </div>
  <?php endif; ?>
</section>
<?php rodape_pagina(); ?>
