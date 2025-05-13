<?php
session_start();
include 'conexao.php';

// Verifica se a conexão foi estabelecida
if (!$conexao) {
    die("Erro na conexão com o banco de dados: " . mysqli_connect_error());
}

// Gera token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Tratamento das mensagens de feedback
$mensagem = '';
$classe_mensagem = '';

if (isset($_GET['sucesso'])) {  
    switch ($_GET['sucesso']) {
        case 'excluido':
            $mensagem = 'Transação excluída com sucesso!';
            $classe_mensagem = 'alert-success';
            break;
        case 'adicionado':
            $mensagem = 'Transação adicionada com sucesso!';
            $classe_mensagem = 'alert-success';
            break;
        case 'editado':
            $mensagem = 'Transação atualizada com sucesso!';
            $classe_mensagem = 'alert-success';
            break;
    }
} elseif (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 'id_invalido':
            $mensagem = 'ID inválido para operação!';
            break;
        case 'registro_nao_encontrado':
            $mensagem = 'Registro não encontrado!';
            break;
        case 'nada_excluido':
            $mensagem = 'Nenhum registro foi excluído!';
            break;
        case 'erro_exclusao':
            $mensagem = 'Erro ao tentar excluir o registro!';
            break;
        case 'token_invalido':
            $mensagem = 'Token de segurança inválido!';
            break;
        default:
            $mensagem = 'Ocorreu um erro desconhecido!';
    }
    $classe_mensagem = 'alert-danger';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Finanças</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <body>
    <div class="container">
        <div class="header">
            <h1>Controle Financeiro Pessoal</h1>
            <p>Gerencie suas receitas e despesas de forma simples</p>
        </div>
        
        <a href="adicionar.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nova Transação
        </a>
        <?php
        // Prepara a consulta para evitar SQL Injection
        $sql = "SELECT * FROM transacoes ORDER BY data_transacao DESC";
        $resultado = $conexao->query($sql);
        
        if (!$resultado) {
            die("Erro na consulta: " . $conexao->error);
        }
        
        if ($resultado->num_rows > 0) {
            // Calcula totais
            $total_receitas = 0;
            $total_despesas = 0;
            
            echo "<table class='finance-table'>";
            echo "<thead><tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Ações</th>
                  </tr></thead><tbody>";
            
            while ($row = $resultado->fetch_assoc()) {
                // Acumula totais
                if ($row['tipo'] == 'receita') {
                    $total_receitas += $row['valor'];
                } else {
                    $total_despesas += $row['valor'];
                }
                
                // Sanitiza os dados antes de exibir
                $descricao = htmlspecialchars($row['descricao']);
                $valor = number_format($row['valor'], 2, ',', '.');
                $tipo = ucfirst(htmlspecialchars($row['tipo']));
                $data = date("d/m/Y", strtotime($row['data_transacao']));
                $id = intval($row['id']);
                
                echo "<tr>";
                echo "<td>{$descricao}</td>";
                echo "<td class='" . ($row['tipo'] == 'receita' ? 'receita' : 'despesa') . "'>R$ {$valor}</td>";
                echo "<td>{$tipo}</td>";
                echo "<td>{$data}</td>";
                echo "<td class='actions'>
                        <a href='editar.php?id={$id}' class='btn-edit'>Editar</a>
                        <form method='post' action='deletar.php' style='display:inline;'>
                            <input type='hidden' name='id' value='{$id}'>
                            <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}'>
                            <button type='submit' class='btn-delete'>Excluir</button>

                        </form>
                      </td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            
            // Exibe totais
            $saldo = $total_receitas - $total_despesas;
            echo "<div class='totals'>";
            echo "<p>Total Receitas: <span class='receita'>R$ " . number_format($total_receitas, 2, ',', '.') . "</span></p>";
            echo "<p>Total Despesas: <span class='despesa'>R$ " . number_format($total_despesas, 2, ',', '.') . "</span></p>";
            echo "<p>Saldo: <span class='" . ($saldo >= 0 ? 'receita' : 'despesa') . "'>R$ " . number_format($saldo, 2, ',', '.') . "</span></p>";
            echo "</div>";
        } else {
            echo "<p class='no-data'>Nenhuma transação encontrada.</p>";
        }
        
        $conexao->close();
        
        ?>
     <div id="confirmModal" class="modal-overlay">
  <div class="modal-box">
    <p>Tem certeza que deseja excluir esta transação?</p>
    <div class="modal-actions">
      <button id="confirmDelete" class="btn-confirm">Sim, excluir</button>
      <button id="confirmCancel" class="btn-cancel">Cancelar</button>
    </div>
  </div>
</div>
<form onsubmit="confirmDelete(event, this)">
  <button type="submit">Excluir</button>
</form>

<?php if (!empty($mensagem)): ?>
  <div class="mensagem-popup <?php echo $classe_mensagem; ?>">
      <?php echo $mensagem; ?>
  </div>
<?php endif; ?>


<script>
// Variáveis globais
let currentDeleteForm = null;

// Função para abrir o modal
function confirmDelete(event, form) {
  event.preventDefault();
  currentDeleteForm = form;
  document.getElementById('confirmModal').style.display = 'flex';
}

// Configura os botões do modal
document.getElementById('confirmCancel').addEventListener('click', () => {
  document.getElementById('confirmModal').style.display = 'none';
});

document.getElementById('confirmDelete').addEventListener('click', () => {
  if (currentDeleteForm) {
    currentDeleteForm.submit();
  }
});

// Fecha o modal ao clicar fora
window.addEventListener('click', (event) => {
  if (event.target === document.getElementById('confirmModal')) {
    document.getElementById('confirmModal').style.display = 'none';
  }
});
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.pathname);
}
</script>
    </div>
</body>
</html>