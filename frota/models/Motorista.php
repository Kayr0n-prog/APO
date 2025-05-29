<?php
require_once 'BaseModel.php';

class Motorista extends BaseModel {
    public function __construct() {
        parent::__construct('motoristas');
    }

    public function create($data) {
        // Validar campos obrigatórios
        $required_fields = ['nome', 'cnh', 'categoria', 'validade_cnh'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("O campo $field é obrigatório.");
            }
        }

        // Validar formato da CNH (11 dígitos)
        if (!preg_match('/^[0-9]{11}$/', $data['cnh'])) {
            throw new Exception("A CNH deve conter 11 dígitos.");
        }

        // Validar categoria
        $categorias_validas = ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'];
        if (!in_array($data['categoria'], $categorias_validas)) {
            throw new Exception("Categoria de CNH inválida.");
        }

        // Validar data de validade
        $validade = new DateTime($data['validade_cnh']);
        $hoje = new DateTime();
        if ($validade < $hoje) {
            throw new Exception("A CNH está vencida.");
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        // Validar formato da CNH se estiver sendo atualizada
        if (isset($data['cnh']) && !preg_match('/^[0-9]{11}$/', $data['cnh'])) {
            throw new Exception("A CNH deve conter 11 dígitos.");
        }

        // Validar categoria se estiver sendo atualizada
        if (isset($data['categoria'])) {
            $categorias_validas = ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'];
            if (!in_array($data['categoria'], $categorias_validas)) {
                throw new Exception("Categoria de CNH inválida.");
            }
        }

        // Validar data de validade se estiver sendo atualizada
        if (isset($data['validade_cnh'])) {
            $validade = new DateTime($data['validade_cnh']);
            $hoje = new DateTime();
            if ($validade < $hoje) {
                throw new Exception("A CNH está vencida.");
            }
        }

        return parent::update($id, $data);
    }
}
?> 