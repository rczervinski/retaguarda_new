<?php
// Debug para Mercado Livre
header('Content-Type: application/json; charset=utf-8');

// Log da requisição
error_log("DEBUG ML: " . print_r($_POST, true));

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método não permitido', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit;
}

// Verificar se tem request
if (!isset($_POST['request'])) {
    echo json_encode(['error' => 'Request não informado', 'post_data' => $_POST]);
    exit;
}

$request = $_POST['request'];

// Incluir conexão
try {
    include "conexao.php";
    
    if (!$conexao) {
        echo json_encode(['error' => 'Erro de conexão com banco']);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao conectar: ' . $e->getMessage()]);
    exit;
}

// Processar request
switch ($request) {
    case 'fetchMercadoLivre':
        try {
            $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                echo json_encode(['error' => 'Erro na query: ' . pg_last_error($conexao)]);
                exit;
            }
            
            $configs = [];
            while ($row = pg_fetch_assoc($result)) {
                $configs[] = $row;
            }
            
            echo json_encode($configs);
            
        } catch (Exception $e) {
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Request não implementado: ' . $request]);
        break;
}
?>
