<?php
// relatorio_pedidos.php

require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php';
require_once 'funcoesGerente.php';

$mysqliConnection = getConnection();

// Inicializa os filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? 'todos',
    'pedido_id' => $_GET['pedido_id'] ?? '',
    'cliente_nome' => $_GET['cliente_nome'] ?? ''
];

// Gera o relatório com base nos filtros
$relatorioPedidos = FuncoesGerente::gerarRelatorioPedidos($filters);

// Busca todos os status possíveis para o filtro
$all_possible_statuses = ['pendente', 'preparo', 'pronto', 'entregue', 'cancelado'];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #1abc9c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }

        header {
            background: linear-gradient(135deg, var(--dark), #1a2530);
            color: var(--white);
            padding: 15px 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 0;
            font-weight: 600;
            color: var(--white);
        }

        .header-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            color: var(--white);
            font-size: 0.9rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn.primary {
            background-color: var(--primary);
        }

        .btn.primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn.danger {
            background-color: var(--danger);
        }

        .btn.danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn.warning {
            background-color: var(--warning);
        }

        .btn.info {
            background-color: var(--info);
        }

        .btn.logout {
            background-color: var(--gray);
        }

        .btn.logout:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .container {
            padding: 20px;
            max-width: 100%;
        }

        .report-section {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .report-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .report-section h2 {
            color: #2c3e50;
            font-weight: 700;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .report-section h2 i {
            margin-right: 12px;
            color: #3498db;
        }

        .filter-form {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-form .form-group {
            margin-bottom: 0;
        }

        .filter-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        .filter-form input, 
        .filter-form select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .filter-form input:focus,
        .filter-form select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        .filter-form button {
            padding: 14px;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .filter-form button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 20px;
            height: 200%;
            background: rgba(255,255,255,0.3);
            transform: rotate(30deg);
            transition: all 0.6s;
        }
        
        .filter-form button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }
        
        .filter-form button:hover::after {
            left: 120%;
        }
        
        .filter-form button:active {
            transform: translateY(1px);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.4);
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #3498db;
        }

        .stat-card h3 {
            font-size: 1.1rem;
            color: #34495e;
            margin-bottom: 5px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .status-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .chart-container h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container h3 i {
            color: #3498db;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e1e5eb;
        }

        .status-name {
            font-weight: 600;
            color: #34495e;
        }

        .status-count {
            font-weight: 700;
            color: #2c3e50;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .report-table th {
            background-color: #3498db;
            color: white;
            text-align: left;
            padding: 12px 15px;
            position: sticky;
            top: 0;
        }

        .report-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #e1e5eb;
        }

        .report-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .report-table tr:hover {
            background-color: #e8f4fc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pendente {
            background-color: rgba(243, 156, 18, 0.2);
            color: #e67e22;
        }

        .status-preparo {
            background-color: rgba(52, 152, 219, 0.2);
            color: #2980b9;
        }

        .status-pronto {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }

        .status-entregue {
            background-color: rgba(155, 89, 182, 0.2);
            color: #8e44ad;
        }

        .status-cancelado {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
        }

        .no-data-message {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-size: 1.1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #e1e5eb;
        }
        
        .no-data-message i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .header-buttons .btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(4px);
        }
        
        .header-buttons .btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.35);
        }

        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .summary-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .header-buttons .btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Relatório de Pedidos</h1>
            <div class="header-buttons">
                <button class="btn primary" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-arrow-left"></i> Voltar ao Kanban
                </button>
                <button class="btn info" onclick="location.href='relatorio_produtos_vendidos.php'">
                    <i class="fas fa-chart-pie"></i> Produtos Vendidos
                </button>
                <button class="btn warning" onclick="location.href='../cardapio/mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn logout" onclick="location.href='../Geral/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="report-section">
            <h2><i class="fas fa-filter"></i> Filtros de Relatório</h2>
            <form class="filter-form" method="GET" action="relatorio_pedidos.php">
                <div class="form-group">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($filters['data_inicio']) ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($filters['data_fim']) ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" class="form-control">
                        <option value="todos" <?= ($filters['status'] === 'todos') ? 'selected' : '' ?>>Todos</option>
                        <?php foreach ($all_possible_statuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pedido_id">ID do Pedido:</label>
                    <input type="number" id="pedido_id" name="pedido_id" class="form-control" value="<?= htmlspecialchars($filters['pedido_id']) ?>">
                </div>
                <div class="form-group">
                    <label for="cliente_nome">Nome do Cliente:</label>
                    <input type="text" id="cliente_nome" name="cliente_nome" class="form-control" value="<?= htmlspecialchars($filters['cliente_nome']) ?>">
                </div>
                <button type="submit">
                    <i class="fas fa-check"></i> Aplicar Filtros
                </button>
            </form>
        </div>

        <div class="report-section">
            <h2><i class="fas fa-chart-bar"></i> Resumo do Relatório</h2>
            
            <div class="summary-stats">
                <div class="stat-card">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Total de Pedidos Filtrados</h3>
                    <div class="value"><?= $relatorioPedidos['total_pedidos'] ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-times-circle"></i>
                    <h3>Pedidos Cancelados</h3>
                    <div class="value"><?= $relatorioPedidos['total_pedidos_cancelados'] ?></div>
                </div>
                
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3>Pedidos Finalizados</h3>
                    <div class="value"><?= $relatorioPedidos['total_pedidos_finalizados'] ?></div>
                </div>
            </div>
            
            <div class="status-charts">
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Pedidos por Status</h3>
                    <?php if (!empty($relatorioPedidos['pedidos_por_status'])): ?>
                        <?php foreach ($relatorioPedidos['pedidos_por_status'] as $status => $count): ?>
                            <div class="status-item">
                                <span class="status-name"><?= ucfirst(htmlspecialchars($status)) ?></span>
                                <span class="status-count"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nenhum pedido encontrado para os filtros aplicados.</p>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <h3><i class="fas fa-exclamation-triangle"></i> Motivos de Cancelamento</h3>
                    <?php if (!empty($relatorioPedidos['detalhes_cancelamento_motivo'])): ?>
                        <?php foreach ($relatorioPedidos['detalhes_cancelamento_motivo'] as $motivo => $quantidade): ?>
                            <div class="status-item">
                                <span class="status-name"><?= htmlspecialchars($motivo) ?></span>
                                <span class="status-count"><?= $quantidade ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nenhum pedido cancelado com motivo registrado para os filtros aplicados.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2><i class="fas fa-list"></i> Detalhes dos Pedidos</h2>
            <?php if (!empty($relatorioPedidos['pedidos_filtrados'])): ?>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Itens</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pagamento</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorioPedidos['pedidos_filtrados'] as $pedido): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pedido['id']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                    <td><?= htmlspecialchars($pedido['cliente_nome']) ?></td>
                                    <td><?= htmlspecialchars($pedido['produtos']) ?></td>
                                    <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($pedido['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($pedido['forma_pagamento_nome'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($pedido['observacoes'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Nenhum pedido encontrado</h3>
                    <p>Não foram encontrados pedidos para os filtros aplicados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Função para ajustar a altura das tabelas
        document.addEventListener('DOMContentLoaded', function() {
            // Adiciona efeito de hover nas linhas da tabela
            const tableRows = document.querySelectorAll('.report-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>