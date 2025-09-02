<?php
/**
 * Script para configurar tabelas de medidas
 * Execute uma vez para criar a tabela no banco
 */

require_once '../conexao.php';
require_once 'size_chart_manager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    echo "=== SETUP SIZE CHARTS ===\n";
    
    // Criar tabela no banco
    $created = createSizeChartTable();
    
    if ($created) {
        echo "✅ Tabela ml_size_charts criada/verificada com sucesso\n";
        
        echo json_encode([
            'success' => true,
            'message' => 'Setup de tabelas de medidas concluído'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar tabela'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>