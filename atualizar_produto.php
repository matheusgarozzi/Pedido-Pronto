<?php
require_once 'conexao.php'; // Inclua sua conexão

function conectar() {
    $host = 'localhost';
    $usuario = 'root';
    $senha = ''; 
    $banco = 'PedidoProntoDB';

    $conn = new mysqli($host, $usuario, $senha, $banco);

    if ($conn->connect_error) {
        die('Erro de conexão: ' . $conn->connect_error);
    }

    return $conn;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    $preco = floatval($_POST['preco']);
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    // Validação simples
    if ($id <= 0 || empty($nome) || $preco <= 0) {
        die("Dados inválidos.");
    }

    $conn = conectar();

    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, preco = ?, descricao = ? WHERE id = ?");
    $stmt->bind_param('sdsi', $nome, $preco, $descricao, $id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redireciona para a página do produto ou listagem com sucesso
        header('Location: produtos.php?msg=Produto atualizado com sucesso');
        exit;
    } else {
        echo "Erro ao atualizar o produto: " . $conn->error;
        $stmt->close();
        $conn->close();
    }
} else {
    echo "Método inválido.";
}
?>