<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$database = "PedidoProntoDB";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$sql = "SELECT id, nome, descricao, preco, imagem FROM Produtos WHERE ativo = 1";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cardápio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
            margin-bottom: 40px;
        }
        .cardapio {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .produto-card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 250px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 15px;
        }
        .produto-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .produto-card h3 {
            color: #333;
            font-size: 18px;
            margin: 10px 0;
        }
        .produto-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .produto-card .preco {
            font-size: 16px;
            color: green;
            font-weight: bold;
        }
        .produto-card button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        .produto-card button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<h1>Nosso Cardápio</h1>

<div class="cardapio">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $imagem = $row['imagem'] ? $row['imagem'] : 'https://via.placeholder.com/250x150';
            echo "
            <div class='produto-card'>
                <img src='$imagem' alt='Imagem do produto'>
                <h3>{$row['nome']}</h3>
                <p>{$row['descricao']}</p>
                <p class='preco'>R$ " . number_format($row['preco'], 2, ',', '.') . "</p>
            </div>";
        }
    } else {
        echo "<p style='text-align:center;'>Nenhum produto disponível no momento.</p>";
    }
    ?>
</div>

</body>
</html>
