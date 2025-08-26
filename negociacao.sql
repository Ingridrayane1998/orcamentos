-- Sistema de Cotações para Facilities
-- Database: facilities_system

CREATE DATABASE IF NOT EXISTS facilities_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE facilities_system;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'usuario') DEFAULT 'usuario',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de fornecedores
CREATE TABLE fornecedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18),
    email VARCHAR(100),
    telefone VARCHAR(20),
    endereco TEXT,
    contato_responsavel VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de cotações
CREATE TABLE cotacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_cotacao VARCHAR(20) UNIQUE NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    categoria_id INT,
    usuario_id INT NOT NULL,
    valor_inicial DECIMAL(12,2) NOT NULL,
    valor_negociado DECIMAL(12,2) NULL,
    economia DECIMAL(12,2) GENERATED ALWAYS AS (valor_inicial - COALESCE(valor_negociado, valor_inicial)) STORED,
    status ENUM('pendente', 'cotando', 'negociando', 'fechado', 'cancelado') DEFAULT 'pendente',
    data_vencimento DATE,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_numero (numero_cotacao),
    INDEX idx_created (created_at)
);

-- Tabela de cotações dos fornecedores
CREATE TABLE cotacao_fornecedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cotacao_id INT NOT NULL,
    fornecedor_id INT NOT NULL,
    valor_cotado DECIMAL(12,2) NOT NULL,
    prazo_entrega INT,
    condicoes_pagamento VARCHAR(200),
    observacoes TEXT,
    selecionado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cotacao_id) REFERENCES cotacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cotacao_fornecedor (cotacao_id, fornecedor_id)
);

-- Inserir dados iniciais

-- Usuários
INSERT INTO usuarios (nome, email, senha, perfil) VALUES 
('Admin do Sistema', 'admin@facilities.com', MD5('admin123'), 'admin'),
('João Silva', 'joao@facilities.com', MD5('123456'), 'usuario'),
('Maria Santos', 'maria@facilities.com', MD5('123456'), 'usuario');

-- Categorias
INSERT INTO categorias (nome, descricao) VALUES 
('Manutenção Predial', 'Serviços de manutenção preventiva e corretiva de instalações'),
('Limpeza e Conservação', 'Produtos e serviços de limpeza e conservação'),
('Segurança Patrimonial', 'Equipamentos e serviços de segurança'),
('Tecnologia da Informação', 'Equipamentos, softwares e serviços de TI'),
('Mobiliário e Equipamentos', 'Móveis e equipamentos para escritório'),
('Jardinagem e Paisagismo', 'Serviços de manutenção de áreas verdes'),
('Utilidades e Suprimentos', 'Materiais de escritório e suprimentos diversos');

-- Fornecedores
INSERT INTO fornecedores (nome, cnpj, email, telefone, contato_responsavel) VALUES 
('Alpha Manutenção Ltda', '12.345.678/0001-90', 'comercial@alphamanutencao.com', '(11) 3333-4444', 'Carlos Santos'),
('Beta Limpeza e Conservação', '98.765.432/0001-10', 'vendas@betalimpeza.com', '(11) 2222-3333', 'Maria Oliveira'),
('Gamma Segurança', '11.222.333/0001-44', 'contato@gammaseguranca.com', '(11) 4444-5555', 'Pedro Costa'),
('Delta TI Solutions', '33.444.555/0001-66', 'comercial@deltati.com', '(11) 5555-6666', 'Ana Silva'),
('Epsilon Móveis', '55.666.777/0001-88', 'vendas@epsilonmoveis.com', '(11) 6666-7777', 'Roberto Lima');

-- Cotações de exemplo
INSERT INTO cotacoes (numero_cotacao, titulo, descricao, categoria_id, usuario_id, valor_inicial, valor_negociado, status, data_vencimento, observacoes) VALUES 
('COT-2024-0001', 'Limpeza de Carpetes - Andar 5', 'Limpeza profunda dos carpetes do 5º andar, incluindo produtos especiais', 2, 2, 2500.00, 2200.00, 'fechado', '2024-02-15', 'Negociação: Desconto obtido devido ao volume de serviços contratados.'),
('COT-2024-0002', 'Manutenção Sistema de Ar Condicionado', 'Manutenção preventiva completa do sistema central de climatização', 1, 2, 8500.00, 7800.00, 'fechado', '2024-01-30', 'Negociação: Valor reduzido em troca de contrato anual.'),
('COT-2024-0003', 'Monitores 24 polegadas - Departamento TI', 'Aquisição de 15 monitores LED 24" Full HD para estações de trabalho', 4, 3, 4500.00, NULL, 'cotando', '2024-03-10', NULL),
('COT-2024-0004', 'Serviço de Segurança Noturna', 'Contratação de vigilância noturna por 6 meses', 3, 2, 18000.00, NULL, 'negociando', '2024-02-20', 'Aguardando proposta final do fornecedor.'),
('COT-2024-0005', 'Cadeiras Ergonômicas - RH', 'Compra de 8 cadeiras ergonômicas para o departamento de recursos humanos', 5, 3, 3200.00, 2950.00, 'fechado', '2024-01-25', 'Negociação: Desconto por pagamento à vista.');

-- Views úteis para relatórios

-- View de economias por categoria
CREATE VIEW vw_economia_categoria AS
SELECT 
    c.id as categoria_id,
    c.nome as categoria,
    COUNT(cot.id) as total_cotacoes,
    SUM(CASE WHEN cot.valor_negociado IS NOT NULL THEN 1 ELSE 0 END) as cotacoes_negociadas,
    SUM(cot.valor_inicial) as valor_total_inicial,
    SUM(COALESCE(cot.valor_negociado, cot.valor_inicial)) as valor_total_final,
    SUM(cot.economia) as economia_total,
    AVG(CASE WHEN cot.valor_negociado IS NOT NULL THEN (cot.economia/cot.valor_inicial)*100 ELSE 0 END) as percentual_economia_medio
FROM categorias c
LEFT JOIN cotacoes cot ON c.id = cot.categoria_id
WHERE c.ativo = TRUE
GROUP BY c.id, c.nome;

-- View de performance por usuário
CREATE VIEW vw_performance_usuario AS
SELECT 
    u.id as usuario_id,
    u.nome as usuario,
    COUNT(c.id) as total_cotacoes,
    SUM(CASE WHEN c.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
    SUM(c.economia) as economia_total,
    AVG(CASE WHEN c.valor_negociado IS NOT NULL THEN (c.economia/c.valor_inicial)*100 ELSE 0 END) as percentual_economia_medio
FROM usuarios u
LEFT JOIN cotacoes c ON u.id = c.usuario_id
WHERE u.ativo = TRUE
GROUP BY u.id, u.nome;

-- Índices adicionais para performance
CREATE INDEX idx_cotacoes_categoria_status ON cotacoes(categoria_id, status);
CREATE INDEX idx_cotacoes_usuario_status ON cotacoes(usuario_id, status);
CREATE INDEX idx_cotacoes_data_vencimento ON cotacoes(data_vencimento);

COMMIT;

