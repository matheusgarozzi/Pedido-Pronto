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
    <link rel="stylesheet" href="Stylecardapio.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="../Gerente/index_gerente.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
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
                fetch('../Geral/funcoes.php', { // Certifique-se de que o caminho está correto
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
                })
                .catch(error => {
                    showNotification('Erro ao excluir o produto: ' + error.message, 'error');
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
