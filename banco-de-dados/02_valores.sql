CREATE DATABASE IF NOT EXISTS nexus DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
USE nexus;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS SolicitacaoSuporte;
DROP TABLE IF EXISTS RelatorioFinanceiro;
DROP TABLE IF EXISTS Despesa;
DROP TABLE IF EXISTS Receita;
DROP TABLE IF EXISTS Mensalidade;
DROP TABLE IF EXISTS Matricula;
DROP TABLE IF EXISTS Aluno;
DROP TABLE IF EXISTS Academia;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS Academia (
  idAcademia INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(160) NOT NULL,
  cnpj VARCHAR(18) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  endereco VARCHAR(255) NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Aluno (
  idAluno INT AUTO_INCREMENT PRIMARY KEY,
  idUsuario INT NULL UNIQUE,
  nome VARCHAR(160) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  dataNascimento DATE NOT NULL,
  idAcademia INT NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aluno_usuario
    FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_aluno_academia
    FOREIGN KEY (idAcademia) REFERENCES Academia(idAcademia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Matricula (
  idMatricula INT AUTO_INCREMENT PRIMARY KEY,
  dataMatricula DATE NOT NULL,
  status ENUM('Ativa', 'Cancelada', 'Trancada') NOT NULL DEFAULT 'Ativa',
  idAluno INT NOT NULL,
  idAcademia INT NOT NULL,
  idAtendente INT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_matricula_aluno
    FOREIGN KEY (idAluno) REFERENCES Aluno(idAluno)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matricula_academia
    FOREIGN KEY (idAcademia) REFERENCES Academia(idAcademia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matricula_atendente
    FOREIGN KEY (idAtendente) REFERENCES Usuario(idUsuario)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Mensalidade (
  idMensalidade INT AUTO_INCREMENT PRIMARY KEY,
  valor DECIMAL(10,2) NOT NULL,
  dataVencimento DATE NOT NULL,
  dataPagamento DATE NULL,
  status ENUM('Pendente', 'Pago', 'Atrasado') NOT NULL DEFAULT 'Pendente',
  idMatricula INT NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (valor >= 0),
  CONSTRAINT fk_mensalidade_matricula
    FOREIGN KEY (idMatricula) REFERENCES Matricula(idMatricula)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Receita (
  idReceita INT AUTO_INCREMENT PRIMARY KEY,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  dataReceita DATE NOT NULL,
  idAcademia INT NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (valor >= 0),
  CONSTRAINT fk_receita_academia
    FOREIGN KEY (idAcademia) REFERENCES Academia(idAcademia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Despesa (
  idDespesa INT AUTO_INCREMENT PRIMARY KEY,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  dataDespesa DATE NOT NULL,
  categoria ENUM('Aluguel', 'Energia', 'Agua', 'Funcionarios', 'Manutencao', 'Outros') NOT NULL DEFAULT 'Outros',
  idAcademia INT NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (valor >= 0),
  CONSTRAINT fk_despesa_academia
    FOREIGN KEY (idAcademia) REFERENCES Academia(idAcademia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS RelatorioFinanceiro (
  idRelatorio INT AUTO_INCREMENT PRIMARY KEY,
  mesReferencia TINYINT NOT NULL,
  anoReferencia SMALLINT NOT NULL,
  totalReceitas DECIMAL(10,2) NOT NULL DEFAULT 0,
  totalDespesas DECIMAL(10,2) NOT NULL DEFAULT 0,
  saldoFinal DECIMAL(10,2) NOT NULL DEFAULT 0,
  idAcademia INT NOT NULL,
  idGerente INT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (mesReferencia BETWEEN 1 AND 12),
  CHECK (anoReferencia >= 2020),
  CHECK (totalReceitas >= 0),
  CHECK (totalDespesas >= 0),
  UNIQUE (mesReferencia, anoReferencia, idAcademia),
  CONSTRAINT fk_relatorio_academia
    FOREIGN KEY (idAcademia) REFERENCES Academia(idAcademia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_relatorio_gerente
    FOREIGN KEY (idGerente) REFERENCES Usuario(idUsuario)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS SolicitacaoSuporte (
  idSolicitacao INT AUTO_INCREMENT PRIMARY KEY,
  descricao TEXT NOT NULL,
  status ENUM('Aberta', 'Em andamento', 'Resolvida') NOT NULL DEFAULT 'Aberta',
  dataAbertura DATE NOT NULL,
  dataFechamento DATE NULL,
  idUsuarioSolicitante INT NULL,
  idAdminResponsavel INT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (dataFechamento IS NULL OR dataFechamento >= dataAbertura),
  CONSTRAINT fk_suporte_usuario
    FOREIGN KEY (idUsuarioSolicitante) REFERENCES Usuario(idUsuario)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_suporte_admin
    FOREIGN KEY (idAdminResponsavel) REFERENCES Usuario(idUsuario)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO Academia (idAcademia, nome, cnpj, telefone, endereco) VALUES
(1, 'NEXUS Fit Centro', '12.345.678/0001-90', '(11) 3333-1010', 'Rua Neon, 120 - Centro'),
(2, 'NEXUS Comunidade Norte', '98.765.432/0001-12', '(11) 3333-2020', 'Av. Movimento, 450 - Zona Norte')
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  cnpj = VALUES(cnpj),
  telefone = VALUES(telefone),
  endereco = VALUES(endereco);

INSERT INTO Aluno (idAluno, idUsuario, nome, cpf, telefone, email, dataNascimento, idAcademia) VALUES
(1, 4, 'Carlos Diego', '111.111.111-11', '(11) 98888-1111', 'cliente@nexus.com', '1998-04-12', 1),
(2, NULL, 'Ana Silva', '222.222.222-22', '(11) 98888-2222', 'ana@email.com', '2001-09-20', 1),
(3, NULL, 'Bruno Pereira', '333.333.333-33', '(11) 98888-3333', 'bruno@email.com', '1994-01-08', 2),
(4, NULL, 'Fernanda Lima', '444.444.444-44', '(11) 98888-4444', 'fernanda@email.com', '1999-11-03', 2)
ON DUPLICATE KEY UPDATE
  idUsuario = VALUES(idUsuario),
  nome = VALUES(nome),
  cpf = VALUES(cpf),
  telefone = VALUES(telefone),
  email = VALUES(email),
  dataNascimento = VALUES(dataNascimento),
  idAcademia = VALUES(idAcademia);

INSERT INTO Matricula (idMatricula, dataMatricula, status, idAluno, idAcademia, idAtendente) VALUES
(1, '2026-03-05', 'Ativa', 1, 1, 3),
(2, '2026-04-11', 'Ativa', 2, 1, 3),
(3, '2026-05-02', 'Ativa', 3, 2, 5),
(4, '2026-05-18', 'Trancada', 4, 2, 5)
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO Mensalidade (idMensalidade, valor, dataVencimento, dataPagamento, status, idMatricula) VALUES
(1, 129.90, '2026-06-10', '2026-06-06', 'Pago', 1),
(2, 129.90, '2026-06-12', NULL, 'Pendente', 2),
(3, 99.90, '2026-06-05', NULL, 'Atrasado', 3),
(4, 99.90, '2026-06-20', NULL, 'Pendente', 4)
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO Receita (idReceita, descricao, valor, dataReceita, idAcademia) VALUES
(1, 'Mensalidades plano performance', 8200.00, '2026-06-02', 1),
(2, 'Aulas personalizadas', 2750.00, '2026-06-08', 1),
(3, 'Convenio comunitario', 3600.00, '2026-06-04', 2),
(4, 'Mensalidades maio', 10400.00, '2026-05-10', 1),
(5, 'Eventos esportivos', 1900.00, '2026-05-18', 2)
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO Despesa (idDespesa, descricao, valor, dataDespesa, categoria, idAcademia) VALUES
(1, 'Aluguel unidade centro', 2800.00, '2026-06-05', 'Aluguel', 1),
(2, 'Energia eletrica', 1180.00, '2026-06-06', 'Energia', 1),
(3, 'Manutencao de esteiras', 760.00, '2026-06-07', 'Manutencao', 1),
(4, 'Equipe de apoio', 2100.00, '2026-06-04', 'Funcionarios', 2),
(5, 'Materiais esportivos', 640.00, '2026-05-12', 'Outros', 2)
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO RelatorioFinanceiro (idRelatorio, mesReferencia, anoReferencia, totalReceitas, totalDespesas, saldoFinal, idAcademia, idGerente) VALUES
(1, 6, 2026, 10950.00, 4740.00, 6210.00, 1, 2),
(2, 5, 2026, 1900.00, 640.00, 1260.00, 2, 2)
ON DUPLICATE KEY UPDATE saldoFinal = VALUES(saldoFinal);

INSERT INTO SolicitacaoSuporte (idSolicitacao, descricao, status, dataAbertura, dataFechamento, idUsuarioSolicitante, idAdminResponsavel) VALUES
(1, 'Erro ao processar mensalidade', 'Aberta', '2026-06-08', NULL, 3, 1),
(2, 'Duvida sobre minha matricula', 'Em andamento', '2026-06-07', NULL, 4, 1),
(3, 'Duvida sobre relatorio mensal', 'Resolvida', '2026-05-30', '2026-06-01', 2, 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);
