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
    id INT NOT NULL PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario INT NOT NULL,
    FOREIGN KEY (tipo_usuario) REFERENCES tipo_usuario(id)
);

INSERT INTO produtos (sku, produto, grupo) VALUES
    (122346, 'cafe normal', 'consumiveis'),
    (3213, 'leite', 'usaveis'),
    (666, 'demonio', 'seres misticos');

INSERT INTO tipo_usuario (id, descricao) VALUES
    (1, 'matriz'),
    (2, 'loja');

INSERT INTO usuarios (id, username, password, tipo_usuario) VALUES
    (1, 'admin', '123456', 1),
    (2, 'star', 'teste', 2);