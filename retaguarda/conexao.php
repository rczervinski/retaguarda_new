<?php

// Desabilitar exibição de erros para evitar interferir no JSON
error_reporting(0);
ini_set('display_errors', 0);

$host = "postgresql-198445-0.cloudclusters.net";
$port = "19627";
$database = "01414955000158";
$user = "u01414955000158";
$password = "014158@@";

// String de conexão
$conn_string = "host={$host} port={$port} dbname={$database} user={$user} password={$password}";

// Tenta conectar
$conexao = pg_connect($conn_string);

// Verificar se a conexão foi bem-sucedida
if (!$conexao) {
    // Log do erro para depuração
    error_log("Erro de conexão PostgreSQL: " . pg_last_error());
}

?>