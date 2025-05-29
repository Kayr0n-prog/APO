<?php

// Report all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection and model read operations...\n";

// Include database configuration and model files
require_once 'config/database.php';
require_once 'models/Veiculo.php';
require_once 'models/Motorista.php';
require_once 'models/MultaInfracao.php';
require_once 'models/Manutencao.php';

// --- Test Database Connection ---
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Database connection successful.\n";
} else {
    echo "Database connection failed.\n";
}

echo "\n"; // Add a newline for better formatting

// --- Test Model Read Operations ---

// Test Veiculo Model
echo "Testing Veiculo model read operation...\n";
$veiculo_model = new Veiculo();
try {
    $veiculos_stmt = $veiculo_model->read();
    if ($veiculos_stmt) {
        $num_veiculos = $veiculos_stmt->rowCount();
        echo "Veiculo read operation successful. Found " . $num_veiculos . " vehicles.\n";
    } else {
         echo "Veiculo read operation failed or returned empty statement.\n";
    }
} catch (PDOException $e) {
    echo "Veiculo read operation failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Motorista Model
echo "Testing Motorista model read operation...\n";
$motorista_model = new Motorista();
try {
    $motoristas_stmt = $motorista_model->read();
     if ($motoristas_stmt) {
        $num_motoristas = $motoristas_stmt->rowCount();
        echo "Motorista read operation successful. Found " . $num_motoristas . " drivers.\n";
    } else {
         echo "Motorista read operation failed or returned empty statement.\n";
    }
} catch (PDOException $e) {
    echo "Motorista read operation failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test MultaInfracao Model
echo "Testing MultaInfracao model read operation...\n";
$multa_model = new MultaInfracao();
try {
    $multas_stmt = $multa_model->read();
     if ($multas_stmt) {
        $num_multas = $multas_stmt->rowCount();
        echo "MultaInfracao read operation successful. Found " . $num_multas . " fines/infractions.\n";
    } else {
         echo "MultaInfracao read operation failed or returned empty statement.\n";
    }
} catch (PDOException $e) {
    echo "MultaInfracao read operation failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Manutencao Model
echo "Testing Manutencao model read operation...\n";
$manutencao_model = new Manutencao();
try {
    $manutencoes_stmt = $manutencao_model->read();
     if ($manutencoes_stmt) {
        $num_manutencoes = $manutencoes_stmt->rowCount();
        echo "Manutencao read operation successful. Found " . $num_manutencoes . " maintenances.\n";
    } else {
         echo "Manutencao read operation failed or returned empty statement.\n";
    }
} catch (PDOException $e) {
    echo "Manutencao read operation failed: " . $e->getMessage() . "\n";
}

echo "\n";

echo "Testing complete.\n";

?> 