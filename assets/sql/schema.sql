-- Sistema RMA - Schema do banco de dados
-- MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS controle_rma
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE controle_rma;

-- -------------------------------------------
-- Clientes
-- -------------------------------------------
CREATE TABLE clientes (
    id   INT          NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clientes_nome (nome)
);

-- -------------------------------------------
-- Modelos de processador
-- -------------------------------------------
CREATE TABLE processadores (
    id     INT          NOT NULL AUTO_INCREMENT,
    modelo VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_processadores_modelo (modelo)
);

-- -------------------------------------------
-- Vínculos: processador ↔ cliente
-- -------------------------------------------
CREATE TABLE processador_cliente (
    id             INT          NOT NULL AUTO_INCREMENT,
    cliente_id     INT          NOT NULL,
    processador_id INT          NOT NULL,
    serial_number  VARCHAR(255) NOT NULL,
    data_cadastro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pc_serial (serial_number),
    CONSTRAINT fk_pc_cliente     FOREIGN KEY (cliente_id)     REFERENCES clientes(id),
    CONSTRAINT fk_pc_processador FOREIGN KEY (processador_id) REFERENCES processadores(id)
);
