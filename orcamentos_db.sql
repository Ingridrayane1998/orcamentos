CREATE DATABASE IF NOT EXISTS controle_orcamentos;
USE controle_orcamentos;

CREATE TABLE orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_orcamento DATE NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    fornecedor VARCHAR(150),
    valor_proposto DECIMAL(10,2) NOT NULL,
    valor_final DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL
);
