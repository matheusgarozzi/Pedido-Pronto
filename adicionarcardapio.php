<?php

$servername = "localhost"; // ou seu IP de servidor
$username = "root";        // usuário do MySQL
$password = "";            // senha do MySQL
$database = "PedidoProntoDB";


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $ativo = isset($_POST['ativo']) ? 1 : 0; // Checkbox para ativo ou não

    $sql = "INSERT INTO Produtos (nome, descricao, preco, ativo) 
            VALUES ('$nome', '$descricao', '$preco', '$ativo')";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Produto adicionado com sucesso!</p>";
    } else {
        echo "<p style='color:red;'>Erro: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Produto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        form {
            max-width: 500px;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
        }
    </style>
</head>
<body>

    <h1>Adicionar Produto</h1>

    <form method="POST" action="">
        <label for="nome">Nome do Produto:</label>
        <input type="text" name="nome" id="nome" required>

        <label for="descricao">Descrição:</label>
        <textarea name="descricao" id="descricao" rows="4"></textarea>

        <label for="preco">Preço:</label>
        <input type="number" name="preco" id="preco" step="0.01" required>

        <label for="ativo">Ativo:</label>
        <input type="checkbox" name="ativo" id="ativo" checked>

        <button type="submit">Salvar Produto</button>
    </form>

</body>
</html>
