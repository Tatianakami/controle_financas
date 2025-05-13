<?php
include 'conexao.php';

// Verifica se a conexão foi estabelecida
if (!$conexao) {
    die("Erro na conexão com o banco de dados: " . mysqli_connect_error());
}

// Verifica se o ID foi passado e é válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// Busca os dados atuais da transação
$sql_select = "SELECT * FROM transacoes WHERE id = ?";
$stmt_select = $conexao->prepare($sql_select);
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$resultado = $stmt_select->get_result();

if ($resultado->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$transacao = $resultado->fetch_assoc();

// Processa o formulário de edição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Valida e sanitiza os dados
    $descricao = trim($_POST['descricao']);
    $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor'])));
    $tipo = in_array($_POST['tipo'], ['receita', 'despesa']) ? $_POST['tipo'] : 'despesa';
    $data = $_POST['data_transacao'];

    // Validações básicas
    $erros = [];
    
    if (empty($descricao)) {
        $erros[] = "A descrição é obrigatória";
    }
    
    if ($valor <= 0) {
        $erros[] = "O valor deve ser positivo";
    }
    
    if (!strtotime($data)) {
        $erros[] = "Data inválida";
    } else {
        $data = date('Y-m-d', strtotime($data));
    }

    // Se não houver erros, atualiza no banco
    if (empty($erros)) {
        $sql_update = "UPDATE transacoes SET descricao = ?, valor = ?, tipo = ?, data_transacao = ? WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bind_param("sdssi", $descricao, $valor, $tipo, $data, $id);
        
        if ($stmt_update->execute()) {
            header("Location: index.php?sucesso=1");
            exit();
        } else {
            $erros[] = "Erro ao atualizar: " . $conexao->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Transação</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Transação</h1>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required 
                       value="<?php echo htmlspecialchars($transacao['descricao']); ?>">
            </div>
            
            <div class="form-group">
                <label for="valor">Valor (R$):</label>
                <input type="text" id="valor" name="valor" required 
                       value="<?php echo number_format($transacao['valor'], 2, ',', '.'); ?>">
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="receita" <?php echo $transacao['tipo'] == 'receita' ? 'selected' : ''; ?>>Receita</option>
                    <option value="despesa" <?php echo $transacao['tipo'] == 'despesa' ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_transacao">Data:</label>
                <input type="date" id="data_transacao" name="data_transacao" required 
                       value="<?php echo date('Y-m-d', strtotime($transacao['data_transacao'])); ?>">
            </div>
            
            <button type="submit" class="btn-save">Salvar Alterações</button>
            <a href="index.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>
</html>