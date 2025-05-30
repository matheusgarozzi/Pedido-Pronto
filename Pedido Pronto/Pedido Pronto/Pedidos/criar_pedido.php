<?php
// Forçar cabeçalho JSON antes de qualquer saída
header('Content-Type: application/json');

require_once '../Geral/verificalog.php';
require_once '../Geral/conexao.php';
require_once '../Gerente/funcoesgerente.php';

// Obter dados JSON da requisição
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$response = ['success' => false, 'message' => ''];

try {
    // Verificar se todos os dados necessários estão presentes
    if (!isset($data['cliente_id']) || !isset($data['itens']) || empty($data['itens'])) {
        throw new Exception("Dados do pedido incompletos");
    }

    $conn = Database::getInstance()->getConnection();
    $conn->begin_transaction();

    // 1. Inserir o pedido principal
    $stmt = $conn->prepare("INSERT INTO pedidos (cliente_id, status, data_pedido) VALUES (?, 'pendente', NOW())");
    $stmt->bind_param("i", $data['cliente_id']);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar pedido: " . $stmt->error);
    }
    $pedido_id = $conn->insert_id;

    // 2. Inserir os itens do pedido
    foreach ($data['itens'] as $item) {
        if (!isset($item['produto_id']) {
            throw new Exception("ID do produto não especificado");
        }

        // Obter preço atual do produto
        $stmt_prod = $conn->prepare("SELECT preco FROM produtos WHERE id = ?");
        $stmt_prod->bind_param("i", $item['produto_id']);
        if (!$stmt_prod->execute()) {
            throw new Exception("Erro ao buscar produto: " . $stmt_prod->error);
        }
        
        $result = $stmt_prod->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Produto não encontrado: ID " . $item['produto_id']);
        }
        
        $produto = $result->fetch_assoc();
        $preco = $produto['preco'];

        // Inserir item do pedido
        $stmt_item = $conn->prepare("INSERT INTO itenspedido 
                                   (pedido_id, produto_id, quantidade, preco_unitario) 
                                   VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $pedido_id, $item['produto_id'], $item['quantidade'], $preco);
        if (!$stmt_item->execute()) {
            throw new Exception("Erro ao adicionar item: " . $stmt_item->error);
        }
    }

    $conn->commit();
    $response = [
        'success' => true,
        'message' => 'Pedido criado com sucesso!',
        'pedido_id' => $pedido_id
    ];
} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
    error_log("Erro ao criar pedido: " . $e->getMessage());
}

// Garantir que nenhuma saída foi enviada antes
if (ob_get_length()) ob_clean();
echo json_encode($response);
exit;