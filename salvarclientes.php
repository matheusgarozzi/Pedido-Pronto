require_once 'funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';

    if ($id) {
        // Atualizar
        if (atualizarCliente($id, $nome, $telefone, $endereco)) {
            header("Location: clientes.php?status=atualizado");
        } else {
            echo "Erro ao atualizar.";
        }
    } else {
        // Inserir novo
        $conn = conectar();
        $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, endereco, data_cadastro) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$nome, $telefone, $endereco])) {
            header("Location: clientes.php?status=criado");
        } else {
            echo "Erro ao criar cliente.";
        }
    }
}