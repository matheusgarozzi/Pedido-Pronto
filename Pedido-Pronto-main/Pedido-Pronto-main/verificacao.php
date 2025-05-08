<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["senha"];
}

if (empty($email) || empty($senha)) {
    echo "Preencha todos os campos!";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hash_senha = $row["Senha"];

    if (password_verify($senha, $hash_senha)) {
        session_start();
        $_SESSION['usuario_logado'] = true;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_nome'] = $row["Nome"];

        if (strpos($email, '@administracao.com') !== false) {
            header('Location: menuadmin.php');
        } elseif (strpos($email, '@gerente.com') !== false) {
            header('Location: menugerente.php');
        } elseif (strpos($email, '@atendente.com') !== false) {
            header('Location: menuatendente.php');
        } else {
            header('Location: menucliente.php');
        }
        exit();
    } else {
        echo "Senha incorreta!";
    }
} else {
    echo "Usuário não encontrado!";
}

$stmt->close();
$conn->close();
?>
