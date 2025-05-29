<?php

// Report all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Fetching vehicle data...\n";

// Include database configuration and vehicle model
require_once 'config/database.php';
require_once 'models/Veiculo.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.\n");
}

// Instantiate Veiculo object
$veiculo_model = new Veiculo();

// Read vehicles from the database
try {
    $veiculos_stmt = $veiculo_model->read();

    if ($veiculos_stmt && $veiculos_stmt->rowCount() > 0) {
        echo "\nVeículos encontrados:\n";
        echo "-----------------------------------------------------\n";
        // Fetch and print vehicle details
        while ($row = $veiculos_stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            echo "ID: {$id} | Placa: {$placa} | Modelo: {$modelo} | Status: {$status}\n";
        }
         echo "-----------------------------------------------------\n";
    } else {
        echo "No vehicles found or read operation failed.\n";
    }

} catch (PDOException $e) {
    echo "Error reading vehicles: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";

?> 