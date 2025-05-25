<?php
require_once '../Geral/conexao.php';
require_once '../Geral/verificalog.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pedido_id']) || !isset($data['cliente_id']) || !isset($data['itens'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }
    
    $pedidoId = (int)$data['pedido_id'];
    $clienteId = (int)$data['cliente_id'];
    $itens = $data['itens'];
    
    try {
        $conn->begin_transaction();
        
        // Atualiza o cliente do pedido
        $sql = "UPDATE pedidos SET cliente_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $clienteId, $pedidoId);
        $stmt->execute();
        
        // Remove itens que não estão mais no pedido
        $sql = "DELETE FROM pedido_itens WHERE pedido_id = ?";
        if (!empty($itens)) {
            $idsManter = array_filter(array_column($itens, 'id'));
            if (!empty($idsManter)) {
                $placeholders = implode(',', array_fill(0, count($idsManter), '?'));
                $sql .= " AND id NOT IN ($placeholders)";
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($idsManter)) {
            $types = str_repeat('i', count($idsManter));
            $stmt->bind_param("i" . $types, $pedidoId, ...$idsManter);
        } else {
            $stmt->bind_param("i", $pedidoId);
        }
        $stmt->execute();
        
        // Adiciona/atualiza itens
        foreach ($itens as $item) {
            if (empty($item['id'])) {
                // Novo item
                $sql = "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $pedidoId, $item['produto_id'], $item['quantidade']);
            } else {
                // Item existente
                $sql = "UPDATE pedido_itens SET produto_id = ?, quantidade = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $item['produto_id'], $item['quantidade'], $item['id']);
            }
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar pedido: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}