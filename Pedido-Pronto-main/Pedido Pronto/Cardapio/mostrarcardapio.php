<?php
require_once '../Geral/conexao.php'; // Certifique-se que está incluindo o arquivo com a conexão
require_once '../geral/funcoes.php'; 

@session_start();

$produtos = buscarProdutosAtivos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cardápio - PedidoPronto</title>
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

        .btn.add {
            background-color: var(--success);
            margin-bottom: 20px;
        }

        .btn.add:hover {
            background-color: #3aa8c4;
        }

        .cardapio-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .produto-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
        }

        .produto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .produto-imagem {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .produto-info {
            padding: 15px;
        }

        .produto-nome {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .produto-descricao {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .produto-preco {
            font-weight: bold;
            color: var(--success);
            font-size: 16px;
        }

        .produto-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .card-menu-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card-menu-btn:hover {
            background: white;
        }

        .card-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            display: none;
            min-width: 120px;
        }

        .card-dropdown.show {
            display: block;
        }

        .card-dropdown button {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .card-dropdown button:hover {
            background-color: #f8f9fa;
        }

        .card-dropdown button.edit {
            color: var(--primary);
        }

        .card-dropdown button.delete {
            color: var(--danger);
        }

        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            display: none;
            z-index: 1100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .notification.success {
            background-color: var(--success);
        }

        .notification.error {
            background-color: var(--danger);
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
            
            .cardapio-container {
                grid-template-columns: 1fr;
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
        <button class="btn add" onclick="location.href='adicionarcardapio.php'">
            <i class="fas fa-plus"></i> Adicionar Item ao Cardápio
        </button>

        <h2>Cardápio</h2>

        <div class="cardapio-container">
            <?php if (!empty($produtos)): ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="produto-card">
                        <div class="produto-actions">
                            <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="card-dropdown">
                                <button class="edit" onclick="editarProduto(<?= $produto['id'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="delete" onclick="confirmarExclusao(<?= $produto['id'] ?>)">
                                    <i class="fas fa-trash"></i> Remover
                                </button>
                            </div>
                        </div>
                        
                        <img src="<?= htmlspecialchars($produto['imagem'] ?: 'https://via.placeholder.com/300x180') ?>" 
                             alt="<?= htmlspecialchars($produto['nome']) ?>" 
                             class="produto-imagem">
                             
                        <div class="produto-info">
                            <h3 class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="produto-descricao"><?= htmlspecialchars($produto['descricao']) ?></p>
                            <p class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center;">Nenhum produto cadastrado no cardápio.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Função para mostrar/ocultar o dropdown de ações
        function toggleDropdown(event, button) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            const allDropdowns = document.querySelectorAll('.card-dropdown');
            
            allDropdowns.forEach(dd => {
                if (dd !== dropdown) dd.classList.remove('show');
            });
            
            dropdown.classList.toggle('show');
        }

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function() {
            document.querySelectorAll('.card-dropdown').forEach(dd => {
                dd.classList.remove('show');
            });
        });

        // Função para editar produto
        function editarProduto(produtoId) {
            showNotification('Abrindo produto #' + produtoId + ' para edição...', 'success');
            // Redirecionar para a página de edição
            window.location.href = 'adicionarcardapio.php?editar=' + produtoId;
        }

        // Função para confirmar exclusão
        function confirmarExclusao(produtoId) {
            if (confirm(`Tem certeza que deseja remover este produto do cardápio?`)) {
                fetch('funcoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        acao: 'excluir_produto',
                        produto_id: produtoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
            }
        }

        // Função para mostrar notificação
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>