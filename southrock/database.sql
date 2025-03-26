CREATE DATABASE southrock;

USE southrock;

CREATE TABLE produtos (
    sku INT NOT NULL PRIMARY KEY,
    produto VARCHAR(255) NOT NULL,
    grupo VARCHAR(255) NOT NULL
);

CREATE TABLE tipo_usuario (
    id INT NOT NULL PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL
);

CREATE TABLE usuarios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario INT NOT NULL,
    cnpj VARCHAR(20),
    responsavel VARCHAR(255),
    endereco VARCHAR(255),
    FOREIGN KEY (tipo_usuario) REFERENCES tipo_usuario(id)
);

INSERT INTO produtos (sku, produto, grupo) VALUES
    (122346, 'cafe normal', 'consumiveis'),
    (3213, 'leite', 'usaveis'),
    (666, 'demonio', 'seres misticos');

INSERT INTO tipo_usuario (id, descricao) VALUES
    (1, 'matriz'),
    (2, 'loja');

INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco) VALUES
    ('admin', '123456', 1, NULL, NULL, NULL),
    ('star', 'teste', 2, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123');