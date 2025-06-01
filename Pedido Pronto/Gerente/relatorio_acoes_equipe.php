<?php
// relatorio_acoes_equipe.php

require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php'; // Caminho corrigido
require_once 'funcoesGerente.php'; // Caminho corrigido

// Obtenha a conexão mysqli através da sua função global getConnection()
$mysqliConnection = getConnection();

// Inicializa os filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'responsavel' => $_GET['responsavel'] ?? '',
    'acao_termo' => $_GET['acao_termo'] ?? ''
];

// Busca os logs de ações com base nos filtros
$logsAcoes = FuncoesGerente::buscarLogsAcoes($filters);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto - Relatório de Ações da Equipe</title>
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
        }

        .report-section h2 {
            color: #2c3e50;
            font-weight: 700;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
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
        }

        .filter-form button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #e9ecef;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Relatório de Ações da Equipe</h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-arrow-left"></i> Voltar ao Kanban
                </button>
                <button class="btn" onclick="location.href='relatorio_pedidos.php'">
                    <i class="fas fa-chart-bar"></i> Relatório de Pedidos
                </button>
                <button class="btn" onclick="location.href='relatorio_produtos_vendidos.php'">
                    <i class="fas fa-chart-pie"></i> Relatório de Produtos
                </button>
                <button class="btn" onclick="location.href='../Geral/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="report-section">
            <h2>Filtros de Ações</h2>
            <form class="filter-form" method="GET" action="relatorio_acoes_equipe.php">
                <div class="form-group">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($filters['data_inicio']) ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($filters['data_fim']) ?>">
                </div>
                <div class="form-group">
                    <label for="responsavel">Responsável:</label>
                    <input type="text" id="responsavel" name="responsavel" class="form-control" value="<?= htmlspecialchars($filters['responsavel']) ?>" placeholder="Nome do responsável">
                </div>
                <div class="form-group">
                    <label for="acao_termo">Termo da Ação:</label>
                    <input type="text" id="acao_termo" name="acao_termo" class="form-control" value="<?= htmlspecialchars($filters['acao_termo']) ?>" placeholder="Ex: 'cadastrou', 'cancelou'">
                </div>
                <button type="submit">Aplicar Filtros</button>
            </form>
        </div>

        <div class="report-section">
            <h2>Histórico de Ações da Equipe</h2>
            <?php if (!empty($logsAcoes)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Responsável</th>
                            <th>Ação</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logsAcoes as $log): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['data_acao'])) ?></td>
                                <td><?= htmlspecialchars($log['responsavel']) ?></td>
                                <td><?= htmlspecialchars($log['acao']) ?></td>
                                <td><?= htmlspecialchars($log['detalhes'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma ação registrada para os filtros aplicados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
