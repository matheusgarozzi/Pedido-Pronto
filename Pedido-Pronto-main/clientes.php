<?php

$servername = "localhost"; 
$username = "root";        
$password = "";            
$database = "PedidoProntoDB";


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}


$sql = "SELECT id, nome, telefone, endereco, data_cadastro FROM Clientes";
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        footer {
            text-align: center;
            padding: 10px;
            margin-top: 30px;
            background-color: #f1f1f1;
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
    <h1>Clientes Registrados</h1>

    <?php
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Telefone</th><th>Endereço</th><th>Data de Cadastro</th></tr>";

        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["nome"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["telefone"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["endereco"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["data_cadastro"]) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>Nenhum cliente encontrado.</p>";
    }

    $conn->close();
    ?>

</body>
</html>
