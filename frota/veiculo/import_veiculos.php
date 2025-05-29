<?php
require_once 'models/Veiculo.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    header('Location: veiculos.php');
    exit;
}

$veiculo = new Veiculo();
$message = '';
$message_type = '';

try {
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove header row
    array_shift($rows);

    $imported = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        try {
            if (empty($row[0])) continue; // Skip empty rows

            $data = [
                'placa' => $row[0],
                'marca' => $row[1],
                'modelo' => $row[2],
                'ano' => $row[3],
                'status' => $row[4],
                'km_atual' => $row[5],
                'ultima_revisao' => $row[6] ? date('Y-m-d', strtotime($row[6])) : null,
                'proxima_revisao' => $row[7] ? date('Y-m-d', strtotime($row[7])) : null,
                'observacoes' => $row[8] ?? ''
            ];

            // Validate data
            if (!preg_match('/^[A-Z]{3}[0-9]{4}$/', $data['placa'])) {
                throw new Exception("Placa inválida: {$data['placa']}");
            }

            if (!in_array($data['status'], ['disponivel', 'em_manutencao', 'indisponivel'])) {
                throw new Exception("Status inválido: {$data['status']}");
            }

            if (!is_numeric($data['ano']) || $data['ano'] < 1900 || $data['ano'] > date('Y')) {
                throw new Exception("Ano inválido: {$data['ano']}");
            }

            if (!is_numeric($data['km_atual']) || $data['km_atual'] < 0) {
                throw new Exception("KM inválido: {$data['km_atual']}");
            }

            // Check if vehicle exists
            $existing = $veiculo->getByPlaca($data['placa']);
            if ($existing) {
                $veiculo->update($existing['id'], $data);
            } else {
                $veiculo->create($data);
            }

            $imported++;
        } catch (Exception $e) {
            $errors[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
        }
    }

    if ($imported > 0) {
        $message = "Importação concluída. $imported veículos importados com sucesso.";
        $message_type = 'success';
    }

    if (!empty($errors)) {
        $message .= "\nErros encontrados:\n" . implode("\n", $errors);
        $message_type = 'warning';
    }

} catch (Exception $e) {
    $message = "Erro ao importar arquivo: " . $e->getMessage();
    $message_type = 'danger';
}

// Store message in session
session_start();
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $message_type;

header('Location: veiculos.php');
exit; 