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
DROP TABLE IF EXISTS Usuario;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS Usuario (
  idUsuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  sobrenome VARCHAR(120) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  email VARCHAR(160) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  senhaCriptografada VARCHAR(255) NOT NULL,
  perfil ENUM('ADMIN', 'GERENTE', 'ATENDENTE', 'CLIENTE') NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO Usuario (idUsuario, nome, sobrenome, cpf, email, telefone, senhaCriptografada, perfil) VALUES
(1, 'Admin', 'Master', '000.000.000-01', 'admin@nexus.com', '(11) 90000-0001', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ADMIN'),
(2, 'Marina', 'Costa', '000.000.000-02', 'gerente@nexus.com', '(11) 90000-0002', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'GERENTE'),
(3, 'Lucas', 'Ribeiro', '000.000.000-03', 'atendente@nexus.com', '(11) 90000-0003', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ATENDENTE'),
(4, 'Carlos', 'Diego', '111.111.111-11', 'cliente@nexus.com', '(11) 98888-1111', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'CLIENTE'),
(5, 'Rafael', 'Nunes', '000.000.000-05', 'rafael@nexus.com', '(11) 90000-0005', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ATENDENTE')
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  sobrenome = VALUES(sobrenome),
  cpf = VALUES(cpf),
  email = VALUES(email),
  telefone = VALUES(telefone),
  senhaCriptografada = VALUES(senhaCriptografada),
  perfil = VALUES(perfil);
