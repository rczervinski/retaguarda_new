<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("../conexao.php");

// Configurações fixas da aplicação NuvemShop
define('CLIENT_ID', '17589');
define('CLIENT_SECRET', '5173763ae2c4286107e02bfd22df1bb1a9c19898092eddf3');
define('APP_SECRET', '5173763ae2c4286107e02bfd22df1bb1a9c19898092eddf3');

// Verificar se a requisição foi enviada
if (!isset($_POST['request']) && !isset($_GET['code'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit;
}

// Se recebemos um código de autorização da NuvemShop
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // URL de redirecionamento atual (no novo servidor)
    $redirect_uri = "https://" . $_SERVER['HTTP_HOST'] . "/nuvemshop/nuvemshop_auth.php";
    
    // Trocar o código por um token de acesso
    $ch = curl_init("https://www.nuvemshop.com.br/apps/authorize/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $tokenData = json_decode($response, true);
        
        // Salvar os dados no banco
        $access_token = $tokenData['access_token'];
        $user_id = $tokenData['user_id']; // Este é o ID da loja
        
        // Verificar se já existe um registro para a NuvemShop
        $query = "SELECT * FROM token_integracao WHERE descricao = 'NUVEMSHOP' LIMIT 1";
        $result = pg_query($conexao, $query);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $codigo = $row['codigo'];
            
            // Atualizar registro existente
            $query = "UPDATE token_integracao SET 
                      access_token = '$access_token',
                      code = '$code',
                      client_id = '" . CLIENT_ID . "',
                      client_secret = '" . CLIENT_SECRET . "',
                      url_checkout = 'https://" . $tokenData['store_url'] . "',
                      ativo = 1
                      WHERE codigo = $codigo";
        } else {
            // Inserir novo registro
            $query = "INSERT INTO token_integracao (descricao, client_id, client_secret, access_token, code, url_checkout, ativo) 
                      VALUES ('NUVEMSHOP', '" . CLIENT_ID . "', '" . CLIENT_SECRET . "', '$access_token', '$code', 'https://" . $tokenData['store_url'] . "', 1)";
        }
        
        $result = pg_query($conexao, $query);
        
        // Salvar os dados na sessão para verificação posterior
        $_SESSION['nuvemshop_auth'] = [
            'authenticated' => true,
            'data' => $tokenData
        ];
        
        // Exibir página de sucesso
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Autenticação NuvemShop</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
                .success { color: green; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 class='success'>Autenticação realizada com sucesso!</h2>
                <p>Sua loja NuvemShop foi conectada com sucesso.</p>
                <p>Você pode fechar esta janela e retornar ao sistema.</p>
            </div>
            <script>
                // Fechar a janela automaticamente após 5 segundos
                setTimeout(function() {
                    window.close();
                }, 5000);
            </script>
        </body>
        </html>";
    } else {
        // Erro ao obter token
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Erro na Autenticação</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
                .error { color: red; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                pre { text-align: left; background: #f5f5f5; padding: 10px; overflow: auto; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 class='error'>Erro na autenticação</h2>
                <p>Ocorreu um erro ao autenticar com a NuvemShop.</p>
                <p>Código HTTP: $httpCode</p>
                <p>Erro cURL: $error</p>
                <pre>$response</pre>
                <p>Você pode fechar esta janela e tentar novamente.</p>
            </div>
        </body>
        </html>";
    }
    exit;
}

// Processar requisições AJAX
$request = $_POST['request'];

switch ($request) {
    case 'getAuthUrl':
        // URL de redirecionamento atual (no novo servidor)
        $redirect_uri = "https://" . $_SERVER['HTTP_HOST'] . "/nuvemshop/nuvemshop_auth.php";
        
        // Construir URL de autenticação
        $auth_url = "https://www.nuvemshop.com.br/apps/authorize?" . 
                    "client_id=" . CLIENT_ID . "&" .
                    "redirect_uri=" . urlencode($redirect_uri) . "&" .
                    "scope=read_products write_products write_orders read_orders&" .
                    "response_type=code";
        
        echo json_encode(['success' => true, 'auth_url' => $auth_url]);
        break;
        
    case 'checkAuthStatus':
        if (isset($_SESSION['nuvemshop_auth']) && $_SESSION['nuvemshop_auth']['authenticated']) {
            echo json_encode([
                'success' => true, 
                'authenticated' => true,
                'data' => $_SESSION['nuvemshop_auth']['data']
            ]);
            
            // Limpar a sessão após retornar os dados
            unset($_SESSION['nuvemshop_auth']);
        } else {
            echo json_encode(['success' => true, 'authenticated' => false]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Requisição desconhecida']);
        break;
}
?>
