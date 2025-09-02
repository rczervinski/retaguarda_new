<?php
/**
 * Arquivo para processar requisições AJAX da integração com a Nuvemshop
 */

// Iniciar buffer de saída para controlar o que é enviado ao navegador
ob_start();

// Iniciar sessão e incluir arquivo de conexão
session_start();
include "conexao.php";

/**
 * Função para verificar e criar a tabela token_integracao se não existir
 */
function verificarECriarTabelaTokenIntegracao($conexao) {
    $check_table_query = "SELECT EXISTS (
        SELECT FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_name = 'token_integracao'
    )";
    $check_result = pg_query($conexao, $check_table_query);

    if ($check_result) {
        $table_exists = pg_fetch_result($check_result, 0, 0);

        if ($table_exists == 'f') {
            // Tabela não existe, criar automaticamente
            $create_table_sql = "
            CREATE TABLE token_integracao (
                codigo SERIAL PRIMARY KEY,
                descricao VARCHAR(50) NOT NULL,
                client_id VARCHAR(100),
                client_secret VARCHAR(100),
                access_token VARCHAR(255),
                code VARCHAR(100),
                url_checkout VARCHAR(255),
                ativo INTEGER DEFAULT 0,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            ";

            $create_result = pg_query($conexao, $create_table_sql);

            if (!$create_result) {
                return ['success' => false, 'message' => 'Erro ao criar tabela token_integracao: ' . pg_last_error($conexao)];
            }

            return ['success' => true, 'message' => 'Tabela token_integracao criada automaticamente'];
        }
    }

    return ['success' => true, 'message' => 'Tabela token_integracao já existe'];
}

// Verificar se a requisição foi feita via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Limpar qualquer saída anterior
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido']);
    exit;
}

// Verificar se o parâmetro 'request' foi enviado
if (!isset($_POST['request'])) {
    // Limpar qualquer saída anterior
    ob_clean();

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetro "request" não informado']);
    exit;
}

// Processar a requisição com base no parâmetro 'request'
$request = $_POST['request'];

switch ($request) {
    case 'fetchNuvemshop':
        // Verificar e criar tabela se necessário
        $table_check = verificarECriarTabelaTokenIntegracao($conexao);
        if (!$table_check['success']) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode($table_check);
            exit;
        }

        // Buscar configurações da Nuvemshop
        $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar configurações: ' . pg_last_error($conexao)]);
            exit;
        }

        $configs = [];
        while ($row = pg_fetch_assoc($result)) {
            $configs[] = $row;
        }

        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode($configs);
        break;

    case 'testarConexaoNuvemshop':
        // Incluir o arquivo com as funções da Nuvemshop
        require_once 'nuvemshop/sincronizar_vendas.php';

        // Obter configurações da Nuvemshop
        $config = obterConfiguracoesNuvemshop();

        if (!$config) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma configuração ativa da Nuvemshop encontrada']);
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

        // Testar a conexão obtendo informações da loja
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url . '/store');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao conectar com a Nuvemshop: ' . $error]);
            exit;
        }

        curl_close($ch);

        if ($http_code == 200) {
            $store_info = json_decode($response, true);

            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso',
                'store_name' => $store_info['name'] ?? 'Não informado',
                'store_url' => $store_info['url'] ?? 'Não informado'
            ]);
        } else {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao conectar com a Nuvemshop',
                'http_code' => $http_code,
                'response' => $response
            ]);
        }
        break;

    case 'atualizarStatusNuvemshop':
        // Verificar se os parâmetros necessários foram enviados
        if (!isset($_POST['codigo']) || !isset($_POST['status'])) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
            exit;
        }

        $codigo = $_POST['codigo'];
        $status = $_POST['status'];

        // Atualizar o status da configuração
        $query = "UPDATE token_integracao SET ativo = $status WHERE codigo = $codigo";
        $result = pg_query($conexao, $query);

        if (!$result) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . pg_last_error($conexao)]);
            exit;
        }

        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
        break;

    case 'excluirConfigNuvemshop':
        // Verificar se o parâmetro necessário foi enviado
        if (!isset($_POST['codigo'])) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetro "codigo" não informado']);
            exit;
        }

        $codigo = $_POST['codigo'];

        // Excluir a configuração
        $query = "DELETE FROM token_integracao WHERE codigo = $codigo";
        $result = pg_query($conexao, $query);

        if (!$result) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir configuração: ' . pg_last_error($conexao)]);
            exit;
        }

        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Configuração excluída com sucesso']);
        break;

    case 'salvarTokenNuvemshop':
        // Verificar se os parâmetros necessários foram enviados
        if (!isset($_POST['access_token']) || !isset($_POST['store_id'])) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
            exit;
        }

        $access_token = $_POST['access_token'];
        $store_id = $_POST['store_id'];
        $url_checkout = $_POST['url_checkout'] ?? '';

        // Verificar e criar tabela se necessário
        $table_check = verificarECriarTabelaTokenIntegracao($conexao);
        if (!$table_check['success']) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode($table_check);
            exit;
        }

        // Verificar se já existe uma configuração para a Nuvemshop
        $query = "SELECT codigo FROM token_integracao WHERE descricao = 'NUVEMSHOP'";
        $result = pg_query($conexao, $query);

        if (pg_num_rows($result) > 0) {
            // Atualizar a configuração existente
            $row = pg_fetch_assoc($result);
            $codigo = $row['codigo'];

            $query = "UPDATE token_integracao SET
                      access_token = '$access_token',
                      code = '$store_id',
                      url_checkout = '$url_checkout',
                      ativo = 1
                      WHERE codigo = $codigo";
        } else {
            // Inserir uma nova configuração
            $query = "INSERT INTO token_integracao (descricao, access_token, code, url_checkout, ativo)
                      VALUES ('NUVEMSHOP', '$access_token', '$store_id', '$url_checkout', 1)";
        }

        $result = pg_query($conexao, $query);

        if (!$result) {
            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configuração: ' . pg_last_error($conexao)]);
            exit;
        }

        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
        break;

    case 'sincronizarProdutosNuvemshop':
        // Incluir o arquivo com as funções da Nuvemshop
        require_once 'nuvemshop/sincronizar_produtos.php';

        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Sincronização de produtos iniciada']);
        break;

    case 'listarProdutosNuvemshop':
        try {
            // Limpar qualquer saída anterior
            ob_clean();

            // Incluir o arquivo com as funções da Nuvemshop
            require_once 'nuvemshop/sincronizar_produtos.php';

            // Obter configurações da Nuvemshop
            $config = obterConfiguracoesNuvemshop();

            if (!$config) {
                throw new Exception('Nenhuma configuração ativa da Nuvemshop encontrada');
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

            // Obter produtos da Nuvemshop
            $produtos = obterProdutosNuvemshop($api_url, $headers);

            // Verificar se houve erro na resposta
            if (isset($produtos['error'])) {
                throw new Exception($produtos['error']);
            }

            // Verificar se produtos é um array válido
            if (!is_array($produtos)) {
                throw new Exception('Resposta da API não é um array válido');
            }

            // Limpar qualquer saída anterior
            ob_clean();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'produtos' => $produtos]);
        } catch (Exception $e) {
            // Limpar qualquer saída anterior
            ob_clean();

            // Registrar o erro no log
            error_log("Erro ao listar produtos da Nuvemshop: " . $e->getMessage());

            // Retornar resposta de erro
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
        break;

    // ==================== MERCADO LIVRE ====================

    case 'fetchMercadoLivre':
        // Buscar configurações do Mercado Livre
        $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar configurações: ' . pg_last_error($conexao)]);
            exit;
        }

        $configs = [];
        while ($row = pg_fetch_assoc($result)) {
            $configs[] = $row;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($configs);
        break;

    case 'salvarConfiguracaoMercadoLivre':
        // Verificar parâmetros
        if (!isset($_POST['client_id']) || !isset($_POST['client_secret'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
            exit;
        }

        $client_id = $_POST['client_id'];
        $client_secret = $_POST['client_secret'];

        // Verificar se já existe configuração
        $query = "SELECT codigo FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
        $result = pg_query($conexao, $query);

        if (pg_num_rows($result) > 0) {
            // Atualizar configuração existente
            $row = pg_fetch_assoc($result);
            $codigo = $row['codigo'];

            $query = "UPDATE token_integracao SET
                      client_id = '$client_id',
                      client_secret = '$client_secret'
                      WHERE codigo = $codigo";
        } else {
            // Inserir nova configuração
            $query = "INSERT INTO token_integracao (descricao, client_id, client_secret, ativo)
                      VALUES ('MERCADO_LIVRE', '$client_id', '$client_secret', 0)";
        }

        $result = pg_query($conexao, $query);

        if (!$result) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configuração: ' . pg_last_error($conexao)]);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
        break;

    case 'atualizarStatusMercadoLivre':
        // Verificar parâmetros
        if (!isset($_POST['codigo']) || !isset($_POST['status'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
            exit;
        }

        $codigo = $_POST['codigo'];
        $status = $_POST['status'];

        // Atualizar status
        $query = "UPDATE token_integracao SET ativo = $status WHERE codigo = $codigo";
        $result = pg_query($conexao, $query);

        if (!$result) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . pg_last_error($conexao)]);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
        break;

    case 'buscarConfiguracaoMercadoLivre':
        // Verificar parâmetros
        if (!isset($_POST['codigo'])) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Código não informado']);
            exit;
        }

        $codigo = $_POST['codigo'];

        // Buscar configuração específica
        $query = "SELECT * FROM token_integracao WHERE codigo = $codigo AND descricao = 'MERCADO_LIVRE'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar configuração: ' . pg_last_error($conexao)]);
            exit;
        }

        if (pg_num_rows($result) > 0) {
            $config = pg_fetch_assoc($result);
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $config]);
        } else {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Configuração não encontrada']);
        }
        break;

    case 'testarConexaoMercadoLivre':
        // Buscar configuração ativa
        $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE' AND ativo = 1";
        $result = pg_query($conexao, $query);

        if (!$result || pg_num_rows($result) == 0) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma configuração ativa encontrada']);
            exit;
        }

        $config = pg_fetch_assoc($result);
        $access_token = $config['access_token'];

        if (empty($access_token)) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token de acesso não configurado. Faça a autenticação primeiro.']);
            exit;
        }

        // Testar conexão com API do ML
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.mercadolibre.com/users/me',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $error]);
            exit;
        }

        if ($httpCode == 200) {
            $userData = json_decode($response, true);
            $nickname = $userData['nickname'] ?? 'Usuário';
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Conectado como: $nickname"]);
        } else {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Erro HTTP $httpCode. Token pode estar expirado."]);
        }
        break;

    default:
        // Limpar qualquer saída anterior
        ob_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Requisição desconhecida: ' . $request]);
        break;
}
