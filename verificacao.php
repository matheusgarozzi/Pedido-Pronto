<?php
    include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["senha"];
}
// Check for empty fields
if (empty($email) || empty($senha)) {
  echo "Preencha todos os campos!";
  exit();
}

// Check if user exists with prepared statement (prevents SQL injection)
$stmt = $conn->prepare("SELECT * FROM usuário WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Usuário encontrado, verifica a senha
    $row = $result->fetch_assoc();
    $hash_senha = $row["senha"];

    if (password_verify($senha, $hash_senha)) {
        // Login bem-sucedido
        session_start();
        $_SESSION['usuario_logado'] = true;

        // Verifica o domínio do email para redirecionamento personalizado
        if (strpos($email, '@ecomodal.com.br') !== false) {
            // Redireciona para o menu administrativo para usuários @ecomodal.com.br
            header('Location: menuadmin.html');
        } else {
            // Redireciona para o menu padrão para usuários comuns
            header('Location: menuuser.html');
        }

        exit(); // Garante que o código abaixo não será executado após o redirecionamento
    } else {
        // Senha incorreta
        echo "Senha incorreta!";
    }
} else {
    // Usuário não encontrado
    echo "Usuário não encontrado!";
}
$stmt->close();
$conn->close();
?>
