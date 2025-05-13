<?php
include 'conexao.php';


if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}


$erros = [];

// Processa o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza e valida os dados
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? ''));
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
    $tipo = in_array($_POST['tipo'] ?? '', ['receita', 'despesa']) ? $_POST['tipo'] : 'despesa';
    $data = $_POST['data_transacao'] ?? date('Y-m-d');

    // Validações
    if (empty($descricao)) {
        $erros[] = "A descrição é obrigatória";
    }

    if (!is_numeric($valor) || $valor <= 0) {
        $erros[] = "O valor deve ser um número positivo";
    }

    if (!strtotime($data)) {
        $erros[] = "Data inválida";
        $data = date('Y-m-d');
    } else {
        $data = date('Y-m-d', strtotime($data));
    }

    // Se não houver erros, insere no banco
    if (empty($erros)) {
        // Usa prepared statement para evitar SQL injection
        $sql = "INSERT INTO transacoes (descricao, valor, tipo, data_transacao) VALUES (?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sdss", $descricao, $valor, $tipo, $data);

        if ($stmt->execute()) {
            header("Location: index.php?sucesso=adicionado");
            exit();
        } else {
            $erros[] = "Erro ao salvar: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Transação</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Nova Transação</h1>
        
        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo $erro; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required 
                       value="<?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="valor">Valor (R$):</label>
                <input type="text" id="valor" name="valor" required 
                       value="<?php echo htmlspecialchars($_POST['valor'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="receita" <?php echo ($_POST['tipo'] ?? '') == 'receita' ? 'selected' : ''; ?>>Receita</option>
                    <option value="despesa" <?php echo ($_POST['tipo'] ?? '') == 'despesa' ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_transacao">Data:</label>
                <input type="date" id="data_transacao" name="data_transacao" required 
                       value="<?php echo htmlspecialchars($_POST['data_transacao'] ?? date('Y-m-d')); ?>">
            </div>
            
            <button type="submit" class="btn-save">Salvar</button>
            <a href="index.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>
</html>