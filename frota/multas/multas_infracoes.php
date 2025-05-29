<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include necessary models
require_once 'models/Veiculo.php';
require_once 'models/Motorista.php';
require_once 'models/MultaInfracao.php';

$message = '';
$message_type = '';

// Handle form submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $multa = new MultaInfracao();

    $multa->veiculo_id = $_POST['veiculo_id'];
    $multa->motorista_id = $_POST['motorista_id'];
    $multa->tipo_infracao = $_POST['tipo_infracao'];
    $multa->data = $_POST['data'];
    $multa->valor = $_POST['valor'];
    $multa->situacao = $_POST['situacao'];
    $multa->descricao = $_POST['descricao'];

    // Handle file upload for documento_anexo
    if (isset($_FILES['documento_anexo']) && $_FILES['documento_anexo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/multas/';
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['documento_anexo']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('multa_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['documento_anexo']['tmp_name'], $target_file)) {
            $multa->documento_anexo = $file_name;
        } else {
            $message = 'Erro ao fazer upload do documento.';
            $message_type = 'danger';
        }
    } else {
        $multa->documento_anexo = ''; // No file uploaded
    }

    // Save the multa entry
    if (empty($message)) { // Only try to save if no upload error
        if ($multa->create()) {
            $message = 'Multa/Infração registrada com sucesso!';
            $message_type = 'success';
             // Redirect to prevent form resubmission
            header('Location: multas_infracoes.php?message=' . urlencode($message) . '&message_type=' . $message_type);
            exit;
        } else {
            $message = 'Erro ao registrar multa/infração.';
            $message_type = 'danger';
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Handle update request
    $multa = new MultaInfracao();
    $multa->id = $_POST['id'];

    // Get existing data to handle file update
    $existing_multa = $multa->readOne();

    $multa->veiculo_id = $_POST['veiculo_id'];
    $multa->motorista_id = $_POST['motorista_id'];
    $multa->tipo_infracao = $_POST['tipo_infracao'];
    $multa->data = $_POST['data'];
    $multa->valor = $_POST['valor'];
    $multa->situacao = $_POST['situacao'];
    $multa->descricao = $_POST['descricao'];

    // Handle file upload for documento_anexo during update
    if (isset($_FILES['documento_anexo']) && $_FILES['documento_anexo']['error'] === UPLOAD_ERR_OK) {
         $upload_dir = 'uploads/multas/';
         // Create upload directory if it doesn't exist
         if (!is_dir($upload_dir)) {
             mkdir($upload_dir, 0777, true);
         }
         $file_ext = pathinfo($_FILES['documento_anexo']['name'], PATHINFO_EXTENSION);
         $file_name = uniqid('multa_') . '.' . $file_ext;
         $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['documento_anexo']['tmp_name'], $target_file)) {
            $multa->documento_anexo = $file_name;
            // Optionally delete the old file if it exists
            if ($existing_multa && $existing_multa['documento_anexo'] && file_exists($upload_dir . $existing_multa['documento_anexo'])) {
                 unlink($upload_dir . $existing_multa['documento_anexo']);
            }
        } else {
            $message = 'Erro ao fazer upload do novo documento.';
            $message_type = 'danger';
             // Retain existing document if upload fails
            $multa->documento_anexo = $_POST['existing_documento_anexo'] ?? '';
        }
    } else {
         // Retain existing document if no new file is uploaded
        $multa->documento_anexo = $_POST['existing_documento_anexo'] ?? '';
    }

    // Update the multa entry
     if (empty($message)) { // Only try to update if no upload error
        if ($multa->update()) {
            $message = 'Multa/Infração atualizada com sucesso!';
            $message_type = 'success';
            // Redirect to prevent form resubmission
            header('Location: multas_infracoes.php?message=' . urlencode($message) . '&message_type=' . $message_type);
            exit;
        } else {
            $message = 'Erro ao atualizar multa/infração.';
            $message_type = 'danger';
        }
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $multa = new MultaInfracao();
    $multa->id = $_GET['delete'];

    if ($multa->delete()) {
        $message = 'Multa/Infração excluída com sucesso!';
        $message_type = 'success';
        // Redirect to clear the GET parameter
        header('Location: multas_infracoes.php?message=' . urlencode($message) . '&message_type=' . $message_type);
        exit;
    } else {
        $message = 'Erro ao excluir multa/infração.';
        $message_type = 'danger';
    }
}

// Handle edit request (fetch data for the form)
$multa_to_edit = null;
if (isset($_GET['edit'])) {
    $multa = new MultaInfracao();
    $multa->id = $_GET['edit'];
    $multa_to_edit = $multa->readOne();
}

// Fetch existing multa entries
$multa_model = new MultaInfracao();
$multas_stmt = $multa_model->read();
$multas = $multas_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Lançamento de Multas e Infrações</h1>

    <?php if (isset($message) && $message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-3" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Form to Add/Edit Multa Entry -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo $multa_to_edit ? 'Editar Multa/Infração' : 'Adicionar Nova Multa/Infração'; ?></h5>
        </div>
        <div class="card-body">
            <form action="multas_infracoes.php" method="POST" enctype="multipart/form-data">
                <?php if ($multa_to_edit): ?>
                    <input type="hidden" name="id" value="<?php echo $multa_to_edit['id']; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="veiculo" class="form-label">Veículo</label>
                        <select class="form-select" id="veiculo" name="veiculo_id" required>
                            <option value="">Selecione o Veículo</option>
                            <?php
                                require_once 'models/Veiculo.php';
                                $veiculo_model = new Veiculo();
                                $veiculos = $veiculo_model->read();
                                foreach ($veiculos as $v) {
                                    $selected = ($multa_to_edit && $v['id'] == $multa_to_edit['veiculo_id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($v['id']) . '" ' . $selected . '>' . htmlspecialchars($v['placa'] . ' - ' . $v['modelo']) . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="motorista" class="form-label">Motorista Responsável</label>
                        <select class="form-select" id="motorista" name="motorista_id" required>
                            <option value="">Selecione o Motorista</option>
                             <?php
                                require_once 'models/Motorista.php';
                                $motorista_model = new Motorista();
                                $motoristas = $motorista_model->read();
                                foreach ($motoristas as $m) {
                                     $selected = ($multa_to_edit && $m['id'] == $multa_to_edit['motorista_id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($m['id']) . '" ' . $selected . '>' . htmlspecialchars($m['nome']) . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipo_infracao" class="form-label">Tipo de Infração</label>
                        <input type="text" class="form-control" id="tipo_infracao" name="tipo_infracao" value="<?php echo $multa_to_edit ? htmlspecialchars($multa_to_edit['tipo_infracao']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="data" class="form-label">Data</label>
                        <input type="date" class="form-control" id="data" name="data" value="<?php echo $multa_to_edit ? htmlspecialchars($multa_to_edit['data']) : ''; ?>" required>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="valor" class="form-label">Valor</label>
                        <input type="number" class="form-control" id="valor" name="valor" step="0.01" value="<?php echo $multa_to_edit ? htmlspecialchars($multa_to_edit['valor']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="situacao" class="form-label">Situação</label>
                        <select class="form-select" id="situacao" name="situacao" required>
                            <option value="paga" <?php echo ($multa_to_edit && $multa_to_edit['situacao'] === 'paga') ? 'selected' : ''; ?>>Paga</option>
                            <option value="nao_paga" <?php echo ($multa_to_edit && $multa_to_edit['situacao'] === 'nao_paga') ? 'selected' : ''; ?>>Não Paga</option>
                        </select>
                    </div>
                </div>
                 <div class="mb-3">
                    <label for="documento_anexo" class="form-label">Documento Anexo</label>
                    <input type="file" class="form-control" id="documento_anexo" name="documento_anexo">
                    <?php if ($multa_to_edit && $multa_to_edit['documento_anexo']): ?>
                        <p class="form-text">Arquivo atual: <a href="uploads/multas/<?php echo htmlspecialchars($multa_to_edit['documento_anexo']); ?>" target="_blank"><?php echo htmlspecialchars($multa_to_edit['documento_anexo']); ?></a></p>
                         <input type="hidden" name="existing_documento_anexo" value="<?php echo htmlspecialchars($multa_to_edit['documento_anexo']); ?>">
                    <?php endif; ?>
                </div>
                 <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $multa_to_edit ? htmlspecialchars($multa_to_edit['descricao']) : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $multa_to_edit ? 'Atualizar Multa/Infração' : 'Salvar Multa/Infração'; ?></button>
            </form>
        </div>
    </div>

    <!-- Table to Display Multa Entries -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Histórico de Multas e Infrações</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Veículo</th>
                            <th>Motorista Responsável</th>
                            <th>Tipo de Infração</th>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Situação</th>
                            <th>Documento Anexo</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (empty($multas)) {
                                echo '<tr><td colspan="9" class="text-center">Nenhuma multa ou infração registrada.</td></tr>';
                            } else {
                                foreach ($multas as $multa) {
                                    echo '<tr>';
                                    // Assuming methods to get vehicle plate and motorista name from IDs
                                    echo '<td>' . htmlspecialchars($multa['veiculo_placa']) . '</td>';
                                    echo '<td>' . htmlspecialchars($multa['motorista_nome']) . '</td>';
                                    echo '<td>' . htmlspecialchars($multa['tipo_infracao']) . '</td>';
                                    echo '<td>' . date('d/m/Y', strtotime($multa['data'])) . '</td>';
                                    echo '<td>R$ ' . number_format($multa['valor'], 2, ',', '.') . '</td>';
                                    echo '<td>' . ucfirst(str_replace('_', ' ', $multa['situacao'])) . '</td>';
                                    echo '<td>' . ($multa['documento_anexo'] ? '<a href="uploads/multas/' . htmlspecialchars($multa['documento_anexo']) . '" target="_blank">Ver Anexo</a>' : 'Sem Anexo') . '</td>';
                                     echo '<td>' . htmlspecialchars($multa['descricao']) . '</td>';
                                    echo '<td>';
                                    echo '<a href="multas_infracoes.php?edit=' . $multa['id'] . '" class="btn btn-primary btn-sm me-1">Editar</a>';
                                    echo '<a href="multas_infracoes.php?delete=' . $multa['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir esta multa/infração?\')">Excluir</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 