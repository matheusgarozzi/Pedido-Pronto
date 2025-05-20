<?php
require_once 'funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Tentativa de obter JSON puro (como fetch API)
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data && isset($data['form'])) {
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

            case 'excluir':
                excluirPedido($data['pedido_id']); // remove da base como o botão "Excluir"
                $response = ['success' => true, 'message' => 'Pedido excluído com sucesso!'];
                break;

            case 'caixa':
                atualizarCaixa($data['acao'], $data['valor']);
                $response = ['success' => true, 'message' => 'Caixa atualizado!'];
                break;
        }

        echo json_encode($response);
        exit;
    }

    // Se estiver usando form-data tradicional (por formulário HTML por exemplo)
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'excluir') {
            $id = $_POST['id'];
            excluirPedido($id);
            echo "Pedido #$id excluído com sucesso!";
            exit;
        }

        if ($action === 'editar') {
        $id = $_POST['id'] ?? null;
        $novoProduto = $_POST['produto_id'] ?? null;

        if ($id !== null && $novoProduto!== null) {
            editarPedidoProduto($id, $novoProduto);
        }
            echo "Pedido #$id atualizado com sucesso!";
            exit;
        }
    }

    // Se nenhum JSON válido nem form-data for encontrado
    echo json_encode(['success' => false, 'message' => 'Nenhuma ação reconhecida.']);
    exit;
}

$pedidos = buscarPedidos();
$clientes = buscarClientes();
$produtos = buscarProdutos();
$caixa = buscarStatusCaixa();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto - Gerente</title>
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

        h1 {
            font-size: 24px;
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
            flex-wrap: wrap;
        }

        .caixa-valor {
            text-align: center;
            min-width: 120px;
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
        flex-wrap: wrap;  /* ADICIONE ESTA LINHA */
        margin-top: 20px;
        padding-bottom: 20px;
    }

        .kanban-column {
            flex: 1;
            min-width: 280px;
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
            position: relative;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

        .card-actions {
            position: relative;
        }

        .card-menu-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1.2em;
            padding: 0 5px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .card-menu-btn:hover {
            background-color: #e9ecef;
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


        .card-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        z-index: 999;
        width: 200px;
        border-radius: 5px;
        padding: 10px;
        }

        .card-dropdown select,
        .card-dropdown button {
        width: 100%;
        margin-top: 5px;
        }

        .card-dropdown.show {
            display: block;
        }

        .card-dropdown button:hover {
            background-color: #f8f9fa;
        }

        .card-dropdown button.edit {
            color: var(--primary);
        }

        .card-dropdown button.cancel {
            color: var(--danger);
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary);
        }

        .modal-header .close {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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

        #itensContainer .item-pedido {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }

        #itensContainer .item-pedido .form-group {
            margin-bottom: 0;
        }

        #itensContainer .item-pedido .subtotal {
            font-weight: bold;
            font-size: 14px;
        }

        #itensContainer .item-pedido button.remove-item {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        #itensContainer .item-pedido button.remove-item:hover {
            background-color: #dc3545;
        }

        #totalPedido {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--secondary);
            margin: 15px 0;
            padding-top: 15px;
            border-top: 1px solid #eee;
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
            
            .kanban-container {
                flex-direction: column;
            }
            
            .kanban-column {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Gerente</h1>
            <div class="header-buttons">
                <button class="btn" onclick="openModal('pedido')">
                    <i class="fas fa-plus"></i> Novo Pedido
                </button>
                <button class="btn" onclick="location.href='mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn" onclick="location.href='historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="btn" onclick="location.href='clientes.php'">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button class="btn logout" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="caixa-info">
            <div class="caixa-status">
                <h2>Situação do Caixa</h2>
                <button class="btn" onclick="abrirCaixa()">Abrir Caixa</button>
                <button class="btn" onclick="fecharCaixa()">Fechar Caixa</button>
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

      <div class="kanban-container">
    <div class="kanban-column" id="pendente" ondragover="allowDrop(event)" ondrop="drop(event, 'pendente')">
        <div class="column-header">
            <h3 class="column-title">Pendente</h3>
            <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'pendente')) ?></span>
        </div>
        <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pendente'): ?>
            <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                <div class="card-header">
                    <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                    <div class="card-actions" style="position: relative;">
                        <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="card-dropdown" style="display: none; position: absolute; right: 0; top: 100%; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 6px; z-index: 10; padding: 10px; min-width: 120px;">
                            <form method="post" style="margin-bottom: 8px;">
                                <input type="hidden" name="action" value="editar">
                                <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                    <select name="produto_id" required>
                                        <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <button type="submit" class="btn edit" style="width: 100%; text-align: left;"><i class="fas fa-edit"></i> Editar</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Deseja mesmo excluir o pedido?')">
                                <input type="hidden" name="action" value="excluir">
                                <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                <button type="submit" class="btn cancel" style="width: 100%; text-align: left;"><i class="fas fa-times"></i> Cancelar</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                    <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                    <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                </div>
            </div>
        <?php endif; endforeach; ?>
    </div>
</div>


            <div class="kanban-column" id="preparando" ondragover="allowDrop(event)" ondrop="drop(event, 'preparando')">
                <div class="column-header">
                    <h3 class="column-title">Em Preparo</h3>
                    <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'preparando')) ?></span>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'preparando'): ?>
                    <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                        <div class="card-header">
                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                            <div class="card-actions">
                                <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <form method="post" style="margin-bottom: 5px;">
                                    <input type="hidden" name="action" value="editar">
                                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                    <select name="produto_id">
                                        <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?> - R$<?= number_format($produto['preco'], 2, ',', '.') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Editar Produto</button>
                                </form>

                                <!-- Formulário de exclusão -->
                                <form method="post" onsubmit="return confirm('Deseja mesmo excluir o pedido?')">
                                    <input type="hidden" name="action" value="excluir">
                                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                    <button type="submit">Excluir</button>
                                </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                            <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="kanban-column" id="pronto" ondragover="allowDrop(event)" ondrop="drop(event, 'pronto')">
                <div class="column-header">
                    <h3 class="column-title">Pronto</h3>
                    <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'pronto')) ?></span>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pronto'): ?>
                    <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                        <div class="card-header">
                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                            <div class="card-actions">
                                <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <form method="post" style="margin-bottom: 5px;">
                                    <input type="hidden" name="action" value="editar">
                                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                    <select name="produto_id">
                                        <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?> - R$<?= number_format($produto['preco'], 2, ',', '.') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Editar Produto</button>
                                </form>

                                <!-- Formulário de exclusão -->
                                <form method="post" onsubmit="return confirm('Deseja mesmo excluir o pedido?')">
                                    <input type="hidden" name="action" value="excluir">
                                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                    <button type="submit">Excluir</button>
                                </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                            <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            </div>

                    <div class="kanban-column" id="entregue" ondragover="allowDrop(event)" ondrop="drop(event, 'entregue')">
                        <div class="column-header">
                            <h3 class="column-title">Entregue</h3>
                            <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'entregue')) ?></span>
                        </div>

                        <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'entregue'): ?>
                            <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                                    <div class="card">
                                        <div class="card-header">
                                            <span class="card-title">Pedido #<?= $pedido['id'] ?></span>

                                            <!-- Botão de menu com dropdown -->
                                            <div class="card-menu" style="position: relative;">
                                                <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>

                                                <div class="card-dropdown" style="display: none; position: absolute; top: 100%; right: 0; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.2); z-index: 999; width: 200px; border-radius: 5px; padding: 10px;">
                                                    
                                                    <!-- Formulário de edição -->
                                                    <form method="post" style="margin-bottom: 10px;">
                                                        <input type="hidden" name="action" value="editar">
                                                        <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                                        <select name="produto_id" style="width: 100%; margin-bottom: 5px;">
                                                            <?php foreach ($produtos as $produto): ?>
                                                                <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?> - R$<?= number_format($produto['preco'], 2, ',', '.') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" style="width: 100%;">Editar Produto</button>
                                                    </form>

                                                    <!-- Formulário de exclusão -->
                                                    <form method="post" onsubmit="return confirm('Deseja mesmo excluir o pedido?')">
                                                        <input type="hidden" name="action" value="excluir">
                                                        <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                                        <button type="submit" style="width: 100%;">Excluir</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <!-- Informações do pedido -->
                                        </div>
                                    </div>


                                <div class="card-body">
                                    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                                    <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                                    <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                                    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>


    <!-- Modal de Novo Pedido -->
    <div class="modal" id="pedidoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Novo Pedido</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Cliente</label>
                    <select id="cliente_id" class="form-control" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['nome']) ?> - <?= $cliente['telefone'] ?>
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
                        <button type="button" class="remove-item" onclick="removerItem(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="btn" onclick="adicionarItem()" style="margin-bottom: 15px;">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>

                <div style="font-weight: bold; font-size: 1.2em;">
                    <label>Total do Pedido:</label>
                    <span id="totalPedido">R$ 0,00</span>
                </div>

                <button type="button" class="btn" onclick="enviarPedido()" style="margin-top: 15px; width: 100%;">
                    <i class="fas fa-save"></i> Salvar Pedido
                </button>
                <!-- Formulário de edição -->
                <form method="post" style="margin-bottom: 5px;">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                    <select name="produto_id">
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?> - R$<?= number_format($produto['preco'], 2, ',', '.') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Editar Produto</button>
                </form>

                <!-- Formulário de exclusão -->
                <form method="post" onsubmit="return confirm('Deseja mesmo excluir o pedido?')">
                    <input type="hidden" name="action" value="excluir">
                    <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                    <button type="submit">Excluir</button>
                </form>
                </form>

            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Funções para o menu de ações nos cards
        function toggleDropdown(event, btn) {
    event.stopPropagation();
    const dropdown = btn.nextElementSibling;
    const isOpen = dropdown.style.display === 'block';

    // Fecha todos os outros dropdowns
    document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');

    // Abre ou fecha o dropdown atual
    dropdown.style.display = isOpen ? 'none' : 'block';
}

// Fecha dropdowns ao clicar fora
document.addEventListener('click', () => {
    document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');
});

        // Função para editar pedido
        function editarPedido(pedidoId) {
            showNotification('Abrindo pedido #' + pedidoId + ' para edição...', 'success');
            // Aqui você pode implementar a lógica para abrir um modal de edição
            // ou redirecionar para uma página de edição específica
        }

        // Função para cancelar pedido
        function excluirPedido(pedidoId) {
            if (confirm(`Tem certeza que deseja excluir o pedido #${pedidoId}?`)) {
                fetch('index_gerente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        form: 'excluir',
                        pedido_id: pedidoId
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

        // Funções para drag and drop
        let draggedPedidoId = null;

        function drag(event) {
            draggedPedidoId = event.target.id;
        }

        function allowDrop(event) {
            event.preventDefault();
        }

        function drop(event, status) {
            event.preventDefault();
            
            if (!draggedPedidoId) return;
            
            const pedidoId = draggedPedidoId.split('-')[1];
            
            fetch('index_gerente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    form: 'update',
                    pedido_id: pedidoId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pedidoElement = document.getElementById(draggedPedidoId);
                    document.getElementById(status).appendChild(pedidoElement);
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }

        // Funções para o modal de pedido
        function openModal(modalId) {
            document.getElementById(modalId + 'Modal').style.display = 'flex';
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
                function abrirCaixa() {
    const valor = prompt("Digite o valor para abrir o caixa:");
    if (valor) {
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form: 'caixa',
                acao: 'abrir',
                valor: parseFloat(valor)
            })
        })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                location.reload(); // Atualiza a página para refletir as mudanças
            }
        });
    }
}

function fecharCaixa() {
    const valor = prompt("Digite o valor para fechar o caixa:");
    if (valor) {
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                form: 'caixa',
                acao: 'fechar',
                valor: parseFloat(valor)
            })
        })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                location.reload(); // Atualiza a página para refletir as mudanças
            }
        });
    }
}

        // Funções para adicionar/remover itens do pedido
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
                <button type="button" class="remove-item" onclick="removerItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            container.appendChild(novoItem);
            
            // Adiciona os event listeners para o novo item
            novoItem.querySelector('.produto-select').addEventListener('change', calcularSubtotal);
            novoItem.querySelector('.quantidade-input').addEventListener('input', calcularSubtotal);
        }

        function removerItem(btn) {
            const item = btn.closest('.item-pedido');
            if (document.querySelectorAll('.item-pedido').length > 1) {
                item.remove();
                calcularTotal();
            } else {
                showNotification('O pedido deve ter pelo menos um item.', 'error');
            }
        }

        function calcularSubtotal(event) {
            const item = event.target.closest('.item-pedido');
            const select = item.querySelector('.produto-select');
            const input = item.querySelector('.quantidade-input');
            const subtotalSpan = item.querySelector('.subtotal');

            const preco = parseFloat(select.selectedOptions[0]?.dataset.preco || 0);
            const quantidade = parseInt(input.value) || 0;
            const subtotal = preco * quantidade;

            subtotalSpan.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            calcularTotal();
        }

        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.item-pedido').forEach(item => {
                const subtotalText = item.querySelector('.subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                total += subtotal;
            });

            document.getElementById('totalPedido').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        function enviarPedido() {
            const clienteId = document.getElementById('cliente_id').value;
            const itens = [];

            if (!clienteId) {
                showNotification('Selecione um cliente', 'error');
                return;
            }

            document.querySelectorAll('.item-pedido').forEach(item => {
                const produtoId = item.querySelector('.produto-select').value;
                const quantidade = item.querySelector('.quantidade-input').value;

                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: quantidade
                    });
                }
            });

            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item ao pedido', 'error');
                return;
            }

            fetch('index_gerente.php', {
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
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                }
            });
        }

        function excluirPedido(id) {
            if (confirm("Tem certeza que deseja cancelar (excluir) o pedido #" + id + "?")) {
            fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                form: 'cancelar',
                pedido_id: id
            })
        })
        .then(res => res.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                location.reload();
            }
        });
    }
}
        function editarPedido(id) {
    fetch('buscar_pedido.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_pedido_id').value = id;
            const container = document.getElementById('edit_itens_container');
            container.innerHTML = '';

            data.itens.forEach((item, index) => {
                const div = document.createElement('div');
                div.innerHTML = `
                    <label>Produto ${index + 1}</label>
                    <select class="edit-produto-select" data-quantidade="${item.quantidade}">
                        ${data.produtos.map(prod =>
                            `<option value="${prod.id}" ${prod.id == item.produto_id ? 'selected' : ''}>
                                ${prod.nome} - R$ ${parseFloat(prod.preco).toFixed(2).replace('.', ',')}
                            </option>`).join('')}
                    </select>
                `;
                container.appendChild(div);
            });

            document.getElementById('modalEditarPedido').style.display = 'flex';
        });
}

function fecharModalEditar() {
    document.getElementById('modalEditarPedido').style.display = 'none';
}

        function salvarEdicaoPedido() {
    const pedido_id = document.getElementById('edit_pedido_id').value;
    const novosItens = [];

    document.querySelectorAll('.edit-produto-select').forEach(select => {
        novosItens.push({
            produto_id: select.value,
            quantidade: select.dataset.quantidade
        });
    });

    fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            form: 'editar_produto',
            pedido_id: pedido_id,
            itens: novosItens
        })
    })
    .then(res => res.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            fecharModalEditar();
            location.reload();
        }
    });
}

        // Inicializa os event listeners quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners para o primeiro item do pedido
            document.querySelector('.produto-select')?.addEventListener('change', calcularSubtotal);
            document.querySelector('.quantidade-input')?.addEventListener('input', calcularSubtotal);
            
            // Fecha o modal ao pressionar ESC
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
