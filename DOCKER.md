# Executar com Docker

Com o Docker Desktop em execucao, rode na pasta do projeto:

```bash
docker compose up --build
```

Abra http://localhost:8080. Os scripts em `banco-de-dados/` sao importados
automaticamente somente na primeira criacao do banco.

Para encerrar os containers:

```bash
docker compose down
```

Para apagar os dados persistidos e recriar o banco com os dados iniciais:

```bash
docker compose down -v
```
