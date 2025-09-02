<?php
/**
 * Mostra logs de debug das imagens
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== LOGS DE DEBUG - SISTEMA DE IMAGENS ===\n\n";

// Tentar diferentes locais de log
$possibleLogs = [
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\apache\logs\error.log',
    '/var/log/php_errors.log',
    '/tmp/php_errors.log',
    ini_get('error_log')
];

$logFound = false;

foreach ($possibleLogs as $logPath) {
    if (file_exists($logPath)) {
        echo "📄 Arquivo de log encontrado: $logPath\n\n";
        
        // Ler últimas 50 linhas
        $lines = file($logPath);
        $totalLines = count($lines);
        $startLine = max(0, $totalLines - 50);
        
        echo "Últimas " . ($totalLines - $startLine) . " linhas:\n";
        echo str_repeat("-", 80) . "\n";
        
        for ($i = $startLine; $i < $totalLines; $i++) {
            $line = $lines[$i];
            // Filtrar apenas linhas relacionadas ao debug de imagens
            if (strpos($line, 'DEBUG parseImageFilename') !== false || 
                strpos($line, 'MLImageManager') !== false ||
                strpos($line, 'image') !== false) {
                echo $line;
            }
        }
        
        $logFound = true;
        break;
    }
}

if (!$logFound) {
    echo "❌ Nenhum arquivo de log encontrado nos locais:\n";
    foreach ($possibleLogs as $logPath) {
        echo "- $logPath\n";
    }
    
    echo "\nConfiguração atual do PHP:\n";
    echo "log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
    echo "error_log: " . ini_get('error_log') . "\n";
    echo "display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Atualizado em: " . date('Y-m-d H:i:s') . "\n";
echo "Recarregue após executar o teste de imagens.\n";
?>
