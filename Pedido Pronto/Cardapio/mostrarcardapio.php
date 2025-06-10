<?php
// Cardapio/mostrarcardapio.php

require_once '../Geral/conexao.php'; 
require_once '../Geral/funcoes.php'; // Ajuste o caminho conforme a localização do seu 'funcoes.php'

@session_start();

$mensagem = '';
$erro = '';

// Verifica se o método é POST e se a ação é adicionar estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_estoque'])) {
    $produto_id = intval($_POST['produto_id']);
    $quantidade = intval($_POST['quantidade']);

    if ($quantidade > 0) {
        if (adicionarEstoque($produto_id, $quantidade)) { 
            $mensagem = "Estoque atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar o estoque.";
        }
    } else {
        $erro = "Quantidade inválida.";
    }
}

// Sempre busca os produtos após o processamento do POST
// FUNÇÃO BUSCARPRODUTOSATIVOS AGORA SELECIONA 'gramas'
$produtos = buscarProdutosAtivos(); 
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio - PedidoPronto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... (seu CSS existente, mantido intacto) ... */
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .cardapio-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .produto-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .produto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .produto-imagem-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .produto-imagem {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .produto-card:hover .produto-imagem {
            transform: scale(1.05);
        }

        .produto-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .card-menu-btn {
            background: rgba(255, 255, 255, 0.9);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--dark);
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card-menu-btn:hover {
            background: white;
            transform: rotate(90deg);
        }

        .card-dropdown {
            position: absolute;
            right: 0;
            top: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            z-index: 1000;
            min-width: 160px;
            overflow: hidden;
        }
        
        .card-dropdown.show {
            display: flex;
        }
        
        .card-dropdown button {
            background: none;
            border: none;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
            transition: all 0.2s ease;
        }
        
        .card-dropdown button:hover {
            background-color: #f8f9fa;
            color: var(--primary);
        }
        
        .card-dropdown .edit i {
            color: #3498db;
        }
        
        .card-dropdown .delete i {
            color: var(--danger);
        }

        .produto-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .produto-nome {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--dark);
        }

        .produto-descricao {
            font-size: 0.95rem;
            color: var(--gray);
            margin: 0 0 15px 0;
            flex-grow: 1;
        }

        .produto-preco {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .gramas { /* Adicione este estilo para a exibição de gramas */
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .estoque-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
        }

        .estoque-label {
            color: var(--dark);
        }

        .estoque-valor {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .add-estoque-form {
            display: flex;
            gap: 10px;
        }

        .add-estoque-form input[type="number"] {
            flex: 1;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e1e5eb;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .add-estoque-form input[type="number"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        .add-estoque-form button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-estoque-form button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .mensagens {
            margin: 20px 0;
        }

        .mensagem {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mensagem-sucesso {
            background-color: var(--secondary);
            color: white;
        }
        
        .mensagem-erro {
            background-color: var(--danger);
            color: white;
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
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-top: 20px;
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
            .cardapio-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .cardapio-container {
                grid-template-columns: 1fr;
            }
            
            .add-estoque-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="../Gerente/index_gerente.php" style="color: white; text-decoration: none;"><i class="fas fa-utensils"></i> PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn primary" onclick="location.href='../Gerente/index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn warning" onclick="location.href='../Pedidos/historicopedidos.php'">
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
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-book-open"></i> Cardápio</h2>
            <div class="action-buttons">
                <button class="btn success" onclick="location.href='adicionarcardapio.php'">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>
                <button class="btn primary" onclick="location.href='mostrar_estoque.php'">
                    <i class="fas fa-eye"></i> Visualizar Estoque
                </button>
            </div>
        </div>

        <div class="mensagens">
            <?php if ($mensagem): ?>
                <div class="mensagem mensagem-sucesso">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php elseif ($erro): ?>
                <div class="mensagem mensagem-erro">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cardapio-container">
            <?php if (!empty($produtos)): ?>
                <?php foreach ($produtos as $produto): ?>
                    <div class="produto-card" data-id="<?= htmlspecialchars($produto['id']) ?>">
                        <div class="produto-imagem-container">
                            <img src="<?= htmlspecialchars($produto['imagem'] ?? 'placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                 class="produto-imagem">
                            
                            <div class="produto-actions">
                                <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="card-dropdown">
                                    <button class="edit" onclick="editarProduto(<?= htmlspecialchars($produto['id']) ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="delete" onclick="confirmarExclusao(<?= htmlspecialchars($produto['id']) ?>)">
                                        <i class="fas fa-trash"></i> Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                             
                        <div class="produto-info">
                            <h3 class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="produto-descricao"><?= htmlspecialchars($produto['descricao']) ?></p>
                            
                            <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                            
                            <div class="gramas"><?= htmlspecialchars($produto['gramas'] ?? '') ?></div>
                            
                            <div class="estoque-info">
                                <span class="estoque-label">Estoque:</span>
                                <span class="estoque-valor"><?= isset($produto['estoque']) ? intval($produto['estoque']) : 0 ?></span>
                            </div>

                            <form method="POST" class="add-estoque-form" onsubmit="return validarEstoque(this);">
                                <input type="hidden" name="produto_id" value="<?= htmlspecialchars($produto['id']) ?>">
                                <input type="number" name="quantidade" min="1" placeholder="Quantidade" required>
                                <button type="submit" name="adicionar_estoque">
                                    <i class="fas fa-plus"></i> Adicionar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-pizza-slice"></i>
                    <h3>Nenhum produto cadastrado</h3>
                    <p>Adicione itens ao seu cardápio para começar</p>
                    <button class="btn success" onclick="location.href='adicionarcardapio.php'" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Adicionar Primeiro Item
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        function toggleDropdown(event, button) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            const allDropdowns = document.querySelectorAll('.card-dropdown');
            
            allDropdowns.forEach(dd => {
                if (dd !== dropdown) dd.classList.remove('show');
            });
            
            dropdown.classList.toggle('show');
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.card-dropdown') && !event.target.closest('.card-menu-btn')) {
                document.querySelectorAll('.card-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        function editarProduto(produtoId) {
            showNotification('Abrindo produto para edição...', 'info'); 
            setTimeout(() => {
                window.location.href = 'adicionarcardapio.php?editar=' + produtoId;
            }, 800);
        }

        function confirmarExclusao(produtoId) {
            if (confirm(`Tem certeza que deseja remover este produto do cardápio? Esta ação não pode ser desfeita.`)) {
                showNotification('Excluindo produto...', 'info'); 

                fetch('../Geral/funcoes.php', { 
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
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        const card = document.querySelector(`.produto-card[data-id="${produtoId}"]`);
                        if (card) {
                            card.remove();
                        }
                        
                        if (document.querySelectorAll('.produto-card').length === 0) {
                            document.querySelector('.cardapio-container').innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-pizza-slice"></i>
                                    <h3>Nenhum produto cadastrado</h3>
                                    <p>Adicione itens ao seu cardápio para começar</p>
                                    <button class="btn success" onclick="location.href='adicionarcardapio.php'" style="margin-top: 20px;">
                                        <i class="fas fa-plus"></i> Adicionar Primeiro Item
                                    </button>
                                </div>
                            `;
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao excluir produto:', error);
                    showNotification('Erro de comunicação ao tentar excluir o produto.', 'error');
                });
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type; 
            notification.style.display = 'block';
            
            setTimeout(() => { 
                notification.style.display = 'none'; 
                notification.className = 'notification'; 
            }, 3000);
        }

        function validarEstoque(form) {
            const qtdInput = form.quantidade;
            if (qtdInput.value === "" || parseInt(qtdInput.value) <= 0 || isNaN(parseInt(qtdInput.value))) {
                showNotification("Informe uma quantidade válida e positiva para adicionar ao estoque.", 'error');
                qtdInput.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>