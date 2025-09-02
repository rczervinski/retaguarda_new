<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$host = $_POST['host'] ?? '';
$port = $_POST['port'] ?? '5432';
$database = $_POST['database'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($action) || empty($host) || empty($database) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios não fornecidos']);
    exit;
}

function connectToDatabase($host, $port, $database, $username, $password) {
    try {
        $connectionString = "host=$host port=$port dbname=$database user=$username password=$password";
        $connection = pg_connect($connectionString);
        
        if (!$connection) {
            return [false, 'Falha ao conectar com o banco de dados'];
        }
        
        return [$connection, 'Conexão estabelecida com sucesso'];
    } catch (Exception $e) {
        return [false, 'Erro: ' . $e->getMessage()];
    }
}

switch ($action) {
    case 'test_connection':
        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);
        
        if ($connection) {
            // Testa a conexão fazendo uma query simples
            $result = pg_query($connection, "SELECT 1 as test");
            
            if ($result) {
                pg_free_result($result);
                pg_close($connection);
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query de teste']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;
        
    case 'get_categories':
        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Query para buscar apenas categorias principais
            $query = "SELECT DISTINCT categoria FROM produtos_ib WHERE categoria IS NOT NULL ORDER BY categoria";
            $result = pg_query($connection, $query);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query: ' . pg_last_error($connection)]);
                exit;
            }

            $categories = [];
            while ($row = pg_fetch_assoc($result)) {
                $categories[] = $row['categoria'];
            }

            pg_free_result($result);
            pg_close($connection);

            echo json_encode(['success' => true, 'data' => $categories]);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'get_products_by_category':
        $categoria = $_POST['categoria'] ?? '';

        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Query para buscar produtos por categoria apenas
            $query = "
                SELECT
                    ib.codigo_interno as codigoInterno,
                    p.codigo_gtin as codigoGtin,
                    p.descricao,
                    ib.descricao_detalhada as descricaoDetalhada,
                    ib.grupo,
                    ib.categoria,
                    ib.preco_venda as precoVenda,
                    COALESCE(ou.qtde, 0) as qtde
                FROM produtos_ib ib
                LEFT JOIN produtos p ON ib.codigo_interno = p.codigo_interno
                LEFT JOIN produtos_ou ou ON ib.codigo_interno = ou.codigo_interno
                WHERE ib.categoria = $1
                ORDER BY ib.descricao_detalhada
            ";

            $result = pg_query_params($connection, $query, [$categoria]);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query: ' . pg_last_error($connection)]);
                exit;
            }

            $products = [];
            while ($row = pg_fetch_assoc($result)) {
                $products[] = [
                    'codigoInterno' => (int)$row['codigointerno'],
                    'codigoGtin' => $row['codigogtin'] ?? '',
                    'descricao' => $row['descricao'] ?? '',
                    'descricaoDetalhada' => $row['descricaodetalhada'] ?? '',
                    'grupo' => $row['grupo'] ?? '',
                    'categoria' => $row['categoria'] ?? '',
                    'precoVenda' => (float)$row['precovenda'],
                    'qtde' => (float)$row['qtde']
                ];
            }

            pg_free_result($result);
            pg_close($connection);

            echo json_encode(['success' => true, 'data' => $products]);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'save_order':
        $orderData = $_POST['order_data'] ?? '';

        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Decodifica os dados do pedido
            $order = json_decode($orderData, true);

            if (!$order) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Dados do pedido inválidos']);
                exit;
            }

            $items = $order['items'] ?? [];
            $customerName = $order['customerName'] ?? null;
            $table = $order['table'] ?? 1;
            $observation = $order['observation'] ?? '';

            $success = true;
            $errorMsg = '';

            // Inicia transação
            pg_query($connection, "BEGIN");

            try {
                foreach ($items as $index => $item) {
                    $produto = $item['product'];
                    $quantidade = $item['quantidade'];
                    $total = $produto['precoVenda'] * $quantidade;

                    $insertQuery = "
                        INSERT INTO pedidos_terminal
                        (comanda, operador, data, hora, codigo_gtin, valor, qtde, total, status, obs, impresso, atendido, item, mesa, nome)
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)
                    ";

                    $params = [
                        $table, // comanda
                        1, // operador
                        date('Y-m-d'), // data
                        date('H:i:s'), // hora
                        $produto['codigoGtin'], // codigo_gtin
                        $produto['precoVenda'], // valor
                        $quantidade, // qtde
                        $total, // total
                        0, // status
                        $observation, // obs
                        0, // impresso
                        0, // atendido
                        $index + 1, // item
                        $table, // mesa
                        $customerName // nome
                    ];

                    $result = pg_query_params($connection, $insertQuery, $params);

                    if (!$result) {
                        throw new Exception('Erro ao inserir item: ' . pg_last_error($connection));
                    }
                }

                // Confirma transação
                pg_query($connection, "COMMIT");

            } catch (Exception $e) {
                // Desfaz transação
                pg_query($connection, "ROLLBACK");
                $success = false;
                $errorMsg = $e->getMessage();
            }

            pg_close($connection);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Pedido salvo com sucesso']);
            } else {
                echo json_encode(['success' => false, 'error' => $errorMsg]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'get_recent_customers':
        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Busca clientes e comandas com pedidos não impressos (não pagos)
            $query = "
                SELECT DISTINCT nome, comanda
                FROM pedidos_terminal
                WHERE impresso = 0
                AND nome IS NOT NULL
                AND nome != ''
                ORDER BY nome
            ";

            $result = pg_query($connection, $query);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query: ' . pg_last_error($connection)]);
                exit;
            }

            $customers = [];
            while ($row = pg_fetch_assoc($result)) {
                $customers[] = [
                    'nome' => $row['nome'],
                    'comanda' => (int)$row['comanda']
                ];
            }

            pg_free_result($result);
            pg_close($connection);

            echo json_encode(['success' => true, 'data' => $customers]);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'get_all_orders':
        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Busca apenas pedidos não impressos (não pagos)
            $query = "
                SELECT codigo, comanda, data, hora, codigo_gtin, valor, qtde, total, nome, obs
                FROM pedidos_terminal
                WHERE impresso = 0
                ORDER BY data DESC, hora DESC
            ";

            $result = pg_query($connection, $query);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query: ' . pg_last_error($connection)]);
                exit;
            }

            $orders = [];
            while ($row = pg_fetch_assoc($result)) {
                $orders[] = [
                    'codigo' => (int)$row['codigo'],
                    'comanda' => (int)$row['comanda'],
                    'data' => $row['data'],
                    'hora' => $row['hora'],
                    'codigo_gtin' => $row['codigo_gtin'],
                    'valor' => (float)$row['valor'],
                    'qtde' => (float)$row['qtde'],
                    'total' => (float)$row['total'],
                    'nome' => $row['nome'],
                    'obs' => $row['obs']
                ];
            }

            pg_free_result($result);
            pg_close($connection);

            echo json_encode(['success' => true, 'data' => $orders]);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'get_next_table_number':
        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Busca o maior número de comanda/mesa
            $query = "SELECT COALESCE(MAX(comanda), 0) + 1 as next_table FROM pedidos_terminal";

            $result = pg_query($connection, $query);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao executar query: ' . pg_last_error($connection)]);
                exit;
            }

            $row = pg_fetch_assoc($result);
            $nextTable = (int)$row['next_table'];

            pg_free_result($result);
            pg_close($connection);

            echo json_encode(['success' => true, 'data' => $nextTable]);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    case 'mark_order_as_paid':
        $table = $_POST['table'] ?? '';

        if (empty($table)) {
            echo json_encode(['success' => false, 'error' => 'Número da mesa/comanda é obrigatório']);
            exit;
        }

        list($connection, $message) = connectToDatabase($host, $port, $database, $username, $password);

        if ($connection) {
            // Atualiza todos os itens do pedido da mesa/comanda para impresso = 1 (pago)
            $updateQuery = "UPDATE pedidos_terminal SET impresso = 1 WHERE comanda = $1 AND impresso = 0";

            $result = pg_query_params($connection, $updateQuery, [$table]);

            if (!$result) {
                pg_close($connection);
                echo json_encode(['success' => false, 'error' => 'Erro ao atualizar pedido: ' . pg_last_error($connection)]);
                exit;
            }

            $affectedRows = pg_affected_rows($result);
            pg_close($connection);

            if ($affectedRows > 0) {
                echo json_encode(['success' => true, 'message' => "Pedido da mesa/comanda $table marcado como pago ($affectedRows itens atualizados)"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Nenhum pedido encontrado para esta mesa/comanda ou já está pago']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Ação não reconhecida']);
        break;
}
?>
