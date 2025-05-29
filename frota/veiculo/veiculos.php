<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'models/Veiculo.php';
require_once 'models/Manutencao.php';

$veiculo = new Veiculo();
$manutencao = new Manutencao();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $status = $_POST['status'];
                    if ($status === 'disponivel') {
                        $status = 'disponivel';
                    } elseif ($status === 'em_manutencao') {
                        $status = 'em_manutencao';
                    } else {
                        $status = 'indisponivel';
                    }
                    
                    $veiculo->create([
                        'placa' => $_POST['placa'],
                        'marca' => $_POST['marca'],
                        'modelo' => $_POST['modelo'],
                        'ano' => $_POST['ano'],
                        'status' => $status,
                        'km_atual' => $_POST['km_atual'],
                        'ultima_revisao' => $_POST['ultima_revisao'],
                        'proxima_revisao' => $_POST['proxima_revisao'],
                        'observacoes' => $_POST['observacoes']
                    ]);
                    $message = 'Veículo cadastrado com sucesso!';
                    $message_type = 'success';
                    break;

                case 'update':
                    $status = $_POST['status'];
                    if ($status === 'disponivel') {
                        $status = 'disponivel';
                    } elseif ($status === 'em_manutencao') {
                        $status = 'em_manutencao';
                    } else {
                        $status = 'indisponivel';
                    }
                    
                    $veiculo->update($_POST['id'], [
                        'placa' => $_POST['placa'],
                        'marca' => $_POST['marca'],
                        'modelo' => $_POST['modelo'],
                        'ano' => $_POST['ano'],
                        'status' => $status,
                        'km_atual' => $_POST['km_atual'],
                        'ultima_revisao' => $_POST['ultima_revisao'],
                        'proxima_revisao' => $_POST['proxima_revisao'],
                        'observacoes' => $_POST['observacoes']
                    ]);
                    $message = 'Veículo atualizado com sucesso!';
                    $message_type = 'success';
                    break;

                case 'delete':
                    $veiculo->delete($_POST['id']);
                    $message = 'Veículo removido com sucesso!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all vehicles
$veiculos = $veiculo->read();

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
        <h2>Veículos</h2>
        <div>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-excel"></i> Importar
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVeiculoModal">
                <i class="bi bi-plus"></i> Novo Veículo
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="disponivel">Disponível</option>
                        <option value="em_manutencao">Em Manutenção</option>
                        <option value="indisponivel">Indisponível</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input type="text" class="form-control" name="marca">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo</label>
                    <input type="text" class="form-control" name="modelo">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ano</label>
                    <input type="number" class="form-control" name="ano">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <button type="reset" class="btn btn-secondary">Limpar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Status</th>
                    <th>KM Atual</th>
                    <th>Última Revisão</th>
                    <th>Próxima Revisão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($veiculos as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['placa']); ?></td>
                    <td><?php echo htmlspecialchars($v['marca']); ?></td>
                    <td><?php echo htmlspecialchars($v['modelo']); ?></td>
                    <td><?php echo htmlspecialchars($v['ano']); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            if ($v['status'] === 'disponivel') {
                                echo 'success';
                            } elseif ($v['status'] === 'em_manutencao') {
                                echo 'warning';
                            } else { // Assuming 'indisponivel'
                                echo 'danger';
                            }
                        ?>"><?php echo ucfirst($v['status']); ?></span>
                    </td>
                    <td><?php echo number_format($v['km_atual'], 0, ',', '.'); ?> km</td>
                    <td><?php echo $v['ultima_revisao'] ? date('d/m/Y', strtotime($v['ultima_revisao'])) : '-'; ?></td>
                    <td><?php echo $v['proxima_revisao'] ? date('d/m/Y', strtotime($v['proxima_revisao'])) : '-'; ?></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary" 
                                    onclick="editVeiculo(<?php echo htmlspecialchars(json_encode($v)); ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-info" 
                                    onclick="viewHistory(<?php echo $v['id']; ?>)">
                                <i class="bi bi-clock-history"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteVeiculo(<?php echo $v['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Vehicle Modal -->
<div class="modal fade" id="createVeiculoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Placa</label>
                            <input type="text" class="form-control" name="placa" required 
                                   pattern="[A-Z]{3}[0-9]{4}" title="Placa no formato ABC1234">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="disponivel">Disponível</option>
                                <option value="em_manutencao">Em Manutenção</option>
                                <option value="indisponivel">Indisponível</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" name="marca" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ano</label>
                            <input type="number" class="form-control" name="ano" required 
                                   min="1900" max="<?php echo date('Y'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">KM Atual</label>
                            <input type="number" class="form-control" name="km_atual" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Última Revisão</label>
                            <input type="date" class="form-control" name="ultima_revisao">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Próxima Revisão</label>
                            <input type="date" class="form-control" name="proxima_revisao">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="3"></textarea>
                        </div>
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

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVeiculoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Placa</label>
                            <input type="text" class="form-control" name="placa" id="edit_placa" required 
                                   pattern="[A-Z]{3}[0-9]{4}" title="Placa no formato ABC1234">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="disponivel">Disponível</option>
                                <option value="em_manutencao">Em Manutenção</option>
                                <option value="indisponivel">Indisponível</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" name="marca" id="edit_marca" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" id="edit_modelo" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ano</label>
                            <input type="number" class="form-control" name="ano" id="edit_ano" required 
                                   min="1900" max="<?php echo date('Y'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">KM Atual</label>
                            <input type="number" class="form-control" name="km_atual" id="edit_km_atual" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Última Revisão</label>
                            <input type="date" class="form-control" name="ultima_revisao" id="edit_ultima_revisao">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Próxima Revisão</label>
                            <input type="date" class="form-control" name="proxima_revisao" id="edit_proxima_revisao">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" id="edit_observacoes" rows="3"></textarea>
                        </div>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Veículos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="import_veiculos.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Arquivo Excel</label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> O arquivo deve conter as colunas: Placa, Marca, Modelo, Ano, Status, KM Atual, Última Revisão, Próxima Revisão, Observações
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vehicle History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histórico do Veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Vehicle Form -->
<form id="deleteVeiculoForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editVeiculo(veiculo) {
    document.getElementById('edit_id').value = veiculo.id;
    document.getElementById('edit_placa').value = veiculo.placa;
    document.getElementById('edit_marca').value = veiculo.marca;
    document.getElementById('edit_modelo').value = veiculo.modelo;
    document.getElementById('edit_ano').value = veiculo.ano;
    document.getElementById('edit_status').value = veiculo.status;
    document.getElementById('edit_km_atual').value = veiculo.km_atual;
    document.getElementById('edit_ultima_revisao').value = veiculo.ultima_revisao;
    document.getElementById('edit_proxima_revisao').value = veiculo.proxima_revisao;
    document.getElementById('edit_observacoes').value = veiculo.observacoes;
    
    new bootstrap.Modal(document.getElementById('editVeiculoModal')).show();
}

function deleteVeiculo(id) {
    if (confirm('Tem certeza que deseja excluir este veículo?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteVeiculoForm').submit();
    }
}

function viewHistory(id) {
    // Load vehicle history via AJAX
    fetch(`get_vehicle_history.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('historyContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('historyModal')).show();
        });
}
</script>

<?php include 'includes/footer.php'; ?> 