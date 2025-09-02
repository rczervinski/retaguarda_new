<?php
include __DIR__ . '/../conexao.php';

$sql = file_get_contents(__DIR__ . '/create_ml_error_learning.sql');
$result = pg_query($conexao, $sql);

if ($result) {
    echo 'Tabela ml_error_learning criada com sucesso!';
} else {
    echo 'Erro: ' . pg_last_error($conexao);
}
?>
