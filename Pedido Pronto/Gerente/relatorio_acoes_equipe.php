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
            vertical-align: top; /* Alinha o conteúdo ao topo */
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

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .filter-form button {
                grid-column: auto;
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
                <button class="btn logout" onclick="location.href='../Geral/logout.php'">
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
