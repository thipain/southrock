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
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    eh_filial BOOLEAN DEFAULT FALSE,
    nome_filial VARCHAR(255) NULL,
    estado CHAR(2) NULL,
    FOREIGN KEY (tipo_usuario) REFERENCES tipo_usuario(id)
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_pedido ENUM('requisicao', 'troca', 'doacao', 'devolucao') NOT NULL DEFAULT 'requisicao',
    status ENUM('novo', 'processo', 'finalizado') NOT NULL DEFAULT 'novo',
    filial_usuario_id INT,
    usuario_id INT,
    observacoes TEXT,
    data_processamento DATETIME,
    data_finalizacao DATETIME,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (filial_usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    sku INT,
    quantidade INT,
    observacao TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
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

-- Usuários com dados unificados (incluindo os que eram filiais)
INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial, estado) VALUES
    ('admin', '123456', 1, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '04929220', 'alto do rivieira', 'São Paulo', 'SP', 'João Silva', FALSE, NULL, NULL),
    ('star01', 'teste', 2, '07.984.267/0001-00', 'Igor Costa', 'Av Paulista, 900 - ANDAR 10 PARTE', '01310-940', 'Bela Vista', 'São Paulo', 'SP', 'Igor Costa', TRUE, 'Filial star01', 'SP'),
    ('star02', 'teste', 2, '07.984.267/0005-33', 'Eduardo Oliveira', 'Av. Roque Petroni Jr., 1.089 - LOJA 234-B/S NIVEL SUPERIOR SHOP.CENTER MORUMBI', '04707-970', 'Vl. Gertrudes', 'São Paulo', 'SP', 'Eduardo Oliveira', TRUE, 'Filial star02', 'SP'),
    ('star03', 'teste', 2, '07.984.267/0006-14', 'Juliana Martins', 'Av. Higienópolis, 618 - SHOPPING CENTER PATIO HIGIENOPOLIS, ARCO 324', '01238-000', 'Higienópolis', 'São Paulo', 'SP', 'Juliana Martins', TRUE, 'Filial star03', 'SP');

-- Pedidos de exemplo com a nova estrutura
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, usuario_id, observacoes, data_processamento, data_finalizacao) VALUES
    (CURRENT_TIMESTAMP(), 'requisicao', 'novo', 2, 1, 'Pedido urgente para reposição de estoque', NULL, NULL),
    (CURRENT_TIMESTAMP(), 'troca', 'processo', 3, 1, 'Troca por defeito no produto', CURRENT_TIMESTAMP(), NULL),
    (CURRENT_TIMESTAMP(), 'doacao', 'finalizado', 4, 1, 'Doação para evento beneficente', CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP()),
    (CURRENT_TIMESTAMP(), 'devolucao', 'novo', 2, 1, 'Devolução por erro no pedido', NULL, NULL);

-- Itens dos pedidos de exemplo
INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES
    (1, 600023065, 5, 'Entrega prioritária'),
    (1, 600028602, 2, NULL),
    (2, 34233001, 1, 'Produto com embalagem danificada'),
    (3, 600023065, 10, 'Para evento do dia 15/05'),
    (4, 600028602, 3, 'Produto incorreto enviado');

-- Adicionar triggers para garantir que as datas sejam sempre atualizadas no fuso horário correto
DELIMITER //

-- Trigger para atualizar data_processamento quando status = 'processo'
CREATE TRIGGER tgr_pedido_processo BEFORE UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF NEW.status = 'processo' AND OLD.status != 'processo' THEN
        SET NEW.data_processamento = CURRENT_TIMESTAMP();
    END IF;
END //

-- Trigger para atualizar data_finalizacao quando status = 'finalizado'
CREATE TRIGGER tgr_pedido_finalizado BEFORE UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF NEW.status = 'finalizado' AND OLD.status != 'finalizado' THEN
        SET NEW.data_finalizacao = CURRENT_TIMESTAMP();
    END IF;
END //

DELIMITER ;