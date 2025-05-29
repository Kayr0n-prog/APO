<?php
require_once 'BaseModel.php';

class Abastecimento extends BaseModel {
    public function __construct() {
        parent::__construct('abastecimentos');
    }

    // Método para buscar abastecimentos por veículo, ordenados por data
    public function getByVeiculo($veiculo_id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE veiculo_id = ? ORDER BY data_abastecimento ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$veiculo_id]);
        return $stmt->fetchAll();
    }

    // Método para calcular o consumo médio de um veículo
    // Assume que os abastecimentos estão ordenados por KM
    public function calcularConsumoMedio($veiculo_id) {
        $abastecimentos = $this->getByVeiculo($veiculo_id);

        if (count($abastecimentos) < 2) {
            return null; // Pelo menos dois abastecimentos são necessários para calcular o consumo
        }

        $primeiro_abastecimento = $abastecimentos[0];
        $ultimo_abastecimento = end($abastecimentos);

        $distancia_percorrida = $ultimo_abastecimento['km_atual'] - $primeiro_abastecimento['km_atual'];
        $total_litros = 0;

        // Soma os litros de todos os abastecimentos, exceto o primeiro (tanque cheio)
        for ($i = 1; $i < count($abastecimentos); $i++) {
            $total_litros += $abastecimentos[$i]['litros'];
        }

        if ($distancia_percorrida <= 0 || $total_litros <= 0) {
            return null; // Evitar divisão por zero ou resultados inválidos
        }

        return $distancia_percorrida / $total_litros; // KM por litro
    }
}
?> 