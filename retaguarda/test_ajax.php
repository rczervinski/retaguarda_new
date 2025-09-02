<?php
// Teste simples para verificar se o AJAX está funcionando
header('Content-Type: application/json; charset=utf-8');

// Simular uma requisição POST para testar
$_POST['request'] = 'fetchall';
$_POST['pagina'] = 0;
$_POST['desc_pesquisa'] = '';

// Incluir o arquivo AJAX
include 'produtos_ajax.php';
?>
