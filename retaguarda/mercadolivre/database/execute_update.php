<?php
include __DIR__ . '/../../conexao.php';

$sql = file_get_contents(__DIR__ . '/update_token_integracao.sql');
$result = pg_query($conexao, $sql);

if ($result) {
    echo 'Tabela atualizada com sucesso!';
} else {
    echo 'Erro: ' . pg_last_error($conexao);
}
?>
