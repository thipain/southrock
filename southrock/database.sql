-- Excluir o banco de dados se ele já existir para um recomeço limpo
DROP DATABASE IF EXISTS southrock;

CREATE DATABASE southrock;

USE southrock;

-- Configurar o timezone para Brasília (UTC-3)
SET time_zone = '-03:00';

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
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    tipo_usuario INT NOT NULL,
    cnpj VARCHAR(20),
    responsavel VARCHAR(255),
    endereco VARCHAR(255),
    cep VARCHAR(10),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2), -- Unidade Federativa (Estado)
    nome VARCHAR(255),
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    eh_filial BOOLEAN DEFAULT FALSE,
    nome_filial VARCHAR(255) NULL,
    FOREIGN KEY (tipo_usuario) REFERENCES tipo_usuario(id)
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_pedido ENUM('requisicao', 'troca', 'doacao', 'devolucao') NOT NULL DEFAULT 'requisicao',
    status ENUM(
        'novo',
        'processo',
        'finalizado',
        'aprovado',
        'rejeitado',
        'cancelado',
        'novo_troca_pendente_aceite_parceiro',
        'troca_aceita_parceiro_pendente_matriz'
    ) NOT NULL DEFAULT 'novo',
    filial_usuario_id INT,
    filial_destino_id INT NULL,
    usuario_id INT, -- Usuário do sistema que efetivamente registrou/processou o pedido
    pedido_original_id INT NULL DEFAULT NULL,
    observacoes TEXT,
    data_processamento DATETIME NULL,
    data_finalizacao DATETIME NULL,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (filial_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (filial_destino_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (pedido_original_id) REFERENCES pedidos(id) ON DELETE SET NULL
);

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    sku INT,
    quantidade DECIMAL(10,2) NOT NULL,
    observacao TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_item_troca ENUM('enviado', 'recebido') NULL DEFAULT NULL COMMENT 'Define se o item em um pedido de troca está sendo enviado ou recebido pela filial de origem',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (sku) REFERENCES produtos(sku) ON DELETE RESTRICT
);

-- Inserção de dados nas tabelas

-- Produtos
INSERT INTO produtos (sku, produto, grupo, unidade_medida) VALUES
    (600023065, 'PRODUTO ALPHA', 'DIVERSOS SKUS', 'UN'),
    (600028602, 'PRODUTO BETA GAMA', 'THERMAL CUTOFF', 'PC'),
    (34233001, 'PRODUTO DELTA EPSILON', 'CONJUNTOS ESPECIAIS', 'CX');

-- Tipos de usuário
INSERT INTO tipo_usuario (id, descricao) VALUES
    (1, 'matriz'),
    (2, 'loja');

-- Usuários (coluna 'estado' removida)
INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial) VALUES
    ('admin', '$2y$10$pGyZc7GXVc58d3nL15zRIOmVPyY03L2uOvXo57tFMgr25qQRjL.Ti', 1, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '04929220', 'Alto do Rivieira', 'São Paulo', 'SP', 'Matriz SouthRock', FALSE, NULL),
    ('star01', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0001-00', 'Igor Costa', 'Av Paulista, 900 - ANDAR 10 PARTE', '01310-940', 'Bela Vista', 'São Paulo', 'SP', 'Igor Costa', TRUE, 'Filial Starbucks Center'),
    ('star02', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0005-33', 'Eduardo Oliveira', 'Av. Roque Petroni Jr., 1.089 - LOJA 234-B/S NIVEL SUPERIOR SHOP.CENTER MORUMBI', '04707-970', 'Vl. Gertrudes', 'São Paulo', 'SP', 'Eduardo Oliveira', TRUE, 'Filial Starbucks Morumbi'),
    ('star03', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0006-14', 'Juliana Martins', 'Av. Higienópolis, 618 - SHOPPING CENTER PATIO HIGIENOPOLIS, ARCO 324', '01238-000', 'Higienópolis', 'São Paulo', 'SP', 'Juliana Martins', TRUE, 'Filial Starbucks Higienopolis');

-- Pedidos de exemplo
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-01 10:00:00', 'requisicao', 'finalizado', 2, NULL, 1, 'Pedido urgente para reposição de estoque da filial star01');
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-02 11:00:00', 'doacao', 'finalizado', 1, 3, 1, 'Doação de itens promocionais para Filial star02');
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-03 14:00:00', 'requisicao', 'finalizado', 1, 4, 1, 'Envio de material para loja star03');
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, pedido_original_id, observacoes) VALUES
    ('2024-05-04 09:30:00', 'devolucao', 'novo', 4, 1, 4, 3, 'Devolução de itens do pedido #3 por excesso de estoque.');

-- Itens dos pedidos de exemplo
INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES
    (1, 600023065, 5, 'Entrega prioritária para star01'),
    (1, 600028602, 2, NULL),
    (2, 34233001, 10, 'Itens para doação à star02'),
    (3, 600023065, 20, 'Material para star03'),
    (3, 34233001, 5, NULL),
    (4, 600023065, 3, 'Devolvendo 3 unidades do PRODUTO ALPHA');

-- Triggers
DELIMITER //
CREATE TRIGGER tgr_pedido_processo BEFORE UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF NEW.status = 'processo' AND OLD.status != 'processo' THEN
        SET NEW.data_processamento = CURRENT_TIMESTAMP();
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER tgr_pedido_finalizado BEFORE UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF NEW.status = 'finalizado' AND OLD.status != 'finalizado' THEN
        SET NEW.data_finalizacao = CURRENT_TIMESTAMP();
    END IF;
END //
DELIMITER ;