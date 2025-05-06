<?php
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Pedidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .header-content {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn:hover {
            background-color: #138496;
            transform: scale(1.05);
        }
        .btn.logout {
            background-color: #dc3545;
        }
        .btn.logout:hover {
            background-color: #c82333;
        }
        main {
            padding: 20px;
        }
        .cardapio {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .produto-card {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            width: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .produto-card img {
            width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .produto-card h3 {
            margin: 10px 0 5px;
        }
        .produto-card p {
            margin: 5px 0;
        }
        .preco {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="index.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
        <div class="header-content">
            <button class="btn" onclick="location.href='mostrarcardapio.php'">Cardápio</button>
            <button class="btn" onclick="location.href='historicopedidos.php'">Histórico de Pedidos</button>
            <button class="btn" onclick="location.href='clientes.php'">Clientes</button>
            <button class="btn" onclick="location.href='adicionarcliente.php'">Adicionar Cliente</button>
            <button class="btn" onclick="location.href='adicionarcardapio.php'">Adicionar Cardápio</button>
            <button class="btn logout" onclick="location.href='logout.php'">Logout</button>
        </div>
    </header>

    <main>
        <h2 style="text-align: center;">Nosso Cardápio</h2>

        <div class="cardapio">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imagem = htmlspecialchars($row['imagem']) ?: 'https://via.placeholder.com/250x150';
                    $nome = htmlspecialchars($row['nome']);
                    $descricao = htmlspecialchars($row['descricao']);
                    $preco = number_format($row['preco'], 2, ',', '.');

                    echo "
                    <div class='produto-card'>
                        <img src='$imagem' alt='Imagem do produto'>
                        <h3>$nome</h3>
                        <p>$descricao</p>
                        <p class='preco'>R$ $preco</p>
                    </div>";
                }
            } else {
                echo "<p style='text-align:center;'>Nenhum produto disponível no momento.</p>";
            }

            $conn->close();
            ?>
        </div>
    </main>
</body>
</html>
