CREATE DATABASE IF NOT EXISTS frota_db;
USE frota_db;

CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(8) NOT NULL UNIQUE,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano INT NOT NULL,
    status ENUM('disponivel', 'indisponivel', 'em_manutencao') NOT NULL DEFAULT 'disponivel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    km_atual INT DEFAULT 0,
    ultima_revisao DATE,
    proxima_revisao DATE,
    observacoes TEXT
);

CREATE TABLE IF NOT EXISTS motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnh VARCHAR(20) NOT NULL UNIQUE,
    categoria VARCHAR(2) NOT NULL,
    validade_cnh DATE NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    descricao TEXT NOT NULL,
    data_manutencao DATE NOT NULL,
    km_manutencao INT NOT NULL,
    custo DECIMAL(10,2) NOT NULL,
    tipo ENUM('preventiva', 'corretiva') NOT NULL,
    status ENUM('agendada', 'em_andamento', 'concluida') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);

-- Table for fuel records
CREATE TABLE IF NOT EXISTS abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    data_abastecimento DATE NOT NULL,
    litros DECIMAL(10,2) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    km_atual INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);

-- Table de Multas
CREATE TABLE multas_infracoes (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT(11) NOT NULL,
    motorista_id INT(11) NOT NULL,
    tipo_infracao VARCHAR(255) NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    situacao ENUM('paga', 'nao_paga') NOT NULL,
    documento_anexo VARCHAR(255), -- Or TEXT if paths can be long
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE CASCADE
);

-- Add index for faster plate search
CREATE INDEX idx_veiculos_placa ON veiculos(placa);

-- Add index for status filtering
CREATE INDEX idx_veiculos_status ON veiculos(status);

-- Add index for maintenance dates
CREATE INDEX idx_veiculos_manutencao ON veiculos(ultima_revisao, proxima_revisao); 