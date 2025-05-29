<?php
require_once 'models/Motorista.php';

$motorista = new Motorista();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $motorista->create([
                        'nome' => $_POST['nome'],
                        'cnh' => $_POST['cnh'],
                        'categoria' => $_POST['categoria'],
                        'validade_cnh' => $_POST['validade_cnh'],
                        'status' => $_POST['status']
                    ]);
                    $message = 'Motorista cadastrado com sucesso!';
                    $message_type = 'success';
                    break;

                case 'update':
                    $motorista->update($_POST['id'], [
                        'nome' => $_POST['nome'],
                        'cnh' => $_POST['cnh'],
                        'categoria' => $_POST['categoria'],
                        'validade_cnh' => $_POST['validade_cnh'],
                        'status' => $_POST['status']
                    ]);
                    $message = 'Motorista atualizado com sucesso!';
                    $message_type = 'success';
                    break;

                case 'delete':
                    $motorista->delete($_POST['id']);
                    $message = 'Motorista removido com sucesso!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all drivers
$motoristas = $motorista->read();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Motoristas</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMotoristaModal">
            <i class="bi bi-plus"></i> Novo Motorista
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CNH</th>
                    <th>Categoria</th>
                    <th>Validade CNH</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($motoristas as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['nome']); ?></td>
                    <td><?php echo htmlspecialchars($m['cnh']); ?></td>
                    <td><?php echo htmlspecialchars($m['categoria']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($m['validade_cnh'])); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $m['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($m['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editMotorista(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="deleteMotorista(<?php echo $m['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Driver Modal -->
<div class="modal fade" id="createMotoristaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Motorista</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CNH</label>
                        <input type="text" class="form-control" name="cnh" required 
                               pattern="[0-9]{11}" title="CNH deve conter 11 dígitos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="AB">AB</option>
                            <option value="AC">AC</option>
                            <option value="AD">AD</option>
                            <option value="AE">AE</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Validade CNH</label>
                        <input type="date" class="form-control" name="validade_cnh" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
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

<!-- Edit Driver Modal -->
<div class="modal fade" id="editMotoristaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Motorista</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="nome" id="edit_nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CNH</label>
                        <input type="text" class="form-control" name="cnh" id="edit_cnh" required 
                               pattern="[0-9]{11}" title="CNH deve conter 11 dígitos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria" id="edit_categoria" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="AB">AB</option>
                            <option value="AC">AC</option>
                            <option value="AD">AD</option>
                            <option value="AE">AE</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Validade CNH</label>
                        <input type="date" class="form-control" name="validade_cnh" id="edit_validade_cnh" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
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

<!-- Delete Driver Form -->
<form id="deleteMotoristaForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editMotorista(motorista) {
    document.getElementById('edit_id').value = motorista.id;
    document.getElementById('edit_nome').value = motorista.nome;
    document.getElementById('edit_cnh').value = motorista.cnh;
    document.getElementById('edit_categoria').value = motorista.categoria;
    document.getElementById('edit_validade_cnh').value = motorista.validade_cnh;
    document.getElementById('edit_status').value = motorista.status;
    
    new bootstrap.Modal(document.getElementById('editMotoristaModal')).show();
}

function deleteMotorista(id) {
    if (confirm('Tem certeza que deseja excluir este motorista?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteMotoristaForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 