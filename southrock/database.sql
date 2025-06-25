
DROP DATABASE IF EXISTS southrock;

CREATE DATABASE southrock;

USE southrock;

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
    uf CHAR(2), 
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
    usuario_id INT, 
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
    tipo_item_troca ENUM('enviado', 'recebido') NULL DEFAULT NULL COMMENT 'Define se o item é enviado ou recebido em um pedido de troca',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (sku) REFERENCES produtos(sku) ON DELETE RESTRICT
);




INSERT INTO produtos (sku, produto, grupo, unidade_medida) VALUES
(600030001, 'PRODUTO GAMA 01', 'COMPONENTES ELETRÔNICOS', 'PC'),
(600030002, 'PRODUTO GAMA 02', 'COMPONENTES ELETRÔNICOS', 'UN'),
(600030003, 'PRODUTO KAPPA 01', 'MATERIAL ESCRITÓRIO', 'CX'),
(600030004, 'PRODUTO KAPPA 02', 'MATERIAL ESCRITÓRIO', 'UN'),
(600030005, 'PRODUTO ÔMEGA 01', 'FERRAMENTAS MANUAIS', 'PC'),
(600030006, 'PRODUTO ÔMEGA 02', 'FERRAMENTAS MANUAIS', 'KT'),
(600030007, 'PRODUTO ZETA 01', 'LIMPEZA PROFISSIONAL', 'GL'),
(600030008, 'PRODUTO ZETA 02', 'LIMPEZA PROFISSIONAL', 'UN'),
(600030009, 'PRODUTO SIGMA 01', 'EMBALAGENS DIVERSAS', 'RL'),
(600030010, 'PRODUTO SIGMA 02', 'EMBALAGENS DIVERSAS', 'PC'),
(600030011, 'PRODUTO THETA 01', 'ILUMINAÇÃO LED', 'UN'),
(600030012, 'PRODUTO THETA 02', 'ILUMINAÇÃO LED', 'CX'),
(600030013, 'PRODUTO IOTA 01', 'CONECTORES E ADAPTADORES', 'PC'),
(600030014, 'PRODUTO IOTA 02', 'CONECTORES E ADAPTADORES', 'KT'),
(600030015, 'PRODUTO LAMBDA 01', 'EQUIPAMENTOS DE PROTEÇÃO', 'PAR'),
(600030016, 'PRODUTO LAMBDA 02', 'EQUIPAMENTOS DE PROTEÇÃO', 'UN'),
(600030017, 'PRODUTO PSI 01', 'ACESSÓRIOS DE INFORMÁTICA', 'UN'),
(600030018, 'PRODUTO PSI 02', 'ACESSÓRIOS DE INFORMÁTICA', 'PC'),
(600030019, 'PRODUTO XI 01', 'MATERIAL HIDRÁULICO', 'PC'),
(600030020, 'PRODUTO XI 02', 'MATERIAL HIDRÁULICO', 'RL'),
(600030021, 'PRODUTO PHI 01', 'DECORAÇÃO INTERNA', 'UN'),
(600030022, 'PRODUTO PHI 02', 'DECORAÇÃO INTERNA', 'CJ'),
(600030023, 'PRODUTO CHI 01', 'SUPRIMENTOS INDUSTRIAIS', 'UN'),
(600030024, 'PRODUTO CHI 02', 'SUPRIMENTOS INDUSTRIAIS', 'CX'),
(600030025, 'PRODUTO NU 01', 'CABOS E FIOS', 'MT'),
(600030026, 'PRODUTO NU 02', 'CABOS E FIOS', 'RL'),
(600030027, 'PRODUTO MU 01', 'UTILIDADES DOMÉSTICAS', 'PC'),
(600030028, 'PRODUTO MU 02', 'UTILIDADES DOMÉSTICAS', 'UN'),
(600030029, 'PRODUTO ETA 01', 'PRODUTOS QUÍMICOS', 'LT'),
(600030030, 'PRODUTO ETA 02', 'PRODUTOS QUÍMICOS', 'KG'),
(600030031, 'PRODUTO RHO 01', 'MATERIAL DE CONSTRUÇÃO', 'SC'),
(600030032, 'PRODUTO RHO 02', 'MATERIAL DE CONSTRUÇÃO', 'UN'),
(600030033, 'PRODUTO TAU 01', 'AUTOMAÇÃO RESIDENCIAL', 'PC'),
(600030034, 'PRODUTO TAU 02', 'AUTOMAÇÃO RESIDENCIAL', 'KT'),
(600030035, 'PRODUTO UPSILON 01', 'JARDINAGEM', 'UN'),
(600030036, 'PRODUTO UPSILON 02', 'JARDINAGEM', 'SC'),
(600030037, 'PRODUTO OMICRON 01', 'BRINDES CORPORATIVOS', 'UN'),
(600030038, 'PRODUTO OMICRON 02', 'BRINDES CORPORATIVOS', 'CX'),
(600030039, 'PRODUTO PI 01', 'PEÇAS DE REPOSIÇÃO', 'PC'),
(600030040, 'PRODUTO PI 02', 'PEÇAS DE REPOSIÇÃO', 'UN'),
(600030041, 'PRODUTO KAPPA EXTRA', 'MATERIAL ESCRITÓRIO PREMIUM', 'KT'),
(600030042, 'PRODUTO ÔMEGA PLUS', 'FERRAMENTAS ELÉTRICAS', 'UN'),
(600030043, 'PRODUTO ZETA MAX', 'LIMPEZA PESADA', 'BD'),
(600030044, 'PRODUTO SIGMA GOLD', 'EMBALAGENS ESPECIAIS', 'UN'),
(600030045, 'PRODUTO THETA ADVANCE', 'LUMINÁRIAS DECORATIVAS', 'PC'),
(600030046, 'PRODUTO IOTA PRIME', 'CABOS ESPECIAIS', 'MT'),
(600030047, 'PRODUTO LAMBDA SECURE', 'SEGURANÇA ELETRÔNICA', 'KT'),
(600030048, 'PRODUTO PSI MASTER', 'PERIFÉRICOS GAMER', 'UN'),
(600030049, 'PRODUTO XI PRO', 'TUBULAÇÕES INDUSTRIAIS', 'PC'),
(600030050, 'PRODUTO PHI DESIGN', 'OBJETOS DE ARTE', 'UN');



INSERT INTO tipo_usuario (id, descricao) VALUES
    (1, 'matriz'),
    (2, 'loja');


INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial) VALUES
    ('admin', '$2y$10$pGyZc7GXVc58d3nL15zRIOmVPyY03L2uOvXo57tFMgr25qQRjL.Ti', 1, '12.345.678/0001-90', 'João Silva', 'Rua das Flores, 123', '04929220', 'Alto do Rivieira', 'São Paulo', 'SP', 'Matriz SouthRock', FALSE, NULL),
    ('star01', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0001-00', 'Igor Costa', 'Av Paulista, 900 - ANDAR 10 PARTE', '01310-940', 'Bela Vista', 'São Paulo', 'SP', 'Igor Costa', TRUE, 'Filial Starbucks Center'),
    ('star02', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0005-33', 'Eduardo Oliveira', 'Av. Roque Petroni Jr., 1.089 - LOJA 234-B/S NIVEL SUPERIOR SHOP.CENTER MORUMBI', '04707-970', 'Vl. Gertrudes', 'São Paulo', 'SP', 'Eduardo Oliveira', TRUE, 'Filial Starbucks Morumbi'),
    ('star03', '$2y$10$V8qU8E8v5cCH.d2kSmzVUOEZqgBMEeX6PIdrYYPUCgLs0gQ.QTNlO', 2, '07.984.267/0006-14', 'Juliana Martins', 'Av. Higienópolis, 618 - SHOPPING CENTER PATIO HIGIENOPOLIS, ARCO 324', '01238-000', 'Higienópolis', 'São Paulo', 'SP', 'Juliana Martins', TRUE, 'Filial Starbucks Higienopolis');



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