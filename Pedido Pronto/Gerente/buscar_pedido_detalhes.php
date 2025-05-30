<?php
// buscar_pedido_detalhes.php

require_once '../conexao.php';
require_once '../Geral/funcoes.php'; // Inclui FuncoesGerente

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'itens' => [], 'produtos' => []];

$pedidoId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pedidoId > 0) {
    $mysqliConnection = getConnection();

    // Buscar itens do pedido
    $stmt = $mysqliConnection->prepare(
        "SELECT ip.id AS item_id, ip.produto_id, ip.quantidade, ip.preco_unitario, p.nome AS produto_nome
         FROM itens_pedido ip
         JOIN produtos p ON ip.produto_id = p.id
         WHERE ip.pedido_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('i', $pedidoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $itens = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Buscar todos os produtos (para preencher o select de edição) - Chamada de método estático
        $produtos = FuncoesGerente::buscarProdutos();

        $response['success'] = true;
        $response['itens'] = $itens;
        $response['produtos'] = $produtos;
    } else {
        $response['message'] = 'Erro ao preparar consulta de itens do pedido: ' . $mysqliConnection->error;
    }
} else {
    $response['message'] = 'ID do pedido inválido.';
}

echo json_encode($response);
?>