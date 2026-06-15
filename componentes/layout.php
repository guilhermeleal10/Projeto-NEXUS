<?php

declare(strict_types=1);

require_once __DIR__ . '/../configuracao/autenticacao.php';

function menu_por_perfil(string $perfil): array
{
    return match ($perfil) {
        'ADMIN' => [
            ['secao' => 'dashboard', 'rotulo' => 'Dashboard', 'icone' => 'dashboard', 'url' => 'painel-administrador.php'],
            ['secao' => 'usuarios', 'rotulo' => 'Usuarios', 'icone' => 'group', 'url' => 'paginas/usuarios.php'],
            ['secao' => 'academias', 'rotulo' => 'Academias', 'icone' => 'business', 'url' => 'paginas/academias.php'],
            ['secao' => 'alunos', 'rotulo' => 'Alunos', 'icone' => 'fitness_center', 'url' => 'paginas/alunos.php'],
            ['secao' => 'matriculas', 'rotulo' => 'Matriculas', 'icone' => 'badge', 'url' => 'paginas/matriculas.php'],
            ['secao' => 'mensalidades', 'rotulo' => 'Mensalidades', 'icone' => 'payments', 'url' => 'paginas/mensalidades.php'],
            ['secao' => 'relatorios', 'rotulo' => 'Relatorios', 'icone' => 'monitoring', 'url' => 'paginas/relatorios-financeiros.php'],
            ['secao' => 'suporte', 'rotulo' => 'Suporte', 'icone' => 'support_agent', 'url' => 'paginas/solicitacoes-suporte.php'],
            ['secao' => 'configuracoes', 'rotulo' => 'Configuracoes', 'icone' => 'settings', 'url' => 'configuracoes.php'],
        ],
        'GERENTE' => [
            ['secao' => 'dashboard', 'rotulo' => 'Dashboard', 'icone' => 'dashboard', 'url' => 'painel-gerente.php'],
            ['secao' => 'receitas', 'rotulo' => 'Receitas', 'icone' => 'trending_up', 'url' => 'paginas/receitas.php'],
            ['secao' => 'despesas', 'rotulo' => 'Despesas', 'icone' => 'trending_down', 'url' => 'paginas/despesas.php'],
            ['secao' => 'relatorios', 'rotulo' => 'Relatorios', 'icone' => 'monitoring', 'url' => 'paginas/relatorios-financeiros.php'],
            ['secao' => 'atendentes', 'rotulo' => 'Atendentes', 'icone' => 'groups', 'url' => 'paginas/atendentes.php'],
            ['secao' => 'alunos', 'rotulo' => 'Alunos', 'icone' => 'fitness_center', 'url' => 'paginas/alunos.php'],
            ['secao' => 'matriculas', 'rotulo' => 'Matriculas', 'icone' => 'badge', 'url' => 'paginas/matriculas.php'],
            ['secao' => 'mensalidades', 'rotulo' => 'Mensalidades', 'icone' => 'payments', 'url' => 'paginas/mensalidades.php'],
            ['secao' => 'suporte', 'rotulo' => 'Suporte', 'icone' => 'support_agent', 'url' => 'paginas/solicitacoes-suporte.php'],
            ['secao' => 'configuracoes', 'rotulo' => 'Configuracoes', 'icone' => 'settings', 'url' => 'configuracoes.php'],
        ],
        'ATENDENTE' => [
            ['secao' => 'dashboard', 'rotulo' => 'Dashboard', 'icone' => 'dashboard', 'url' => 'painel-atendente.php'],
            ['secao' => 'alunos', 'rotulo' => 'Alunos', 'icone' => 'fitness_center', 'url' => 'paginas/alunos.php'],
            ['secao' => 'matriculas', 'rotulo' => 'Matriculas', 'icone' => 'badge', 'url' => 'paginas/matriculas.php'],
            ['secao' => 'mensalidades', 'rotulo' => 'Mensalidades', 'icone' => 'payments', 'url' => 'paginas/mensalidades.php'],
            ['secao' => 'suporte', 'rotulo' => 'Suporte', 'icone' => 'support_agent', 'url' => 'paginas/solicitacoes-suporte.php'],
            ['secao' => 'configuracoes', 'rotulo' => 'Configuracoes', 'icone' => 'settings', 'url' => 'configuracoes.php'],
        ],
        'CLIENTE' => [
            ['secao' => 'dashboard', 'rotulo' => 'Dashboard', 'icone' => 'dashboard', 'url' => 'painel-cliente.php'],
            ['secao' => 'dados-aluno', 'rotulo' => 'Meus dados', 'icone' => 'person', 'url' => 'paginas/meus-dados.php'],
            ['secao' => 'matricula', 'rotulo' => 'Minha matricula', 'icone' => 'badge', 'url' => 'paginas/minha-matricula.php'],
            ['secao' => 'mensalidades', 'rotulo' => 'Mensalidades', 'icone' => 'payments', 'url' => 'paginas/minhas-mensalidades.php'],
            ['secao' => 'historico', 'rotulo' => 'Historico', 'icone' => 'history', 'url' => 'paginas/meu-historico.php'],
            ['secao' => 'suporte', 'rotulo' => 'Suporte', 'icone' => 'support_agent', 'url' => 'paginas/solicitacoes-suporte.php'],
            ['secao' => 'configuracoes', 'rotulo' => 'Configuracoes', 'icone' => 'settings', 'url' => 'configuracoes.php'],
        ],
        default => [],
    };
}

function url_app(string $url): string
{
    if (str_starts_with($url, '#')) {
        return $url;
    }

    return caminho_base() . $url;
}

function iniciais_usuario(array $usuario): string
{
    $nome = mb_substr((string) ($usuario['nome'] ?? 'N'), 0, 1, 'UTF-8');
    $sobrenome = mb_substr((string) ($usuario['sobrenome'] ?? 'X'), 0, 1, 'UTF-8');

    return mb_strtoupper($nome . $sobrenome, 'UTF-8');
}

function cabecalho_pagina(string $titulo, string $secao, array $usuario): void
{
    $base = caminho_base();
    $menu = menu_por_perfil((string) $usuario['perfil']);
    $painel = painel_por_perfil((string) $usuario['perfil']);
    $urlConfiguracoes = 'configuracoes.php';
    $versaoEstilo = (string) filemtime(__DIR__ . '/../recursos/css/estilo.css');
    $versaoContraste = (string) filemtime(__DIR__ . '/../recursos/css/style.css');
    $acaoRapida = match ($usuario['perfil']) {
        'ADMIN' => ['url' => 'paginas/usuarios.php?novo=1', 'rotulo' => 'Novo usuario'],
        'GERENTE' => ['url' => 'paginas/receitas.php?novo=1', 'rotulo' => 'Nova receita'],
        'ATENDENTE' => ['url' => 'paginas/alunos.php?novo=1', 'rotulo' => 'Novo aluno'],
        default => null,
    };
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NEXUS | <?= h($titulo) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;700&family=JetBrains+Mono:wght@500;700&family=Material+Symbols+Outlined:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= h($base) ?>recursos/css/estilo.css?v=<?= h($versaoEstilo) ?>">
  <link id="styleContraste" rel="stylesheet" href="<?= h($base) ?>recursos/css/style.css?v=<?= h($versaoContraste) ?>" data-acessibilidade-css-base="<?= h($base) ?>recursos/css/">
</head>
<body class="app-body">
  <a class="skip-link" href="#conteudo-principal">Ir para o conteudo</a>
  <aside class="sidebar" data-sidebar>
    <div class="sidebar-brand">
      <strong>NEXUS</strong>
      <span>Command Center</span>
    </div>
    <nav aria-label="Menu principal">
      <?php foreach ($menu as $item): ?>
        <a class="nav-link <?= $item['secao'] === $secao ? 'is-active' : '' ?>" href="<?= h(url_app($item['url'])) ?>">
          <span class="material-symbols-outlined" aria-hidden="true"><?= h($item['icone']) ?></span>
          <span><?= h($item['rotulo']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <?php if ($acaoRapida): ?>
        <a class="btn btn-primary" href="<?= h(url_app($acaoRapida['url'])) ?>">
          <span class="material-symbols-outlined" aria-hidden="true">add</span>
          <span><?= h($acaoRapida['rotulo']) ?></span>
        </a>
      <?php endif; ?>
      <a class="nav-link" href="<?= h($base) ?>sair.php">
        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
        <span>Sair</span>
      </a>
    </div>
  </aside>
  <div class="content-shell">
    <header class="topbar">
      <button class="btn btn-ghost menu-toggle" type="button" data-menu-toggle aria-label="Abrir menu">
        <span class="material-symbols-outlined" aria-hidden="true">menu</span>
      </button>
      <button class="btn btn-ghost sidebar-collapse-toggle" type="button" data-sidebar-collapse-toggle aria-label="Esconder barra lateral" aria-pressed="false" title="Esconder barra lateral">
        <span class="material-symbols-outlined" aria-hidden="true">left_panel_close</span>
      </button>
      <h1><?= h($titulo) ?></h1>
      <div class="topbar-actions">
        <div class="accessibility-tools" role="toolbar" aria-label="Acessibilidade">
          <button class="accessibility-btn" type="button" data-acessibilidade-fonte="menos" aria-label="Diminuir fonte" title="Diminuir fonte">
            <span class="material-symbols-outlined" aria-hidden="true">text_decrease</span>
          </button>
          <button class="accessibility-btn" type="button" data-acessibilidade-fonte="resetar" aria-label="Restaurar fonte" title="Restaurar fonte">
            <span class="material-symbols-outlined" aria-hidden="true">restart_alt</span>
          </button>
          <button class="accessibility-btn" type="button" data-acessibilidade-fonte="mais" aria-label="Aumentar fonte" title="Aumentar fonte">
            <span class="material-symbols-outlined" aria-hidden="true">text_increase</span>
          </button>
          <button class="accessibility-btn" id="mudaEstilo" type="button" data-acessibilidade-contraste aria-label="Ativar alto contraste" aria-pressed="false" title="Alto contraste">
            <span class="material-symbols-outlined" aria-hidden="true">contrast</span>
          </button>
        </div>
        <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
        <a href="<?= h($base . $urlConfiguracoes) ?>" aria-label="Configuracoes">
          <span class="material-symbols-outlined" aria-hidden="true">settings</span>
        </a>
        <div class="user-chip">
          <div class="user-meta">
            <strong><?= h($usuario['nome'] . ' ' . $usuario['sobrenome']) ?></strong>
            <span><?= h($usuario['perfil']) ?></span>
          </div>
          <div class="avatar" aria-label="Usuario atual"><?= h(iniciais_usuario($usuario)) ?></div>
        </div>
      </div>
    </header>
    <main id="conteudo-principal" class="page-content">
    <?php
}

function rodape_pagina(): void
{
    $base = caminho_base();
    $versaoAplicacao = (string) filemtime(__DIR__ . '/../recursos/js/aplicacao.js');
    $versaoAcessibilidade = (string) filemtime(__DIR__ . '/../recursos/js/acessibilidade.js');
    ?>
    </main>
  </div>
  <script src="<?= h($base) ?>recursos/js/aplicacao.js?v=<?= h($versaoAplicacao) ?>"></script>
  <script src="<?= h($base) ?>recursos/js/acessibilidade.js?v=<?= h($versaoAcessibilidade) ?>"></script>
</body>
</html>
    <?php
}
