<?php
// verificalog_compat.php (versão de compatibilidade)
require_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$conn = Database::getInstance()->getConnection();
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
?>