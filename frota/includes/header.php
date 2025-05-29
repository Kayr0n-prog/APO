<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gerenciamento de Frota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #f8f9fa;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .nav-link {
            font-weight: 500;
            color: #333;
        }
        .nav-link.active {
            color: #2470dc;
        }
        .nav-link:hover {
            color: #2470dc;
        }
        .nav-link i {
            margin-right: 8px;
        }
        main {
            padding-top: 48px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Sistema de Frota</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="veiculos.php">
                                <i class="bi bi-truck"></i>
                                Veículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="motoristas.php">
                                <i class="bi bi-person"></i>
                                Motoristas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manutencoes.php">
                                <i class="bi bi-tools"></i>
                                Manutenções
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="abastecimentos.php">
                                <img src="img/bomba.png" alt="Abastecimento Icon" style="width: 16px; height: 16px; margin-right: 8px;">
                                Abastecimento
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="multas_infracoes.php">
                                <i class="bi bi-journal-text"></i>
                                Multas e Infrações
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php if (isset($message) && $message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-3" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?> 