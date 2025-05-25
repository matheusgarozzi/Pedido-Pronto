<?php
require_once '../Geral/conexao.php';
require_once '../Geral/verificalog.php';

// Habilita o CORS (para desenvolvimento)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Arquivo de log para depuração
file_put_contents('pedido_debug.log', "\n" . date('Y-m-d H:i:s') . " - Requisição recebida\n", FILE_APPEND);

try {
    // Recebe os dados JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Log dos dados recebidos
    file_put_contents('pedido_debug.log', "Dados recebidos: " . print_r($data, true) . "\n", FILE_APPEND);

    if (empty($data['cliente_id']) || empty($data['itens'])) {
        throw new Exception('Dados incompletos para criar pedido');
    }

    $conn->begin_transaction();

    // 1. Cria o pedido
    $sql = "INSERT INTO pedidos (cliente_id, status, data_pedido) 
            VALUES (?, 'pendente', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data['cliente_id']);
    $stmt->execute();
    $pedidoId = $conn->insert_id;

    // 2. Adiciona itens
    foreach ($data['itens'] as $item) {
        $sql = "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade) 
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $pedidoId, $item['produto_id'], $item['quantidade']);
        $stmt->execute();
    }

    // 3. Atualiza o total do pedido
    $sql = "UPDATE pedidos SET total = (
                SELECT SUM(pi.quantidade * p.preco) 
                FROM pedido_itens pi
                JOIN produtos p ON pi.produto_id = p.id
                WHERE pi.pedido_id = ?
            ) WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pedidoId, $pedidoId);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido criado com sucesso',
        'pedido_id' => $pedidoId
    ]);

} catch (Exception $e) {
    $conn->rollback();
    file_put_contents('pedido_debug.log', "Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar pedido: ' . $e->getMessage()
    ]);
}