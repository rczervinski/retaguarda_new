<?php
/**
 * AJAX específico para Mercado Livre
 */

// Headers para evitar problemas com CloudFront
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar se tem request
if (!isset($_POST['request'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Request não informado']);
    exit;
}

$request = $_POST['request'];

// Incluir conexão
session_start();
include "conexao.php";

if (!$conexao) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com banco']);
    exit;
}

// Processar requisições
switch ($request) {
    case 'fetchMercadoLivre':
        try {
            $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro na query: ' . pg_last_error($conexao)]);
                exit;
            }
            
            $configs = [];
            while ($row = pg_fetch_assoc($result)) {
                $configs[] = $row;
            }
            
            echo json_encode($configs);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;
        
    case 'salvarConfiguracaoMercadoLivre':
        try {
            if (!isset($_POST['client_id']) || !isset($_POST['client_secret'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Parâmetros incompletos']);
                exit;
            }
            
            $client_id = pg_escape_string($conexao, $_POST['client_id']);
            $client_secret = pg_escape_string($conexao, $_POST['client_secret']);
            
            // Verificar se já existe
            $query = "SELECT codigo FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'";
            $result = pg_query($conexao, $query);
            
            if (pg_num_rows($result) > 0) {
                // Atualizar
                $row = pg_fetch_assoc($result);
                $codigo = $row['codigo'];
                
                $query = "UPDATE token_integracao SET
                          client_id = '$client_id',
                          client_secret = '$client_secret'
                          WHERE codigo = $codigo";
            } else {
                // Inserir
                $query = "INSERT INTO token_integracao (descricao, client_id, client_secret, ativo)
                          VALUES ('MERCADO_LIVRE', '$client_id', '$client_secret', 0)";
            }
            
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao salvar: ' . pg_last_error($conexao)]);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;
        
    case 'atualizarStatusMercadoLivre':
        try {
            if (!isset($_POST['codigo']) || !isset($_POST['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Parâmetros incompletos']);
                exit;
            }
            
            $codigo = intval($_POST['codigo']);
            $status = intval($_POST['status']);
            
            $query = "UPDATE token_integracao SET ativo = $status WHERE codigo = $codigo";
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar: ' . pg_last_error($conexao)]);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Status atualizado']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;
        
    case 'buscarConfiguracaoMercadoLivre':
        try {
            if (!isset($_POST['codigo'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Código não informado']);
                exit;
            }
            
            $codigo = intval($_POST['codigo']);
            
            $query = "SELECT * FROM token_integracao WHERE codigo = $codigo AND descricao = 'MERCADO_LIVRE'";
            $result = pg_query($conexao, $query);
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro na query: ' . pg_last_error($conexao)]);
                exit;
            }
            
            if (pg_num_rows($result) > 0) {
                $config = pg_fetch_assoc($result);
                echo json_encode(['success' => true, 'data' => $config]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Configuração não encontrada']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;
        
    case 'testarConexaoMercadoLivre':
        try {
            // Teste simples para localhost
            $query = "SELECT * FROM token_integracao WHERE descricao = 'MERCADO_LIVRE' AND ativo = 1";
            $result = pg_query($conexao, $query);

            if (!$result || pg_num_rows($result) == 0) {
                echo json_encode(['success' => false, 'message' => 'Nenhuma configuração ativa encontrada']);
                exit;
            }

            $config = pg_fetch_assoc($result);
            $access_token = $config['access_token'];

            if (empty($access_token)) {
                echo json_encode(['success' => false, 'message' => 'Token não configurado. Faça a autenticação primeiro.']);
                exit;
            }

            // Verificar se token está válido (simples)
            $tokenAge = time() - intval($config['token_created_at'] ?? 0);
            $expiresIn = intval($config['expires_in'] ?? 21600);
            $isValid = $tokenAge < ($expiresIn - 300); // 5 min de margem

            if (!$isValid) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token expirado. Use ngrok para renovação automática ou re-autentique.',
                    'token_age_minutes' => round($tokenAge / 60),
                    'expires_in_minutes' => round($expiresIn / 60)
                ]);
                exit;
            }

            // Testar API do ML
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
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $curlError]);
                exit;
            }

            if ($httpCode == 200) {
                $userData = json_decode($response, true);
                $nickname = $userData['nickname'] ?? 'Usuário';
                $timeLeft = $expiresIn - $tokenAge;
                $message = "Conectado como: $nickname (Token válido por " . round($timeLeft / 60) . " min)";
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => "Erro HTTP $httpCode. Token pode estar inválido."]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;

    case 'renovarTokenMercadoLivre':
        try {
            // Incluir script de renovação manual
            include 'mercadolivre/refresh_token_manual.php';

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Exceção: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Request não reconhecido: ' . $request]);
        break;
}
?>
