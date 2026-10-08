<?php

require_once __DIR__ . '/componentes/layout.php';

$usuario = exigir_login();
$pdo = obter_conexao();
$mensagem = (string) ($_GET['mensagem'] ?? '');
$tipoMensagem = (string) ($_GET['tipo'] ?? 'success');
$senhaVerificada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = (string) ($_POST['acao'] ?? '');

        if ($acao === 'atualizar_conta') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $sobrenome = trim((string) ($_POST['sobrenome'] ?? ''));
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');
            $telefone = trim((string) ($_POST['telefone'] ?? ''));

            if ($nome === '' || $sobrenome === '' || $email === '' || $telefone === '') {
                throw new RuntimeException('Preencha todos os dados da conta.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe um e-mail valido.');
            }

            $duplicidade = $pdo->prepare('SELECT COUNT(*) FROM Usuario WHERE email = :email AND idUsuario <> :idUsuario');
            $duplicidade->execute(['email' => $email, 'idUsuario' => $usuario['idUsuario']]);

            if ((int) $duplicidade->fetchColumn() > 0) {
                throw new RuntimeException('Este e-mail ja esta sendo usado por outro usuario.');
            }

            $atualizar = $pdo->prepare(
                'UPDATE Usuario
                 SET nome = :nome, sobrenome = :sobrenome, email = :email, telefone = :telefone
                 WHERE idUsuario = :idUsuario'
            );
            $atualizar->execute([
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                'email' => $email,
                'telefone' => $telefone,
                'idUsuario' => $usuario['idUsuario'],
            ]);

            header('Location: configuracoes.php?mensagem=' . urlencode('Dados da conta atualizados com sucesso.') . '&tipo=success');
            exit;
        }

        if ($acao === 'verificar_senha') {
            $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
            validar_senha_sistema($senhaAtual);

            if (!hash_equals((string) $usuario['senhaCriptografada'], senha_criptografada($senhaAtual))) {
                throw new RuntimeException('A senha informada nao confere.');
            }

            $senhaVerificada = true;
            $mensagem = 'Usuario reconhecido. A senha atual foi confirmada; o texto original permanece protegido.';
            $tipoMensagem = 'success';
        }

        if ($acao === 'alterar_senha') {
            $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
            $novaSenha = (string) ($_POST['nova_senha'] ?? '');
            $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

            validar_senha_sistema($senhaAtual);
            validar_senha_sistema($novaSenha);

            if (!hash_equals((string) $usuario['senhaCriptografada'], senha_criptografada($senhaAtual))) {
                throw new RuntimeException('A senha atual nao confere.');
            }

            if ($novaSenha !== $confirmarSenha) {
                throw new RuntimeException('A confirmacao da senha nao confere.');
            }

            $atualizarSenha = $pdo->prepare('UPDATE Usuario SET senhaCriptografada = :senha WHERE idUsuario = :idUsuario');
            $atualizarSenha->execute([
                'senha' => senha_criptografada($novaSenha),
                'idUsuario' => $usuario['idUsuario'],
            ]);

            header('Location: configuracoes.php?mensagem=' . urlencode('Senha atualizada com sucesso.') . '&tipo=success');
            exit;
        }

        if (!in_array($acao, ['atualizar_conta', 'verificar_senha', 'alterar_senha'], true)) {
            throw new RuntimeException('Acao invalida.');
        }
    } catch (Throwable $erro) {
        $mensagem = $erro instanceof PDOException
            ? 'Nao foi possivel salvar as configuracoes agora.'
            : $erro->getMessage();
        $tipoMensagem = 'error';
    }
}

$usuario = usuario_atual() ?: $usuario;
$dataCriacao = !empty($usuario['criadoEm']) ? data_br(substr((string) $usuario['criadoEm'], 0, 10)) : 'Nao informado';

cabecalho_pagina('Configurações', 'configuracoes', $usuario);
?>
<div class="section-kicker">Conta e segurança</div>

<?php if ($mensagem): ?>
  <div class="feedback is-visible <?= h($tipoMensagem) ?>" role="status" style="margin:0 0 18px">
    <?= h($mensagem) ?>
  </div>
<?php endif; ?>

<section class="settings-grid" aria-label="Configuracoes da conta">
  <article class="panel">
    <div class="panel-header">
      <h2>Perfil</h2>
    </div>
    <div class="profile-summary">
      <div class="avatar profile-avatar" aria-hidden="true"><?= h(iniciais_usuario($usuario)) ?></div>
      <div>
        <strong><?= h($usuario['nome'] . ' ' . $usuario['sobrenome']) ?></strong>
        <span><?= h($usuario['email']) ?></span>
      </div>
    </div>
    <div class="list-stack">
      <div class="list-item"><strong>Perfil</strong><span><?= h($usuario['perfil']) ?></span></div>
      <div class="list-item"><strong>CPF</strong><span><?= h($usuario['cpf']) ?></span></div>
      <div class="list-item"><strong>Telefone</strong><span><?= h($usuario['telefone']) ?></span></div>
      <div class="list-item"><strong>Cadastro</strong><span><?= h($dataCriacao) ?></span></div>
      <div class="list-item"><strong>Senha</strong><span><?= h($senhaVerificada ? 'Confirmada nesta sessão' : 'Protegida') ?></span></div>
    </div>
  </article>

  <article class="panel">
    <div class="panel-header">
      <h2>Dados da conta</h2>
    </div>
    <form class="crud-form" method="post" novalidate>
      <input type="hidden" name="acao" value="atualizar_conta">
      <div class="form-grid">
        <div class="field">
          <label for="config_nome">Nome</label>
          <input id="config_nome" name="nome" type="text" value="<?= h($usuario['nome']) ?>" required>
        </div>
        <div class="field">
          <label for="config_sobrenome">Sobrenome</label>
          <input id="config_sobrenome" name="sobrenome" type="text" value="<?= h($usuario['sobrenome']) ?>" required>
        </div>
        <div class="field">
          <label for="config_email">E-mail de acesso</label>
          <input id="config_email" name="email" type="email" value="<?= h($usuario['email']) ?>" required>
        </div>
        <div class="field">
          <label for="config_telefone">Telefone</label>
          <input id="config_telefone" name="telefone" type="tel" value="<?= h($usuario['telefone']) ?>" required>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">save</span>
          Salvar conta
        </button>
      </div>
    </form>
  </article>

  <article class="panel">
    <div class="panel-header">
      <h2>Verificar senha</h2>
    </div>
    <form class="crud-form" method="post" novalidate>
      <input type="hidden" name="acao" value="verificar_senha">
      <div class="field">
        <label for="verificar_senha_atual">Senha atual</label>
        <div class="input-shell password-shell">
          <span class="material-symbols-outlined" aria-hidden="true">lock</span>
          <input id="verificar_senha_atual" name="senha_atual" type="password" autocomplete="current-password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" data-password-input required>
          <button class="password-toggle" type="button" data-password-toggle aria-controls="verificar_senha_atual" aria-label="Mostrar senha" aria-pressed="false">
            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
          </button>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
          Reconhecer usuário
        </button>
        <a class="btn btn-ghost" href="recuperar-senha.php">
          <span class="material-symbols-outlined" aria-hidden="true">help</span>
          Esqueci a senha
        </a>
      </div>
    </form>
  </article>

  <article class="panel">
    <div class="panel-header">
      <h2>Alterar senha</h2>
    </div>
    <form class="crud-form" method="post" novalidate>
      <input type="hidden" name="acao" value="alterar_senha">
      <div class="form-grid">
        <div class="field full">
          <label for="senha_atual">Senha atual</label>
          <div class="input-shell password-shell">
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" data-password-input required>
            <button class="password-toggle" type="button" data-password-toggle aria-controls="senha_atual" aria-label="Mostrar senha" aria-pressed="false">
              <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
            </button>
          </div>
        </div>
        <div class="field">
          <label for="nova_senha">Nova senha</label>
          <div class="input-shell password-shell">
            <span class="material-symbols-outlined" aria-hidden="true">lock_reset</span>
            <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" data-password-input required>
            <button class="password-toggle" type="button" data-password-toggle aria-controls="nova_senha" aria-label="Mostrar senha" aria-pressed="false">
              <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
            </button>
          </div>
        </div>
        <div class="field">
          <label for="confirmar_senha">Confirmar senha</label>
          <div class="input-shell password-shell">
            <span class="material-symbols-outlined" aria-hidden="true">lock_reset</span>
            <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" data-password-input required>
            <button class="password-toggle" type="button" data-password-toggle aria-controls="confirmar_senha" aria-label="Mostrar senha" aria-pressed="false">
              <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
            </button>
          </div>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">lock_reset</span>
          Atualizar senha
        </button>
      </div>
    </form>
  </article>

  <article class="panel">
    <div class="panel-header">
      <h2>Preferências</h2>
    </div>
    <div class="settings-actions" role="toolbar" aria-label="Preferências de interface">
      <button class="accessibility-btn settings-tool" type="button" data-acessibilidade-fonte="menos" aria-label="Diminuir fonte" title="Diminuir fonte">
        <span class="material-symbols-outlined" aria-hidden="true">text_decrease</span>
      </button>
      <button class="accessibility-btn settings-tool" type="button" data-acessibilidade-fonte="resetar" aria-label="Restaurar fonte" title="Restaurar fonte">
        <span class="material-symbols-outlined" aria-hidden="true">restart_alt</span>
      </button>
      <button class="accessibility-btn settings-tool" type="button" data-acessibilidade-fonte="mais" aria-label="Aumentar fonte" title="Aumentar fonte">
        <span class="material-symbols-outlined" aria-hidden="true">text_increase</span>
      </button>
      <button class="accessibility-btn settings-tool" type="button" data-acessibilidade-contraste aria-label="Ativar alto contraste" title="Alto contraste">
        <span class="material-symbols-outlined" aria-hidden="true">contrast</span>
      </button>
    </div>
  </article>

  <?php if ($usuario['perfil'] === 'ADMIN'): ?>
    <article class="panel">
      <div class="panel-header">
        <h2>Administração</h2>
      </div>
      <div class="list-stack">
        <a class="btn btn-ghost" href="paginas/usuarios.php">
          <span class="material-symbols-outlined" aria-hidden="true">group</span>
          Gerenciar usuários
        </a>
        <a class="btn btn-ghost" href="paginas/academias.php">
          <span class="material-symbols-outlined" aria-hidden="true">business</span>
          Gerenciar academias
        </a>
        <a class="btn btn-danger" href="sair.php">
          <span class="material-symbols-outlined" aria-hidden="true">logout</span>
          Sair da conta
        </a>
      </div>
    </article>
  <?php endif; ?>
</section>
<?php rodape_pagina(); ?>
