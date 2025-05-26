<?php
require_once '../Geral/funcoes.php';

$pedidos = buscarPedidos(); // Usando a função do funcoes.php
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Pedidos - PedidoPronto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f94144;
            --light: #f8f9fa;
            --dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        h1, h2 {
            font-weight: 500;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background-color: var(--secondary);
        }

        .btn.logout {
            background-color: var(--danger);
        }

        .btn.logout:hover {
            background-color: #c82333;
        }

        .pedidos-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .pedidos-table th, 
        .pedidos-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .pedidos-table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #666;
        }

        .pedidos-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pendente {
            background-color: var(--warning);
            color: white;
        }

        .status-preparando {
            background-color: #ffc107;
            color: #212529;
        }

        .status-pronto {
            background-color: var(--success);
            color: white;
        }

        .status-entregue {
            background-color: #6c757d;
            color: white;
        }

        .status-cancelado {
            background-color: var(--danger);
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .pedidos-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="index.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='../Gerente/index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn" onclick="location.href='../Cardapio/mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn" onclick="location.href='../Clientes/clientes.php'">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button class="btn logout" onclick="location.href='../Geral/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <h2>Histórico de Pedidos</h2>

        <?php if (!empty($pedidos)): ?>
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th>Produtos</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                            <td><?= htmlspecialchars($pedido['cliente_nome']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($pedido['status']) ?>">
                                    <?= htmlspecialchars($pedido['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($pedido['produtos']) ?></td>
                            <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Nenhum pedido registrado ainda.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>