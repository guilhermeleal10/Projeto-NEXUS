# NEXUS — Sistema de Gestão de Academias e Projetos Esportivos

O **NEXUS** é um sistema web para apoiar a gestão administrativa e financeira de academias, projetos esportivos locais e iniciativas comunitárias. Esta versão mantém a identidade visual, o nome, a temática esportiva e os recursos de tema claro/escuro e acessibilidade. O código foi organizado em diretórios `frontend/` e `backend/`, com documentação de instalação, uso e testes.

> **Importante:** neste sistema, o perfil **CLIENTE** representa o aluno da academia. Esse perfil consulta os próprios dados, matrícula, mensalidades, histórico e suporte; não administra os módulos financeiros.

## 1. Objetivo e descrição

O NEXUS centraliza informações que podem ficar dispersas em planilhas ou registros manuais. A aplicação reúne cadastros, matrículas, mensalidades, movimentações financeiras, relatórios e solicitações de suporte em uma interface web com acesso por perfil.

O sistema foi organizado para quatro perfis:

- **ADMIN:** administra usuários e academias, acompanha cadastros e registros, acessa relatórios e configurações gerais e acompanha solicitações de suporte.
- **GERENTE:** acompanha indicadores financeiros, receitas, despesas e relatórios; consulta alunos, matrículas e mensalidades e pode abrir solicitações para o administrador.
- **ATENDENTE:** realiza tarefas operacionais, como cadastro de alunos, matrículas e mensalidades, consulta pagamentos e acompanhamento de suporte.
- **CLIENTE / ALUNO:** consulta os próprios dados, matrícula, mensalidades e histórico, além de enviar solicitações de suporte.

## 2. Funcionalidades

- Login, encerramento de sessão e direcionamento ao painel correspondente ao perfil.
- Recuperação de senha e configurações de conta.
- Cadastro, consulta, edição e exclusão de registros (CRUD) conforme as permissões.
- Gestão de usuários, atendentes, academias, alunos, matrículas e mensalidades.
- Gestão de receitas, despesas e relatórios financeiros.
- Solicitações de suporte e acompanhamento por status.
- Painéis com gráficos e indicadores adequados a cada perfil.
- Busca em listagens, validação de campos obrigatórios, mensagens de resultado e confirmação antes de excluir.
- Interface responsiva e acessibilidade: alternância entre tema claro e escuro, painel de acessibilidade flutuante, ajuste do tamanho do texto e alto contraste.

### Planos e mensalidades

As matrículas recebem um código gerado automaticamente. Os planos de demonstração definidos no projeto são:

| Plano | Valor de referência |
|---|---:|
| Básico | Gratuito |
| Maromba | R$ 49,90 |
| Shape | R$ 99,99 |

Ao criar uma mensalidade, o sistema utiliza o valor associado ao plano da matrícula selecionada. Confirme os valores e as regras de negócio antes de usar o sistema fora de um ambiente de demonstração.

## 3. Tecnologias

- **PHP** para páginas, autenticação, sessões, permissões e operações CRUD.
- **PDO** para acesso ao banco de dados.
- **MySQL/MariaDB** para persistência dos dados.
- **HTML, CSS e JavaScript** para interface, interações e acessibilidade.
- **XAMPP** para execução local tradicional.
- **Docker Compose** como alternativa para subir a aplicação e o banco em containers.

## 4. Requisitos

Escolha uma das opções de execução:

### Opção A — XAMPP

- Windows, macOS ou Linux com uma instalação compatível do XAMPP.
- Apache, PHP com PDO MySQL e MySQL/MariaDB habilitados.
- Navegador atualizado.

### Opção B — Docker

- Docker Desktop (ou Docker Engine com Docker Compose) instalado e em execução.
- Portas `8080` e `3306` disponíveis, conforme a configuração local.

## 5. Instalação e execução com XAMPP

1. Extraia o ZIP do projeto.
2. Copie a pasta do projeto para a pasta `htdocs` do XAMPP. Para seguir os endereços deste guia, deixe a pasta com o nome `NEXUS`, por exemplo `C:\xampp\htdocs\NEXUS`.
3. Abra o painel do XAMPP e inicie **Apache** e **MySQL**.
4. Acesse `http://localhost/phpmyadmin/`.
5. Importe o script `backend/banco-de-dados/01_usuarios.sql`.
6. Em seguida, importe `backend/banco-de-dados/02_valores.sql`.
7. Acesse `http://localhost/NEXUS/`. Se necessário, abra diretamente `http://localhost/NEXUS/frontend/entrar.php`.

**Ordem dos scripts:** importe primeiro `01_usuarios.sql` e depois `02_valores.sql`, pois os dados de exemplo dependem dos usuários que serão referenciados.

Se a pasta tiver outro nome, ajuste `NEXUS` na URL. Caso a conexão com o banco falhe, confira as configurações de conexão em `backend/configuracao/conexao.php` e confirme que o serviço MySQL está ativo.

## 6. Instalação e execução com Docker

Na pasta que contém `compose.yaml`, execute:

```bash
docker compose up --build
```

Quando os containers estiverem prontos, abra `http://localhost:8080`. No Docker, o Apache publica diretamente `frontend/` como raiz do site; `backend/` fica fora do diretório público. No XAMPP, o `index.php` da raiz encaminha para `frontend/entrar.php`.

Para encerrar os serviços:

```bash
docker compose down
```

Para remover também o volume do banco e recriar os dados iniciais na próxima inicialização:

```bash
docker compose down -v
```

> **Atenção:** `docker compose down -v` apaga os dados persistidos no volume do banco. Use esse comando apenas quando quiser realmente reiniciar os dados de demonstração. Os scripts SQL de inicialização são importados automaticamente pelo container do banco na primeira criação do volume; se o volume já existir, eles não são reaplicados automaticamente.

As credenciais e configurações de banco definidas no `compose.yaml` são destinadas ao ambiente local de desenvolvimento. Não as reutilize em produção.

## 7. Usuários de demonstração

Os scripts SQL incluem contas de teste. A senha inicial indicada para todas elas é `123456`.

| Perfil | E-mail de acesso | Senha inicial |
|---|---|---|
| Administrador | `admin@nexus.com` | `123456` |
| Gerente | `gerente@nexus.com` | `123456` |
| Atendente | `atendente@nexus.com` | `123456` |
| Cliente / aluno | `cliente@nexus.com` | `123456` |

A conta de cliente está associada ao aluno de demonstração Carlos Diego.

**Segurança:** essas credenciais são públicas e servem apenas para testes locais. Troque-as antes de qualquer implantação acessível por outras pessoas e não utilize dados pessoais reais em demonstrações públicas.

## 8. Como utilizar o sistema

1. Abra o endereço local e entre com uma das contas de demonstração.
2. Confira se o painel apresentado corresponde ao perfil usado.
3. Utilize o menu lateral para acessar os módulos disponíveis para esse perfil.
4. Para cadastrar um registro, abra o módulo correspondente e preencha os campos obrigatórios.
5. Para atualizar um registro, localize-o na listagem e escolha a ação de edição.
6. Para excluir, revise o registro e confirme a operação somente se tiver certeza.
7. Use as buscas e os indicadores do painel para localizar registros e acompanhar os dados.
8. No perfil de cliente/aluno, consulte os dados pessoais, matrícula, mensalidades e histórico; utilize o módulo de suporte para registrar uma solicitação.
9. Encerre a sessão ao terminar, especialmente em computadores compartilhados.

### Diferenças por perfil

| Módulo/ação | ADMIN | GERENTE | ATENDENTE | CLIENTE / ALUNO |
|---|---|---|---|---|
| Usuários e configurações gerais | Gerencia | Acesso limitado | Não administra | Apenas a própria conta/configurações disponíveis |
| Academias e alunos | Administra e audita | Consulta | Cadastra e consulta conforme permissões | Consulta apenas seus dados pessoais e vínculo |
| Matrículas e mensalidades | Audita/gerencia | Consulta | Cadastra e acompanha pagamentos | Consulta os próprios registros |
| Receitas, despesas e relatórios financeiros | Acesso administrativo | Gerencia e consulta | Sem acesso gerencial | Sem acesso |
| Suporte | Acompanha e administra | Abre/acompanha | Abre/acompanha | Abre/acompanha suas solicitações |

A disponibilidade exata das ações é determinada pelas verificações de perfil implementadas nas páginas. O conteúdo acima descreve a finalidade geral dos perfis.

## 9. Como testar

Faça os testes em uma cópia local com os dados de demonstração. Antes de começar, confirme que o banco foi importado sem erros e que a tela de login abre.

### Testes de acesso e permissões

- [ ] Entre como `admin@nexus.com` e confirme que aparece o painel de administrador.
- [ ] Entre como `gerente@nexus.com` e confira os indicadores financeiros.
- [ ] Entre como `atendente@nexus.com` e confira as funções operacionais.
- [ ] Entre como `cliente@nexus.com` e confira os dados do aluno associado.
- [ ] Encerre a sessão e confirme que o sistema volta à tela de login.
- [ ] Tente abrir diretamente uma página restrita a outro perfil; o acesso deve ser bloqueado ou redirecionado.

### Testes de cadastros (CRUD)

- [ ] Cadastre um registro de teste em um módulo permitido ao perfil atual.
- [ ] Confira se ele aparece na listagem e se a busca consegue localizá-lo.
- [ ] Edite um campo e confirme que a alteração persiste após recarregar a página.
- [ ] Teste a validação deixando vazio um campo obrigatório.
- [ ] Teste a exclusão de um registro descartável e confira a confirmação apresentada.

### Testes de matrículas e mensalidades

- [ ] Crie uma matrícula de teste com um dos planos disponíveis.
- [ ] Confira se um código de matrícula foi gerado.
- [ ] Crie uma mensalidade para essa matrícula e confirme o valor do plano.
- [ ] Verifique o status da mensalidade e como ele aparece na consulta do aluno.

### Testes financeiros e suporte

- [ ] Como gerente, confira os gráficos de receitas e despesas e os relatórios disponíveis.
- [ ] Crie dados de teste, quando permitido, e verifique se os indicadores refletem os registros.
- [ ] Abra uma solicitação de suporte com outro perfil e confira se aparece na área correspondente.
- [ ] Confira se o status da solicitação é exibido corretamente aos perfis autorizados.

### Critério de aprovação

Considere um teste aprovado quando a ação esperada ocorrer sem erro, os dados persistirem no banco, o resultado aparecer na interface e as restrições de acesso forem respeitadas. Registre mensagens de erro e os passos necessários para reproduzi-las. Este roteiro é manual; a execução dele depende do ambiente local e não representa uma certificação automática do sistema.

## 10. Estrutura dos arquivos

- `index.php`: entrada na raiz; encaminha para o login dentro de `frontend/`.
- `frontend/`: camada web de apresentação e páginas PHP que entregam a interface.
  - `frontend/entrar.php`, `sair.php` e `recuperar-senha.php`: autenticação, encerramento de sessão e recuperação de senha.
  - `frontend/painel-*.php`: painéis de administrador, gerente, atendente e cliente.
  - `frontend/paginas/`: módulos de cadastro e páginas específicas do aluno.
  - `frontend/componentes/`: layout compartilhado, área do cliente e componentes CRUD.
  - `frontend/recursos/css/`: identidade visual, responsividade, tema claro/escuro e alto contraste.
  - `frontend/recursos/js/`: interações, alternância de tema e painel flutuante de acessibilidade.
  - `frontend/recursos/imagens/`: imagens do NEXUS, incluindo o fundo de login.
  - `frontend/html/mapa-do-site.html`: mapa estático de navegação.
- `backend/`: configuração, autenticação, definições de entidades e scripts de banco de dados.
  - `backend/configuracao/`: conexão PDO, autenticação, funções compartilhadas e definição das entidades.
  - `backend/banco-de-dados/`: scripts SQL de estrutura e dados demonstrativos.
  - `backend/README.md`: descrição dessa camada.
- `Dockerfile`, `compose.yaml`, `DOCKER.md`: execução em containers.

**Nota sobre a arquitetura:** a estrutura de diretórios foi separada sem reescrever a aplicação PHP original como SPA/API. As páginas continuam sendo renderizadas no servidor; algumas consultas e operações de negócio ainda são chamadas pelos controladores PHP em `frontend/`. Os arquivos de configuração e SQL estão agrupados em `backend/`, e o acesso HTTP direto a essa pasta é bloqueado por `.htaccess`.

## 11. Banco de dados

O banco é preparado pelos seguintes arquivos:

1. `backend/banco-de-dados/01_usuarios.sql`: cria a estrutura de usuários e as contas iniciais.
2. `backend/banco-de-dados/02_valores.sql`: inclui academias, alunos, matrículas, mensalidades, receitas, despesas, relatórios financeiros e solicitações de suporte de demonstração.

A ordem de importação é importante para manter as referências entre usuários e registros relacionados. Se quiser recomeçar os testes, faça backup dos dados antes de recriar o banco.

## 12. Solução de problemas

- **A página não abre:** confirme que o Apache ou os containers estão ativos e verifique a URL e o nome da pasta.
- **Erro de conexão com o banco:** confirme que MySQL/MariaDB está ativo, que os scripts foram importados e que as credenciais de conexão correspondem ao ambiente escolhido.
- **Login não funciona:** use um e-mail e senha de demonstração exatamente como documentados e confirme que os scripts SQL terminaram sem erros.
- **Tabelas ou dados ausentes:** importe os scripts na ordem indicada. No Docker, confira se o volume já existia; scripts de inicialização não são reaplicados em um banco já inicializado.
- **Acesso negado a um módulo:** confirme o perfil usado; a aplicação restringe módulos conforme as permissões.
- **Mudanças de CSS/JS não aparecem:** atualize a página sem usar o cache do navegador.

## 13. Limites e recomendações

O NEXUS é um projeto de gestão e demonstração acadêmica/local. Antes de utilizá-lo em produção, faça revisão de segurança, substitua credenciais padrão, configure segredos fora do código, habilite HTTPS, revise recuperação de senha e permissões, valide backups e restauração e teste todos os fluxos com dados controlados. Não exponha o ambiente de demonstração à internet com as credenciais iniciais.

---

**NEXUS — Gestão de academias e projetos esportivos.** Esta versão mantém a identidade original e acrescenta a separação dos diretórios `frontend/` e `backend/`, preservando o tema claro/escuro e o painel flutuante de acessibilidade.

## Tema claro e acessibilidade

O NEXUS oferece tema escuro (padrão) e tema claro, mantendo a identidade visual em tons de ciano/azul. Os controles ficam em dois botões flutuantes no canto inferior direito: o botão com o ícone de sol/lua alterna o tema, e o botão de acessibilidade abre um painel separado.

No painel de acessibilidade, é possível diminuir o texto, restaurar o tamanho padrão, aumentar o texto e ativar/desativar o alto contraste. O painel também pode ser fechado pelo botão `X`, clicando fora dele ou pressionando `Esc`. Os controles continuam disponíveis em telas de login, recuperação de senha e páginas internas autenticadas.

As preferências de tema, tamanho de fonte e contraste são guardadas no navegador por meio de `localStorage` e/ou cookies, quando essas funcionalidades estão permitidas. O tema escuro continua sendo usado como padrão quando não existe preferência salva. O tema claro muda superfícies, campos, tabelas e tipografia sem substituir logotipo, nome, imagem de login ou a paleta ciano característica do NEXUS.

### Como testar as preferências

1. Abra o sistema e localize os dois botões circulares no canto inferior direito.
2. Clique no botão de sol para ativar o tema claro; navegue para outra página e confirme que a escolha foi preservada.
3. Clique novamente no botão de lua para voltar ao tema escuro.
4. Abra o botão de acessibilidade e teste o aumento/diminuição da fonte e o alto contraste.
5. Feche o painel pelo `X`, clicando fora ou com `Esc`; confira também se o foco do teclado pode alcançar os botões.
6. Repita o teste nas telas de login e recuperação de senha.

