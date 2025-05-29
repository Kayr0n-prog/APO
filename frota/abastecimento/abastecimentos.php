<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'models/Abastecimento.php';
require_once 'models/Veiculo.php';

$abastecimento_model = new Abastecimento();
$veiculo_model = new Veiculo();

// Initialize message variables from session if they exist (Flash Messages)
$message = $_SESSION['flash_message'] ?? '';
$message_type = $_SESSION['flash_message_type'] ?? '';

// Clear flash messages from session after reading them
unset($_SESSION['flash_message']);
unset($_SESSION['flash_message_type']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $abastecimento_model->create([
                        'veiculo_id' => $_POST['veiculo_id'],
                        'data_abastecimento' => $_POST['data_abastecimento'],
                        'litros' => $_POST['litros'],
                        'valor' => $_POST['valor'],
                        'km_atual' => $_POST['km_atual']
                    ]);
                    $_SESSION['flash_message'] = 'Abastecimento registrado com sucesso!';
                    $_SESSION['flash_message_type'] = 'success';
                    break;

                case 'update':
                    $abastecimento_model->update($_POST['id'], [
                        'veiculo_id' => $_POST['veiculo_id'],
                        'data_abastecimento' => $_POST['data_abastecimento'],
                        'litros' => $_POST['litros'],
                        'valor' => $_POST['valor'],
                        'km_atual' => $_POST['km_atual']
                    ]);
                     $_SESSION['flash_message'] = 'Abastecimento atualizado com sucesso!';
                    $_SESSION['flash_message_type'] = 'success';
                    break;

                case 'delete':
                    $abastecimento_model->delete($_POST['id']);
                    $_SESSION['flash_message'] = 'Abastecimento removido com sucesso!';
                    $_SESSION['flash_message_type'] = 'success';
                    break;
            }
             header('Location: abastecimentos.php');
             exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_message_type'] = 'danger';
        header('Location: abastecimentos.php');
        exit;
    }
}

// Get all fuel records
$abastecimentos = $abastecimento_model->read();
// Get all vehicles for the dropdown
$veiculos = $veiculo_model->read();

// Calculate average consumption for each vehicle (basic)
$consumo_medio = [];
foreach ($veiculos as $v) {
    $consumo = $abastecimento_model->calcularConsumoMedio($v['id']);
    if ($consumo !== null) {
        $consumo_medio[$v['id']] = number_format($consumo, 2, ',', '.') . ' km/l';
    } else {
        $consumo_medio[$v['id']] = 'N/A';
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Controle de Abastecimento</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAbastecimentoModal">
            <i class="bi bi-plus"></i> Novo Abastecimento
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Consumo Médio por Veículo</h5>
        </div>
        <div class="card-body">
            <ul>
                <?php foreach ($veiculos as $v): ?>
                    <li><?php echo htmlspecialchars($v['placa'] . ' - ' . $v['modelo']); ?>: <?php echo $consumo_medio[$v['id']]; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Veículo</th>
                    <th>Data</th>
                    <th>Litros</th>
                    <th>Valor</th>
                    <th>KM Atual</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($abastecimentos as $a): 
                    $veiculo_data = $veiculo_model->read($a['veiculo_id'])[0];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($veiculo_data['placa'] . ' - ' . $veiculo_data['modelo']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($a['data_abastecimento'])); ?></td>
                    <td><?php echo number_format($a['litros'], 2, ',', '.'); ?> L</td>
                    <td>R$ <?php echo number_format($a['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($a['km_atual'], 0, ',', '.'); ?> km</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editAbastecimento(<?php echo htmlspecialchars(json_encode($a)); ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="deleteAbastecimento(<?php echo $a['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Abastecimento Modal -->
<div class="modal fade" id="createAbastecimentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Abastecimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Veículo</label>
                        <select class="form-select" name="veiculo_id" required>
                            <option value="">Selecione um veículo</option>
                            <?php foreach ($veiculos as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo htmlspecialchars($v['placa'] . ' - ' . $v['modelo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data do Abastecimento</label>
                        <input type="date" class="form-control" name="data_abastecimento" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Litros</label>
                        <input type="number" class="form-control" name="litros" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" class="form-control" name="valor" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">KM Atual</label>
                        <input type="number" class="form-control" name="km_atual" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Abastecimento Modal -->
<div class="modal fade" id="editAbastecimentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Abastecimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Veículo</label>
                        <select class="form-select" name="veiculo_id" id="edit_veiculo_id" required>
                            <option value="">Selecione um veículo</option>
                            <?php foreach ($veiculos as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo htmlspecialchars($v['placa'] . ' - ' . $v['modelo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data do Abastecimento</label>
                        <input type="date" class="form-control" name="data_abastecimento" id="edit_data_abastecimento" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Litros</label>
                        <input type="number" class="form-control" name="litros" id="edit_litros" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" class="form-control" name="valor" id="edit_valor" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">KM Atual</label>
                        <input type="number" class="form-control" name="km_atual" id="edit_km_atual" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Abastecimento Form -->
<form id="deleteAbastecimentoForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editAbastecimento(abastecimento) {
    document.getElementById('edit_id').value = abastecimento.id;
    document.getElementById('edit_veiculo_id').value = abastecimento.veiculo_id;
    document.getElementById('edit_data_abastecimento').value = abastecimento.data_abastecimento;
    document.getElementById('edit_litros').value = abastecimento.litros;
    document.getElementById('edit_valor').value = abastecimento.valor;
    document.getElementById('edit_km_atual').value = abastecimento.km_atual;
    
    new bootstrap.Modal(document.getElementById('editAbastecimentoModal')).show();
}

function deleteAbastecimento(id) {
    if (confirm('Tem certeza que deseja excluir este abastecimento?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteAbastecimentoForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 