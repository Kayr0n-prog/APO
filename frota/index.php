<?php
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'models/Veiculo.php';
require_once 'models/Motorista.php';
require_once 'models/Manutencao.php';

$veiculo = new Veiculo();
$motorista = new Motorista();
$manutencao = new Manutencao();

// Get statistics
$totalVeiculos = count($veiculo->read());
$totalMotoristas = count($motorista->read());
$veiculosDisponiveis = count(array_filter($veiculo->read(), function($v) { return $v['status'] === 'disponivel'; }));
$manutencoesPendentes = count(array_filter($manutencao->read(), function($m) { return $m['status'] === 'pendente'; }));

// Get recent activities
$ultimasManutencoes = $manutencao->readLatestWithVehiclePlate(5);

include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h2 class="card-title">Bem-vindo ao Sistema de Gestão de Frota</h2>
                    <p class="card-text">Gerencie sua frota de veículos de forma eficiente e organizada.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Veículos Disponíveis</h5>
                    <h2 class="display-4"><?php echo $veiculosDisponiveis; ?></h2>
                    <p class="card-text">de <?php echo $totalVeiculos; ?> veículos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Motoristas</h5>
                    <h2 class="display-4"><?php echo $totalMotoristas; ?></h2>
                    <p class="card-text">motoristas cadastrados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Manutenções Pendentes</h5>
                    <h2 class="display-4"><?php echo $manutencoesPendentes; ?></h2>
                    <p class="card-text">requerem atenção</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Veículos em Manutenção</h5>
                    <h2 class="display-4"><?php echo $totalVeiculos - $veiculosDisponiveis; ?></h2>
                    <p class="card-text">indisponíveis</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="veiculos.php" class="btn btn-primary w-100">
                                <i class="bi bi-car-front"></i> Gerenciar Veículos
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="motoristas.php" class="btn btn-info w-100">
                                <i class="bi bi-person"></i> Gerenciar Motoristas
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manutencoes.php" class="btn btn-warning w-100">
                                <i class="bi bi-tools"></i> Gerenciar Manutenções
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Últimas Manutenções</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Veículo</th>
                                    <th>Tipo</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasManutencoes as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['veiculo_placa']); ?></td>
                                    <td><?php echo htmlspecialchars($m['tipo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($m['data_manutencao'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $m['status'] === 'concluida' ? 'success' : 
                                                ($m['status'] === 'pendente' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($m['status']); ?>
                                        </span>
                                    </td>
                                    <td>R$ <?php echo number_format($m['custo'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatório Modal -->
<div class="modal fade" id="relatorioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerar Relatório</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="relatorios.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Relatório</label>
                        <select class="form-select" name="tipo_relatorio" required>
                            <option value="veiculos">Veículos</option>
                            <option value="manutencoes">Manutenções</option>
                            <option value="custos">Custos</option>
                            <option value="motoristas">Motoristas</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="data_inicio" required>
                            </div>
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="data_fim" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="formato" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 