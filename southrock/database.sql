CREATE DATABASE southrock;

USE southrock;

CREATE TABLE produtos (
    sku INT NOT NULL PRIMARY KEY,
    produto VARCHAR(255) NOT NULL,
    grupo VARCHAR(255) NOT NULL,
    unidade_medida VARCHAR(10) DEFAULT 'UN'
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
    cep VARCHAR(10),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    nome VARCHAR(255),
    FOREIGN KEY (tipo_usuario) REFERENCES tipo_usuario(id)
);

CREATE TABLE filiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_filial VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20) NOT NULL,
    responsavel VARCHAR(255),
    endereco VARCHAR(255),
    cep VARCHAR(10),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    estado CHAR(2)
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_pedido ENUM('requisicao', 'troca', 'doacao', 'devolucao') NOT NULL DEFAULT 'requisicao',
    status ENUM('novo', 'processo', 'finalizado') NOT NULL DEFAULT 'novo',
    filial_id INT,
    usuario_id INT,
    observacoes TEXT,
    data_processamento DATETIME,
    data_finalizacao DATETIME,
    FOREIGN KEY (filial_id) REFERENCES filiais(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    sku INT,
    quantidade INT,
    observacao TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (sku) REFERENCES produtos(sku)
);

-- Inserção de dados nas tabelas

-- Produtos
INSERT INTO produtos (sku, produto, grupo, unidade_medida) VALUES
    (600023065, '3221', 'DIVERSOS SKUS', 'UN'),
    (600028602, '27945', 'THERMAL CUTOFF W/LEAD. RED 51', 'PC'),
    (34233001, 'A ESTRELICIA', '600024382', 'CX');

-- Tipos de usuário
INSERT INTO tipo_usuario (id, descricao) VALUES
    (1, 'matriz'),
    (2, 'loja');

-- Usuários
INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome) VALUES
    ('admin', '123456', 1, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '04929220', 'alto do rivieira', 'São Paulo', 'SP', 'João Silva'),
    ('star01', 'teste', 2, '07.984.267/0001-00', 'Igor Costa', 'Av Paulista, 900 - ANDAR 10 PARTE', '01310-940', 'Bela Vista', 'São Paulo', 'SP', 'Igor Costa'),
    ('star02', 'teste', 2, '07.984.267/0005-33', 'Eduardo Oliveira', 'Av. Roque Petroni Jr., 1.089 - LOJA 234-B/S NIVEL SUPERIOR SHOP.CENTER MORUMBI', '04707-970', 'Vl. Gertrudes', 'São Paulo', 'SP', 'Eduardo Oliveira'),
    ('star03', 'teste', 2, '07.984.267/0006-14', 'Juliana Martins', 'Av. Higienópolis, 618 - SHOPPING CENTER PATIO HIGIENOPOLIS, ARCO 324', '01238-000', 'Higienópolis', 'São Paulo', 'SP', 'Juliana Martins');

-- Filiais (baseadas nos usuários tipo loja)
INSERT INTO filiais (nome_filial, cnpj, responsavel, endereco, cep, bairro, cidade, uf, estado) 
SELECT 
    CONCAT('Filial ', username), 
    cnpj, 
    responsavel, 
    endereco, 
    cep, 
    bairro, 
    cidade, 
    uf,
    uf
FROM usuarios 
WHERE tipo_usuario = 2;

-- Alguns pedidos de exemplo
INSERT INTO pedidos (data, tipo_pedido, status, filial_id, usuario_id, observacoes) VALUES
    (NOW() - INTERVAL 7 DAY, 'requisicao', 'novo', 1, 1, 'Pedido urgente para reposição de estoque'),
    (NOW() - INTERVAL 5 DAY, 'troca', 'processo', 2, 1, 'Troca por defeito no produto'),
    (NOW() - INTERVAL 3 DAY, 'doacao', 'finalizado', 3, 1, 'Doação para evento beneficente'),
    (NOW() - INTERVAL 1 DAY, 'devolucao', 'novo', 1, 1, 'Devolução por erro no pedido');

-- Preencher datas de processamento e finalização
UPDATE pedidos SET data_processamento = NOW() - INTERVAL 4 DAY WHERE status = 'processo';
UPDATE pedidos SET data_processamento = NOW() - INTERVAL 2 DAY, data_finalizacao = NOW() - INTERVAL 1 DAY WHERE status = 'finalizado';

-- Itens dos pedidos de exemplo
INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES
    (1, 600023065, 5, 'Entrega prioritária'),
    (1, 600028602, 2, NULL),
    (2, 34233001, 1, 'Produto com embalagem danificada'),
    (3, 600023065, 10, 'Para evento do dia 15/05'),
    (4, 600028602, 3, 'Produto incorreto enviado');