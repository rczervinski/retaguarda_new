<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar a sessão e incluir a conexão com o banco de dados
session_start();
include("../conexao.php");

// Código da plataforma NuvemShop
$CODIGO_NUVEMSHOP = 1;

// Buscar configuração da NuvemShop
$query = "SELECT * FROM tokenmercado WHERE codigo = $CODIGO_NUVEMSHOP LIMIT 1";
$result = pg_query($conexao, $query);

header('Content-Type: application/json');

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'data' => $row
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Configuração não encontrada'
    ]);
}
?>
