<?php
require_once 'BaseModel.php';

class Manutencao extends BaseModel {
    public function __construct() {
        parent::__construct('manutencoes');
    }

    public function create($data) {
        // Validar campos obrigatórios
        $required_fields = ['veiculo_id', 'descricao', 'data_manutencao', 'custo', 'tipo', 'status'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("O campo $field é obrigatório.");
            }
        }

        // Validar se o veículo existe
        $veiculo = new Veiculo();
        $veiculo_data = $veiculo->read($data['veiculo_id']);
        if (empty($veiculo_data)) {
            throw new Exception("Veículo não encontrado.");
        }

        // Validar tipo de manutenção
        $tipos_validos = ['preventiva', 'corretiva'];
        if (!in_array($data['tipo'], $tipos_validos)) {
            throw new Exception("Tipo de manutenção inválido.");
        }

        // Validar status
        $status_validos = ['agendada', 'em_andamento', 'concluida'];
        if (!in_array($data['status'], $status_validos)) {
            throw new Exception("Status inválido.");
        }

        // Validar custo
        if (!is_numeric($data['custo']) || $data['custo'] < 0) {
            throw new Exception("Custo inválido.");
        }

        // Validar data da manutenção
        $data_manutencao = new DateTime($data['data_manutencao']);
        $hoje = new DateTime();
        if ($data['status'] === 'agendada' && $data_manutencao < $hoje) {
            throw new Exception("A data da manutenção não pode ser anterior à data atual.");
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        // Validar se o veículo existe se estiver sendo atualizado
        if (isset($data['veiculo_id'])) {
            $veiculo = new Veiculo();
            $veiculo_data = $veiculo->read($data['veiculo_id']);
            if (empty($veiculo_data)) {
                throw new Exception("Veículo não encontrado.");
            }
        }

        // Validar tipo de manutenção se estiver sendo atualizado
        if (isset($data['tipo'])) {
            $tipos_validos = ['preventiva', 'corretiva'];
            if (!in_array($data['tipo'], $tipos_validos)) {
                throw new Exception("Tipo de manutenção inválido.");
            }
        }

        // Validar status se estiver sendo atualizado
        if (isset($data['status'])) {
            $status_validos = ['agendada', 'em_andamento', 'concluida'];
            if (!in_array($data['status'], $status_validos)) {
                throw new Exception("Status inválido.");
            }
        }

        // Validar custo se estiver sendo atualizado
        if (isset($data['custo'])) {
            if (!is_numeric($data['custo']) || $data['custo'] < 0) {
                throw new Exception("Custo inválido.");
            }
        }

        // Validar data da manutenção se estiver sendo atualizada
        if (isset($data['data_manutencao'])) {
            $data_manutencao = new DateTime($data['data_manutencao']);
            $hoje = new DateTime();
            if ($data['status'] === 'agendada' && $data_manutencao < $hoje) {
                throw new Exception("A data da manutenção não pode ser anterior à data atual.");
            }
        }

        return parent::update($id, $data);
    }

    public function getByVeiculo($veiculo_id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE veiculo_id = ? ORDER BY data_manutencao DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$veiculo_id]);
        return $stmt->fetchAll();
    }

    public function readLatestWithVehiclePlate($limit = 5) {
        $sql = "SELECT m.*, v.placa AS veiculo_placa FROM " . $this->table . " m JOIN veiculos v ON m.veiculo_id = v.id ORDER BY m.data_manutencao DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?> 