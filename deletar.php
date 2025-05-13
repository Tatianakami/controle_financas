<?php
session_start();
include 'conexao.php';

if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: index.php?erro=token_invalido");
        exit();
    }

    // Verifica se o ID foi passado e é válido
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        header("Location: index.php?erro=id_invalido");
        exit();
    }

    $id = intval($_POST['id']);

    // Verifica se o registro existe antes de deletar (sem usuario_id)
    $sql_check = "SELECT id FROM transacoes WHERE id = ?";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if ($resultado->num_rows === 0) {
        header("Location: index.php?erro=registro_nao_encontrado");
        exit();
    }

    // Usa prepared statement para evitar SQL Injection
    $sql = "DELETE FROM transacoes WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: index.php?sucesso=excluido");
        } else {
            header("Location: index.php?erro=nada_excluido");
        }
    } else {
        header("Location: index.php?erro=erro_exclusao");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>