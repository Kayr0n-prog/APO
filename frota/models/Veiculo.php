<?php
require_once 'BaseModel.php';

class Veiculo extends BaseModel {
    protected $table = 'veiculos';
    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'ano',
        'status',
        'km_atual',
        'ultima_revisao',
        'proxima_revisao',
        'observacoes'
    ];

    public function __construct() {
        parent::__construct('veiculos');
    }

    public function create($data) {
        // Validar campos obrigatórios
        $required_fields = ['placa', 'marca', 'modelo', 'ano'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("O campo $field é obrigatório.");
            }
        }

        // Validar formato da placa (ABC1234)
        if (!preg_match('/^[A-Z]{3}[0-9]{4}$/', $data['placa'])) {
            throw new Exception("A placa deve estar no formato ABC1234.");
        }

        // Validar ano
        $ano = intval($data['ano']);
        $ano_atual = intval(date('Y'));
        if ($ano < 1900 || $ano > $ano_atual) {
            throw new Exception("O ano deve estar entre 1900 e $ano_atual.");
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        // Validar formato da placa se estiver sendo atualizada
        if (isset($data['placa']) && !preg_match('/^[A-Z]{3}[0-9]{4}$/', $data['placa'])) {
            throw new Exception("A placa deve estar no formato ABC1234.");
        }

        // Validar ano se estiver sendo atualizado
        if (isset($data['ano'])) {
            $ano = intval($data['ano']);
            $ano_atual = intval(date('Y'));
            if ($ano < 1900 || $ano > $ano_atual) {
                throw new Exception("O ano deve estar entre 1900 e $ano_atual.");
            }
        }

        return parent::update($id, $data);
    }

    public function getByPlaca($placa) {
        $sql = "SELECT * FROM {$this->table} WHERE placa = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$placa]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDisponiveis() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'disponivel'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmManutencao() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'em_manutencao'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProximasRevisoes($dias = 30) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE proxima_revisao BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY proxima_revisao ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVeiculosPorStatus() {
        $sql = "SELECT status, COUNT(*) as total FROM {$this->table} GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVeiculosPorMarca() {
        $sql = "SELECT marca, COUNT(*) as total FROM {$this->table} GROUP BY marca ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVeiculosPorAno() {
        $sql = "SELECT ano, COUNT(*) as total FROM {$this->table} GROUP BY ano ORDER BY ano DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVeiculosPorKm() {
        $sql = "SELECT 
                    CASE 
                        WHEN km_atual < 10000 THEN '0-10.000'
                        WHEN km_atual < 50000 THEN '10.000-50.000'
                        WHEN km_atual < 100000 THEN '50.000-100.000'
                        ELSE '100.000+'
                    END as faixa_km,
                    COUNT(*) as total
                FROM {$this->table}
                GROUP BY faixa_km
                ORDER BY MIN(km_atual)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateKm($id, $km) {
        $sql = "UPDATE {$this->table} SET km_atual = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$km, $id]);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    public function updateManutencao($id, $ultima_revisao, $proxima_revisao) {
        $sql = "UPDATE {$this->table} SET ultima_revisao = ?, proxima_revisao = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$ultima_revisao, $proxima_revisao, $id]);
    }

    public function search($term) {
        $term = "%{$term}%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE placa LIKE ? 
                OR marca LIKE ? 
                OR modelo LIKE ? 
                OR observacoes LIKE ?
                ORDER BY placa";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 