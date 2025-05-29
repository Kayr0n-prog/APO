<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'models/Veiculo.php';
require_once 'models/Manutencao.php';

if (!isset($_GET['id'])) {
    die('ID do veículo não fornecido');
}

$veiculo = new Veiculo();
$manutencao = new Manutencao();

$veiculo_data = $veiculo->read($_GET['id'])[0];
$manutencoes = $manutencao->getByVeiculo($_GET['id']);

// Group maintenance by type
$manutencoes_por_tipo = [];
foreach ($manutencoes as $m) {
    if (!isset($manutencoes_por_tipo[$m['tipo']])) {
        $manutencoes_por_tipo[$m['tipo']] = [];
    }
    $manutencoes_por_tipo[$m['tipo']][] = $m;
}

// Calculate total costs
$custo_total = array_sum(array_column($manutencoes, 'valor'));
?>

<div class="vehicle-info mb-4">
    <h4><?php echo htmlspecialchars($veiculo_data['marca'] . ' ' . $veiculo_data['modelo']); ?></h4>
    <p class="text-muted">Placa: <?php echo htmlspecialchars($veiculo_data['placa']); ?></p>
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">KM Atual</h6>
                    <p class="card-text"><?php echo number_format($veiculo_data['km_atual'], 0, ',', '.'); ?> km</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Última Revisão</h6>
                    <p class="card-text"><?php echo $veiculo_data['ultima_revisao'] ? date('d/m/Y', strtotime($veiculo_data['ultima_revisao'])) : '-'; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Próxima Revisão</h6>
                    <p class="card-text"><?php echo $veiculo_data['proxima_revisao'] ? date('d/m/Y', strtotime($veiculo_data['proxima_revisao'])) : '-'; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Custo Total</h6>
                    <p class="card-text">R$ <?php echo number_format($custo_total, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="maintenance-history">
    <?php foreach ($manutencoes_por_tipo as $tipo => $manutencoes): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo ucfirst($tipo); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>KM</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manutencoes as $m): ?>
                        <tr>
                            <td><?php echo isset($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '-'; ?></td>
                            <td><?php echo isset($m['km']) ? number_format($m['km'], 0, ',', '.') . ' km' : '-'; ?></td>
                            <td><?php echo htmlspecialchars($m['descricao']); ?></td>
                            <td>R$ <?php echo isset($m['valor']) ? number_format($m['valor'], 2, ',', '.') : '0,00'; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $m['status'] === 'concluida' ? 'success' : 
                                        ($m['status'] === 'pendente' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($m['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($manutencoes)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Nenhuma manutenção registrada para este veículo.
</div>
<?php endif; ?> 