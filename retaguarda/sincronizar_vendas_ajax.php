<?php
/**
 * Script para sincronizar pedidos da Nuvemshop
 * Este script é chamado via AJAX pela página de Vendas Online
 */

ob_start();

session_start();
include "conexao.php";

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$response = array(
    'success' => true,
    'message' => 'Sincronização concluída com sucesso',
    'servicos' => array()
);

$query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
$result = pg_query($conexao, $query);

if (!$result || pg_num_rows($result) == 0) {
    $response['success'] = false;
    $response['message'] = "Nenhuma configuração ativa da Nuvemshop encontrada";

    // Limpar qualquer saída anterior
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$servico = pg_fetch_assoc($result);
$servico_info = array(
    'descricao' => 'NUVEMSHOP',
    'success' => false,
    'message' => '',
    'pedidos_novos' => 0,
    'pedidos_atualizados' => 0
);

// Incluir o arquivo com a função de sincronização
require_once 'nuvemshop/sincronizar_vendas.php';

// Definir uma variável global para indicar que estamos em modo silencioso
$SILENT_MODE = true;

// Obter configurações da Nuvemshop
$config = obterConfiguracoesNuvemshop();

if (!$config) {
    $servico_info['message'] = 'Nenhuma configuração ativa da Nuvemshop encontrada';
    $response['servicos'][] = $servico_info;
    $response['success'] = false;
    $response['message'] = 'Erro ao sincronizar pedidos';

    // Limpar qualquer saída anterior
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Configurações da API
$access_token = $config['access_token'];
$store_id = $config['store_id'];
$api_url = "https://api.tiendanube.com/v1/{$store_id}";

// Definir headers padrão
$headers = [
    'Authentication: bearer ' . $access_token,
    'Content-Type: application/json',
    'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
];

// Verificar se a coluna 'origem' existe na tabela ped_online_base
$query = "SELECT EXISTS (
    SELECT FROM information_schema.columns
    WHERE table_schema = 'public'
    AND table_name = 'ped_online_base'
    AND column_name = 'origem'
)";
$result = pg_query($conexao, $query);
$coluna_origem_existe = pg_fetch_result($result, 0, 0) === 't';

// Obter data da última sincronização
if ($coluna_origem_existe) {
    $query = "SELECT MAX(data) as ultima_data FROM ped_online_base WHERE origem = 'nuvemshop'";
} else {
    // Se a coluna 'origem' não existir, buscar a data mais recente sem filtro
    $query = "SELECT MAX(data) as ultima_data FROM ped_online_base";

    // Tentar adicionar a coluna 'origem' para uso futuro
    try {
        $alter_query = "ALTER TABLE ped_online_base ADD COLUMN origem VARCHAR(50)";
        pg_query($conexao, $alter_query);
    } catch (Exception $e) {
        // Ignorar erro
    }
}

$result = pg_query($conexao, $query);
if ($result) {
    $row = pg_fetch_assoc($result);
    $ultima_data = $row['ultima_data'];
} else {
    $ultima_data = null;
}

// Se não houver data, usar data de 30 dias atrás
if (empty($ultima_data)) {
    $ultima_data = date('Y-m-d', strtotime('-30 days'));
}

// Parâmetros para obter pedidos
$params = [
    'created_at_min' => $ultima_data . 'T00:00:00-03:00',
    'per_page' => 50
];

// Obter pedidos da Nuvemshop
$pedidos = obterPedidosNuvemshop($api_url, $headers, $params);

if (isset($pedidos['error'])) {
    $servico_info['message'] = 'Erro ao obter pedidos: ' . $pedidos['error'];
    $response['servicos'][] = $servico_info;
    $response['success'] = false;
    $response['message'] = 'Erro ao sincronizar pedidos';

    // Limpar qualquer saída anterior
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Processar pedidos
$novos_pedidos = 0;
$pedidos_atualizados = 0;
$erros = 0;

foreach ($pedidos as $pedido) {
    $resultado = processarPedido($pedido);

    if ($resultado['success']) {
        if ($resultado['novo']) {
            $novos_pedidos++;
        } else {
            $pedidos_atualizados++;
        }
    } else {
        $erros++;
    }
}

// Atualizar informações do serviço
$servico_info['success'] = true;
$servico_info['message'] = "Sincronização concluída: $novos_pedidos novos pedidos, $pedidos_atualizados pedidos atualizados";
$servico_info['pedidos_novos'] = $novos_pedidos;
$servico_info['pedidos_atualizados'] = $pedidos_atualizados;

$response['servicos'][] = $servico_info;

// Limpar qualquer saída anterior
ob_clean();

// Retornar resposta em formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
