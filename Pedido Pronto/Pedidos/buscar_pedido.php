<?php
require_once 'funcoes.php';
$id = $_GET['id'];

$conn = conectar();
$stmt = $conn->prepare("SELECT produto_id, quantidade FROM ItensPedido WHERE pedido_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$itens = [];
while ($row = $result->fetch_assoc()) {
    $itens[] = $row;
}
$stmt->close();

$produtos = buscarProdutos(); // já existente

echo json_encode([
    'itens' => $itens,
    'produtos' => $produtos
]);
