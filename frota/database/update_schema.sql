-- Add new columns to veiculos table
ALTER TABLE veiculos
ADD COLUMN km_atual INT DEFAULT 0,
ADD COLUMN ultima_revisao DATE,
ADD COLUMN proxima_revisao DATE,
ADD COLUMN observacoes TEXT;

-- Add index for faster plate search
CREATE INDEX idx_veiculos_placa ON veiculos(placa);

-- Add index for status filtering
CREATE INDEX idx_veiculos_status ON veiculos(status);

-- Add index for maintenance dates
CREATE INDEX idx_veiculos_manutencao ON veiculos(ultima_revisao, proxima_revisao); 