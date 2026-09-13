# NEXUS

NEXUS e um sistema web CRUD para gestao de academias, projetos esportivos locais e academias comunitarias. O foco e organizar alunos, matriculas, mensalidades, receitas, despesas, relatorios financeiros e solicitacoes de suporte em um ambiente simples para teste no XAMPP.

Importante: no NEXUS, CLIENTE = ALUNO DA ACADEMIA. O cliente/aluno consulta seus proprios dados, sua matricula, suas mensalidades e suas solicitacoes de suporte. Ele nao gerencia academias, receitas, despesas ou relatorios financeiros.

## Objetivo do projeto

Desenvolver um sistema web CRUD funcional capaz de cadastrar, consultar, editar e excluir informacoes aplicadas a uma necessidade real de extensao: a organizacao administrativa e financeira de academias e iniciativas esportivas comunitarias.

## Tecnologias

- PHP com PDO para telas, autenticacao, sessoes, permissoes e CRUDs.
- MySQL/MariaDB para persistencia dos dados.
- HTML, CSS e JavaScript para interface responsiva, menu lateral, feedback visual e confirmacao de exclusao.
- XAMPP como ambiente local de execucao.

## Perfis de usuario

| Perfil | Funcao |
| --- | --- |
| ADMIN | Gerencia usuarios e academias, audita alunos, matriculas, mensalidades e relatorios, visualiza suporte e acessa configuracoes gerais. |
| GERENTE | Visualiza dashboard financeiro, gerencia receitas, despesas, relatorios financeiros e atendentes, consulta alunos, matriculas e mensalidades, e abre solicitacoes de suporte para o ADMIN. |
| ATENDENTE | Cadastra alunos, matriculas e mensalidades, consulta pagamentos e abre ou acompanha solicitacoes de suporte. |
| CLIENTE | Representa o aluno da academia. Consulta seus dados, matricula, mensalidades e historico em paginas separadas, alem de abrir suporte. |

## CRUDs implementados

- Usuarios
- Atendentes
- Academias
- Alunos
- Matriculas
- Mensalidades
- Receitas
- Despesas
- Relatorios financeiros
- Solicitacoes de suporte

Cada CRUD possui formulario de cadastro/edicao, listagem em tabela, busca simples, botao editar, botao excluir com confirmacao, validacao de campos obrigatorios e mensagens de sucesso ou erro. As telas compartilhadas mudam conforme o perfil: ADMIN ve auditoria/manutencao, GERENTE ve consulta gerencial e ATENDENTE ve operacao de cadastro/baixa.

## Planos e matriculas

Cada matricula recebe um codigo unico gerado automaticamente no formato `NX-AAAAMM-ALUNO-RANDOMICO`. O atendente ou admin escolhe um dos planos ofertados:

| Plano | Valor |
| --- | --- |
| Basico | Gratuito |
| Maromba | R$ 49,90 |
| Shape | R$ 99,99 |

As mensalidades novas usam automaticamente o valor do plano da matricula selecionada.

## Graficos disponiveis

- ADMIN: usuarios por perfil e solicitacoes de suporte por status.
- GERENTE: receitas x despesas por mes e receitas por academia.
- ATENDENTE: alunos por academia, matriculas por status e mensalidades por status.

## Fluxo de credenciais e perfis

- O acesso ao sistema usa o e-mail cadastrado no usuario e a senha informada no CRUD de Usuarios.
- As senhas aceitam somente numeros e devem ter exatamente 6 digitos.
- Ao alterar e-mail, senha ou perfil de um usuario, o proximo login passa a obedecer aos novos dados.
- Ao excluir um usuario, o perfil de acesso deixa de existir; historicos de alunos, matriculas, relatorios e suporte sao mantidos com o responsavel antigo em branco.

## Como rodar no XAMPP

1. Copie a pasta `NEXUS` para `htdocs`.
2. Abra o painel do XAMPP.
3. Inicie Apache e MySQL.
4. Acesse `http://localhost/phpmyadmin/`.
5. Importe primeiro `banco-de-dados/01_usuarios.sql`.
6. Depois importe `banco-de-dados/02_valores.sql`.
7. Abra `http://localhost/NEXUS/` no navegador.

## Arquivos do banco

O banco esta separado em dois arquivos principais:

1. `01_usuarios.sql`: cria o banco, recria a tabela `Usuario` e cadastra os usuarios de teste.
2. `02_valores.sql`: cria academias, alunos, matriculas, mensalidades, receitas, despesas, relatorios financeiros e solicitacoes de suporte.

Importe sempre nesta ordem, porque os valores dependem dos usuarios para vincular cliente, atendente, gerente e admin responsavel.

## Usuarios de teste

Todos usam a senha `123456`.

| Perfil | E-mail |
| --- | --- |
| ADMIN | admin@nexus.com |
| GERENTE | gerente@nexus.com |
| ATENDENTE | atendente@nexus.com |
| CLIENTE / ALUNO | cliente@nexus.com |

O usuario `cliente@nexus.com` esta vinculado ao aluno de teste Carlos Diego.

## Estrutura principal

Organizacao por tecnologia:

- `html/`: arquivos HTML estaticos de apoio, como `mapa-do-site.html`.
- `recursos/js/`: JavaScript do menu, confirmacoes, particulas e acessibilidade.
- `banco-de-dados/`: scripts SQL separados em usuarios e valores.
- `recursos/css/`: estilos, responsividade e alto contraste.
- `recursos/imagens/`: imagens usadas pela interface.
- `*.php` na raiz: rotas principais do sistema, login, paineis e configuracoes.
- `paginas/`: paginas PHP dos CRUDs e paginas separadas do aluno/cliente.
- `componentes/`: componentes PHP compartilhados, como layout e CRUD generico.
- `configuracao/`: conexao, autenticacao, funcoes e entidades PHP.

Rotas principais:

- `entrar.php`: login e redirecionamento por perfil.
- `recuperar-senha.php`: reconhecimento por e-mail/CPF e redefinicao de senha.
- `sair.php`: logout.
- `configuracoes.php`: pagina propria de configuracoes, perfil, verificacao de senha, troca de senha e preferencias.
- `painel-administrador.php`: dashboard do ADMIN.
- `painel-gerente.php`: dashboard financeiro do GERENTE.
- `painel-atendente.php`: dashboard do ATENDENTE.
- `painel-cliente.php`: dashboard resumido do CLIENTE/ALUNO.
- `paginas/meus-dados.php`: dados pessoais e academia do CLIENTE/ALUNO.
- `paginas/minha-matricula.php`: matriculas do CLIENTE/ALUNO.
- `paginas/minhas-mensalidades.php`: mensalidades do CLIENTE/ALUNO.
- `paginas/meu-historico.php`: historico consolidado do CLIENTE/ALUNO.

## Controle de acesso

As paginas usam verificacao de perfil. Um CLIENTE nao acessa CRUD administrativo digitando a URL, um ATENDENTE nao acessa telas financeiras do GERENTE, e um GERENTE nao acessa gerenciamento de usuarios do ADMIN.
 
 http://localhost:8080/entrar.php