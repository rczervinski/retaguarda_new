<?php
/**
 * Endpoint para buscar informações de categoria
 */

error_reporting(E_ALL);
ini_set('display_errors', 1); // Mostrar erros para debug
ini_set('log_errors', 1);

// Debug inicial
error_log("=== DEBUG get_category_info.php INICIADO ===");

header('Content-Type: application/json; charset=utf-8');

error_log("DEBUG: Carregando dependências...");

try {
    require_once 'category_mapper.php';
    error_log("DEBUG: category_mapper.php carregado");

    require_once 'dynamic_category_mapper.php';
    error_log("DEBUG: dynamic_category_mapper.php carregado");

} catch (Exception $e) {
    error_log("ERRO ao carregar dependências: " . $e->getMessage());
    echo json_encode([
        'error' => 'Erro ao carregar dependências: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}

$categoryId = $_GET['category_id'] ?? '';
error_log("DEBUG: category_id recebido: '$categoryId'");

if (empty($categoryId)) {
    error_log("ERRO: category_id vazio");
    echo json_encode([
        'error' => 'category_id é obrigatório'
    ]);
    exit;
}

try {
    error_log("DEBUG: Iniciando processamento da categoria $categoryId");

    // ✅ NOVO: Tentar buscar da API do ML primeiro
    error_log("DEBUG: Tentando buscar da API do ML...");

    try {
        error_log("DEBUG: Tentando criar dynamic mapper...");
        $dynamicMapper = getMLDynamicCategoryMapper();
        error_log("DEBUG: Dynamic mapper criado, buscando atributos...");
        $dynamicInfo = $dynamicMapper->getCategoryAttributes($categoryId);
        error_log("DEBUG: Atributos obtidos: " . json_encode($dynamicInfo));
    } catch (Exception $e) {
        error_log("ERRO no dynamic mapper: " . $e->getMessage());
        error_log("ERRO trace: " . $e->getTraceAsString());
        $dynamicInfo = ['success' => false];
    }

if ($dynamicInfo['success']) {
    // Usar dados da API do ML
    $response = [
        'known' => true,
        'category_id' => $categoryId,
        'source' => 'ml_api',
        'message' => $dynamicInfo['message'],
        'help_text' => 'Campos obtidos diretamente da API do Mercado Livre.',
        'fields' => []
    ];

    // Converter campos obrigatórios para formato do modal
    foreach ($dynamicInfo['required_fields'] as $field) {
        $response['fields'][] = [
            'id' => $field['id'],
            'name' => $field['name'],
            'type' => $field['type'],
            'description' => $field['description'],
            'examples' => $field['examples'],
            'required' => true,
            'allowed_values' => $field['allowed_values'] ?? null,
            'allowed_units' => $field['allowed_units'] ?? null
        ];
    }

} else {
    error_log("DEBUG: Usando fallback para mapeamento manual");
    // Fallback para mapeamento manual
    try {
        $categoryMapper = getMLCategoryMapper();
        error_log("DEBUG: Category mapper criado");
        $categoryInfo = $categoryMapper->prepareModalFields($categoryId);
        error_log("DEBUG: Modal fields preparados: " . json_encode($categoryInfo));
        $response = $categoryInfo;
        $response['source'] = 'manual_mapping';
    } catch (Exception $e) {
        error_log("ERRO no fallback: " . $e->getMessage());
        throw $e;
    }
}

if ($response['known']) {
    if (isset($response['category_name'])) {
        $response['message'] = "Categoria mapeada: {$response['category_name']}. Preencha os campos abaixo:";
        $response['help_text'] = "Estes campos são obrigatórios para esta categoria no Mercado Livre.";
    }
} else {
    $response['message'] = "Categoria não mapeada ainda. Usando campos padrão.";
    $response['help_text'] = "Se você encontrar erros, nos informe para mapearmos esta categoria.";
    
    // Campos padrão para categorias não mapeadas
    $response['fields'] = [
        [
            'id' => 'MANUFACTURER',
            'name' => 'Fabricante/Marca',
            'type' => 'string',
            'description' => 'Marca ou fabricante do produto',
            'examples' => ['Nike', 'Samsung', 'Genérica'],
            'required' => true
        ],
        [
            'id' => 'PRODUCT_NAME',
            'name' => 'Nome do Produto',
            'type' => 'string',
            'description' => 'Nome específico do produto',
            'examples' => ['Produto Original', 'Modelo Específico'],
            'required' => true
        ]
    ];
}

error_log("DEBUG: Resposta final: " . json_encode($response));
echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("ERRO EXCEPTION: " . $e->getMessage());
    error_log("ERRO FILE: " . $e->getFile());
    error_log("ERRO LINE: " . $e->getLine());
    error_log("ERRO TRACE: " . $e->getTraceAsString());

    echo json_encode([
        'error' => 'Erro interno: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'debug' => true
    ]);
} catch (Error $e) {
    error_log("ERRO FATAL: " . $e->getMessage());
    error_log("ERRO FATAL FILE: " . $e->getFile());
    error_log("ERRO FATAL LINE: " . $e->getLine());

    echo json_encode([
        'error' => 'Erro fatal: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'debug' => true
    ]);
}

error_log("=== DEBUG get_category_info.php FINALIZADO ===");
?>
