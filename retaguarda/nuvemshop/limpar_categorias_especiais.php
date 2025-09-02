<?php
/**
 * Script para limpar categorias especiais criadas incorretamente
 * Remove categorias "SEM_GRUPO" e "SEM_CATEGORIA" da Nuvemshop
 */

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧹 Limpeza de Categorias Especiais</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; }
.section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
</style>";

// Incluir configurações
require_once 'nuvemshop_config.php';

/**
 * Fazer requisição para API da Nuvemshop
 */
function makeRequest($method, $endpoint, $data = null) {
    global $access_token, $user_id, $store_id;
    
    $url = "https://api.nuvemshop.com.br/v1/{$user_id}/stores/{$store_id}" . $endpoint;
    
    $headers = [
        'Authentication: bearer ' . $access_token,
        'Content-Type: application/json',
        'User-Agent: Sistema Categorias (contato@empresa.com)'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode
    ];
}

echo "<div class='section'>";
echo "<h2>🔍 Buscando Categorias Especiais</h2>";

// Buscar todas as categorias
$result = makeRequest('GET', '/categories?fields=id,name,parent');

if ($result['http_code'] !== 200) {
    echo "<p class='error'>❌ Erro ao buscar categorias: HTTP {$result['http_code']}</p>";
    echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";
    exit;
}

$categories = json_decode($result['response'], true);

if (!$categories) {
    echo "<p class='error'>❌ Erro ao decodificar resposta da API</p>";
    exit;
}

echo "<p class='info'>📊 Total de categorias encontradas: " . count($categories) . "</p>";

// Procurar categorias especiais
$categoriasEspeciais = [];
$categoriasNormais = [];

foreach ($categories as $category) {
    $name = '';
    
    // Verificar se name é array ou string
    if (is_array($category['name'])) {
        $name = $category['name']['pt'] ?? $category['name']['es'] ?? $category['name']['en'] ?? '';
    } else {
        $name = $category['name'];
    }
    
    // Verificar se é categoria especial
    if ($name === 'SEM_GRUPO' || $name === 'SEM_CATEGORIA') {
        $categoriasEspeciais[] = [
            'id' => $category['id'],
            'name' => $name,
            'parent' => $category['parent'] ?? 0
        ];
    } else {
        $categoriasNormais[] = [
            'id' => $category['id'],
            'name' => $name,
            'parent' => $category['parent'] ?? 0
        ];
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>📋 Resultado da Busca</h2>";

if (empty($categoriasEspeciais)) {
    echo "<p class='success'>✅ Nenhuma categoria especial encontrada! Sistema está limpo.</p>";
} else {
    echo "<p class='warning'>⚠️ Encontradas " . count($categoriasEspeciais) . " categoria(s) especial(is):</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Nome</th>";
    echo "<th style='padding: 8px;'>Parent ID</th>";
    echo "<th style='padding: 8px;'>Ação</th>";
    echo "</tr>";
    
    foreach ($categoriasEspeciais as $cat) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$cat['id']}</td>";
        echo "<td style='padding: 8px; font-weight: bold; color: red;'>{$cat['name']}</td>";
        echo "<td style='padding: 8px;'>{$cat['parent']}</td>";
        echo "<td style='padding: 8px;'>🗑️ Deve ser removida</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p class='info'>📊 Categorias normais: " . count($categoriasNormais) . "</p>";

echo "</div>";

// Se há categorias especiais, oferecer opção de remoção
if (!empty($categoriasEspeciais)) {
    echo "<div class='section'>";
    echo "<h2>🗑️ Remoção de Categorias Especiais</h2>";
    
    if (isset($_POST['confirmar_remocao'])) {
        echo "<h3>🔄 Removendo categorias especiais...</h3>";
        
        $removidas = 0;
        $erros = 0;
        
        foreach ($categoriasEspeciais as $cat) {
            echo "<p>Removendo categoria: <strong>{$cat['name']}</strong> (ID: {$cat['id']})...</p>";
            
            $result = makeRequest('DELETE', "/categories/{$cat['id']}");
            
            if ($result['http_code'] === 200 || $result['http_code'] === 204) {
                echo "<p class='success'>✅ Categoria removida com sucesso!</p>";
                $removidas++;
            } else {
                echo "<p class='error'>❌ Erro ao remover categoria: HTTP {$result['http_code']}</p>";
                echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";
                $erros++;
            }
        }
        
        echo "<hr>";
        echo "<h3>📊 Resumo da Limpeza:</h3>";
        echo "<ul>";
        echo "<li class='success'>✅ Categorias removidas: $removidas</li>";
        echo "<li class='error'>❌ Erros: $erros</li>";
        echo "</ul>";
        
        if ($removidas > 0) {
            echo "<p class='success'><strong>🎉 Limpeza concluída! Sistema está agora limpo.</strong></p>";
        }
        
    } else {
        echo "<p class='warning'>⚠️ <strong>ATENÇÃO:</strong> Esta ação irá remover permanentemente as categorias especiais da Nuvemshop.</p>";
        echo "<p>Categorias que serão removidas:</p>";
        echo "<ul>";
        foreach ($categoriasEspeciais as $cat) {
            echo "<li><strong>{$cat['name']}</strong> (ID: {$cat['id']})</li>";
        }
        echo "</ul>";
        
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<input type='hidden' name='confirmar_remocao' value='1'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>";
        echo "🗑️ Confirmar Remoção";
        echo "</button>";
        echo "</form>";
        
        echo "<p class='info'><small>💡 <strong>Dica:</strong> Após a remoção, teste o sistema novamente para garantir que não criará mais categorias especiais.</small></p>";
    }
    
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>🔧 Sistema Corrigido</h2>";

echo "<p class='success'>✅ <strong>CategoryManager foi corrigido!</strong></p>";
echo "<p>Agora o sistema:</p>";
echo "<ul>";
echo "<li>✅ Detecta valores especiais (SEM_CATEGORIA, SEM_GRUPO)</li>";
echo "<li>✅ NÃO cria categorias para esses valores</li>";
echo "<li>✅ Retorna null para valores especiais</li>";
echo "<li>✅ Trata corretamente quando findOrCreateCategory retorna null</li>";
echo "</ul>";

echo "<h3>🧪 Próximos Testes:</h3>";
echo "<ol>";
echo "<li>Teste com produto: Categoria = 'PRINCIPAL', Grupo = 'Sem grupo'</li>";
echo "<li>Teste com produto: Categoria = 'Sem categoria', Grupo = qualquer</li>";
echo "<li>Verifique se não são criadas categorias especiais</li>";
echo "</ol>";

echo "</div>";

echo "<hr>";
echo "<p><small>Script executado em: " . date('Y-m-d H:i:s') . "</small></p>";
?>
