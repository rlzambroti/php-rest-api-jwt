-- ============================================================
-- Migração do Banco de Dados - API Acadêmica
-- ============================================================
-- Execute este arquivo no MySQL/MariaDB para criar as tabelas.
-- Comando: mysql -u root -p < database/migration.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS api_academica
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE api_academica;

-- ──────────────────────────────────────────────────────────
-- Tabela: usuarios
-- Armazena as credenciais de acesso à API
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT          NOT NULL AUTO_INCREMENT,
    usuario    VARCHAR(100) NOT NULL,
    senha      VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt da senha',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────
-- Tabela: refresh_tokens
-- Armazena os refresh tokens ativos
-- Um usuário pode ter múltiplos refresh tokens (múltiplos dispositivos)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id          INT          NOT NULL AUTO_INCREMENT,
    usuario_id  INT          NOT NULL,
    token       VARCHAR(500) NOT NULL,
    expires_at  DATETIME     NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY  uk_token (token),
    KEY         idx_expires_at (expires_at),
    CONSTRAINT  fk_rt_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────
-- Tabela: clientes
-- Cadastro de clientes (CRUD demonstrativo)
-- ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clientes (
    id               INT          NOT NULL AUTO_INCREMENT,
    cpf              VARCHAR(14)  NOT NULL COMMENT 'Formato: XXX.XXX.XXX-XX',
    nome             VARCHAR(255) NOT NULL,
    data_nascimento  DATE         NOT NULL,
    whatsapp         VARCHAR(20)      NULL,
    email            VARCHAR(255)     NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_cpf (cpf),
    KEY        idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios
(id, usuario, senha, created_at)
VALUES(1, 'admin', '$2y$10$aiMOvXC8z3FHQ5Ua2a9QI.eMI4vdhnhMKrdzhNqoqN5QGz9iwjdtC', '2026-03-18 16:08:59');