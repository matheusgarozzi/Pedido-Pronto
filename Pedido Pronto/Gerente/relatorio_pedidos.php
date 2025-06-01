<?php
// relatorio_pedidos.php

require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php'; // Caminho corrigido
require_once 'funcoesGerente.php'; // Caminho corrigido

// Obtenha a conexão mysqli através da sua função global getConnection()
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
    <title>PedidoPronto - Relatório de Pedidos</title>
    <link rel="stylesheet" href="stylegerente.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Estilos do Relatório (podem ser movidos para stylegerente.css se preferir) */
        .report-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .report-section h3 {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .report-section ul {
            list-style: none;
            padding: 0;
        }

        .report-section ul li {
            padding: 5px 0;
            border-bottom: 1px dashed #f0f0f0;
        }

        .report-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .report-section th,
        .report-section td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .report-section th {
            background-color: #e9ecef;
        }

        .filter-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-form .form-group {
            margin-bottom: 0; /* Remove margin-bottom padrão */
        }

        .filter-form button {
            grid-column: span var(--filter-button-span, 1); /* Ajusta a largura do botão */
            padding: 10px 15px;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .filter-form button:hover {
            background-color: #3f37c9;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr; /* Uma coluna em telas menores */
            }
            .filter-form button {
                grid-column: auto; /* Botão ocupa a largura total */
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Relatório de Pedidos</h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-arrow-left"></i> Voltar ao Kanban
                </button>
                <button class="btn" onclick="location.href='../cardapio/mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn" onclick="location.href='../Pedidos/historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
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
        <div class="report-section">
            <h2>Filtros de Relatório</h2>
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
                <button type="submit">Aplicar Filtros</button>
            </form>
        </div>

        <div class="report-section">
            <h2>Resumo do Relatório</h2>
            <ul>
                <li><strong>Total de Pedidos Filtrados:</strong> <?= $relatorioPedidos['total_pedidos'] ?></li>
                <li><strong>Total de Pedidos Cancelados (Filtrados):</strong> <?= $relatorioPedidos['total_pedidos_cancelados'] ?></li>
                <li><strong>Total de Pedidos Finalizados (Pronto/Entregue - Filtrados):</strong> <?= $relatorioPedidos['total_pedidos_finalizados'] ?></li>
            </ul>

            <h3>Pedidos por Status (Filtrados):</h3>
            <ul>
                <?php if (!empty($relatorioPedidos['pedidos_por_status'])): ?>
                    <?php foreach ($relatorioPedidos['pedidos_por_status'] as $status => $count): ?>
                        <li><strong><?= ucfirst(htmlspecialchars($status)) ?>:</strong> <?= $count ?></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Nenhum pedido encontrado para os filtros aplicados.</li>
                <?php endif; ?>
            </ul>

            <h3>Motivos de Cancelamento (Filtrados):</h3>
            <?php if (!empty($relatorioPedidos['detalhes_cancelamento_motivo'])): ?>
                <ul>
                    <?php foreach ($relatorioPedidos['detalhes_cancelamento_motivo'] as $motivo => $quantidade): ?>
                        <li><strong><?= htmlspecialchars($motivo) ?>:</strong> <?= $quantidade ?> cancelamentos</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum pedido cancelado com motivo registrado para os filtros aplicados.</p>
            <?php endif; ?>

            <h3>Detalhes dos Pedidos Filtrados:</h3>
            <?php if (!empty($relatorioPedidos['pedidos_filtrados'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Forma Pagamento</th>
                            <th>Observações</th>
                            <th>Status Anterior</th>
                            <th>Motivo Cancelamento</th>
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
                                <td><?= ucfirst(htmlspecialchars($pedido['status'])) ?></td>
                                <td><?= htmlspecialchars($pedido['forma_pagamento_nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pedido['observacoes'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pedido['status_anterior'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pedido['motivo_cancelamento'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum pedido encontrado para os filtros aplicados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
