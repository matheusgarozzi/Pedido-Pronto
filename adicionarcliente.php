<?php
// Conexão com o banco de dados
$servername = "localhost"; // ou seu IP de servidor
$username = "root";        // usuário do MySQL
$password = "";            // senha do MySQL
$database = "PedidoProntoDB";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $database);

// Checar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    $sql = "INSERT INTO Clientes (nome, telefone, endereco) 
            VALUES ('$nome', '$telefone', '$endereco')";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Cliente adicionado com sucesso!</p>";
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
    <title>Adicionar Cliente</title>
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
        input[type="tel"],
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

    <h1>Adicionar Cliente</h1>

    <form method="POST" action="">
        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="nome" required>

        <label for="telefone">Telefone:</label>
        <input type="tel" name="telefone" id="telefone">

        <label for="endereco">Endereço:</label>
        <textarea name="endereco" id="endereco" rows="4"></textarea>

        <button type="submit">Salvar Cliente</button>
    </form>

</body>
</html>
