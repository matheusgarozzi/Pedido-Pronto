<?php
require_once 'funcoes.php';

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $response = ['success' => false, 'message' => ''];

    if (isset($data['form'])) {
        switch ($data['form']) {
            case 'cliente':
                cadastrarCliente($data['nome'], $data['telefone'], $data['endereco']);
                $response = ['success' => true, 'message' => 'Cliente cadastrado!'];
                break;
            case 'produto':
                cadastrarProduto($data['nome_produto'], $data['preco_produto']);
                $response = ['success' => true, 'message' => 'Produto cadastrado!'];
                break;
            case 'pedido':
                // Modificação para lidar com múltiplos itens
                if (isset($data['cliente_id']) && isset($data['itens']) && is_array($data['itens'])) {
                    $pedido_id = cadastrarPedido($data['cliente_id'], $data['itens']);
                    if ($pedido_id) {
                        $response = ['success' => true, 'message' => "Pedido #{$pedido_id} cadastrado!"];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao cadastrar o pedido.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Dados do pedido incompletos.'];
                }
                break;
            case 'update':
                atualizarStatus($data['pedido_id'], $data['status']);
                $response = ['success' => true, 'message' => 'Status atualizado!'];
                break;
            case 'caixa':
                atualizarCaixa($data['acao'], $data['valor']);
                $response = ['success' => true, 'message' => 'Caixa atualizado!'];
                break;
        }
    }
    echo json_encode($response);
    exit;
}

// Buscar todos os registros
$pedidos = buscarPedidos();
$clientes = buscarClientes();
$produtos = buscarProdutos();
$caixa = buscarStatusCaixa();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQmQa7TBcCJIBayjilWXgQvrji1FdspR/oucPQXwJQhlTv/sgfXD9GcUp+OlL9jxediN2ytimY9yy6g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            padding: 0 20px;
        }

        h1 {
            font-size: 24px;
            font-weight: 500;
        }

        .caixa-info {
            background-color: white;
            color: var(--dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .caixa-status {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .caixa-valores {
            display: flex;
            gap: 20px;
        }

        .caixa-valor {
            text-align: center;
        }

        .caixa-valor span {
            font-size: 12px;
            color: #666;
        }

        .caixa-valor p {
            font-weight: 500;
            font-size: 18px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-fechado {
            background-color: var(--danger);
            color: white;
        }

        .status-aberto {
            background-color: var(--success);
            color: white;
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
            cursor: grab;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .card-title {
            font-weight: 500;
            color: var(--primary);
        }

        .card-body p {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--secondary);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px; /* Aumentei o max-width para acomodar mais conteúdo */
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: none;
            z-index: 1100;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
        }

        .modal-header button {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
        }

        #itensContainer .item-pedido {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto; /* Melhor layout para os campos */
            gap: 10px;
            align-items: center;
        }

        #itensContainer .item-pedido .form-group {
            margin-bottom: 5px;
        }

        #itensContainer .item-pedido .subtotal {
            font-weight: bold;
        }

        #itensContainer .item-pedido button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        #itensContainer .item-pedido button:hover {
            background-color: #e05252;
        }

        #totalPedido {
            font-size: 1.4em;
            color: var(--secondary);
        }

        #itensContainer .item-pedido label {
            font-size: 0.9em;
        }

        #itensContainer .item-pedido input,
        #itensContainer .item-pedido select {
            padding: 8px;
            font-size: 0.9em;
        }

        #itensContainer .item-pedido .form-group:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    
    <header>
        <div class="header-content">
            <h1>PedidoPronto</h1>
            <button class="btn" onclick="openModal('pedido')">Novo Pedido</button>
            <button class="btn" onclick="location.href='mostrarcardapio.php'">Cardapio</button>
            <button class="btn" onclick="location.href='historicopedidos.php'">Histórico de Pedidos</button>
             <button class="btn" onclick="location.href='clientes.php'">Clientes</button>
             <button class="btn" onclick="location.href='adicionarcliente.php'">Adicionar Cliente</button>
             <button class="btn" onclick="location.href='adicionarcardapio.php'">Adicionar Cardapio</button>

            <button class="btn" style="background-color: red;" onclick="location.href='logout.php'">Logout</button>
            
        </div>
    </header>
    <div class="container">
        <div class="caixa-info">
            <div class="caixa-status">
                <h2>Situação do Caixa</h2>
                <span class="status-badge status-<?= $caixa['status'] ?>">
                    <?= strtoupper($caixa['status']) ?>
                </span>
            </div>
            <div class="caixa-valores">
                <div class="caixa-valor">
                    <span>Saldo Inicial</span>
                    <p>R$ <?= number_format($caixa['saldo_inicial'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Entradas</span>
                    <p>R$ <?= number_format($caixa['entradas'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saídas</span>
                    <p>R$ <?= number_format($caixa['saidas'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saldo Atual</span>
                    <p>R$ <?= number_format($caixa['saldo_atual'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>
        <div class="kanban-container">
            <div class="kanban-column" id="pendente" ondragover="allowDrop(event)" ondrop="drop(event, 'pendente')">
                <div class="column-header">
                    <h3 class="column-title">Pendente</h3>
                </div>
                    <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pendente'): ?>
                        <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                            </div>
                            <div class="card-body">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                                <p><strong>Produto:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                            </div>
                        </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="kanban-column" id="preparando" ondragover="allowDrop(event)" ondrop="drop(event, 'preparando')">
                <div class="column-header">
                    <h3 class="column-title">Em Preparo</h3>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'preparando'): ?>
                    <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                        <div class="card-header">
                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Produto:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="kanban-column" id="pronto" ondragover="allowDrop(event)" ondrop="drop(event, 'pronto')">
                <div class="column-header">
                    <h3 class="column-title">Pronto</h3>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pronto'): ?>
                    <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                        <div class="card-header">
                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Produto:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="kanban-column" id="entregue" ondragover="allowDrop(event)" ondrop="drop(event, 'entregue')">
                <div class="column-header">
                    <h3 class="column-title">Entregue</h3>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'entregue'): ?>
                    <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                        <div class="card-header">
                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Produto:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="pedidoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Novo Pedido</h3>
                <button onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Cliente</label>
                    <select id="cliente_id" class="form-control" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h4>Itens do Pedido</h4>
                <div id="itensContainer">
                    <div class="item-pedido">
                        <div class="form-group">
                            <label>Produto</label>
                            <select name="produto_id[]" class="form-control produto-select" required>
                                <option value="">Selecione um produto</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>" data-preco="<?= $produto['preco'] ?>">
                                        <?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantidade</label>
                            <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Subtotal</label>
                            <span class="subtotal">R$ 0,00</span>
                        </div>
                        <button type="button" class="btn" style="background-color: #ff6b6b;" onclick="removerItem(this)">Remover Item</button>
                    </div>
                </div>

                <button type="button" class="btn" onclick="adicionarItem()" style="margin-bottom: 15px;">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>

                <div style="font-weight: bold; font-size: 1.2em;">
                    <label>Total do Pedido:</label>
                    <span id="totalPedido">R$ 0,00</span>
                </div>

                <button type="button" class="btn" onclick="enviarPedido()" style="margin-top: 15px;">Salvar Pedido</button>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Variável para contar os itens
        let itemCount = 1;

        // Função para abrir o modal
        function openModal(modalId) {
            document.getElementById(modalId + 'Modal').style.display = 'flex';
        }

        // Função para fechar o modal
        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => modal.style.display = 'none');
        }

        // Função para adicionar novo item
        function adicionarItem() {
            const container = document.getElementById('itensContainer');
            const novoItem = document.createElement('div');
            novoItem.className = 'item-pedido';
            novoItem.innerHTML = `
                <div class="form-group">
                    <label>Produto</label>
                    <select name="produto_id[]" class="form-control produto-select" required>
                        <option value="">Selecione um produto</option>
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?= $produto['id'] ?>" data-preco="<?= $produto['preco'] ?>">
                                <?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Subtotal</label>
                    <span class="subtotal">R$ 0,00</span>
                </div>
                <button type="button" class="btn" style="background-color: #ff6b6b;" onclick="removerItem(this)">Remover Item</button>
            `;

            container.appendChild(novoItem);
            itemCount++;

            // Adiciona os event listeners para o novo item
            novoItem.querySelector('.produto-select').addEventListener('change', calcularSubtotal);
            novoItem.querySelector('.quantidade-input').addEventListener('input', calcularSubtotal);
        }

        // Função para remover item
        function removerItem(btn) {
            const item = btn.closest('.item-pedido');
            item.remove();
            calcularTotal();
        }

        // Função para calcular subtotal de cada item
        function calcularSubtotal(event) {
            const item = event.target.closest('.item-pedido');
            const select = item.querySelector('.produto-select');
            const input = item.querySelector('.quantidade-input');
            const subtotalSpan = item.querySelector('.subtotal');

            const preco = parseFloat(select.selectedOptions[0].dataset.preco || 0);
            const quantidade = parseInt(input.value) || 0;
            const subtotal = preco * quantidade;

            subtotalSpan.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            calcularTotal();
        }

        // Função para calcular o total do pedido
        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.item-pedido').forEach(item => {
                const subtotalText = item.querySelector('.subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                total += subtotal;
            });

            document.getElementById('totalPedido').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        // Função para enviar o pedido
        function enviarPedido() {
            const clienteId = document.getElementById('cliente_id').value;
            const itens = [];

            // Validação do cliente
            if (!clienteId) {
                showNotification('Selecione um cliente', 'error');
                return;
            }

            // Coleta os itens
            document.querySelectorAll('.item-pedido').forEach((item, index) => {
                const produtoId = item.querySelector('.produto-select').value;
                const quantidade = item.querySelector('.quantidade-input').value;

                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: quantidade
                    });
                }
            });

            // Validação dos itens
            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item ao pedido', 'error');
                return;
            }

            // Envia via AJAX
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    form: 'pedido',
                    cliente_id: clienteId,
                    itens: itens
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

        // Função para mostrar notificações
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Adiciona eventos aos elementos iniciais
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.produto-select').addEventListener('change', calcularSubtotal);
            document.querySelector('.quantidade-input').addEventListener('input', calcularSubtotal);
        });
         let draggedPedidoId = null;

    // Função chamada quando o arrasto começa
    function drag(event) {
        draggedPedidoId = event.target.id; // Armazena o ID do pedido arrastado
    }

    // Permite que o elemento seja solto
    function allowDrop(event) {
        event.preventDefault(); // Impede o comportamento padrão
    }

    // Função chamada quando o pedido é solto em uma nova coluna
    function drop(event, status) {
        event.preventDefault(); // Impede o comportamento padrão

        // Atualiza o status do pedido no servidor
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form: 'update',
                pedido_id: draggedPedidoId.split('-')[1], // Extrai o ID do pedido
                status: status // Novo status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Move o pedido para a nova coluna
                const pedidoElement = document.getElementById(draggedPedidoId);
                document.getElementById(status).appendChild(pedidoElement);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }

    // Função para mostrar notificações
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = 'notification ' + type;
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Adiciona eventos aos elementos iniciais
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('dragstart', drag);
        });
    });
    </script>
</body>
</html>