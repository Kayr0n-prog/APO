<?php

require_once 'config/database.php';

class MultaInfracao {
    private $conn;
    private $table_name = "multas_infracoes"; // Assuming your table name is multas_infracoes

    public $id;
    public $veiculo_id;
    public $motorista_id;
    public $tipo_infracao;
    public $data;
    public $valor;
    public $situacao;
    public $documento_anexo;
    public $descricao;
    public $created_at;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create multa/infracao entry
    public function create(){
        // query to insert record
        $query = "INSERT INTO " . $this->table_name . " SET veiculo_id=:veiculo_id, motorista_id=:motorista_id, tipo_infracao=:tipo_infracao, data=:data, valor=:valor, situacao=:situacao, documento_anexo=:documento_anexo, descricao=:descricao";
        
        // prepare query
        $stmt = $this->conn->prepare($query);
        
        // sanitize
        $this->veiculo_id=htmlspecialchars(strip_tags($this->veiculo_id));
        $this->motorista_id=htmlspecialchars(strip_tags($this->motorista_id));
        $this->tipo_infracao=htmlspecialchars(strip_tags($this->tipo_infracao));
        $this->data=htmlspecialchars(strip_tags($this->data));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->documento_anexo=htmlspecialchars(strip_tags($this->documento_anexo));
        $this->descricao=htmlspecialchars(strip_tags($this->descricao));
        
        // bind values
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":motorista_id", $this->motorista_id);
        $stmt->bindParam(":tipo_infracao", $this->tipo_infracao);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":situacao", $this->situacao);
        $stmt->bindParam(":documento_anexo", $this->documento_anexo);
        $stmt->bindParam(":descricao", $this->descricao);
        
        // execute query
        if($stmt->execute()){
            return true;
        }
        
        return false;
    }

    // Read multa/infracao entries
    public function read(){
        // select all query with related vehicle plate and motorista name
        $query = "SELECT m.id, m.veiculo_id, m.motorista_id, m.tipo_infracao, m.data, m.valor, m.situacao, m.documento_anexo, m.descricao, m.created_at, v.placa as veiculo_placa, mt.nome as motorista_nome FROM " . $this->table_name . " m LEFT JOIN veiculos v ON m.veiculo_id = v.id LEFT JOIN motoristas mt ON m.motorista_id = mt.id ORDER BY m.created_at DESC";
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // execute query
        $stmt->execute();
        
        return $stmt;
    }

    // Read single multa/infracao entry by ID
    public function readOne(){
        // query to read single record with related vehicle plate and motorista name
        $query = "SELECT m.id, m.veiculo_id, m.motorista_id, m.tipo_infracao, m.data, m.valor, m.situacao, m.documento_anexo, m.descricao, m.created_at, v.placa as veiculo_placa, mt.nome as motorista_nome FROM " . $this->table_name . " m LEFT JOIN veiculos v ON m.veiculo_id = v.id LEFT JOIN motoristas mt ON m.motorista_id = mt.id WHERE m.id = ? LIMIT 0,1";

        // prepare query statement
        $stmt = $this->conn->prepare( $query );

        // bind id of record to read
        $stmt->bindParam(1, $this->id);

        // execute query
        $stmt->execute();

        // get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // set values to object properties
        if($row) {
            $this->veiculo_id = $row['veiculo_id'];
            $this->motorista_id = $row['motorista_id'];
            $this->tipo_infracao = $row['tipo_infracao'];
            $this->data = $row['data'];
            $this->valor = $row['valor'];
            $this->situacao = $row['situacao'];
            $this->documento_anexo = $row['documento_anexo'];
            $this->descricao = $row['descricao'];
        }

        return $row;
    }

    // Update multa/infracao entry
    public function update(){
        // update query
        $query = "UPDATE " . $this->table_name . " SET veiculo_id=:veiculo_id, motorista_id=:motorista_id, tipo_infracao=:tipo_infracao, data=:data, valor=:valor, situacao=:situacao, documento_anexo=:documento_anexo, descricao=:descricao WHERE id = :id";
        
        // prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // sanitize
        $this->veiculo_id=htmlspecialchars(strip_tags($this->veiculo_id));
        $this->motorista_id=htmlspecialchars(strip_tags($this->motorista_id));
        $this->tipo_infracao=htmlspecialchars(strip_tags($this->tipo_infracao));
        $this->data=htmlspecialchars(strip_tags($this->data));
        $this->valor=htmlspecialchars(strip_tags($this->valor));
        $this->situacao=htmlspecialchars(strip_tags($this->situacao));
        $this->documento_anexo=htmlspecialchars(strip_tags($this->documento_anexo));
        $this->descricao=htmlspecialchars(strip_tags($this->descricao));
        $this->id=htmlspecialchars(strip_tags($this->id));
        
        // bind new values
        $stmt->bindParam(':veiculo_id', $this->veiculo_id);
        $stmt->bindParam(':motorista_id', $this->motorista_id);
        $stmt->bindParam(':tipo_infracao', $this->tipo_infracao);
        $stmt->bindParam(':data', $this->data);
        $stmt->bindParam(':valor', $this->valor);
        $stmt->bindParam(':situacao', $this->situacao);
        $stmt->bindParam(':documento_anexo', $this->documento_anexo);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':id', $this->id);
        
        // execute the query
        if($stmt->execute()){
            return true;
        }
        
        return false;
    }

    // Delete multa/infracao entry
    public function delete(){
        // delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // prepare query
        $stmt = $this->conn->prepare($query);
        
        // sanitize
        $this->id=htmlspecialchars(strip_tags($this->id));
        
        // bind id of record to delete
        $stmt->bindParam(':id', $this->id);
        
        // execute query
        if($stmt->execute()){
            return true;
        }
        
        return false;
    }
}

?> 