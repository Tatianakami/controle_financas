<?php
// Define o ambiente (development/production)
define('ENVIRONMENT', 'development');

// Configurações por ambiente
$config = [
    'development' => [
        'host' => 'localhost',
        'usuario' => 'root',
        'senha' => '',
        'banco' => 'controle_financas', // Removi o 'ç' para evitar problemas
        'charset' => 'utf8mb4'
    ],
    'production' => [
        'host' => 'localhost',
        'usuario' => 'usuario_producao',
        'senha' => 'senha_forte_aqui',
        'banco' => 'controle_financas_prod',
        'charset' => 'utf8mb4'
    ]
];

// Seleciona a configuração baseada no ambiente
$dbConfig = $config[ENVIRONMENT];

// Estabelece a conexão
try {
    $conexao = new mysqli(
        $dbConfig['host'],
        $dbConfig['usuario'],
        $dbConfig['senha'],
        $dbConfig['banco']
    );

    // Verifica erros de conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }

    // Define o charset para evitar problemas com caracteres especiais
    if (!$conexao->set_charset($dbConfig['charset'])) {
        throw new Exception("Erro ao definir charset: " . $conexao->error);
    }

    // Configura para lançar exceções em erros SQL
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

} catch (Exception $e) {
    // Log do erro em produção
    if (ENVIRONMENT === 'production') {
        error_log($e->getMessage());
        die("Erro crítico no sistema. Por favor, tente novamente mais tarde.");
    } else {
        die($e->getMessage());
    }
}

// Função auxiliar para executar queries com parâmetros
function executarQuery($conexao, $sql, $params = [], $types = '') {
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }
    
    return $stmt;
}
?>