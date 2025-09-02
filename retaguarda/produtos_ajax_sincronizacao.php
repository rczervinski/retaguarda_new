<?php
/**
 * Funções para sincronização de produtos com a Nuvemshop
 */

// Incluir conexão com banco de dados
require_once 'conexao.php';

// Definir request para endpoints AJAX
$request = $_GET['request'] ?? $_POST['request'] ?? '';

// Função para obter as configurações da Nuvemshop
function obterConfiguracoesNuvemshop() {
    global $conexao;

    $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' AND ativo = 1 LIMIT 1";
    $result = pg_query($conexao, $query);

    if (pg_num_rows($result) > 0) {
        $config = pg_fetch_assoc($result);
        return [
            'access_token' => $config['access_token'],
            'store_id' => $config['code'] // O ID da loja está armazenado no campo 'code'
        ];
    }

    return null;
}

/**
 * Sincroniza o status dos produtos com a Nuvemshop
 * NOVO FLUXO: Verifica produtos do banco na Nuvemshop (de baixo para cima)
 *
 * @param array $dados_nuvemshop Dados da Nuvemshop (configurações)
 * @param object $conexao Conexão com o banco de dados
 * @return array Resultado da sincronização
 */
function sincronizarStatusProdutos($dados_nuvemshop, $conexao) {
    // Inicializar contadores
    $atualizados = 0;
    $removidos = 0;
    $adicionados = 0;
    $log = array();

    try {
        // Iniciar transação
        pg_query($conexao, "BEGIN");

        // 1. Obter todos os produtos com status de e-commerce no banco
        $query = "SELECT codigo_interno, codigo_gtin, descricao, status FROM produtos WHERE status IN ('ENS', 'ENSVI', 'ENSV') ORDER BY status, codigo_gtin";
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao consultar produtos: " . pg_last_error($conexao));
        }

        $produtos_no_banco = array();
        while ($row = pg_fetch_assoc($result)) {
            $produtos_no_banco[] = $row;
        }

        $log[] = "Encontrados " . count($produtos_no_banco) . " produtos com status de e-commerce no banco de dados";

        // 2. Configurar API da Nuvemshop
        $config = obterConfiguracoesNuvemshop();
        if (!$config) {
            throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
        }

        $access_token = $config['access_token'];
        $store_id = $config['store_id'];
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // 3. Agrupar produtos por tipo (simples vs com variantes)
        $produtos_simples = array();
        $produtos_vitrine = array();
        $variantes = array();

        foreach ($produtos_no_banco as $produto) {
            $status_original = $produto['status'];
            $status = trim($status_original); // Remover espaços em branco
            $codigo_gtin = $produto['codigo_gtin'];

            $log[] = "Produto encontrado: GTIN={$codigo_gtin}, Status='{$status}' (original: '{$status_original}'), Descrição=" . substr($produto['descricao'], 0, 30);

            // Usar comparação case-insensitive e verificar múltiplas variações
            $status_upper = strtoupper($status);

            if ($status_upper === 'ENS' || $status === 'ENS') {
                $produtos_simples[] = $produto;
                $log[] = "  → Classificado como produto simples (ENS)";
            } elseif ($status_upper === 'ENSVI' || $status === 'ENSVI') {
                $produtos_vitrine[] = $produto;
                $log[] = "  → Classificado como produto vitrine (ENSVI)";
            } elseif ($status_upper === 'ENSV' || $status === 'ENSV') {
                $variantes[] = $produto;
                $log[] = "  → Classificado como variante (ENSV)";
            } else {
                $log[] = "  → Status não reconhecido: '{$status}' (bytes: " . bin2hex($status) . ") para produto {$codigo_gtin}";
            }
        }

        $log[] = "Produtos simples (ENS): " . count($produtos_simples);
        $log[] = "Produtos vitrine (ENSVI): " . count($produtos_vitrine);
        $log[] = "Variantes (ENSV): " . count($variantes);

        // 4. Verificar produtos simples
        foreach ($produtos_simples as $produto) {
            $codigo_gtin = $produto['codigo_gtin'];
            $codigo_interno = $produto['codigo_interno'];
            $descricao = $produto['descricao'];

            $log[] = "Verificando produto simples: $codigo_gtin";

            // Buscar produto na Nuvemshop por SKU
            $produto_encontrado = buscarProdutoPorSKU($api_url, $headers, $codigo_gtin);

            if (!$produto_encontrado) {
                // Produto não encontrado na Nuvemshop - remover status
                $update_query = "UPDATE produtos SET status = '' WHERE codigo_interno = $codigo_interno";
                $update_result = pg_query($conexao, $update_query);

                if (!$update_result) {
                    throw new Exception("Erro ao remover status do produto $codigo_interno: " . pg_last_error($conexao));
                }

                $log[] = "❌ Produto simples removido: $codigo_gtin - $descricao";
                $removidos++;
                $atualizados++;
            } else {
                $log[] = "✅ Produto simples mantido: $codigo_gtin";
            }
        }

        // 5. Verificar produtos vitrine e suas variantes
        foreach ($produtos_vitrine as $produto_pai) {
            $sku_pai = $produto_pai['codigo_gtin'];
            $codigo_interno_pai = $produto_pai['codigo_interno'];
            $descricao_pai = $produto_pai['descricao'];

            $log[] = "Verificando produto vitrine: $sku_pai";

            // Buscar produto pai na Nuvemshop
            $produto_pai_encontrado = buscarProdutoPorSKU($api_url, $headers, $sku_pai);

            if (!$produto_pai_encontrado) {
                // Produto pai não encontrado - remover status do pai e de todas as variantes
                $update_query = "UPDATE produtos SET status = '' WHERE codigo_interno = $codigo_interno_pai";
                $update_result = pg_query($conexao, $update_query);

                if (!$update_result) {
                    throw new Exception("Erro ao remover status do produto pai $codigo_interno_pai: " . pg_last_error($conexao));
                }

                $log[] = "❌ Produto vitrine removido: $sku_pai - $descricao_pai";
                $removidos++;
                $atualizados++;

                // Buscar variantes relacionadas ao produto pai na tabela produtos_gd
                $query_variantes = "SELECT p.codigo_interno, p.codigo_gtin, p.descricao
                                   FROM produtos p
                                   INNER JOIN produtos_gd pg ON p.codigo_gtin = pg.codigo_gtin
                                   WHERE pg.codigo_interno = (SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$sku_pai' LIMIT 1)
                                   AND p.status = 'ENSV'";
                $result_variantes = pg_query($conexao, $query_variantes);

                if ($result_variantes) {
                    while ($variante_relacionada = pg_fetch_assoc($result_variantes)) {
                        $update_query = "UPDATE produtos SET status = '' WHERE codigo_interno = " . $variante_relacionada['codigo_interno'];
                        $update_result = pg_query($conexao, $update_query);

                        if (!$update_result) {
                            throw new Exception("Erro ao remover status da variante " . $variante_relacionada['codigo_interno'] . ": " . pg_last_error($conexao));
                        }

                        $log[] = "❌ Variante relacionada removida: " . $variante_relacionada['codigo_gtin'] . " - " . $variante_relacionada['descricao'];
                        $removidos++;
                        $atualizados++;

                        // Remover da lista de variantes para não processar novamente
                        foreach ($variantes as $key => $variante) {
                            if ($variante['codigo_gtin'] === $variante_relacionada['codigo_gtin']) {
                                unset($variantes[$key]);
                                break;
                            }
                        }
                    }
                }
            } else {
                $log[] = "✅ Produto vitrine mantido: $sku_pai";

                // Produto pai existe - verificar variantes individuais
                $produto_id = $produto_pai_encontrado['id'];
                $variantes_nuvemshop = buscarVariantesProduto($api_url, $headers, $produto_id);

                // Criar lista de barcodes das variantes na Nuvemshop
                $barcodes_nuvemshop = array();
                if ($variantes_nuvemshop) {
                    foreach ($variantes_nuvemshop as $variante_nuvem) {
                        $barcode = $variante_nuvem['barcode'] ?? '';
                        if ($barcode) {
                            $barcodes_nuvemshop[] = $barcode;
                        }
                    }
                }

                // Buscar e verificar variantes relacionadas ao produto pai
                $query_variantes = "SELECT p.codigo_interno, p.codigo_gtin, p.descricao
                                   FROM produtos p
                                   INNER JOIN produtos_gd pg ON p.codigo_gtin = pg.codigo_gtin
                                   WHERE pg.codigo_interno = (SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$sku_pai' LIMIT 1)
                                   AND p.status = 'ENSV'";
                $result_variantes = pg_query($conexao, $query_variantes);

                if ($result_variantes) {
                    while ($variante_relacionada = pg_fetch_assoc($result_variantes)) {
                        $codigo_gtin_variante = $variante_relacionada['codigo_gtin'];
                        $codigo_interno_variante = $variante_relacionada['codigo_interno'];
                        $descricao_variante = $variante_relacionada['descricao'];

                        if (!in_array($codigo_gtin_variante, $barcodes_nuvemshop)) {
                            // Variante não encontrada na Nuvemshop - remover status
                            $update_query = "UPDATE produtos SET status = '' WHERE codigo_interno = $codigo_interno_variante";
                            $update_result = pg_query($conexao, $update_query);

                            if (!$update_result) {
                                throw new Exception("Erro ao remover status da variante $codigo_interno_variante: " . pg_last_error($conexao));
                            }

                            $log[] = "❌ Variante removida: $codigo_gtin_variante - $descricao_variante";
                            $removidos++;
                            $atualizados++;
                        } else {
                            $log[] = "✅ Variante mantida: $codigo_gtin_variante";
                        }

                        // Remover da lista de variantes para não processar novamente
                        foreach ($variantes as $key => $variante) {
                            if ($variante['codigo_gtin'] === $codigo_gtin_variante) {
                                unset($variantes[$key]);
                                break;
                            }
                        }
                    }
                }
            }
        }

        // 6. Verificar variantes órfãs (sem produto pai)
        foreach ($variantes as $variante) {
            $codigo_gtin_variante = $variante['codigo_gtin'];
            $codigo_interno_variante = $variante['codigo_interno'];
            $descricao_variante = $variante['descricao'];

            // Variante órfã - remover status
            $update_query = "UPDATE produtos SET status = '' WHERE codigo_interno = $codigo_interno_variante";
            $update_result = pg_query($conexao, $update_query);

            if (!$update_result) {
                throw new Exception("Erro ao remover status da variante órfã $codigo_interno_variante: " . pg_last_error($conexao));
            }

            $log[] = "❌ Variante órfã removida: $codigo_gtin_variante - $descricao_variante";
            $removidos++;
            $atualizados++;
        }

        // Commit da transação
        pg_query($conexao, "COMMIT");

        // Garantir que o log seja um array válido para JSON
        $log_limpo = array();
        foreach ($log as $entrada) {
            if (is_string($entrada)) {
                // Remover caracteres especiais que podem quebrar o JSON
                $entrada_limpa = mb_convert_encoding($entrada, 'UTF-8', 'UTF-8');
                $entrada_limpa = preg_replace('/[^\x20-\x7E\x{00A0}-\x{FFFF}]/u', '', $entrada_limpa);
                $log_limpo[] = $entrada_limpa;
            } else {
                $log_limpo[] = (string)$entrada;
            }
        }

        return array(
            "success" => true,
            "atualizados" => $atualizados,
            "removidos" => $removidos,
            "adicionados" => $adicionados,
            "log" => $log_limpo
        );
    } catch (Exception $e) {
        // Rollback em caso de erro
        pg_query($conexao, "ROLLBACK");

        // Garantir que o log seja um array válido para JSON mesmo em caso de erro
        $log_limpo = array();
        if (is_array($log)) {
            foreach ($log as $entrada) {
                if (is_string($entrada)) {
                    $entrada_limpa = mb_convert_encoding($entrada, 'UTF-8', 'UTF-8');
                    $entrada_limpa = preg_replace('/[^\x20-\x7E\x{00A0}-\x{FFFF}]/u', '', $entrada_limpa);
                    $log_limpo[] = $entrada_limpa;
                } else {
                    $log_limpo[] = (string)$entrada;
                }
            }
        }

        return array(
            "success" => false,
            "error" => $e->getMessage(),
            "log" => $log_limpo
        );
    }
}

/**
 * Busca um produto na Nuvemshop por SKU
 *
 * @param string $api_url URL base da API
 * @param array $headers Headers para autenticação
 * @param string $sku SKU do produto
 * @return array|false Dados do produto ou false se não encontrado
 */
function buscarProdutoPorSKU($api_url, $headers, $sku) {
    // Usar o endpoint correto: /products/sku/{sku}
    $url = $api_url . '/products/sku/' . urlencode($sku);

    error_log("🔍 BUSCAR PRODUTO: URL = $url");
    error_log("🔍 BUSCAR PRODUTO: SKU = $sku");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    error_log("📡 HTTP Code: $http_code");
    error_log("📄 Response: " . substr($response, 0, 500));

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        error_log("❌ Erro cURL: $error");
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($http_code == 200) {
        $produto = json_decode($response, true);

        // A API retorna um produto específico (não array)
        if (is_array($produto) && isset($produto['id'])) {
            error_log("✅ Produto encontrado: " . $produto['name']);
            return $produto;
        } else {
            error_log("❌ Resposta inválida da API");
        }
    } elseif ($http_code == 404) {
        // Produto não encontrado
        error_log("⚠️ Produto não encontrado (404)");
        return false;
    } else {
        error_log("❌ Erro HTTP $http_code: " . substr($response, 0, 200));
    }

    return false;
}

/**
 * Busca as variantes de um produto na Nuvemshop
 *
 * @param string $api_url URL base da API
 * @param array $headers Headers para autenticação
 * @param int $produto_id ID do produto
 * @return array|false Lista de variantes ou false se erro
 */
function buscarVariantesProduto($api_url, $headers, $produto_id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/products/' . $produto_id . '/variants');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($http_code == 200) {
        $variantes = json_decode($response, true);

        if (is_array($variantes)) {
            return $variantes;
        }
    }

    return false;
}

/**
 * Sincroniza status E estoque dos produtos com a Nuvemshop
 * Combina sincronização de status com sincronização de estoque
 *
 * @param object $conexao Conexão com o banco de dados
 * @return array Resultado da sincronização completa
 */
function sincronizarStatusEEstoque($conexao) {
    $log = array();
    $status_atualizados = 0;
    $estoque_atualizados = 0;
    $erros = 0;

    try {
        // Verificar conexão com banco
        if (!$conexao || !is_resource($conexao)) {
            throw new Exception('Conexão com banco de dados inválida');
        }

        $log[] = "=== INICIANDO SINCRONIZAÇÃO COMPLETA (STATUS + ESTOQUE) ===";
        error_log("🚀 SYNC FUNCTION: Função sincronizarStatusEEstoque iniciada");

        // 1. Primeiro, sincronizar status dos produtos
        $log[] = "1. Sincronizando status dos produtos...";
        error_log("📋 SYNC: Iniciando sincronização de status");
        $resultado_status = sincronizarStatusProdutos(array(), $conexao);

        if ($resultado_status['success']) {
            $status_atualizados = $resultado_status['atualizados'];
            $log[] = "Status sincronizado: {$status_atualizados} produtos atualizados";

            // Adicionar logs do status ao log principal
            if (isset($resultado_status['log']) && is_array($resultado_status['log'])) {
                foreach ($resultado_status['log'] as $status_log) {
                    $log[] = "  [STATUS] " . $status_log;
                }
            }
        } else {
            $log[] = "Erro na sincronização de status: " . $resultado_status['error'];
            $erros++;
        }

        // 2. Depois, sincronizar estoque (local para Nuvemshop)
        $log[] = "2. Sincronizando estoque (local → Nuvemshop)...";
        $resultado_estoque = sincronizarEstoqueLocalParaNuvemshop($conexao);

        if ($resultado_estoque['success']) {
            $estoque_atualizados = $resultado_estoque['atualizados'];
            $log[] = "Estoque sincronizado: {$estoque_atualizados} produtos atualizados";

            // Adicionar logs do estoque ao log principal
            if (isset($resultado_estoque['log']) && is_array($resultado_estoque['log'])) {
                foreach ($resultado_estoque['log'] as $estoque_log) {
                    $log[] = "  [ESTOQUE] " . $estoque_log;
                }
            }
        } else {
            $log[] = "Erro na sincronização de estoque: " . $resultado_estoque['message'];
            $erros++;
        }

        $log[] = "=== SINCRONIZAÇÃO COMPLETA FINALIZADA ===";
        $log[] = "Resumo: {$status_atualizados} status + {$estoque_atualizados} estoques sincronizados, {$erros} erros";

        // Limpar logs de caracteres UTF-8 inválidos
        $log_limpo = array();
        foreach ($log as $entrada) {
            if (is_string($entrada)) {
                $entrada = mb_convert_encoding($entrada, 'UTF-8', 'UTF-8');
                $entrada = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $entrada);
            }
            $log_limpo[] = $entrada;
        }

        return array(
            'success' => true,
            'status_atualizados' => $status_atualizados,
            'estoque_atualizados' => $estoque_atualizados,
            'erros' => $erros,
            'log' => $log_limpo
        );

    } catch (Exception $e) {
        $log[] = "ERRO CRÍTICO: " . $e->getMessage();

        return array(
            'success' => false,
            'error' => $e->getMessage(),
            'status_atualizados' => $status_atualizados,
            'estoque_atualizados' => $estoque_atualizados,
            'erros' => $erros + 1,
            'log' => $log
        );
    }
}

/**
 * Sincroniza estoque do banco local para a Nuvemshop
 * Pega produtos com status que começam com "E" e atualiza estoque na Nuvemshop
 *
 * @param object $conexao Conexão com o banco de dados
 * @return array Resultado da sincronização de estoque
 */
function sincronizarEstoqueLocalParaNuvemshop($conexao) {
    $log = array();
    $atualizados = 0;
    $erros = 0;

    try {
        // 1. Obter configurações da Nuvemshop
        $config = obterConfiguracoesNuvemshop();
        if (!$config) {
            throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
        }

        $access_token = $config['access_token'];
        $store_id = $config['store_id'];
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // 2. Buscar produtos com status que começam com "E" (estoque e preço)
        $query = "SELECT p.codigo_gtin, p.descricao, p.status, po.qtde, pb.preco_venda
                  FROM produtos p
                  INNER JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  WHERE p.status LIKE 'E%' AND p.status != ''
                  ORDER BY p.codigo_gtin";

        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao consultar produtos para sincronização de estoque: " . pg_last_error($conexao));
        }

        $produtos_locais = array();
        while ($row = pg_fetch_assoc($result)) {
            // Limpar caracteres UTF-8 inválidos nos dados do banco
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    $row[$key] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $row[$key]);
                }
            }
            $produtos_locais[] = $row;
        }

        $log[] = "Encontrados " . count($produtos_locais) . " produtos com status de e-commerce para sincronizar estoque";

        // 3. Obter todos os produtos da Nuvemshop
        $produtos_nuvemshop = obterTodosProdutosNuvemshop($api_url, $headers);

        if (!$produtos_nuvemshop) {
            throw new Exception('Erro ao obter produtos da Nuvemshop');
        }

        $log[] = "Obtidos " . count($produtos_nuvemshop) . " produtos da Nuvemshop";

        // 4. Criar mapa de BARCODE para produto/variante da Nuvemshop
        $mapa_barcode_nuvemshop = array();
        $log[] = "=== ANÁLISE DOS PRODUTOS DA NUVEMSHOP (MAPEAMENTO POR BARCODE) ===";

        foreach ($produtos_nuvemshop as $produto) {
            $log[] = "📦 Produto Nuvemshop: ID={$produto['id']}, Nome=" . substr($produto['name']['pt'] ?? 'Sem nome', 0, 50);

            if (isset($produto['variants']) && is_array($produto['variants'])) {
                $log[] = "  → Tem " . count($produto['variants']) . " variantes";

                foreach ($produto['variants'] as $variante) {
                    $sku = $variante['sku'] ?? 'SEM_SKU';
                    $barcode = $variante['barcode'] ?? 'SEM_BARCODE';
                    $stock = $variante['stock'] ?? 0;

                    $log[] = "    ✓ Variante: ID={$variante['id']}, SKU='$sku', Barcode='$barcode', Stock=$stock";

                    // Mapear por BARCODE (que é único) ao invés de SKU
                    if (isset($variante['barcode']) && !empty($variante['barcode'])) {
                        $mapa_barcode_nuvemshop[$variante['barcode']] = [
                            'product_id' => $produto['id'],
                            'variant_id' => $variante['id'],
                            'sku' => $variante['sku'] ?? '',
                            'current_stock' => $variante['stock'] ?? 0,
                            'current_price' => $variante['price'] ?? 0
                        ];

                        // Log do mapeamento
                        error_log("🗺️ MAPEADO: Barcode {$variante['barcode']} → Product ID: {$produto['id']}, Variant ID: {$variante['id']}, Stock: " . ($variante['stock'] ?? 0));
                    } else {
                        $log[] = "    ⚠️ Variante sem BARCODE ignorada";
                    }
                }
            } else {
                $log[] = "  → Produto sem variantes ou variantes inválidas";
            }
        }

        $log[] = "📊 Total de Barcodes mapeados: " . count($mapa_barcode_nuvemshop);
        $log[] = "🔑 Barcodes encontrados: " . implode(', ', array_keys($mapa_barcode_nuvemshop));
        error_log("📊 Total de Barcodes mapeados: " . count($mapa_barcode_nuvemshop));

        // 5. Sincronizar estoque e preço para cada produto local (usando BARCODE) - PROCESSAMENTO EM LOTES
        $total_produtos = count($produtos_locais);
        $batch_size = 50; // Processar 50 produtos por vez
        $batches = array_chunk($produtos_locais, $batch_size);
        $total_batches = count($batches);

        error_log("📦 BATCH: Processando $total_produtos produtos em $total_batches lotes de $batch_size produtos");
        $log[] = "📦 Processando $total_produtos produtos em $total_batches lotes de $batch_size produtos";

        foreach ($batches as $batch_index => $batch_produtos) {
            $batch_num = $batch_index + 1;
            $produtos_no_batch = count($batch_produtos);

            error_log("🔄 BATCH $batch_num/$total_batches: Processando $produtos_no_batch produtos");
            $log[] = "🔄 Lote $batch_num/$total_batches: Processando $produtos_no_batch produtos";

            $batch_start_time = microtime(true);

            foreach ($batch_produtos as $produto_local) {
                $codigo_gtin = $produto_local['codigo_gtin']; // Este é o BARCODE na Nuvemshop
                $quantidade_local = intval($produto_local['qtde']);
                $preco_local = floatval(str_replace(',', '.', $produto_local['preco_venda']));
                $descricao = $produto_local['descricao'];

                // Buscar por BARCODE ao invés de SKU
                if (isset($mapa_barcode_nuvemshop[$codigo_gtin])) {
                    $dados_nuvemshop = $mapa_barcode_nuvemshop[$codigo_gtin];
                    $quantidade_atual = intval($dados_nuvemshop['current_stock']);
                    $preco_atual = floatval($dados_nuvemshop['current_price'] ?? 0);

                    // Verificar se precisa atualizar estoque ou preço
                    $precisa_atualizar_estoque = ($quantidade_local !== $quantidade_atual);
                    $precisa_atualizar_preco = (abs($preco_local - $preco_atual) > 0.01); // Diferença maior que 1 centavo

                    if ($precisa_atualizar_estoque || $precisa_atualizar_preco) {
                        // Implementar retry logic para requisições à API
                        $max_tentativas = 3;
                        $tentativa = 1;
                        $resultado = null;

                        while ($tentativa <= $max_tentativas) {
                            $resultado = atualizarEstoquePrecoNuvemshop(
                                $api_url,
                                $headers,
                                $dados_nuvemshop['product_id'],
                                $dados_nuvemshop['variant_id'],
                                $quantidade_local,
                                $preco_local
                            );

                            if ($resultado['success']) {
                                break; // Sucesso, sair do loop
                            } else {
                                error_log("⚠️ RETRY: Tentativa $tentativa/$max_tentativas falhou para barcode {$codigo_gtin}: " . ($resultado['error'] ?? 'Erro desconhecido'));

                                if ($tentativa < $max_tentativas) {
                                    // Pausa progressiva entre tentativas (backoff exponencial)
                                    $pausa = pow(2, $tentativa) * 1000000; // 2s, 4s, 8s...
                                    usleep($pausa);
                                }
                                $tentativa++;
                            }
                        }

                        if ($resultado['success']) {
                            $atualizados++;
                            $mudancas = [];
                            if ($precisa_atualizar_estoque) {
                                $mudancas[] = "Estoque: {$quantidade_atual} → {$quantidade_local}";
                            }
                            if ($precisa_atualizar_preco) {
                                $mudancas[] = "Preço: R$" . number_format($preco_atual, 2, ',', '.') . " → R$" . number_format($preco_local, 2, ',', '.');
                            }
                            $log[] = "✅ Atualizado: Barcode '{$codigo_gtin}' ({$descricao}) - " . implode(', ', $mudancas) . ($tentativa > 1 ? " (tentativa $tentativa)" : "");
                            error_log("✅ SYNC SUCCESS: Barcode {$codigo_gtin} - " . implode(', ', $mudancas) . ($tentativa > 1 ? " (tentativa $tentativa)" : ""));
                        } else {
                            $erros++;
                            $log[] = "❌ Erro ao atualizar: Barcode '{$codigo_gtin}' - " . ($resultado['error'] ?? 'Erro desconhecido') . " (após $max_tentativas tentativas)";
                            error_log("❌ SYNC ERROR: Barcode {$codigo_gtin} - " . ($resultado['error'] ?? 'Erro desconhecido') . " (após $max_tentativas tentativas)");
                        }

                        // Pequena pausa entre requisições para evitar rate limiting
                        usleep(200000); // 200ms
                    } else {
                        $log[] = "⚪ Já sincronizado: Barcode '{$codigo_gtin}' - Estoque: {$quantidade_local}, Preço: R$" . number_format($preco_local, 2, ',', '.');
                    }
                } else {
                    $log[] = "⚠️ Produto não encontrado na Nuvemshop: Barcode '{$codigo_gtin}' ({$descricao})";
                    error_log("⚠️ SYNC NOT FOUND: Barcode {$codigo_gtin} não encontrado na Nuvemshop");
                }
            }

            $batch_end_time = microtime(true);
            $batch_time = round(($batch_end_time - $batch_start_time), 2);

            error_log("✅ BATCH $batch_num/$total_batches: Concluído em {$batch_time}s");
            $log[] = "✅ Lote $batch_num/$total_batches: Concluído em {$batch_time}s";

            // Pausa maior entre lotes para dar uma folga ao servidor
            if ($batch_num < $total_batches) {
                sleep(1); // 1 segundo entre lotes
            }
        }

        // Limpar logs de caracteres UTF-8 inválidos
        $log_limpo = array();
        foreach ($log as $entrada) {
            if (is_string($entrada)) {
                $entrada = mb_convert_encoding($entrada, 'UTF-8', 'UTF-8');
                $entrada = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $entrada);
            }
            $log_limpo[] = $entrada;
        }

        return array(
            'success' => true,
            'atualizados' => $atualizados,
            'erros' => $erros,
            'log' => $log_limpo
        );

    } catch (Exception $e) {
        $log[] = "ERRO: " . $e->getMessage();

        return array(
            'success' => false,
            'message' => $e->getMessage(),
            'atualizados' => $atualizados,
            'erros' => $erros + 1,
            'log' => $log
        );
    }
}



/**
 * Obter todos os produtos da Nuvemshop
 *
 * @param string $api_url URL base da API
 * @param array $headers Headers para autenticação
 * @return array|false Lista de produtos ou false se erro
 */
function obterTodosProdutosNuvemshop($api_url, $headers) {
    $produtos = array();
    $page = 1;
    $per_page = 200; // Máximo permitido pela API
    $total_produtos = 0;

    error_log("🔄 API: Iniciando busca de produtos na Nuvemshop");
    error_log("📍 API: URL base: $api_url");

    do {
        $url = $api_url . '/products?page=' . $page . '&per_page=' . $per_page;
        error_log("📄 API: Buscando página $page ($per_page produtos por página)");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        $request_time = round(($end_time - $start_time) * 1000, 2);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_errno($ch);

        if ($curl_error) {
            $error_msg = curl_error($ch);
            error_log("❌ API ERROR: cURL erro $curl_error: $error_msg");
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        error_log("⏱️ API: Página $page processada em {$request_time}ms (HTTP $http_code)");

        if ($http_code == 200) {
            $produtos_pagina = json_decode($response, true);

            if (is_array($produtos_pagina) && count($produtos_pagina) > 0) {
                $produtos = array_merge($produtos, $produtos_pagina);
                $total_produtos += count($produtos_pagina);
                error_log("✅ API: Página $page: " . count($produtos_pagina) . " produtos obtidos (Total: $total_produtos)");
                $page++;
            } else {
                error_log("🏁 API: Página $page vazia - fim da busca");
                break; // Não há mais produtos
            }
        } else {
            error_log("❌ API ERROR: HTTP $http_code na página $page");
            error_log("📄 API ERROR: Response: " . substr($response, 0, 500));
            return false;
        }

    } while (count($produtos_pagina) == $per_page);

    error_log("🎉 API: Busca finalizada - Total de $total_produtos produtos obtidos em " . ($page - 1) . " páginas");
    return $produtos;
}

/**
 * Atualizar quantidade de um produto na Nuvemshop
 *
 * @param string $api_url URL base da API
 * @param array $headers Headers para autenticação
 * @param int $product_id ID do produto
 * @param int $variant_id ID da variante
 * @param int $quantidade Nova quantidade
 * @return array Resultado da operação
 */
function atualizarEstoquePrecoNuvemshop($api_url, $headers, $product_id, $variant_id, $quantidade, $preco) {
    $url = $api_url . '/products/' . $product_id . '/variants/' . $variant_id;

    $data = json_encode([
        'stock_management' => true,
        'stock' => intval($quantidade),
        'price' => number_format($preco, 2, '.', '')
    ]);

    // Log detalhado da requisição
    error_log("🔄 SYNC: Atualizando estoque e preço na Nuvemshop");
    error_log("📍 URL: $url");
    error_log("📦 Payload: $data");
    error_log("🆔 Product ID: $product_id, Variant ID: $variant_id, Quantidade: $quantidade, Preço: $preco");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    $request_time = round(($end_time - $start_time) * 1000, 2);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Log da resposta
    error_log("📡 UPDATE: HTTP $http_code em {$request_time}ms");
    error_log("📄 UPDATE: Response: " . substr($response, 0, 500)); // Primeiros 500 chars

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        error_log("❌ UPDATE ERROR: cURL erro: $error");
        curl_close($ch);
        return ['success' => false, 'error' => "Erro de conexão: $error", 'http_code' => $http_code];
    }

    curl_close($ch);

    if ($http_code == 200) {
        error_log("✅ UPDATE SUCCESS: Produto $product_id/variante $variant_id atualizado");
        return ['success' => true, 'response' => json_decode($response, true)];
    } else {
        error_log("❌ UPDATE ERROR: HTTP $http_code para produto $product_id/variante $variant_id");
        return ['success' => false, 'error' => "Erro HTTP $http_code ao atualizar estoque/preço na Nuvemshop", 'http_code' => $http_code, 'response' => $response];
    }
}

// Manter função antiga para compatibilidade
function atualizarQuantidadeNuvemshop($api_url, $headers, $product_id, $variant_id, $quantidade) {
    return atualizarEstoquePrecoNuvemshop($api_url, $headers, $product_id, $variant_id, $quantidade, null);
}

// Endpoint para buscar variante específica por barcode
if ($request == 'buscarVariantePorBarcode') {
    $barcode = $_POST['barcode'];

    if (!$barcode) {
        echo json_encode([
            'success' => false,
            'error' => 'Barcode não informado'
        ]);
        die();
    }

    try {
        // 1. Obter configurações da Nuvemshop
        $config = obterConfiguracoesNuvemshop();
        if (!$config) {
            throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
        }

        $access_token = $config['access_token'];
        $store_id = $config['store_id'];
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // 2. Buscar todos os produtos da Nuvemshop
        $produtos_nuvemshop = obterTodosProdutosNuvemshop($api_url, $headers);

        // 3. Procurar variante com o barcode específico
        foreach ($produtos_nuvemshop as $produto) {
            if (isset($produto['variants']) && is_array($produto['variants'])) {
                foreach ($produto['variants'] as $variante) {
                    if (isset($variante['barcode']) && $variante['barcode'] === $barcode) {
                        echo json_encode([
                            'success' => true,
                            'variante' => [
                                'product_id' => $produto['id'],
                                'variant_id' => $variante['id'],
                                'sku' => $variante['sku'],
                                'barcode' => $variante['barcode'],
                                'current_stock' => $variante['stock'] ?? 0,
                                'current_price' => $variante['price'] ?? 0
                            ]
                        ]);
                        die();
                    }
                }
            }
        }

        // Variante não encontrada
        echo json_encode([
            'success' => false,
            'error' => 'Variante não encontrada na Nuvemshop'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para atualizar variante específica
if ($request == 'atualizarVarianteEspecifica') {
    $product_id = $_POST['product_id'];
    $variant_id = $_POST['variant_id'];
    $dados = json_decode($_POST['dados'], true);

    if (!$product_id || !$variant_id || !$dados) {
        echo json_encode([
            'success' => false,
            'error' => 'Parâmetros obrigatórios não informados'
        ]);
        die();
    }

    try {
        // 1. Obter configurações da Nuvemshop
        $config = obterConfiguracoesNuvemshop();
        if (!$config) {
            throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
        }

        $access_token = $config['access_token'];
        $store_id = $config['store_id'];
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // 2. Atualizar variante na Nuvemshop
        $url = $api_url . '/products/' . $product_id . '/variants/' . $variant_id;

        error_log("🔄 EDIT VARIANT: Atualizando variante específica");
        error_log("📍 URL: $url");
        error_log("📦 Dados: " . json_encode($dados));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        error_log("📡 HTTP Code: $http_code");
        error_log("📄 Response: " . substr($response, 0, 500));

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erro cURL: " . $error);
        }

        curl_close($ch);

        if ($http_code == 200) {
            echo json_encode([
                'success' => true,
                'response' => json_decode($response, true)
            ]);
        } else {
            throw new Exception("Erro HTTP $http_code: " . $response);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para buscar produto na Nuvemshop por SKU
if ($request == 'buscarProdutoPorSku') {
    try {
        $sku = $_GET['sku'] ?? $_POST['sku'];

        if (!$sku) {
            echo json_encode([
                'success' => false,
                'error' => 'SKU não informado'
            ]);
            die();
        }

        // 1. Obter configurações da Nuvemshop
        $config = obterConfiguracoesNuvemshop();
        if (!$config) {
            throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
        }

        $access_token = $config['access_token'];
        $store_id = $config['store_id'];
        $api_url = "https://api.tiendanube.com/v1/{$store_id}";

        $headers = [
            'Authentication: bearer ' . $access_token,
            'Content-Type: application/json',
            'User-Agent: IncludeDB (renanczervinski@hotmail.com)'
        ];

        // 2. Usar a função existente para buscar produto por SKU
        error_log("🔍 SEARCH PRODUCT: Buscando produto por SKU: $sku");

        $produto = buscarProdutoPorSKU($api_url, $headers, $sku);

        if ($produto) {
            error_log("✅ Produto encontrado: " . $produto['name']);
            echo json_encode([
                'success' => true,
                'produto' => $produto
            ]);
        } else {
            error_log("❌ Produto não encontrado com SKU: $sku");
            echo json_encode([
                'success' => false,
                'error' => 'Produto não encontrado com SKU: ' . $sku
            ]);
        }

    } catch (Exception $e) {
        error_log("❌ Erro ao buscar produto por SKU: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}
?>
