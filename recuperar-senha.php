<?php

require_once __DIR__ . '/configuracao/autenticacao.php';

$pdo = obter_conexao();
$erro = '';
$mensagem = '';
$usuarioReconhecido = null;
$versaoEstilo = (string) filemtime(__DIR__ . '/recursos/css/estilo.css');
$versaoContraste = (string) filemtime(__DIR__ . '/recursos/css/style.css');

if (!empty($_GET['cancelar'])) {
    unset($_SESSION['recuperar_usuario'], $_SESSION['recuperar_validade']);
    header('Location: recuperar-senha.php');
    exit;
}

if (!empty($_SESSION['recuperar_usuario']) && !empty($_SESSION['recuperar_validade'])) {
    if ((int) $_SESSION['recuperar_validade'] < time()) {
        unset($_SESSION['recuperar_usuario'], $_SESSION['recuperar_validade']);
        $erro = 'Reconhecimento expirado. Informe seus dados novamente.';
    } else {
        $consulta = $pdo->prepare('SELECT * FROM Usuario WHERE idUsuario = :idUsuario LIMIT 1');
        $consulta->execute(['idUsuario' => $_SESSION['recuperar_usuario']]);
        $usuarioReconhecido = $consulta->fetch() ?: null;

        if (!$usuarioReconhecido) {
            unset($_SESSION['recuperar_usuario'], $_SESSION['recuperar_validade']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = (string) ($_POST['acao'] ?? '');

        if ($acao === 'reconhecer_usuario') {
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
            $cpf = trim((string) ($_POST['cpf'] ?? ''));

            if ($email === '' || $cpf === '') {
                throw new RuntimeException('Informe e-mail e CPF.');
            }

            $consulta = $pdo->prepare('SELECT * FROM Usuario WHERE email = :email AND cpf = :cpf LIMIT 1');
            $consulta->execute(['email' => $email, 'cpf' => $cpf]);
            $usuarioReconhecido = $consulta->fetch() ?: null;

            if (!$usuarioReconhecido) {
                throw new RuntimeException('Usuario nao reconhecido com os dados informados.');
            }

            $_SESSION['recuperar_usuario'] = (int) $usuarioReconhecido['idUsuario'];
            $_SESSION['recuperar_validade'] = time() + 900;
            $mensagem = 'Usuario reconhecido. Defina uma nova senha.';
        }

        if ($acao === 'redefinir_senha') {
            if (!$usuarioReconhecido) {
                throw new RuntimeException('Reconheca o usuario antes de redefinir a senha.');
            }

            $novaSenha = (string) ($_POST['nova_senha'] ?? '');
            $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

            if (mb_strlen($novaSenha, 'UTF-8') < 6) {
                throw new RuntimeException('A nova senha deve ter pelo menos 6 caracteres.');
            }

            if ($novaSenha !== $confirmarSenha) {
                throw new RuntimeException('A confirmacao da senha nao confere.');
            }

            $atualizar = $pdo->prepare('UPDATE Usuario SET senhaCriptografada = :senha WHERE idUsuario = :idUsuario');
            $atualizar->execute([
                'senha' => senha_criptografada($novaSenha),
                'idUsuario' => $usuarioReconhecido['idUsuario'],
            ]);

            unset($_SESSION['recuperar_usuario'], $_SESSION['recuperar_validade']);
            $usuarioReconhecido = null;
            $mensagem = 'Senha redefinida com sucesso. Entre usando a nova senha.';
        }

        if (!in_array($acao, ['reconhecer_usuario', 'redefinir_senha'], true)) {
            throw new RuntimeException('Acao invalida.');
        }
    } catch (Throwable $erroCapturado) {
        $erro = $erroCapturado->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NEXUS | Recuperar senha</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;700&family=JetBrains+Mono:wght@500;700&family=Material+Symbols+Outlined:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="recursos/css/estilo.css?v=<?= h($versaoEstilo) ?>">
  <link id="styleContraste" rel="stylesheet" href="recursos/css/style.css?v=<?= h($versaoContraste) ?>" data-acessibilidade-css-base="recursos/css/">
</head>
<body class="login-body">
  <a class="skip-link" href="#conteudo-principal">Ir para o conteudo</a>
  <div class="accessibility-tools login-accessibility" role="toolbar" aria-label="Acessibilidade">
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

  <main id="conteudo-principal" class="login-card recover-card" aria-labelledby="recover-title">
    <header>
      <h1 id="recover-title">Recuperar senha</h1>
      <p>NEXUS</p>
    </header>

    <?php if ($mensagem): ?>
      <div class="feedback is-visible success" role="status"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
      <div class="feedback is-visible error" role="alert"><?= h($erro) ?></div>
    <?php endif; ?>

    <?php if (!$usuarioReconhecido): ?>
      <form class="form-stack" method="post">
        <input type="hidden" name="acao" value="reconhecer_usuario">
        <div class="field">
          <label for="email">E-mail</label>
          <div class="input-shell">
            <span class="material-symbols-outlined" aria-hidden="true">mail</span>
            <input id="email" name="email" type="email" autocomplete="email" required>
          </div>
        </div>

        <div class="field">
          <label for="cpf">CPF</label>
          <div class="input-shell">
            <span class="material-symbols-outlined" aria-hidden="true">badge</span>
            <input id="cpf" name="cpf" type="text" autocomplete="off" required>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
          Reconhecer usuário
        </button>
      </form>
    <?php else: ?>
      <div class="list-stack recover-profile">
        <div class="list-item"><strong>Nome</strong><span><?= h($usuarioReconhecido['nome'] . ' ' . $usuarioReconhecido['sobrenome']) ?></span></div>
        <div class="list-item"><strong>E-mail</strong><span><?= h($usuarioReconhecido['email']) ?></span></div>
        <div class="list-item"><strong>Perfil</strong><span><?= h($usuarioReconhecido['perfil']) ?></span></div>
      </div>

      <form class="form-stack" method="post">
        <input type="hidden" name="acao" value="redefinir_senha">
        <div class="field">
          <label for="nova_senha">Nova senha</label>
          <div class="input-shell">
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password" minlength="6" required>
          </div>
        </div>

        <div class="field">
          <label for="confirmar_senha">Confirmar senha</label>
          <div class="input-shell">
            <span class="material-symbols-outlined" aria-hidden="true">lock_reset</span>
            <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" minlength="6" required>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">save</span>
          Salvar nova senha
        </button>
      </form>
    <?php endif; ?>

    <div class="login-links">
      <a class="btn btn-ghost" href="entrar.php">
        <span class="material-symbols-outlined" aria-hidden="true">login</span>
        Voltar ao login
      </a>
      <?php if ($usuarioReconhecido): ?>
        <a class="btn btn-ghost" href="recuperar-senha.php?cancelar=1">
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
          Cancelar
        </a>
      <?php endif; ?>
    </div>
  </main>

  <script src="recursos/js/aplicacao.js"></script>
  <script src="recursos/js/acessibilidade.js"></script>
</body>
</html>
