<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'models/Manutencao.php';
require_once 'models/Veiculo.php';

$manutencao = new Manutencao();
$veiculo = new Veiculo();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $manutencao->create([
                        'veiculo_id' => $_POST['veiculo_id'],
                        'descricao' => $_POST['descricao'],
                        'data_manutencao' => $_POST['data_manutencao'],
                        'custo' => $_POST['custo'],
                        'tipo' => $_POST['tipo'],
                        'status' => $_POST['status']
                    ]);
                    $message = 'Manutenção registrada com sucesso!';
                    $message_type = 'success';
                    break;

                case 'update':
                    $manutencao->update($_POST['id'], [
                        'veiculo_id' => $_POST['veiculo_id'],
                        'descricao' => $_POST['descricao'],
                        'data_manutencao' => $_POST['data_manutencao'],
                        'custo' => $_POST['custo'],
                        'tipo' => $_POST['tipo'],
                        'status' => $_POST['status']
                    ]);
                    $message = 'Manutenção atualizada com sucesso!';
                    $message_type = 'success';
                    break;

                case 'delete':
                    $manutencao->delete($_POST['id']);
                    $message = 'Manutenção removida com sucesso!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all maintenance records
$manutencoes = $manutencao->read();
// Get all vehicles for the dropdown
$veiculos = $veiculo->read();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manutenções</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createManutencaoModal">
            <i class="bi bi-plus"></i> Nova Manutenção
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Veículo</th>
                    <th>Descrição</th>
                    <th>Data</th>
                    <th>Custo</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manutencoes as $m): 
                    $veiculo_data = $veiculo->read($m['veiculo_id'])[0];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($veiculo_data['placa'] . ' - ' . $veiculo_data['modelo']); ?></td>
                    <td><?php echo htmlspecialchars($m['descricao']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($m['data_manutencao'])); ?></td>
                    <td>R$ <?php echo number_format($m['custo'], 2, ',', '.'); ?></td>
                    <td><?php echo ucfirst($m['tipo']); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $m['status'] === 'concluida' ? 'success' : 
                                ($m['status'] === 'em_andamento' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $m['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editManutencao(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="deleteManutencao(<?php echo $m['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Maintenance Modal -->
<div class="modal fade" id="createManutencaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Manutenção</h5>
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
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" required rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da Manutenção</label>
                        <input type="date" class="form-control" name="data_manutencao" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custo (R$)</label>
                        <input type="number" class="form-control" name="custo" required 
                               step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="preventiva">Preventiva</option>
                            <option value="corretiva">Corretiva</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="agendada">Agendada</option>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="concluida">Concluída</option>
                        </select>
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

<!-- Edit Maintenance Modal -->
<div class="modal fade" id="editManutencaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Manutenção</h5>
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
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" id="edit_descricao" required rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da Manutenção</label>
                        <input type="date" class="form-control" name="data_manutencao" id="edit_data_manutencao" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custo (R$)</label>
                        <input type="number" class="form-control" name="custo" id="edit_custo" required 
                               step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" id="edit_tipo" required>
                            <option value="preventiva">Preventiva</option>
                            <option value="corretiva">Corretiva</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="agendada">Agendada</option>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="concluida">Concluída</option>
                        </select>
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

<!-- Delete Maintenance Form -->
<form id="deleteManutencaoForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editManutencao(manutencao) {
    document.getElementById('edit_id').value = manutencao.id;
    document.getElementById('edit_veiculo_id').value = manutencao.veiculo_id;
    document.getElementById('edit_descricao').value = manutencao.descricao;
    document.getElementById('edit_data_manutencao').value = manutencao.data_manutencao;
    document.getElementById('edit_custo').value = manutencao.custo;
    document.getElementById('edit_tipo').value = manutencao.tipo;
    document.getElementById('edit_status').value = manutencao.status;
    
    new bootstrap.Modal(document.getElementById('editManutencaoModal')).show();
}

function deleteManutencao(id) {
    if (confirm('Tem certeza que deseja excluir esta manutenção?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteManutencaoForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 