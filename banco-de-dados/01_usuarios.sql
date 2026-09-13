-- NEXUS - estrutura e dados iniciais de usuarios
-- Importe este arquivo antes de 02_valores.sql.

CREATE DATABASE IF NOT EXISTS nexus
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE nexus;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Usuario;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Usuario (
  idUsuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  sobrenome VARCHAR(120) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  email VARCHAR(160) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  senhaCriptografada CHAR(64) NOT NULL,
  perfil ENUM('ADMIN', 'GERENTE', 'ATENDENTE', 'CLIENTE') NOT NULL,
  criadoEm TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Senha de todos os acessos de demonstracao: 123456
-- O valor armazenado e o hash SHA-256 usado pela aplicacao.
INSERT INTO Usuario
  (idUsuario, nome, sobrenome, cpf, email, telefone, senhaCriptografada, perfil)
VALUES
  (1, 'Ana', 'Administrador', '000.000.000-01', 'admin@nexus.com', '(11) 99999-0001', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ADMIN'),
  (2, 'Gustavo', 'Gerente', '000.000.000-02', 'gerente@nexus.com', '(11) 99999-0002', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'GERENTE'),
  (3, 'Aline', 'Atendente', '000.000.000-03', 'atendente@nexus.com', '(11) 99999-0003', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ATENDENTE'),
  (4, 'Carlos', 'Diego', '111.111.111-11', 'cliente@nexus.com', '(11) 98888-1111', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'CLIENTE'),
  (5, 'Beatriz', 'Atendente', '000.000.000-05', 'atendente2@nexus.com', '(11) 99999-0005', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ATENDENTE');
