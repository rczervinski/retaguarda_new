<?php
include __DIR__ . '/../conexao.php';

$sql = file_get_contents(__DIR__ . '/add_categoria_ml_field.sql');
$result = pg_query($conexao, $sql);

if ($result) {
    echo 'Campo categoria_ml criado com sucesso!';
} else {
    echo 'Erro: ' . pg_last_error($conexao);
}
?>
