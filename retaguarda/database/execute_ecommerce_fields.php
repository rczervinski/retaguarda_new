<?php
include __DIR__ . '/../conexao.php';

$sql = file_get_contents(__DIR__ . '/add_ecommerce_fields.sql');
$result = pg_query($conexao, $sql);

if ($result) {
    echo 'Campos de e-commerce criados com sucesso!';
} else {
    echo 'Erro: ' . pg_last_error($conexao);
}
?>
