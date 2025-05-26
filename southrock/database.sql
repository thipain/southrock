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
    filial_destino_id INT,
    usuario_id INT, -- Usuário que efetivamente registrou/processou o pedido
    pedido_original_id INT NULL DEFAULT NULL, -- ID do pedido que originou este (ex: uma devolução refere-se a um pedido anterior)
    observacoes TEXT,
    data_processamento DATETIME,
    data_finalizacao DATETIME,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (filial_usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (filial_destino_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (pedido_original_id) REFERENCES pedidos(id) ON DELETE SET NULL -- Se o pedido original for deletado, este campo fica NULL
);

CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    sku INT, -- Chave estrangeira para produtos.sku
    quantidade INT,
    observacao TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE, -- Se o pedido for deletado, seus itens também são
    FOREIGN KEY (sku) REFERENCES produtos(sku)
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

-- Usuários com dados unificados (incluindo os que eram filiais)
INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial, estado) VALUES
    ('admin', '$2y$10$pGyZc7GXVc58d3nL15zRIOmVPyY03L2uOvXo57tFMgr25qQRjL.Ti', 1, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '04929220', 'Alto do Rivieira', 'São Paulo', 'SP', 'Matriz SouthRock', FALSE, NULL, 'SP'), -- Senha '123456'
    ('star01', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0001-00', 'Igor Costa', 'Av Paulista, 900 - ANDAR 10 PARTE', '01310-940', 'Bela Vista', 'São Paulo', 'SP', 'Igor Costa', TRUE, 'Filial Starbucks Center', 'SP'), -- Senha 'teste'
    ('star02', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0005-33', 'Eduardo Oliveira', 'Av. Roque Petroni Jr., 1.089 - LOJA 234-B/S NIVEL SUPERIOR SHOP.CENTER MORUMBI', '04707-970', 'Vl. Gertrudes', 'São Paulo', 'SP', 'Eduardo Oliveira', TRUE, 'Filial Starbucks Morumbi', 'SP'), -- Senha 'teste'
    ('star03', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0006-14', 'Juliana Martins', 'Av. Higienópolis, 618 - SHOPPING CENTER PATIO HIGIENOPOLIS, ARCO 324', '01238-000', 'Higienópolis', 'São Paulo', 'SP', 'Juliana Martins', TRUE, 'Filial Starbucks Higienopolis', 'SP'); -- Senha 'teste'

-- Pedidos de exemplo com a nova estrutura
-- Pedido 1: Loja star01 (ID 2) requisita para Matriz (filial_destino_id NULL, processado pelo admin/matriz - usuario_id 1)
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-01 10:00:00', 'requisicao', 'finalizado', 2, NULL, 1, 'Pedido urgente para reposição de estoque da filial star01');

-- Pedido 2: Matriz (ID 1) envia uma doação para Loja star02 (ID 3)
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-02 11:00:00', 'doacao', 'finalizado', 1, 3, 1, 'Doação de itens promocionais para Filial star02');

-- Pedido 3: Loja star03 (ID 4) faz uma devolução para Matriz (NULL) de itens do Pedido X (ex: pedido_original_id = 1, se fosse uma devolução do pedido 1)
-- Para este exemplo, vamos supor que é uma devolução de um pedido não listado aqui, ou o pedido_original_id será NULL se não houver um original direto.
-- Se este fosse uma devolução do pedido 1 (onde star01 foi o requisitante e recebeu da matriz), a lógica de devolução seria star01 devolvendo para a matriz.
-- Vamos criar um pedido que a star03 recebeu para que ela possa devolver:
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) VALUES
    ('2024-05-03 14:00:00', 'requisicao', 'finalizado', 1, 4, 1, 'Envio de material para loja star03'); -- Matriz envia para star03

-- Pedido 4: Loja star03 (ID 4) devolve itens do Pedido 3 para Matriz (ID 1, que foi o filial_usuario_id do pedido 3)
INSERT INTO pedidos (data, tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, pedido_original_id, observacoes) VALUES
    ('2024-05-04 09:30:00', 'devolucao', 'novo', 4, 1, 4, 3, 'Devolução de itens do pedido #3 por excesso de estoque.');


-- Itens dos pedidos de exemplo
INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES
    (1, 600023065, 5, 'Entrega prioritária para star01'), -- Itens para o Pedido 1
    (1, 600028602, 2, NULL),
    (2, 34233001, 10, 'Itens para doação à star02'), -- Itens para o Pedido 2
    (3, 600023065, 20, 'Material para star03'), -- Itens para o Pedido 3
    (3, 34233001, 5, NULL),
    (4, 600023065, 3, 'Devolvendo 3 unidades do PRODUTO ALPHA'); -- Itens para o Pedido 4 (devolução do Pedido 3)


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