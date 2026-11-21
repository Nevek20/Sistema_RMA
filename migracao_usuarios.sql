-- Sistema RMA - Migração para login com usuários
-- Rode UMA vez num banco que já existia antes desta versão.
-- Banco novo não precisa: o schema.sql já vem com tudo.

USE controle_rma;

CREATE TABLE IF NOT EXISTS usuarios (
    id         INT                         NOT NULL AUTO_INCREMENT,
    usuario    VARCHAR(50)                 NOT NULL,
    senha_hash VARCHAR(255)                NOT NULL,
    perfil     ENUM('admin', 'operador')   NOT NULL DEFAULT 'operador',
    criado_em  DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario)
);

-- Quem fez cada ação passa a aparecer no histórico
ALTER TABLE logs ADD COLUMN usuario VARCHAR(50) NULL AFTER id;
