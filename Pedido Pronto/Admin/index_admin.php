<?php
require_once __DIR__ . '/../Geral/verificalog.php';
require_once __DIR__ . '/../Geral/conexao.php';
require_once __DIR__ . '/funcoesAdmin.php';
require_once __DIR__ . '/../Caixa/CaixaManager.php'; // Inclui CaixaManager para usar seus métodos de consulta

// Verifica se o usuário é admin
if ($_SESSION['usuario']['nivel_acesso'] !== 'admin') {
    header('Location: ../Geral/acessoneg.php');
    exit;
}

// Obtém conexão do Database
$db = Database::getInstance();
$conn = $db->getConnection();
$caixaManager = new CaixaManager($conn); // Instancia CaixaManager

// Obtém dados do caixa e usuários
$caixa = AdminFunctions::obterUltimoCaixa(); // Usa AdminFunctions para obter o último caixa
$usuarios = AdminFunctions::obterUsuarios();

// Obtém informações adicionais do caixa usando CaixaManager (para getTotalVendasCaixa)
$caixaAtualDetalhes = $caixaManager->getCaixaAtual();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - PedidoPronto</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        header h1 {
            font-size: 1.8rem;
            margin-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
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

        .btn.success {
            background-color: var(--secondary);
        }

        .btn.success:hover {
            background-color: #27ae60;
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            display: flex;
            flex-direction: column;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card h2 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }

        .card h2 i {
            color: var(--primary);
        }

        .card-content {
            flex-grow: 1;
        }

        .caixa-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .status {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status.open {
            color: var(--secondary);
        }

        .status.closed {
            color: var(--danger);
        }

        .status-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-icon.open {
            background-color: var(--secondary);
        }

        .status-icon.closed {
            background-color: var(--danger);
        }

        .caixa-buttons {
            display: flex;
            flex-direction: column; 
            gap: 12px;
            margin-top: 15px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
        }

        tbody tr {
            border-bottom: 1px solid #e1e5eb;
            transition: background-color 0.2s;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        td {
            padding: 15px 20px;
            color: #34495e;
        }

        .notification {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--secondary);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            display: none;
            z-index: 9999;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }
        
        .notification.error {
            background-color: var(--danger);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin: 20px 0;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray);
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            header {
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
            
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .caixa-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 15px;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-lock"></i> Painel Administrativo</h1>
        <div class="header-buttons">
            <button class="btn primary" onclick="location.href='cadastrousu.php'">
                <i class="fas fa-users"></i> Usuários
            </button>
            <button class="btn warning" onclick="location.href='../Caixa/CaixaAdmin.php'">
                <i class="fas fa-cash-register"></i> Caixa
            </button>
            <button class="btn logout" onclick="location.href='../Geral/logout.php'">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
        </div>
    </header>

    <div class="container">
        <div class="dashboard">
            <div class="card">
                <h2><i class="fas fa-cash-register"></i> Controle do Caixa</h2>
                <div class="card-content">
                    <div class="caixa-info">
                        <div class="status <?= ($caixa && $caixa['status'] === 'aberto') ? 'open' : 'closed' ?>">
                            <span class="status-icon <?= ($caixa && $caixa['status'] === 'aberto') ? 'open' : 'closed' ?>"></span>
                            Status: <strong><?= ($caixa && $caixa['status']) ? htmlspecialchars($caixa['status']) : 'fechado' ?></strong>
                        </div>
                        
                        <?php if ($caixa && $caixa['status'] === 'aberto'): ?>
                            <div class="valor-info">
                                <i class="fas fa-wallet"></i> Saldo Inicial: <strong>R$ <?= number_format($caixa['saldo_inicial'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="valor-info">
                                <i class="fas fa-money-bill-wave"></i> Saldo Atual: <strong>R$ <?= number_format($caixa['saldo_atual'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="valor-info">
                                <?php $totalVendasAdmin = $caixaManager->getTotalVendasCaixa($caixa['id']); ?>
                                <i class="fas fa-hand-holding-usd"></i> Total Vendas: <strong>R$ <?= number_format($totalVendasAdmin, 2, ',', '.') ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="caixa-buttons">
                        <button class="btn primary" onclick="location.href='../Gerente/relatorio_pedidos.php'">
                            <i class="fas fa-file-invoice"></i> Relatório de Pedidos
                        </button>
                        <button class="btn primary" onclick="location.href='../Gerente/relatorio_acoes_equipe.php'">
                            <i class="fas fa-users-cog"></i> Relatório de Ações da Equipe
                        </button>
                        <button class="btn primary" onclick="location.href='../Gerente/relatorio_produtos_vendidos.php'">
                            <i class="fas fa-chart-bar"></i> Relatório de Produtos Vendidos
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-users-cog"></i> Usuários Cadastrados</h2>
                <div class="card-content">
                    <?php if (count($usuarios) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Cargo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($user['nivel_acesso'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button class="btn primary" onclick="location.href='cadastrousu.php'" style="margin-top: 20px; width: 100%;">
                            <i class="fas fa-user-plus"></i> Novo Usuário
                        </button>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h3>Nenhum usuário cadastrado</h3>
                            <p>Adicione usuários para começar a gerenciar</p>
                            <button class="btn primary" onclick="location.href='cadastrousu.php'" style="margin-top: 20px;">
                                <i class="fas fa-user-plus"></i> Adicionar Primeiro Usuário
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Funções de notificação (mantidas para compatibilidade visual, mas não usadas para abrir/fechar caixa aqui)
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + (type === 'error' ? 'error' : '');
            notification.style.display = 'block';
            
            setTimeout(() => { 
                notification.style.display = 'none'; 
            }, 3000);
        }
    </script>
</body>
</html>