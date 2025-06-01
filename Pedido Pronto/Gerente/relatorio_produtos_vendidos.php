<?php
// relatorio_produtos_vendidos.php

require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php'; // Caminho corrigido
require_once 'funcoesGerente.php'; // Caminho corrigido

// Obtenha a conexão mysqli através da sua função global getConnection()
$mysqliConnection = getConnection();

// Inicializa os filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Gera o relatório de produtos vendidos com base nos filtros
$produtosVendidos = FuncoesGerente::gerarRelatorioProdutosVendidos($filters);

// Encontra a quantidade máxima para normalizar a barra de gráfico
$max_quantidade = 0;
foreach ($produtosVendidos as $produto) {
    if ($produto['total_quantidade_vendida'] > $max_quantidade) {
        $max_quantidade = $produto['total_quantidade_vendida'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto - Relatório de Produtos Vendidos</title>
    <link rel="stylesheet" href="stylegerente.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos gerais para relatórios (podem ser movidos para stylegerente.css) */
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
            margin-bottom: 0;
        }

        .filter-form button {
            grid-column: span var(--filter-button-span, 1);
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

        .bar-chart-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .bar-chart-label {
            flex: 0 0 150px; /* Largura fixa para o nome do produto */
            font-weight: bold;
            margin-right: 10px;
        }

        .bar-chart-bar {
            height: 20px;
            background-color: #4CAF50; /* Cor da barra */
            border-radius: 3px;
            transition: width 0.5s ease-in-out;
        }

        .bar-chart-info {
            margin-left: 10px;
            font-size: 0.9em;
            color: #555;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .filter-form button {
                grid-column: auto;
            }
            .bar-chart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .bar-chart-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Relatório de Produtos Vendidos</h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-arrow-left"></i> Voltar ao Kanban
                </button>
                <button class="btn" onclick="location.href='relatorio_pedidos.php'">
                    <i class="fas fa-chart-bar"></i> Relatório de Pedidos
                </button>
                <button class="btn" onclick="location.href='../cardapio/mostrarcardapio.php'">
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
            <h2>Filtros de Produtos Vendidos</h2>
            <form class="filter-form" method="GET" action="relatorio_produtos_vendidos.php">
                <div class="form-group">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($filters['data_inicio']) ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($filters['data_fim']) ?>">
                </div>
                <button type="submit">Aplicar Filtros</button>
            </form>
        </div>

        <div class="report-section">
            <h2>Produtos Mais Vendidos</h2>
            <?php if (!empty($produtosVendidos)): ?>
                <?php foreach ($produtosVendidos as $produto): 
                    $bar_width = ($max_quantidade > 0) ? ($produto['total_quantidade_vendida'] / $max_quantidade) * 100 : 0;
                ?>
                    <div class="bar-chart-item">
                        <span class="bar-chart-label"><?= htmlspecialchars($produto['produto_nome']) ?>:</span>
                        <div style="width: calc(100% - 150px - 10px - 10px);"> <div class="bar-chart-bar" style="width: <?= $bar_width ?>%;"></div>
                        </div>
                        <span class="bar-chart-info">
                            <?= $produto['total_quantidade_vendida'] ?> unidades (<?= $produto['total_pedidos'] ?> pedidos)
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum produto vendido encontrado para os filtros aplicados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
