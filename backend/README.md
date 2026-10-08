# Backend do NEXUS

Esta pasta reúne a camada de configuração e persistência do NEXUS:

- `configuracao/conexao.php`: PDO e conexão MySQL/MariaDB, com verificações do esquema.
- `configuracao/autenticacao.php`: sessões, login e regras de acesso por perfil.
- `configuracao/entidades.php`: definição dos módulos e campos usados pelas telas CRUD.
- `configuracao/funcoes.php`: funções auxiliares de domínio e formatação.
- `banco-de-dados/`: scripts de estrutura e dados iniciais para desenvolvimento/testes.

O acesso HTTP direto a esta pasta é bloqueado por `.htaccess`. A aplicação inclui os arquivos do backend pelo PHP. Os scripts SQL devem ser importados pelo administrador do banco de dados e não executados via navegador.

> Nota arquitetural: o NEXUS original é uma aplicação PHP renderizada no servidor. Esta reorganização separa os diretórios e recursos de configuração/persistência, mantendo compatibilidade das páginas. Algumas consultas e operações de negócio ainda são invocadas pelos controladores PHP dentro de `frontend/`; não foi introduzida uma API REST independente nesta versão.
