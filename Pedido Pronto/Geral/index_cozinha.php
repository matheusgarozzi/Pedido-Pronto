<?php
session_start();
require_once 'conexao.php';

// Obtém a conexão com o banco de dados
$conn = getConnection(); // Adicione esta linha para obter a conexão

// Função para buscar pedidos
function buscarPedidos() {
    global $conn; // Usa a conexão global
    
    $query = "SELECT p.id, p.status, p.data_pedido, p.observacoes, 
                     c.nome AS cliente_nome,
                     GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.nome) SEPARATOR ', ') AS produtos,
                     SUM(ip.quantidade * ip.preco_unitario) AS total
              FROM pedidos p
              JOIN clientes c ON p.cliente_id = c.id
              JOIN itenspedido ip ON p.id = ip.pedido_id
              JOIN produtos pr ON ip.produto_id = pr.id
              WHERE p.status IN ('pendente', 'preparo')
              GROUP BY p.id
              ORDER BY p.data_pedido ASC";
    
    $result = $conn->query($query);
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    return $pedidos;
}

// Função para atualizar status
function atualizarStatus($pedido_id, $novo_status) {
    global $conn; // Usa a conexão global
    
    $query = "UPDATE pedidos SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $novo_status, $pedido_id);
    
    return $stmt->execute();
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['acao'])) {
    $pedido_id = $_POST['pedido_id'];
    $acao = $_POST['acao'];
    
    if ($acao === 'iniciar_preparo') {
        if (atualizarStatus($pedido_id, 'preparo')) {
            $_SESSION['mensagem'] = "Pedido #$pedido_id iniciado com sucesso!";
        } else {
            $_SESSION['erro'] = "Erro ao iniciar pedido #$pedido_id";
        }
    } elseif ($acao === 'marcar_pronto') {
        if (atualizarStatus($pedido_id, 'pronto')) {
            $_SESSION['mensagem'] = "Pedido #$pedido_id marcado como pronto!";
        } else {
            $_SESSION['erro'] = "Erro ao marcar pedido #$pedido_id como pronto";
        }
    }
    
    // Redirecionamento imediato para evitar reenvio do formulário
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obter pedidos para exibição
$pedidos = buscarPedidos();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cozinha - PedidoPronto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <style>
        :root {
            --primary: #4361ee;
            --warning: #f8961e;
            --success: #4cc9f0;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--dark);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            font-weight: 500;
            margin-bottom: 10px;
        }

        .btn {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .kanban-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .kanban-column {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .column-title {
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            color: #666;
        }

        .card {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .card-title {
            font-weight: 500;
            color: var(--dark);
        }

        .card-body p {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .card-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .status-btn {
            padding: 5px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-preparo {
            background-color: var(--warning);
            color: #212529;
        }

        .btn-pronto {
            background-color: var(--success);
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: white;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s forwards;
        }

        .alert-success {
            background-color: var(--success);
        }

        .alert-error {
            background-color: var(--danger);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @media (max-width: 768px) {
            .kanban-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Cozinha - PedidoPronto</h1>
        <button class="btn" onclick="location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i> Sair
        </button>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['mensagem'] ?>
                <?php unset($_SESSION['mensagem']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['erro'] ?>
                <?php unset($_SESSION['erro']); ?>
            </div>
        <?php endif; ?>

        <div class="kanban-container">
            <!-- Coluna Pendente -->
            <div class="kanban-column">
                <div class="column-header">
                    <h3 class="column-title">Pendente</h3>
                </div>
                
                <?php 
                $pedidosPendentes = array_filter($pedidos, function($pedido) {
                    return $pedido['status'] === 'pendente';
                });
                
                if (empty($pedidosPendentes)): ?>
                    <div class="empty-message">
                        <p>Nenhum pedido pendente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosPendentes as $pedido): ?>
                        <div class="card" id="pedido-<?= $pedido['id'] ?>">
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                                <span><?= date('H:i', strtotime($pedido['data_pedido'])) ?></span>
                            </div>
                            <div class="card-body">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                                <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                                <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                                <?php if (!empty($pedido['observacoes'])): ?>
                                    <p><strong>Observações:</strong> <?= htmlspecialchars($pedido['observacoes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                    <input type="hidden" name="acao" value="iniciar_preparo">
                                    <button type="submit" class="status-btn btn-preparo">
                                        <i class="fas fa-utensils"></i> Iniciar Preparo
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Coluna Em Preparo -->
            <div class="kanban-column">
                <div class="column-header">
                    <h3 class="column-title">Em Preparo</h3>
                </div>
                
                <?php 
                $pedidosPreparo = array_filter($pedidos, function($pedido) {
                    return $pedido['status'] === 'preparo';
                });
                
                if (empty($pedidosPreparo)): ?>
                    <div class="empty-message">
                        <p>Nenhum pedido em preparo</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosPreparo as $pedido): ?>
                        <div class="card" id="pedido-<?= $pedido['id'] ?>">
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                                <span><?= date('H:i', strtotime($pedido['data_pedido'])) ?></span>
                            </div>
                            <div class="card-body">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                                <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                                <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                                <?php if (!empty($pedido['observacoes'])): ?>
                                    <p><strong>Observações:</strong> <?= htmlspecialchars($pedido['observacoes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                    <input type="hidden" name="acao" value="marcar_pronto">
                                    <button type="submit" class="status-btn btn-pronto">
                                        <i class="fas fa-check"></i> Marcar como Pronto
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Seu JavaScript aqui
    </script>
</body>
</html>
