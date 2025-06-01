    <?php
    // index_gerente.php - VERSÃO CORRIGIDA COM CHAMADAS DE MÉTODOS ESTÁTICOS

    // 1. Inclua seu arquivo de conexão
    require_once '../Geral/conexao.php';

    // 2. Inclua a classe CaixaManager refatorada
    require_once '../Caixa/CaixaManager.php';

    // 3. Inclua seu arquivo de funções
    require_once '../Geral/funcoes.php'; // Caminho corrigido

    require_once 'funcoesGerente.php'; // Caminho corrigido

    // Obtenha a conexão mysqli através da sua função global getConnection()
    $mysqliConnection = getConnection();

    // Instancia o CaixaManager com a conexão mysqli
    $caixaManager = new CaixaManager($mysqliConnection);

    $mensagemStatus = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = ['success' => false, 'message' => ''];
        $data = json_decode(file_get_contents("php://input"), true);

        if ($data && isset($data['form'])) {
            switch ($data['form']) {
                case 'cliente':
                    // Chamada de método estático
                    if (FuncoesGerente::cadastrarCliente($data['nome'], $data['telefone'], $data['endereco'])) {
                        $response = ['success' => true, 'message' => 'Cliente cadastrado!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao cadastrar cliente.'];
                    }
                    break;

                case 'produto':
                    // Chamada de método estático
                    if (FuncoesGerente::cadastrarProduto($data['nome_produto'], $data['preco_produto'])) {
                        $response = ['success' => true, 'message' => 'Produto cadastrado!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao cadastrar produto.'];
                    }
                    break;

                case 'pedido':
                    if (isset($data['cliente_id']) && isset($data['itens']) && is_array($data['itens'])) {
                        // Chamada de método estático
                        $pedido_id = FuncoesGerente::cadastrarPedido($data['cliente_id'], $data['itens']);
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
                    // Ao arrastar para 'pronto', finalize o pedido e registre a forma de pagamento
                    if ($data['status'] === 'pronto') {
                        $formaPagamentoPadraoId = 1; // Ajuste para o ID da sua forma de pagamento padrão
                        $caixaResultado = $caixaManager->finalizarPedidoEAdicionarAoCaixa($data['pedido_id'], $formaPagamentoPadraoId);
                        
                        // Verifica se o resultado da operação do caixa é um array e se indica falha
                        if (is_array($caixaResultado) && isset($caixaResultado['success']) && $caixaResultado['success'] === false) {
                            $response = ['success' => false, 'message' => $caixaResultado['message'] ?? 'Erro desconhecido ao finalizar pedido e adicionar ao caixa.'];
                        } else {
                            // Se a finalização do caixa foi bem-sucedida, atualize o status do pedido
                            if (FuncoesGerente::atualizarStatus($data['pedido_id'], $data['status'])) {
                                $response = ['success' => true, 'message' => 'Status atualizado e valor adicionado ao caixa!'];
                            } else {
                                $response = ['success' => false, 'message' => 'Erro ao atualizar status do pedido após finalização do caixa.'];
                            }
                        }
                    } else {
                        // Chamada de método estático para outros status
                        if (FuncoesGerente::atualizarStatus($data['pedido_id'], $data['status'])) {
                            $response = ['success' => true, 'message' => 'Status atualizado!'];
                        } else {
                            $response = ['success' => false, 'message' => 'Erro ao atualizar status.'];
                        }
                    }
                    break;

                case 'cancelar':
                    if (isset($data['pedido_id']) && isset($data['motivo_id'])) {
                        // Chamada de método estático
                        if (FuncoesGerente::cancelarPedido($data['pedido_id'], $data['motivo_id'])) {
                            $response = ['success' => true, 'message' => 'Pedido cancelado com sucesso!'];
                        } else {
                            $response = ['success' => false, 'message' => 'Erro ao cancelar o pedido.'];
                        }
                    } else {
                        $response = ['success' => false, 'message' => 'Dados de cancelamento incompletos.'];
                    }
                    break;

                case 'editar_pedido_completo': // Nova ação para edição completa
                    if (isset($data['pedido_id']) && isset($data['itens']) && is_array($data['itens'])) {
                        // Chamada de método estático
                        if (FuncoesGerente::editarItensPedido($data['pedido_id'], $data['itens'])) {
                            $response = ['success' => true, 'message' => 'Itens do pedido atualizados!'];
                        } else {
                            $response = ['success' => false, 'message' => 'Erro ao atualizar itens do pedido.'];
                        }
                    } else {
                        $response = ['success' => false, 'message' => 'Dados de edição incompletos.'];
                    }
                    break;
            }

            echo json_encode($response);
            exit;
        }

        // Se estiver usando form-data tradicional
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            // Ajuste estas partes se ainda usar POST tradicional.
            // O ideal é migrar tudo para a abordagem JSON/Fetch API.
            if ($action === 'excluir') {
                $response = ['success' => false, 'message' => 'A exclusão direta foi desativada. Use a opção de Cancelar com motivo.'];
                echo json_encode($response);
                exit;
            }

            if ($action === 'editar') {
                $id = $_POST['id'] ?? null;
                $novoProduto = $_POST['produto_id'] ?? null;
                $response = ['success' => false, 'message' => 'A edição direta foi desativada. Use o modal de edição completo.'];
                echo json_encode($response);
                exit;
            }
        }

        echo json_encode(['success' => false, 'message' => 'Nenhuma ação reconhecida.']);
        exit;
    }

    // Carregar dados para a página (Chamadas de métodos estáticos)
    $pedidos = FuncoesGerente::buscarPedidos(); // Buscar todos os pedidos para o Kanban
    $clientes = FuncoesGerente::buscarClientes();
    $produtos = FuncoesGerente::buscarProdutos();
    $caixa = $caixaManager->getCaixaAtual(); // Esta linha está correta, pois é do CaixaManager
    $motivosCancelamento = FuncoesGerente::buscarMotivosCancelamento(); // Chamada de método estático
    // $relatorioPedidos = FuncoesGerente::gerarRelatorioPedidos(); // Removido daqui, agora está em relatorio_pedidos.php

    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>PedidoPronto - Gerente</title>
        <link rel="stylesheet" href="stylegerente.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    </head>
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
        font-family: "Roboto", sans-serif;
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        background-color: #4361ee;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
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
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .caixa-status {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        }

        .caixa-valor span {
        font-size: 12px;
        color: #666;
        display: block;
        margin-bottom: 5px;
        }

        .caixa-valor p {
        font-weight: 500;
        font-size: 18px;
        margin: 0;
        }

        .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
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
        overflow-x: auto;
        padding-bottom: 20px;
        }

        .kanban-column {
        flex: 1;
        min-width: 280px;
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        display: flex;
        align-items: center;
        gap: 8px;
        }

        .badge-count {
        background-color: var(--primary);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        }

        .card {
        background-color: white;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: grab;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #eee;
        position: relative;
        }

        .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
        }

        .card-title {
        font-weight: 500;
        color: var(--primary);
        font-size: 15px;
        }

        .card-body p {
        margin-bottom: 5px;
        font-size: 13px;
        color: #555;
        }

        .card-body p strong {
        color: #333;
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
        transition: background-color 0.2s;
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 100;
        display: none;
        min-width: 160px;
        padding: 5px 0;
        }

        .card-dropdown.show {
        display: block;
        }

        .card-dropdown button,
        .card-dropdown a {
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
        color: #333;
        text-decoration: none;
        }

        .card-dropdown button:hover,
        .card-dropdown a:hover {
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
        background-color: rgba(0, 0, 0, 0.5);
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
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
        font-size: 1.5em;
        }

        .modal-header .close {
        background: none;
        border: none;
        font-size: 1.5em;
        cursor: pointer;
        color: #666;
        transition: color 0.2s;
        }

        .modal-header .close:hover {
        color: var(--danger);
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

        input,
        select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.2s;
        }

        input:focus,
        select:focus {
        border-color: var(--primary);
        outline: none;
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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        }

        .notification.success {
        background-color: var(--success);
        }

        .notification.error {
        background-color: var(--danger);
        }

        #itensContainer .item-pedido {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 6px;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 15px;
        align-items: center;
        background-color: #f8f9fa;
        }

        #itensContainer .item-pedido .form-group {
        margin-bottom: 0;
        }

        #itensContainer .item-pedido .subtotal {
        font-weight: bold;
        font-size: 14px;
        color: var(--secondary);
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
        transition: background-color 0.2s;
        }

        #itensContainer .item-pedido button.remove-item:hover {
        background-color: #dc3545;
        }

        #totalPedido {
        font-size: 1.3em;
        font-weight: bold;
        color: var(--secondary);
        margin: 15px 0;
        padding-top: 15px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
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

        #itensContainer .item-pedido {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        /* Estilo para os itens do dropdown */
        .dropdown-item {
            display: block;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        /* Estilo para o botão de submit dentro do dropdown */
        .dropdown-item button {
            width: 100%;
            text-align: left;
            padding: 0;
            background: none;
            border: none;
        }
        }

        /* Estilos do Relatório */
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

        .report-section ul {
        list-style: none;
        padding: 0;
        }

        .report-section ul li {
        padding: 5px 0;
        border-bottom: 1px dashed #f0f0f0;
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
        }

        .report-section th {
        background-color: #e9ecef;
        }

        .kanban-column#cancelado {
        /* Estilo específico para a coluna cancelado */
        background-color: #ffe6e6; /* Um fundo mais claro */
        border: 1px solid #ffcccc;
        }

        .kanban-column#cancelado .card {
        background-color: #fffafa; /* Fundo mais claro para cards cancelados */
        border-left: 5px solid #dc3545; /* Uma borda colorida para destacar */
        }

    </style>
    <body>
        <header>
            <div class="header-content">
                <h1>PedidoPronto - Gerente</h1>
                <div class="header-buttons">
                    <button class="btn" onclick="openModal('pedido')">
                        <i class="fas fa-plus"></i> Novo Pedido
                    </button>
                    <button class="btn" onclick="location.href='../cardapio/mostrarcardapio.php'">
                        <i class="fas fa-utensils"></i> Cardápio
                    </button>
                    <button class="btn" onclick="location.href='../Pedidos/historicopedidos.php'">
                        <i class="fas fa-history"></i> Histórico
                    </button>
                    <button class="btn" onclick="location.href='../Clientes/clientes.php'">
                        <i class="fas fa-users"></i> Clientes
                    </button>
                    <button class="btn" onclick="location.href='relatorio_pedidos.php'">
                        <i class="fas fa-chart-bar"></i> Relatórios de Pedidos
                    </button>
                    <button class="btn" onclick="location.href='relatorio_produtos_vendidos.php'">
                        <i class="fas fa-chart-pie"></i> Relatório de Produtos
                    </button>
                    <button class="btn" onclick="location.href='relatorio_acoes_equipe.php'">
                        <i class="fas fa-clipboard-list"></i> Ações da Equipe
                    </button>
                    <button class="btn logout" onclick="location.href='../Geral/logout.php'">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </button>
                </div>
            </div>
        </header>

        <div class="container">
            <?php echo $mensagemStatus; ?>

            <div class="caixa-info">
                <div class="caixa-status">
                    <h2>Situação do Caixa</h2>
                    <span class="status-badge status-<?= $caixa['status'] ?>">
                        <?= strtoupper($caixa['status']) ?>
                    </span>
                </div>
                <div>
                    <?php if ($caixa['status'] === 'fechado'): ?>
                        <button class="btn primary" onclick="abrirCaixaComPrompt()">Abrir Caixa</button>
                    <?php else: ?>
                        <button class="btn danger" onclick="fecharCaixaComPrompt()">Fechar Caixa</button>
                    <?php endif; ?>
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
                                    <div class="card-dropdown">
                                        <a href="../Pedidos/editar_pedido.php?id=<?= $pedido['id'] ?>" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="dropdown-item danger" onclick="openCancelModal(<?= $pedido['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
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

                <div class="kanban-column" id="preparo" ondragover="allowDrop(event)" ondrop="drop(event, 'preparo')">
                    <div class="column-header">
                        <h3 class="column-title">Em Preparo</h3>
                        <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'preparo')) ?></span>
                    </div>
                    <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'preparo'): ?>
                        <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                                <div class="card-actions">
                                    <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="card-dropdown">
                                        <a href="../Pedidos/editar_pedido.php?id=<?= $pedido['id'] ?>" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="dropdown-item danger" onclick="openCancelModal(<?= $pedido['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
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
                                    <div class="card-dropdown">
                                        <a href="../Pedidos/editar_pedido.php?id=<?= $pedido['id'] ?>" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="dropdown-item danger" onclick="openCancelModal(<?= $pedido['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
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
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                                <div class="card-actions">
                                    <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="card-dropdown">
                                        <a href="../Pedidos/editar_pedido.php?id=<?= $pedido['id'] ?>" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                            <i class="fas fa-eye"></i> Visualizar Detalhes
                                        </a>
                                        <button type="button" class="dropdown-item danger" onclick="openCancelModal(<?= $pedido['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
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

                <div class="kanban-column" id="cancelado" ondragover="allowDrop(event)" ondrop="drop(event, 'cancelado')">
                    <div class="column-header">
                        <h3 class="column-title">Cancelado</h3>
                        <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'cancelado')) ?></span>
                    </div>
                    <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'cancelado'): ?>
                        <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
                            <div class="card-header">
                                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                                <div class="card-actions">
                                    <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="card-dropdown">
                                        <a href="../Pedidos/editar_pedido.php?id=<?= $pedido['id'] ?>" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                            <i class="fas fa-eye"></i> Visualizar Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                                <p><strong>Motivo:</strong> <?= htmlspecialchars($pedido['motivo_cancelamento'] ?? 'Não informado') ?></p>
                                <p><strong>Status Anterior:</strong> <?= htmlspecialchars($pedido['status_anterior'] ?? 'N/A') ?></p>
                                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>


        <div class="modal" id="pedidoModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Novo Pedido</h3>
                    <button class="close-button" onclick="closeModal()">&times;</button>
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

                    <button type="button" class="btn primary" onclick="adicionarItem()" style="margin-bottom: 15px;">
                        <i class="fas fa-plus"></i> Adicionar Item
                    </button>

                    <div style="font-weight: bold; font-size: 1.2em;">
                        <label>Total do Pedido:</label>
                        <span id="totalPedido">R$ 0,00</span>
                    </div>

                    <button type="button" class="btn primary" onclick="enviarPedido()" style="margin-top: 15px; width: 100%;">
                        <i class="fas fa-save"></i> Salvar Pedido
                    </button>
                </div>
            </div>
        </div>

        <div class="modal" id="cancelModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Cancelar Pedido #<span id="cancelPedidoId"></span></h3>
                    <button class="close-button" onclick="closeModal('cancelModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentCancelPedidoId">
                    <div class="form-group">
                        <label for="motivo_cancelamento">Motivo do Cancelamento:</label>
                        <select id="motivo_cancelamento" class="form-control" required>
                            <option value="">Selecione um motivo</option>
                            <?php foreach ($motivosCancelamento as $motivo): ?>
                                <option value="<?= $motivo['id'] ?>"><?= htmlspecialchars($motivo['motivo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn danger" onclick="confirmCancelPedido()">Confirmar Cancelamento</button>
                </div>
            </div>
        </div>

        <div class="modal" id="modalEditarPedido">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Editar Pedido #<span id="edit_pedido_id_display"></span></h3>
                    <button class="close-button" onclick="fecharModalEditar()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_pedido_id">
                    <div id="edit_itens_container">
                        </div>
                    <button type="button" class="btn primary" onclick="salvarEdicaoPedido()">Salvar Edição</button>
                </div>
            </div>
        </div>


        <div class="notification" id="notification"></div>

        <script>
            // Variável global para armazenar o status original do pedido arrastado
            let draggedPedidoOriginalStatus = null;
            let draggedPedidoId = null;

            // Funções para o menu de ações nos cards
            function toggleDropdown(event, btn) {
                event.stopPropagation();
                const dropdown = btn.nextElementSibling;
                const isOpen = dropdown.style.display === 'block';

                document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');

                dropdown.style.display = isOpen ? 'none' : 'block';
            }

            document.addEventListener('click', () => {
                document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');
            });

            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                notification.textContent = message;
                notification.className = 'notification ' + type;
                notification.style.display = 'block';
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 500);
                }, 3000);
            }

            function drag(event) {
                draggedPedidoId = event.target.id;
                // Encontra a coluna pai para obter o status original
                let parentColumn = event.target.closest('.kanban-column');
                if (parentColumn) {
                    draggedPedidoOriginalStatus = parentColumn.id;
                } else {
                    draggedPedidoOriginalStatus = null;
                }

                // Impede arrastar da coluna 'cancelado'
                if (draggedPedidoOriginalStatus === 'cancelado') {
                    event.preventDefault(); // Impede a operação de arrastar
                    showNotification('Pedidos cancelados não podem ser movidos.', 'error');
                    draggedPedidoId = null; // Reseta para evitar drops não intencionais
                    draggedPedidoOriginalStatus = null;
                }
            }

            function allowDrop(event) {
                event.preventDefault();
            }

            function drop(event, targetStatus) {
                event.preventDefault();
                
                if (!draggedPedidoId || draggedPedidoOriginalStatus === null) return; // Garante que um arrasto válido começou
                
                const pedidoId = draggedPedidoId.split('-')[1];

                let allowTransition = false;

                // Regras de transição
                if (targetStatus === 'cancelado') {
                    allowTransition = true; // Permite sempre cancelar
                } else if (draggedPedidoOriginalStatus === 'pendente' && targetStatus === 'preparo') {
                    allowTransition = true;
                } else if (draggedPedidoOriginalStatus === 'preparo' && targetStatus === 'pronto') {
                    allowTransition = true;
                } else if (draggedPedidoOriginalStatus === 'pronto' && targetStatus === 'entregue') {
                    allowTransition = true;
                } else {
                    // Qualquer outra transição não é permitida
                    showNotification(`Transição de "${draggedPedidoOriginalStatus}" para "${targetStatus}" não permitida.`, 'error');
                    draggedPedidoId = null; // Reseta o estado de arrasto
                    draggedPedidoOriginalStatus = null;
                    return; // Interrompe a execução
                }

                // Se for um cancelamento, o modal já lida com o fetch.
                // Para outras transições permitidas, prossegue com o fetch.
                if (allowTransition) {
                    if (targetStatus === 'cancelado') {
                        openCancelModal(pedidoId); // Abre o modal de cancelamento
                    } else {
                        fetch('index_gerente.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                form: 'update',
                                pedido_id: pedidoId,
                                status: targetStatus
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const pedidoElement = document.getElementById(draggedPedidoId);
                                document.getElementById(targetStatus).appendChild(pedidoElement);
                                showNotification(data.message, 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Erro no fetch:', error);
                            showNotification('Erro de comunicação com o servidor.', 'error');
                        })
                        .finally(() => {
                            draggedPedidoId = null; // Reseta o estado de arrasto
                            draggedPedidoOriginalStatus = null;
                        });
                    }
                }
                // Reseta o estado de arrasto em qualquer caso, mesmo após abrir o modal de cancelamento
                draggedPedidoId = null;
                draggedPedidoOriginalStatus = null;
            }

            function openModal(modalId) {
                document.getElementById(modalId + 'Modal').style.display = 'flex';
                if (modalId === 'pedido') {
                    calcularTotal();
                }
            }

            function closeModal(modalId = null) {
                if (modalId) {
                    document.getElementById(modalId).style.display = 'none';
                } else {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.style.display = 'none';
                    });
                }
            }

            function abrirCaixaComPrompt() {
                const responsavel = prompt("Digite o nome do responsável pelo caixa:");
                if (responsavel) {
                    const saldoInicial = prompt("Digite o saldo inicial para abrir o caixa (opcional, padrão 0.00):");
                    let valorInicial = parseFloat(saldoInicial) || 0.00;

                    fetch('index_gerente.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            form: 'abrir_caixa_gerente', // Nova ação para diferenciar
                            responsavel: responsavel,
                            saldo_inicial: valorInicial
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao abrir caixa:', error);
                        showNotification('Erro de comunicação ao abrir caixa.', 'error');
                    });
                }
            }

            function fecharCaixaComPrompt() {
                const responsavel = prompt("Digite o nome do responsável pelo fechamento do caixa:");
                if (responsavel) {
                    fetch('index_gerente.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            form: 'fechar_caixa_gerente', // Nova ação para diferenciar
                            responsavel: responsavel
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao fechar caixa:', error);
                        showNotification('Erro de comunicação ao fechar caixa.', 'error');
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

            // --- Funções para Cancelamento de Pedido ---
            let pedidoIdToCancel = null;

            function openCancelModal(pedidoId) {
                pedidoIdToCancel = pedidoId;
                document.getElementById('cancelPedidoId').textContent = pedidoId;
                document.getElementById('currentCancelPedidoId').value = pedidoId;
                openModal('cancel');
            }

            function confirmCancelPedido() {
                const motivoId = document.getElementById('motivo_cancelamento').value;
                if (!motivoId) {
                    showNotification('Por favor, selecione um motivo de cancelamento.', 'error');
                    return;
                }

                fetch('index_gerente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        form: 'cancelar',
                        pedido_id: pedidoIdToCancel,
                        motivo_id: motivoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeModal('cancelModal');
                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(error => {
                    console.error('Erro ao cancelar pedido:', error);
                    showNotification('Erro de comunicação ao cancelar pedido.', 'error');
                });
            }

            // --- Funções de Edição de Pedido ---
            function editarPedido(id) {
                fetch('buscar_pedido_detalhes.php?id=' + id)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit_pedido_id').value = id;
                            document.getElementById('edit_pedido_id_display').textContent = id;
                            const container = document.getElementById('edit_itens_container');
                            container.innerHTML = '';

                            data.itens.forEach((item, index) => {
                                const div = document.createElement('div');
                                div.className = 'item-pedido'; // Pode ser 'item-pedido-edit' para estilos específicos
                                div.innerHTML = `
                                    <div class="form-group">
                                        <label>Produto</label>
                                        <select name="produto_id_edit[]" class="form-control edit-produto-select" required data-item-id="${item.item_id}">
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
                                        <input type="number" name="quantidade_edit[]" class="form-control edit-quantidade-input" min="1" value="1" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Subtotal</label>
                                        <span class="edit-subtotal">R$ ${ (item.quantidade * parseFloat(data.produtos.find(p => p.id == item.produto_id)?.preco || 0)).toFixed(2).replace('.', ',') }</span>
                                    </div>
                                    <button type="button" class="remove-item" onclick="removerItemEdicao(this, ${item.item_id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                                container.appendChild(div);

                                div.querySelector('.edit-produto-select').addEventListener('change', calcularSubtotalEdicao);
                                div.querySelector('.edit-quantidade-input').addEventListener('input', calcularSubtotalEdicao);
                            });

                            const addButton = document.createElement('button');
                            addButton.type = 'button';
                            addButton.className = 'btn primary';
                            addButton.innerHTML = '<i class="fas fa-plus"></i> Adicionar Item';
                            addButton.onclick = adicionarItemEdicao;
                            container.appendChild(addButton);

                            const totalDiv = document.createElement('div');
                            totalDiv.style.fontWeight = 'bold';
                            totalDiv.style.fontSize = '1.2em';
                            totalDiv.innerHTML = '<label>Total do Pedido:</label><span id="totalPedidoEdicao">R$ 0,00</span>';
                            container.appendChild(totalDiv);
                            calcularTotalEdicao();

                            openModal('modalEditarPedido');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar detalhes do pedido para edição:', error);
                        showNotification('Erro ao carregar dados do pedido para edição.', 'error');
                    });
            }

            function fecharModalEditar() {
                closeModal('modalEditarPedido');
            }

            function salvarEdicaoPedido() {
                const pedido_id = document.getElementById('edit_pedido_id').value;
                const itens = [];

                document.querySelectorAll('#edit_itens_container .item-pedido').forEach(itemDiv => { // Corrigido se a classe for 'item-pedido'
                    const produtoId = itemDiv.querySelector('.edit-produto-select').value;
                    const quantidade = itemDiv.querySelector('.edit-quantidade-input').value;
                    const itemId = itemDiv.querySelector('.edit-produto-select').dataset.itemId;

                    if (produtoId && quantidade) {
                        itens.push({
                            item_id: itemId,
                            produto_id: produtoId,
                            quantidade: parseInt(quantidade)
                        });
                    }
                });

                if (itens.length === 0) {
                    showNotification('Adicione pelo menos um item ao pedido.', 'error');
                    return;
                }

                fetch('index_gerente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        form: 'editar_pedido_completo',
                        pedido_id: pedido_id,
                        itens: itens
                    })
                })
                .then(res => res.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        fecharModalEditar();
                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(error => {
                    console.error('Erro ao salvar edição do pedido:', error);
                    showNotification('Erro de comunicação ao salvar edição.', 'error');
                });
            }

            function adicionarItemEdicao() {
                const container = document.getElementById('edit_itens_container');
                const novoItem = document.createElement('div');
                novoItem.className = 'item-pedido'; // Pode ser 'item-pedido-edit'
                novoItem.innerHTML = `
                    <div class="form-group">
                        <label>Produto</label>
                        <select name="produto_id_edit[]" class="form-control edit-produto-select" required>
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
                        <input type="number" name="quantidade_edit[]" class="form-control edit-quantidade-input" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Subtotal</label>
                        <span class="edit-subtotal">R$ 0,00</span>
                    </div>
                    <button type="button" class="remove-item" onclick="removerItemEdicao(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                // Inserir antes do botão "Adicionar Item" e do total
                const addButton = container.querySelector('.btn.primary');
                const totalDiv = container.querySelector('#totalPedidoEdicao')?.parentNode; // Usar ? para evitar erro se não existir ainda
                if (totalDiv) {
                    container.insertBefore(novoItem, totalDiv);
                } else {
                    container.appendChild(novoItem);
                }
                

                novoItem.querySelector('.edit-produto-select').addEventListener('change', calcularSubtotalEdicao);
                novoItem.querySelector('.edit-quantidade-input').addEventListener('input', calcularSubtotalEdicao);
                calcularTotalEdicao();
            }

            function removerItemEdicao(btn, itemId = null) {
                const item = btn.closest('.item-pedido'); // Pode ser 'item-pedido-edit'
                if (document.querySelectorAll('#edit_itens_container .item-pedido').length > 1) { // Corrigido a classe
                    item.remove();
                    calcularTotalEdicao();
                } else {
                    showNotification('O pedido deve ter pelo menos um item.', 'error');
                }
            }

            function calcularSubtotalEdicao(event) {
                const item = event.target.closest('.item-pedido'); // Pode ser 'item-pedido-edit'
                const select = item.querySelector('.edit-produto-select');
                const input = item.querySelector('.edit-quantidade-input');
                const subtotalSpan = item.querySelector('.edit-subtotal');

                const preco = parseFloat(select.selectedOptions[0]?.dataset.preco || 0);
                const quantidade = parseInt(input.value) || 0;
                const subtotal = preco * quantidade;

                subtotalSpan.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
                calcularTotalEdicao();
            }

            function calcularTotalEdicao() {
                let total = 0;
                document.querySelectorAll('#edit_itens_container .item-pedido').forEach(item => { // Corrigido a classe
                    const subtotalText = item.querySelector('.edit-subtotal').textContent;
                    const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                    total += subtotal;
                });
                document.getElementById('totalPedidoEdicao').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            }

            // Helper para htmlspecialchars no JS (não nativo no JS, mas útil para simular)
            function htmlspecialchars(str) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return str.replace(/[&<>"']/g, function(m) { return map[m]; });
            }


            // Inicializa os event listeners quando o DOM estiver carregado
            document.addEventListener('DOMContentLoaded', function() {
                // Event listeners para o primeiro item do pedido (modal de novo pedido)
                // Certifique-se de que estes elementos existem antes de adicionar o listener
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
