<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT id FROM Usuarios WHERE id = ? AND ativo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['usuario']['id']);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();
$conn->close();
?>