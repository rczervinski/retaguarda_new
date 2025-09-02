<?php
/**
 * Mostra logs de debug da exportação ML
 */

header('Content-Type: text/plain; charset=utf-8');

// Ler logs do PHP
$phpLogFile = ini_get('error_log');
if (!$phpLogFile) {
    $phpLogFile = '/tmp/php_errors.log'; // Padrão Linux
    if (!file_exists($phpLogFile)) {
        $phpLogFile = 'C:\xampp\php\logs\php_error_log'; // Padrão XAMPP Windows
    }
}

echo "=== LOGS DE DEBUG MERCADO LIVRE ===\n\n";

if (file_exists($phpLogFile)) {
    echo "Arquivo de log: $phpLogFile\n\n";
    
    // Ler últimas 50 linhas
    $lines = file($phpLogFile);
    $totalLines = count($lines);
    $startLine = max(0, $totalLines - 50);
    
    echo "Últimas " . ($totalLines - $startLine) . " linhas:\n";
    echo str_repeat("-", 50) . "\n";
    
    for ($i = $startLine; $i < $totalLines; $i++) {
        $line = $lines[$i];
        // Filtrar apenas linhas relacionadas ao ML
        if (strpos($line, 'DEBUG EXPORTAÇÃO ML') !== false || 
            strpos($line, 'GTIN:') !== false ||
            strpos($line, 'Campo') !== false ||
            strpos($line, 'Preço') !== false ||
            strpos($line, 'JSON para ML') !== false ||
            strpos($line, 'Valores finais') !== false) {
            echo $line;
        }
    }
} else {
    echo "Arquivo de log não encontrado: $phpLogFile\n";
    echo "Logs podem estar em outro local.\n\n";
    
    // Tentar outros locais comuns
    $possibleLogs = [
        'C:\xampp\apache\logs\error.log',
        'C:\xampp\logs\php_error_log',
        '/var/log/php_errors.log',
        '/var/log/apache2/error.log'
    ];
    
    echo "Locais verificados:\n";
    foreach ($possibleLogs as $logPath) {
        echo "- $logPath: " . (file_exists($logPath) ? "EXISTE" : "não existe") . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Atualizado em: " . date('Y-m-d H:i:s') . "\n";
echo "Recarregue esta página após tentar exportar um produto.\n";
?>
