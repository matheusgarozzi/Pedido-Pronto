<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "PedidoProntoDB";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Definir charset para utf8 (opcional)
$conn->set_charset("utf8mb4");
?>