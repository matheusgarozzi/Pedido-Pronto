<?php
// index_gerente.php

require_once '../Geral/conexao.php';
require_once '../Caixa/CaixaManager.php';
require_once '../Geral/funcoes.php';
require_once 'funcoesGerente.php';

$mysqliConnection = getConnection();
$caixaManager = new CaixaManager($mysqliConnection);

$mensagemStatus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data && isset($data['form'])) {
        switch ($data['form']) {
            case 'cliente':
                if (FuncoesGerente::cadastrarCliente($data['nome'], $data['telefone'], $data['endereco'])) {
                    $response = ['success' => true, 'message' => 'Cliente cadastrado!'];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao cadastrar cliente.'];
                }
                break;

            case 'produto':
                if (FuncoesGerente::cadastrarProduto($data['nome_produto'], $data['preco_produto'])) {
                    $response = ['success' => true, 'message' => 'Produto cadastrado!'];
                } else {
                    $response = ['success' => false, 'message' => 'Erro ao cadastrar produto.'];
                }
                break;

            case 'pedido':
                if (isset($data['cliente_id']) && isset($data['itens']) && is_array($data['itens'])) {
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
                if ($data['status'] === 'pronto') {
                    $formaPagamentoPadraoId = 1; // ID padrão (ex: Dinheiro)
                    $caixaResultado = $caixaManager->finalizarPedidoEAdicionarAoCaixa($data['pedido_id'], $formaPagamentoPadraoId);
                    
                    if (is_array($caixaResultado) && isset($caixaResultado['success']) && $caixaResultado['success'] === false) {
                        $response = ['success' => false, 'message' => $caixaResultado['message'] ?? 'Erro desconhecido ao finalizar pedido e adicionar ao caixa.'];
                    } else {
                        if (FuncoesGerente::atualizarStatus($data['pedido_id'], $data['status'])) {
                            $response = ['success' => true, 'message' => 'Status atualizado e valor adicionado ao caixa!'];
                        } else {
                            $response = ['success' => false, 'message' => 'Erro ao atualizar status do pedido após finalização do caixa.'];
                        }
                    }
                } else if ($data['status'] === 'entregue') {
                    $response = ['success' => false, 'message' => 'Pedidos não podem ser arrastados diretamente para "Entregue". Use o botão "Fechar Pedido" na coluna "Pronto".'];
                }
                else {
                    if (FuncoesGerente::atualizarStatus($data['pedido_id'], $data['status'])) {
                        $response = ['success' => true, 'message' => 'Status atualizado!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao atualizar status.'];
                    }
                }
                break;

            case 'cancelar':
                if (isset($data['pedido_id']) && isset($data['motivo_id'])) {
                    if (FuncoesGerente::cancelarPedido($data['pedido_id'], $data['motivo_id'])) {
                        $response = ['success' => true, 'message' => 'Pedido cancelado com sucesso!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao cancelar o pedido.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Dados de cancelamento incompletos.'];
                }
                break;

            case 'editar_pedido_completo':
                if (isset($data['pedido_id']) && isset($data['itens']) && is_array($data['itens'])) {
                    if (FuncoesGerente::editarItensPedido($data['pedido_id'], $data['itens'])) {
                        $response = ['success' => true, 'message' => 'Itens do pedido atualizados!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao atualizar itens do pedido.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Dados de edição incompletos.'];
                }
                break;
            
            case 'fechar_pedido':
                if (isset($data['pedido_id']) && isset($data['forma_pagamento_id'])) {
                    $result = $caixaManager->marcarPedidoComoEntregueERegistrarPagamento(
                        $data['pedido_id'],
                        $data['forma_pagamento_id']
                    );
                    $response = ['success' => $result['success'] ?? false, 'message' => $result['message'] ?? 'Erro desconhecido ao fechar pedido.'];
                } else {
                    $response = ['success' => false, 'message' => 'Dados incompletos para fechar pedido.'];
                }
                break;

            case 'abrir_caixa_gerente':
                if (isset($data['responsavel']) && isset($data['saldo_inicial'])) {
                    $result = $caixaManager->abrirCaixa($data['responsavel'], (float)$data['saldo_inicial']);
                    $response = ['success' => $result['success'] ?? false, 'message' => $result['message'] ?? 'Erro desconhecido ao abrir caixa.'];
                } else {
                    $response = ['success' => false, 'message' => 'Dados incompletos para abrir o caixa.'];
                }
                break;

            case 'fechar_caixa_gerente':
                if (isset($data['responsavel'])) {
                    $result = $caixaManager->fecharCaixa($data['responsavel']);
                    $response = ['success' => $result['success'] ?? false, 'message' => $result['message'] ?? 'Erro desconhecido ao fechar caixa.'];
                } else {
                    $response = ['success' => false, 'message' => 'Nome do responsável não fornecido para fechar o caixa.'];
                }
                break;
        }

        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'excluir') {
            $response = ['success' => false, 'message' => 'A exclusão direta foi desativada. Use a opção de Cancelar com motivo.'];
            echo json_encode($response);
            exit;
        }

        if ($action === 'editar') {
            $response = ['success' => false, 'message' => 'A edição direta foi desativada. Use o modal de edição completo.'];
            echo json_encode($response);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Nenhuma ação reconhecida.']);
    exit;
}

$pedidos = FuncoesGerente::buscarPedidos();
$clientes = FuncoesGerente::buscarClientes();
$produtos = FuncoesGerente::buscarProdutos();
$caixa = $caixaManager->getCaixaAtual() ?: ['status' => 'fechado', 'saldo_inicial' => 0, 'entradas' => 0, 'saidas' => 0, 'saldo_atual' => 0, 'id' => null];
$motivosCancelamento = FuncoesGerente::buscarMotivosCancelamento();
$vendasPorFormaPagamento = [];
if ($caixa['status'] === 'aberto' && $caixa['id'] !== null) {
    $vendasPorFormaPagamento = $caixaManager->getTotalVendasPorFormaPagamento($caixa['id']);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto - Gerente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
    /* CSS incorporado diretamente para garantir o carregamento */
    :root {
        --primary: #3498db;
        --primary-dark: #2980b9;
        --secondary: #2ecc71;
        --danger: #e74c3c;
        --danger-dark: #c0392b;
        --warning: #f39c12;
        --info: #1abc9c;
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

    .btn.danger {
        background-color: var(--danger);
    }

    .btn.danger:hover {
        background-color: var(--danger-dark);
        transform: translateY(-2px);
    }

    .btn.info {
        background-color: var(--info);
    }

    .btn.info:hover {
        background-color: #16a085;
        transform: translateY(-2px);
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
        max-width: 100%;
    }

    .caixa-info {
        background: var(--white);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }

    .caixa-status {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .caixa-status h2 {
        font-size: 1.4rem;
        color: var(--dark);
    }

    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.9rem;
    }

    .status-aberto {
        background-color: rgba(46, 204, 113, 0.2);
        color: var(--secondary);
    }

    .status-fechado {
        background-color: rgba(231, 76, 60, 0.2);
        color: var(--danger);
    }

    .caixa-valores {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .caixa-valor {
        text-align: center;
        min-width: 120px;
    }

    .caixa-valor span {
        display: block;
        font-size: 0.9rem;
        color: var(--gray);
        margin-bottom: 5px;
    }

    .caixa-valor p {
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--dark);
        margin: 0;
    }

    .kanban-container {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 20px;
    }

    .kanban-column {
        background: var(--white);
        border-radius: 10px;
        box-shadow: var(--shadow);
        min-width: 280px;
        width: 100%;
        max-width: 280px;
        display: flex;
        flex-direction: column;
        max-height: 80vh;
    }

    .column-header {
        padding: 15px;
        background: var(--light);
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .column-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark);
        margin: 0;
    }

    .badge-count {
        background: var(--dark);
        color: var(--white);
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        font-weight: bold;
    }

    .kanban-column .card {
        margin: 10px;
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        cursor: grab;
        transition: var(--transition);
    }

    .kanban-column .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .card-header {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-weight: 600;
        font-size: 1rem;
        color: var(--dark);
    }

    .card-actions {
        position: relative;
    }

    .card-menu-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--gray);
        padding: 5px;
        border-radius: 4px;
        transition: var(--transition);
    }

    .card-menu-btn:hover {
        background: #f5f5f5;
        color: var(--dark);
    }

    .card-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: var(--white);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 6px;
        z-index: 10;
        min-width: 180px;
        overflow: hidden;
    }

    .card-dropdown.show {
        display: block;
    }

    .dropdown-item {
        padding: 10px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
        font-size: 0.95rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background: #f9f9f9;
    }

    .dropdown-item.danger {
        color: var(--danger);
    }

    .dropdown-item.danger:hover {
        background: rgba(231, 76, 60, 0.1);
    }

    .card-body {
        padding: 15px;
        font-size: 0.95rem;
    }

    .card-body p {
        margin-bottom: 8px;
    }

    .card-body strong {
        color: var(--dark);
    }

    /* Modais */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow-y: auto;
    }

    .modal-content {
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 1.4rem;
        color: var(--dark);
        margin: 0;
    }

    .close-button {
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: var(--gray);
        transition: var(--transition);
    }

    .close-button:hover {
        color: var(--dark);
    }

    .modal-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--dark);
    }

    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .item-pedido {
        display: flex;
        gap: 10px;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 6px;
        margin-bottom: 15px;
        position: relative;
    }

    .item-pedido .form-group {
        flex: 1;
        margin-bottom: 0;
    }

    .remove-item {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        font-size: 1.1rem;
        transition: var(--transition);
    }

    .remove-item:hover {
        transform: scale(1.1);
    }

    /* Notificação */
    .notification {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 3000;
        max-width: 350px;
        transition: all 0.5s ease;
        opacity: 0;
        transform: translateY(100%);
    }

    .notification.show {
        opacity: 1;
        transform: translateY(0);
    }

    .notification.success {
        background: var(--secondary);
    }

    .notification.error {
        background: var(--danger);
    }

    .notification.info {
        background: var(--primary);
    }

    /* Responsividade */
    @media (max-width: 1200px) {
        .kanban-column {
            min-width: 260px;
        }
    }

    @media (max-width: 992px) {
        .header-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .header-buttons {
            width: 100%;
            justify-content: center;
        }

        .caixa-info {
            flex-direction: column;
        }

        .kanban-container {
            flex-wrap: nowrap;
            overflow-x: auto;
        }
    }

    @media (max-width: 768px) {
        .btn {
            font-size: 0.8rem;
            padding: 8px 12px;
        }

        .caixa-valores {
            width: 100%;
            justify-content: space-between;
        }

        .caixa-valor {
            min-width: calc(50% - 10px);
        }
    }

    @media (max-width: 576px) {
        .modal-content {
            max-width: 100%;
        }

        .item-pedido {
            flex-direction: column;
            gap: 10px;
        }

        .remove-item {
            position: static;
            align-self: flex-end;
        }
    }
</style>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Gerente</h1>
            <div class="header-buttons">
                <button class="btn" onclick="openModal('pedidoModal')">
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
                <?php if ($caixa['status'] === 'fechado'): ?>
                    <button class="btn primary" onclick="abrirCaixaComPrompt()">
                        <i class="fas fa-cash-register"></i> Abrir Caixa
                    </button>
                <?php else: ?>
                    <button class="btn danger" onclick="openFecharCaixaModal()">
                        <i class="fas fa-cash-register"></i> Fechar Caixa
                    </button>
                <?php endif; ?>
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
            <div class="caixa-valores">
                <div class="caixa-valor">
                    <span>Saldo Inicial</span>
                    <p>R$ <?= number_format($caixa['saldo_inicial'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Entradas</span>
                    <p>R$ <?= number_format($caixa['entradas'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saídas</span>
                    <p>R$ <?= number_format($caixa['saidas'] ?? 0, 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saldo Atual</span>
                    <p>R$ <?= number_format($caixa['saldo_atual'] ?? 0, 2, ',', '.') ?></p>
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
                                <div class="card-dropdown">
                                    <a href="#" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
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
                                    <a href="#" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
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
                                    <a href="#" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button type="button" class="dropdown-item" onclick="openFecharPedidoModal(<?= $pedido['id'] ?>)">
                                        <i class="fas fa-check-circle"></i> Fechar Pedido
                                    </button>
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
                                    <a href="#" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
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
                                    <a href="#" class="dropdown-item edit" onclick="editarPedido(<?= $pedido['id'] ?>); return false;">
                                        <i class="fas fa-eye"></i> Visualizar Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                            <p><strong>Motivo:</strong> <?= htmlspecialchars($pedido['motivo_cancelamento'] ?? 'N/A') ?></p>
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
                <button class="close-button" onclick="closeModal('pedidoModal')">&times;</button>
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

    <div class="modal" id="modalEditar">
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

    <div class="modal" id="fecharCaixaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Fechar Caixa</h3>
                <button class="close-button" onclick="closeModal('fecharCaixaModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Confirme o fechamento do caixa atual.</p>
                <p><strong>ID do Caixa:</strong> <span id="fecharCaixaIdDisplay"></span></p>
                <p><strong>Saldo Inicial:</strong> R$ <span id="fecharCaixaSaldoInicial"></span></p>
                <p><strong>Saldo Atual:</strong> R$ <span id="fecharCaixaSaldoAtual"></span></p>
                
                <h4>Total de Vendas por Forma de Pagamento:</h4>
                <ul id="vendasPorFormaList">
                    </ul>

                <div class="form-group">
                    <label for="fecharCaixaResponsavel">Responsável pelo Fechamento:</label>
                    <input type="text" id="fecharCaixaResponsavel" class="form-control" required>
                </div>
                <button type="button" class="btn danger" onclick="confirmFecharCaixa()">Confirmar Fechamento</button>
            </div>
        </div>
    </div>

    <div class="modal" id="fecharPedidoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Fechar Pedido #<span id="fecharPedidoIdDisplay"></span></h3>
                <button class="close-button" onclick="closeModal('fecharPedidoModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentFecharPedidoId">
                <p>Confirme o fechamento deste pedido. Ele será marcado como 'entregue'.</p>
                <div class="form-group">
                    <label for="forma_pagamento_fechar_pedido">Forma de Pagamento:</label>
                    <select id="forma_pagamento_fechar_pedido" class="form-control" required>
                        <option value="">Selecione a forma de pagamento</option>
                        <?php foreach ($caixaManager->getFormasPagamento() as $forma): ?>
                            <option value="<?= $forma['id'] ?>"><?= htmlspecialchars($forma['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn primary" onclick="confirmFecharPedido()">Confirmar Fechamento</button>
            </div>
        </div>
    </div>


    <div class="notification" id="notification"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let draggedPedidoOriginalStatus = null;
        let draggedPedidoId = null;
        const currentCaixaStatus = "<?= $caixa['status'] ?? 'fechado' ?>";
        const currentCaixaId = "<?= $caixa['id'] ?? 'null' ?>";
        const vendasPorFormaPagamentoData = <?= json_encode($vendasPorFormaPagamento) ?>;


        function toggleDropdown(event, btn) {
            event.stopPropagation();
            const dropdown = btn.nextElementSibling;
            const isOpen = dropdown.style.display === 'block';

            document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');

            dropdown.style.display = isOpen ? 'none' : 'block';
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.card-dropdown') && !event.target.closest('.card-menu-btn')) {
                document.querySelectorAll('.card-dropdown').forEach(el => el.style.display = 'none');
            }
        });

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 3000);
        }

        function drag(event) {
            draggedPedidoId = event.target.id;
            let parentColumn = event.target.closest('.kanban-column');
            if (parentColumn) {
                draggedPedidoOriginalStatus = parentColumn.id;
            } else {
                draggedPedidoOriginalStatus = null;
            }

            if (draggedPedidoOriginalStatus === 'cancelado' || draggedPedidoOriginalStatus === 'entregue') {
                event.preventDefault();
                showNotification('Pedidos "cancelados" ou "entregues" não podem ser movidos via arrasto.', 'error');
                draggedPedidoId = null;
                draggedPedidoOriginalStatus = null;
            }
        }

        function allowDrop(event) {
            event.preventDefault();
        }

        // Alterada a função openModal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex'; // Removido o '+ 'Modal''
            if (modalId === 'pedidoModal') { // Mudança para o ID completo do modal
                calcularTotal();
            }
        }

        function drop(event, targetStatus) {
            event.preventDefault();
            
            if (!draggedPedidoId || draggedPedidoOriginalStatus === null) return;
            
            const pedidoId = draggedPedidoId.split('-')[1];

            if (targetStatus === 'pronto' && currentCaixaStatus !== 'aberto') {
                showNotification('O caixa precisa estar aberto para finalizar pedidos.', 'error');
                draggedPedidoId = null;
                draggedPedidoOriginalStatus = null;
                return;
            }

            let allowTransition = false;

            if (targetStatus === 'cancelado') {
                allowTransition = true;
            } else if (draggedPedidoOriginalStatus === 'pendente' && targetStatus === 'preparo') {
                allowTransition = true;
            } else if (draggedPedidoOriginalStatus === 'preparo' && targetStatus === 'pronto') {
                allowTransition = true;
            }
            else {
                showNotification(`Transição de "${draggedPedidoOriginalStatus}" para "${targetStatus}" não permitida via arrasto.`, 'error');
                draggedPedidoId = null;
                draggedPedidoOriginalStatus = null;
                return;
            }

            if (allowTransition) {
                if (targetStatus === 'cancelado') {
                    openCancelModal(pedidoId);
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
                        draggedPedidoId = null;
                        draggedPedidoOriginalStatus = null;
                    });
                }
            }
            draggedPedidoId = null;
            draggedPedidoOriginalStatus = null;
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
                let saldoInicial = prompt("Digite o saldo inicial para abrir o caixa (opcional, padrão 0.00):");
                saldoInicial = parseFloat(saldoInicial) || 0.00;

                fetch('index_gerente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        form: 'abrir_caixa_gerente',
                        responsavel: responsavel,
                        saldo_inicial: saldoInicial
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

        function openFecharCaixaModal() {
            document.getElementById('fecharCaixaIdDisplay').textContent = "<?= $caixa['id'] ?? 'N/A' ?>";
            document.getElementById('fecharCaixaSaldoInicial').textContent = "R$ <?= number_format($caixa['saldo_inicial'] ?? 0, 2, ',', '.') ?>";
            document.getElementById('fecharCaixaSaldoAtual').textContent = "R$ <?= number_format($caixa['saldo_atual'] ?? 0, 2, ',', '.') ?>";

            const vendasList = document.getElementById('vendasPorFormaList');
            vendasList.innerHTML = ''; 

            if (Object.keys(vendasPorFormaPagamentoData).length > 0) {
                for (const forma in vendasList.vendasPorFormaPagamentoData) {
                    const li = document.createElement('li');
                    li.textContent = `${forma}: R$ ${parseFloat(vendasPorFormaPagamentoData[forma]).toFixed(2).replace('.', ',')}`;
                    vendasList.appendChild(li);
                }
            } else {
                const li = document.createElement('li');
                li.textContent = 'Nenhuma venda registrada neste caixa.';
                vendasList.appendChild(li);
            }

            openModal('fecharCaixaModal'); // Corrigido para passar o ID completo do modal
        }

        function confirmFecharCaixa() {
            const responsavel = document.getElementById('fecharCaixaResponsavel').value;
            if (!responsavel) {
                showNotification('Por favor, digite o nome do responsável.', 'error');
                return;
            }

            fetch('index_gerente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    form: 'fechar_caixa_gerente',
                    responsavel: responsavel
                })
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('fecharCaixaModal');
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                console.error('Erro ao fechar caixa:', error);
                showNotification('Erro de comunicação ao fechar caixa.', 'error');
            });
        }

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

                if (produtoId && quantidade && parseInt(quantidade) > 0) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: quantidade
                    });
                }
            });

            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item válido ao pedido.', 'error');
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
                    closeModal('pedidoModal'); // Corrigido para passar o ID completo do modal
                    setTimeout(() => location.reload(), 1500);
                }
            });
        }

        let pedidoIdToCancel = null;

        function openCancelModal(pedidoId) {
            pedidoIdToCancel = pedidoId;
            document.getElementById('cancelPedidoId').textContent = pedidoId;
            document.getElementById('currentCancelPedidoId').value = pedidoId;
            openModal('cancelModal'); // Corrigido para passar o ID completo do modal
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
                console.error('Erro no fetch (cancelar):', error);
                showNotification('Erro de comunicação ao cancelar pedido.', 'error');
            });
        }

        function editarPedido(id) {
            fetch('buscar_pedido_detalhes.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_pedido_id').value = id;
                        document.getElementById('edit_pedido_id_display').textContent = id;
                        const container = document.getElementById('edit_itens_container');
                        container.innerHTML = '';

                        if (data.itens.length === 0) {
                            showNotification('Nenhum item encontrado para este pedido. Adicione itens.', 'info');
                            adicionarItemEdicao(); // Adiciona um item vazio para começar
                        } else {
                            data.itens.forEach((item, index) => {
                                const div = document.createElement('div');
                                div.className = 'item-pedido';
                                let selectOptions = `<option value="">Selecione um produto</option>`;
                                data.produtos.forEach(product => {
                                    selectOptions += `<option value="${product.id}" data-preco="${product.preco}" ${product.id == item.produto_id ? 'selected' : ''}>${htmlspecialchars(product.nome)} - R$ ${parseFloat(product.preco).toFixed(2).replace('.', ',')}</option>`;
                                });

                                div.innerHTML = `
                                    <div class="form-group">
                                        <label>Produto</label>
                                        <select name="produto_id_edit[]" class="form-control edit-produto-select" required data-item-id="${item.item_id}">
                                            ${selectOptions}
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Quantidade</label>
                                        <input type="number" name="quantidade[]" class="form-control edit-quantidade-input" min="1" value="${item.quantidade}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Subtotal</label>
                                        <span class="edit-subtotal">R$ ${ (item.quantidade * parseFloat(data.produtos.find(p => p.id == item.produto_id)?.preco || 0)).toFixed(2).replace('.', ',') }</span>
                                    </div>
                                    <button type="button" class="remove-item" onclick="removerItemEdicao(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                                container.appendChild(div);

                                div.querySelector('.edit-produto-select').addEventListener('change', calcularSubtotalEdicao);
                                div.querySelector('.edit-quantidade-input').addEventListener('input', calcularSubtotalEdicao);
                            });
                        }

                        const addButton = document.createElement('button');
                        addButton.type = 'button';
                        addButton.className = 'btn primary';
                        addButton.innerHTML = '<i class="fas fa-plus"></i> Adicionar Item';
                        addButton.onclick = adicionarItemEdicao;
                        container.appendChild(addButton);

                        const totalDiv = document.createElement('div');
                        totalDiv.style.fontWeight = 'bold';
                        totalDiv.style.fontSize = '1.2em';
                        totalDiv.style.marginTop = '15px';
                        totalDiv.innerHTML = '<label>Total do Pedido:</label><span id="totalPedidoEdicao">R$ 0,00</span>';
                        container.appendChild(totalDiv);
                        calcularTotalEdicao();

                        openModal('modalEditar'); // Corrigido para passar o ID completo do modal
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
            closeModal('modalEditar');
        }

        function salvarEdicaoPedido() {
            const pedido_id = document.getElementById('edit_pedido_id').value;
            const itens = [];

            document.querySelectorAll('#edit_itens_container .item-pedido').forEach(itemDiv => {
                const produtoId = itemDiv.querySelector('.edit-produto-select').value;
                const quantidade = itemDiv.querySelector('.edit-quantidade-input').value;

                if (produtoId && quantidade && parseInt(quantidade) > 0) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: parseInt(quantidade)
                    });
                }
            });

            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item válido ao pedido.', 'error');
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
            novoItem.className = 'item-pedido';
            let selectOptions = `<option value="">Selecione um produto</option>`;
            <?php foreach ($produtos as $produto): ?>
                selectOptions += `<option value="<?= $produto['id'] ?>" data-preco="<?= $produto['preco'] ?>"><?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?></option>`;
            <?php endforeach; ?>

            novoItem.innerHTML = `
                <div class="form-group">
                    <label>Produto</label>
                    <select name="produto_id_edit[]" class="form-control edit-produto-select" required>
                        ${selectOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control edit-quantidade-input" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Subtotal</label>
                    <span class="edit-subtotal">R$ 0,00</span>
                </div>
                <button type="button" class="remove-item" onclick="removerItemEdicao(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            const addButton = container.querySelector('.btn.primary');
            const totalDiv = container.querySelector('#totalPedidoEdicao')?.parentNode;
            if (totalDiv) {
                container.insertBefore(novoItem, totalDiv);
            } else {
                container.appendChild(novoItem);
            }
            
            novoItem.querySelector('.edit-produto-select').addEventListener('change', calcularSubtotalEdicao);
            novoItem.querySelector('.edit-quantidade-input').addEventListener('input', calcularSubtotalEdicao);
            calcularTotalEdicao();
        }

        function removerItemEdicao(btn) {
            const item = btn.closest('.item-pedido');
            if (document.querySelectorAll('#edit_itens_container .item-pedido').length > 1) {
                item.remove();
                calcularTotalEdicao();
            } else {
                showNotification('O pedido deve ter pelo menos um item.', 'error');
            }
        }

        function calcularSubtotalEdicao(event) {
            const item = event.target.closest('.item-pedido');
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
            document.querySelectorAll('#edit_itens_container .item-pedido').forEach(item => {
                const subtotalText = item.querySelector('.edit-subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                total += subtotal;
            });
            document.getElementById('totalPedidoEdicao').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        let pedidoIdToFechar = null;

        function openFecharPedidoModal(pedidoId) {
            pedidoIdToFechar = pedidoId;
            document.getElementById('fecharPedidoIdDisplay').textContent = pedidoId;
            document.getElementById('currentFecharPedidoId').value = pedidoId;
            openModal('fecharPedidoModal'); // Corrigido para passar o ID completo do modal
        }

        function confirmFecharPedido() {
            const formaPagamentoId = document.getElementById('forma_pagamento_fechar_pedido').value;
            if (!formaPagamentoId) {
                showNotification('Por favor, selecione uma forma de pagamento.', 'error');
                return;
            }

            fetch('index_gerente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    form: 'fechar_pedido',
                    pedido_id: pedidoIdToFechar,
                    forma_pagamento_id: parseInt(formaPagamentoId)
                })
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('fecharPedidoModal');
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                console.error('Erro ao fechar pedido:', error);
                showNotification('Erro de comunicação ao fechar pedido.', 'error');
            });
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            // Inicialização de event listeners para elementos que podem estar presentes
            document.querySelector('.produto-select')?.addEventListener('change', calcularSubtotal);
            document.querySelector('.quantidade-input')?.addEventListener('input', calcularSubtotal);
            
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>