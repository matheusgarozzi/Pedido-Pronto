<?php

$servername = "localhost"; // ou seu IP de servidor
$username = "root";        // usuário do MySQL
$password = "";            // senha do MySQL
$database = "PedidoProntoDB";


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}


$sql = "SELECT id, nome, descricao, preco, ativo FROM Produtos WHERE ativo = 1";
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #e6f0ff;
            color: #003366;
        }
        h1 {
            text-align: center;
            padding: 30px 0;
            background-color: #0056b3;
            color: #ffffff;
            margin: 0 0 40px 0;
            font-size: 36px;
        }
        .cardapio {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .produto-card {
            background-color: #ffffff;
            border: 2px solid #99c2ff;
            border-radius: 12px;
            width: 260px;
            box-shadow: 0 4px 8px rgba(0, 86, 179, 0.1);
            text-align: center;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .produto-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 16px rgba(0, 86, 179, 0.3);
        }
        .produto-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .produto-card h3 {
            font-size: 20px;
            margin: 15px 10px 5px 10px;
            color: #003366;
        }
        .produto-card p {
            font-size: 14px;
            margin: 0 10px 15px 10px;
            color: #336699;
        }
        .produto-card .preco {
            font-size: 18px;
            color: #0056b3;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .produto-card button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 12px 0;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .produto-card button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>Nosso Cardápio</h1>

    <div class="cardapio">
        <?php
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "
                <div class='produto-card'>
                    <img src='https://via.placeholder.com/250x150' alt='Imagem do produto'>
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
