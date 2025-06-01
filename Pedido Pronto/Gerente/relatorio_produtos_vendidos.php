<?php
// relatorio_produtos_vendidos.php

require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php';
require_once 'funcoesGerente.php';

$mysqliConnection = getConnection();

// Inicializa os filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Gera o relatório de produtos vendidos
$produtosVendidos = FuncoesGerente::gerarRelatorioProdutosVendidos($filters);

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
    <title>Relatório de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .filter-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .filter-form input:focus {
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

        .bar-chart-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        
        .bar-chart-item:hover {
            background-color: #e8f4fc;
            transform: translateX(5px);
        }

        .bar-chart-label {
            flex: 0 0 180px;
            font-weight: 600;
            color: #2c3e50;
            margin-right: 15px;
        }

        .bar-chart-container {
            flex: 1;
            height: 30px;
            background-color: #e1e5eb;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .bar-chart-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 15px;
            transition: width 1s ease-in-out;
            position: relative;
        }
        
        .bar-chart-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
        }

        .bar-chart-info {
            flex: 0 0 220px;
            margin-left: 15px;
            font-size: 0.95em;
            color: #34495e;
            font-weight: 500;
            display: flex;
            gap: 15px;
        }
        
        .bar-chart-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .bar-chart-info i {
            color: #3498db;
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
            
            .bar-chart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .bar-chart-label {
                margin-right: 0;
                margin-bottom: 5px;
                flex: 0 0 auto;
            }
            
            .bar-chart-container {
                width: 100%;
            }
            
            .bar-chart-info {
                margin-left: 0;
                flex: 0 0 auto;
                width: 100%;
                justify-content: space-between;
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
            <h1>Relatório de Produtos</h1>
            <div class="header-buttons">
                <button class="btn primary" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-arrow-left"></i> Voltar ao Kanban
                </button>
                <button class="btn warning" onclick="location.href='relatorio_pedidos.php'">
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
            <h2><i class="fas fa-filter"></i> Filtros de Produtos Vendidos</h2>
            <form class="filter-form" method="GET" action="relatorio_produtos_vendidos.php">
                <div class="form-group">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($filters['data_inicio']) ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($filters['data_fim']) ?>">
                </div>
                <button type="submit">
                    <i class="fas fa-check"></i> Aplicar Filtros
                </button>
            </form>
        </div>

        <div class="report-section">
            <h2><i class="fas fa-chart-line"></i> Produtos Mais Vendidos</h2>
            <?php if (!empty($produtosVendidos)): ?>
                <?php foreach ($produtosVendidos as $produto): 
                    $bar_width = ($max_quantidade > 0) ? ($produto['total_quantidade_vendida'] / $max_quantidade) * 100 : 0;
                ?>
                    <div class="bar-chart-item">
                        <span class="bar-chart-label"><?= htmlspecialchars($produto['produto_nome']) ?>:</span>
                        <div class="bar-chart-container">
                            <div class="bar-chart-bar" style="width: <?= $bar_width ?>%;"></div>
                        </div>
                        <div class="bar-chart-info">
                            <span>
                                <i class="fas fa-box"></i> 
                                <?= $produto['total_quantidade_vendida'] ?> unidades
                            </span>
                            <span>
                                <i class="fas fa-receipt"></i> 
                                <?= $produto['total_pedidos'] ?> pedidos
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Nenhum produto vendido encontrado</h3>
                    <p>Não foram encontrados produtos vendidos para os filtros aplicados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animar as barras ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('.bar-chart-bar');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.transition = 'width 1.5s ease-in-out';
                    bar.style.width = width;
                }, 300);
            });
        });
    </script>
</body>
</html>