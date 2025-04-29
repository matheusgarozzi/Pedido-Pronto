<?php
// Conexão com o banco de dados
$servername = "localhost"; 
$username = "root";        
$password = "";            
$database = "PedidoProntoDB";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $database);

// Checar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Buscar todos os clientes
$sql = "SELECT id, nome, telefone, endereco, data_cadastro FROM Clientes";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
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
