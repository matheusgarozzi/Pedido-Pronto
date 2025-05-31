<?php
require_once '../Geral/conexao.php'; 
require_once '../geral/funcoes.php'; 

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

    <style>
        /* Seu CSS existente + ajustes para estoque */
        .produto-info {
            margin-bottom: 0.7rem;
        }
        .estoque {
            font-weight: 600;
            margin-top: 6px;
            color: #2f3542;
        }
        .add-estoque-form {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .add-estoque-form input[type="number"] {
            width: 70px;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
        }
        .add-estoque-form button {
            background-color: #3742fa;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        .add-estoque-form button:hover {
            background-color: #273cfa;
        }
        /* Notificações */
        .mensagem-sucesso {
            background-color: #2ed573;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            margin: 15px auto;
            text-align: center;
            width: 300px;
            font-weight: 600;
        }
        .mensagem-erro {
            background-color: #ff4757;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            margin: 15px auto;
            text-align: center;
            width: 300px;
            font-weight: 600;
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
        <button class="btn add" onclick="location.href='mostrar_estoque.php'">
            <i class="fas fa-eye"></i> Visualizar Estoque
        </button>

        <h2>Cardápio</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></div>
        <?php elseif ($erro): ?>
            <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

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
                            <div class="estoque">Estoque: <?= isset($produto['estoque']) ? intval($produto['estoque']) : 0 ?></div>

                            <form method="POST" class="add-estoque-form" onsubmit="return validarEstoque(this);">
                                <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                <input type="number" name="quantidade" min="1" placeholder="+ Estoque" required>
                                <button type="submit" name="adicionar_estoque">Adicionar</button>
                            </form>
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
        function toggleDropdown(event, button) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            const allDropdowns = document.querySelectorAll('.card-dropdown');
            
            allDropdowns.forEach(dd => {
                if (dd !== dropdown) dd.classList.remove('show');
            });
            
            dropdown.classList.toggle('show');
        }

        document.addEventListener('click', function() {
            document.querySelectorAll('.card-dropdown').forEach(dd => {
                dd.classList.remove('show');
            });
        });

        function editarProduto(produtoId) {
            showNotification('Abrindo produto #' + produtoId + ' para edição...', 'success');
            window.location.href = 'adicionarcardapio.php?editar=' + produtoId;
        }

        function confirmarExclusao(produtoId) {
            if (confirm(`Tem certeza que deseja remover este produto do cardápio?`)) {
                fetch('../Geral/funcoes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'excluir_produto', produto_id: produtoId })
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1500);
                })
                .catch(error => {
                    showNotification('Erro ao excluir o produto: ' + error.message, 'error');
                });
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + (type === 'error' ? 'error' : '');
            notification.style.display = 'block';
            setTimeout(() => { notification.style.display = 'none'; }, 3000);
        }

        // Validação simples de formulário pra impedir submit vazio
        function validarEstoque(form) {
            const qtdInput = form.quantidade;
            if (qtdInput.value === "" || qtdInput.value <= 0) {
                alert("Informe uma quantidade válida para adicionar ao estoque.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
