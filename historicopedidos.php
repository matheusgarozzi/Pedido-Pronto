<?php

$servername = "localhost"; 
$username = "root";        
$password = "";            
$database = "PedidoProntoDB";


$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$sql = "SELECT Pedidos.id, Clientes.nome AS cliente_nome, Pedidos.data_pedido, Pedidos.status, Pedidos.observacoes
        FROM Pedidos
        JOIN Clientes ON Pedidos.cliente_id = Clientes.id";
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
            margin: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
    </style>
</head>
<body>

    <h1>Pedidos Registrados</h1>

    <?php
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cliente</th><th>Data do Pedido</th><th>Status</th><th>Observações</th></tr>";

        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["cliente_nome"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["data_pedido"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["observacoes"]) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>Nenhum pedido encontrado.</p>";
    }

    $conn->close();
    ?>

</body>
</html>
