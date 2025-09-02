<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar a sessão e incluir a conexão com o banco de dados
session_start();
include("../conexao.php");

// Verificar se os dados foram enviados
if (!isset($_POST['client_id']) || !isset($_POST['access_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$codigo = $_POST['codigo'] ?? 1; // Código da plataforma (1 = NuvemShop)
$client_id = $_POST['client_id'];
$access_token = $_POST['access_token'];
$url_checkout = $_POST['url_checkout'] ?? '';
$ativo = $_POST['ativo'] ?? 0;
$descricao = $_POST['descricao'] ?? 'NUVEMSHOP';
$code = ''; // Não utilizado para NuvemShop
$cliente_secret = ''; // Não utilizado para NuvemShop

// Verificar se já existe configuração para esta plataforma
$query = "SELECT * FROM tokenmercado WHERE codigo = $codigo";
$result = pg_query($conexao, $query);

if (pg_num_rows($result) > 0) {
    // Atualizar configuração existente
    $query = "UPDATE tokenmercado SET 
              client_id = '$client_id', 
              access_token = '$access_token',
              url_checkout = '$url_checkout',
              ativo = $ativo,
              descricao = '$descricao'
              WHERE codigo = $codigo";
} else {
    // Inserir nova configuração
    $query = "INSERT INTO tokenmercado (codigo, code, access_token, client_id, cliente_secret, descricao, url_checkout, ativo) 
              VALUES ($codigo, '$code', '$access_token', '$client_id', '$cliente_secret', '$descricao', '$url_checkout', $ativo)";
}

$result = pg_query($conexao, $query);

header('Content-Type: application/json');

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Configuração salva com sucesso'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar configuração: ' . pg_last_error($conexao)
    ]);
}
?>
