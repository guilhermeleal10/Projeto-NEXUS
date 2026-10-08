<?php

require_once __DIR__ . '/../backend/configuracao/autenticacao.php';

$erro = '';
$usuarioLogado = usuario_atual();
$versaoEstilo = (string) filemtime(__DIR__ . '/recursos/css/estilo.css');
$versaoContraste = (string) filemtime(__DIR__ . '/recursos/css/style.css');
$versaoAplicacao = (string) filemtime(__DIR__ . '/recursos/js/aplicacao.js');
$versaoAcessibilidade = (string) filemtime(__DIR__ . '/recursos/js/acessibilidade.js');

if ($usuarioLogado) {
    header('Location: ' . painel_por_perfil((string) $usuarioLogado['perfil']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = entrar_usuario((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''));

    if ($usuario) {
        header('Location: ' . painel_por_perfil((string) $usuario['perfil']));
        exit;
    }

    $erro = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NEXUS | Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;700&family=JetBrains+Mono:wght@500;700&family=Material+Symbols+Outlined:wght@400;500;600&display=swap" rel="stylesheet">
  <script>try { if (localStorage.getItem('nexus-theme') === 'light') document.documentElement.setAttribute('data-theme', 'light'); } catch (e) {}</script>
  <link rel="stylesheet" href="recursos/css/estilo.css?v=<?= h($versaoEstilo) ?>">
  <link id="styleContraste" rel="stylesheet" href="recursos/css/style.css?v=<?= h($versaoContraste) ?>" data-acessibilidade-css-base="recursos/css/">
</head>
<body class="login-body">
  <a class="skip-link" href="#conteudo-principal">Ir para o conteudo</a>

  <div class="floating-preferences" aria-label="Preferências de aparência e acessibilidade">
    <button class="floating-action theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Ativar tema claro" title="Ativar tema claro">
      <span class="material-symbols-outlined" data-theme-icon aria-hidden="true">light_mode</span>
      <span class="sr-only" data-theme-label>Ativar tema claro</span>
    </button>
    <button class="floating-action accessibility-launcher" type="button" data-accessibility-toggle aria-expanded="false" aria-controls="accessibility-panel" aria-label="Abrir painel de acessibilidade" title="Acessibilidade">
      <span class="material-symbols-outlined" aria-hidden="true">accessibility_new</span>
    </button>
    <section class="accessibility-panel" id="accessibility-panel" data-accessibility-panel hidden aria-labelledby="accessibility-panel-title">
      <header class="accessibility-panel-header">
        <div>
          <span class="accessibility-panel-kicker">NEXUS / PREFERÊNCIAS</span>
          <h2 id="accessibility-panel-title">Acessibilidade</h2>
        </div>
        <button class="accessibility-close" type="button" data-accessibility-close aria-label="Fechar painel de acessibilidade" title="Fechar">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
      </header>
      <p class="accessibility-panel-description">Personalize a leitura e o contraste da interface.</p>
      <div class="accessibility-panel-controls" role="toolbar" aria-label="Opções de acessibilidade">
        <button class="accessibility-btn" type="button" data-acessibilidade-fonte="menos" aria-label="Diminuir fonte" title="Diminuir fonte">
          <span class="material-symbols-outlined" aria-hidden="true">text_decrease</span><span class="accessibility-control-label">Diminuir texto</span>
        </button>
        <button class="accessibility-btn" type="button" data-acessibilidade-fonte="resetar" aria-label="Restaurar fonte" title="Restaurar fonte">
          <span class="material-symbols-outlined" aria-hidden="true">restart_alt</span><span class="accessibility-control-label">Texto padrão</span>
        </button>
        <button class="accessibility-btn" type="button" data-acessibilidade-fonte="mais" aria-label="Aumentar fonte" title="Aumentar fonte">
          <span class="material-symbols-outlined" aria-hidden="true">text_increase</span><span class="accessibility-control-label">Aumentar texto</span>
        </button>
        <button class="accessibility-btn" id="mudaEstilo" type="button" data-acessibilidade-contraste aria-label="Ativar alto contraste" aria-pressed="false" title="Ativar alto contraste">
          <span class="material-symbols-outlined" aria-hidden="true">contrast</span><span class="accessibility-control-label">Alto contraste</span>
        </button>
      </div>
      <p class="accessibility-panel-footnote">As preferências ficam salvas neste navegador.</p>
    </section>
  </div>

  <canvas class="particle-canvas" data-particulas aria-hidden="true"></canvas>

  <div class="login-visual" aria-hidden="true">
    <img src="recursos/imagens/fundo-login-nexus.png" alt="">
  </div>

  <main id="conteudo-principal" class="login-card" aria-labelledby="login-title">
    <header>
      <h1 id="login-title">NEXUS</h1>
      <p>Controle de alta performance</p>
    </header>

    <form class="form-stack" method="post" novalidate>
      <div class="field">
        <label for="email">E-mail</label>
        <div class="input-shell">
          <span class="material-symbols-outlined" aria-hidden="true">person</span>
          <input id="email" name="email" type="email" autocomplete="email" placeholder="Seu acesso aqui..." required>
        </div>
      </div>

      <div class="field">
        <label for="senha">Senha</label>
        <div class="input-shell password-shell">
          <span class="material-symbols-outlined" aria-hidden="true">lock</span>
          <input id="senha" name="senha" type="password" autocomplete="current-password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" data-password-input required>
          <button class="password-toggle" type="button" data-password-toggle aria-controls="senha" aria-label="Mostrar senha" aria-pressed="false">
            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
          </button>
        </div>
      </div>

      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">login</span>
        Entrar
      </button>
    </form>

    <div class="login-links">
      <a class="btn btn-ghost" href="recuperar-senha.php">
        <span class="material-symbols-outlined" aria-hidden="true">help</span>
        Esqueci a senha
      </a>
    </div>

    <?php if ($erro): ?>
      <div class="feedback is-visible error" role="alert"><?= h($erro) ?></div>
    <?php endif; ?>
  </main>

  <script src="recursos/js/aplicacao.js?v=<?= h($versaoAplicacao) ?>"></script>
  <script src="recursos/js/acessibilidade.js?v=<?= h($versaoAcessibilidade) ?>"></script>
</body>
</html>
