<?php
/**
 * Debug do endpoint de categoria
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$categoryId = $_GET['category_id'] ?? '';

echo json_encode([
    'debug' => true,
    'category_id' => $categoryId,
    'message' => 'Debug endpoint funcionando',
    'timestamp' => date('Y-m-d H:i:s'),
    'files_exist' => [
        'token_manager' => file_exists('token_manager.php'),
        'dynamic_mapper' => file_exists('dynamic_category_mapper.php'),
        'category_mapper' => file_exists('category_mapper.php'),
        'conexao' => file_exists('../conexao.php')
    ]
], JSON_PRETTY_PRINT);
?>
