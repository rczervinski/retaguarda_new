<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificação e Correção de Permissões</h1>";

// Diretório atual
$currentDir = dirname(__FILE__);
echo "<p>Diretório atual: " . $currentDir . "</p>";

// Verificar permissões do diretório
$dirPerms = substr(sprintf('%o', fileperms($currentDir)), -4);
echo "<p>Permissões do diretório atual: " . $dirPerms . "</p>";

// Criar diretório de logs se não existir
$logDir = $currentDir . '/logs';
if (!is_dir($logDir)) {
    echo "<p>Tentando criar diretório de logs...</p>";
    if (mkdir($logDir, 0777, true)) {
        echo "<p style='color:green'>✅ Diretório de logs criado com sucesso!</p>";
    } else {
        echo "<p style='color:red'>❌ Falha ao criar diretório de logs!</p>";
    }
} else {
    echo "<p>Diretório de logs já existe.</p>";
}

// Verificar permissões do diretório de logs
if (is_dir($logDir)) {
    $logDirPerms = substr(sprintf('%o', fileperms($logDir)), -4);
    echo "<p>Permissões do diretório de logs: " . $logDirPerms . "</p>";

    // Tentar alterar permissões
    echo "<p>Tentando alterar permissões do diretório de logs...</p>";
    if (chmod($logDir, 0777)) {
        echo "<p style='color:green'>✅ Permissões do diretório de logs alteradas com sucesso!</p>";
    } else {
        echo "<p style='color:red'>❌ Falha ao alterar permissões do diretório de logs!</p>";
    }
}

// Testar criação de arquivo de log
$logFile = $logDir . '/nuvemshop_log.txt';
echo "<p>Tentando criar/escrever no arquivo de log...</p>";
$fp = @fopen($logFile, 'a');
if ($fp) {
    fwrite($fp, "Teste de escrita: " . date('Y-m-d H:i:s') . "\n");
    fclose($fp);
    echo "<p style='color:green'>✅ Arquivo de log criado/escrito com sucesso!</p>";

    // Verificar permissões do arquivo
    $filePerms = substr(sprintf('%o', fileperms($logFile)), -4);
    echo "<p>Permissões do arquivo de log: " . $filePerms . "</p>";

    // Tentar alterar permissões
    echo "<p>Tentando alterar permissões do arquivo de log...</p>";
    if (chmod($logFile, 0666)) {
        echo "<p style='color:green'>✅ Permissões do arquivo de log alteradas com sucesso!</p>";
    } else {
        echo "<p style='color:red'>❌ Falha ao alterar permissões do arquivo de log!</p>";
    }
} else {
    echo "<p style='color:red'>❌ Falha ao criar/escrever no arquivo de log!</p>";
}

// Verificar se o arquivo nuvemshop/nuvemshop_proxy.php existe
if (file_exists($currentDir . '/nuvemshop/nuvemshop_proxy.php')) {
    echo "<p>✅ O arquivo nuvemshop/nuvemshop_proxy.php existe.</p>";

    // Verificar permissões
    $proxyPerms = substr(sprintf('%o', fileperms($currentDir . '/nuvemshop/nuvemshop_proxy.php')), -4);
    echo "<p>Permissões do arquivo nuvemshop/nuvemshop_proxy.php: " . $proxyPerms . "</p>";
} else {
    echo "<p style='color:red'>❌ O arquivo nuvemshop/nuvemshop_proxy.php NÃO existe!</p>";
}

// Verificar extensões PHP necessárias
if (function_exists('curl_init')) {
    echo "<p>✅ A extensão cURL está habilitada.</p>";
} else {
    echo "<p style='color:red'>❌ A extensão cURL NÃO está habilitada!</p>";
}

if (function_exists('json_encode')) {
    echo "<p>✅ A extensão JSON está habilitada.</p>";
} else {
    echo "<p style='color:red'>❌ A extensão JSON NÃO está habilitada!</p>";
}

echo "<h2>Instruções para correção manual</h2>";
echo "<p>Se os problemas persistirem, execute os seguintes comandos no terminal:</p>";
echo "<pre>
cd " . $currentDir . "
mkdir -p logs
chmod 777 logs
touch logs/nuvemshop_log.txt
chmod 666 logs/nuvemshop_log.txt
</pre>";

echo "<p>Ou, se estiver usando Windows, abra o prompt de comando como administrador e execute:</p>";
echo "<pre>
cd " . $currentDir . "
mkdir logs
</pre>";
echo "<p>Em seguida, clique com o botão direito na pasta 'logs', vá em Propriedades > Segurança e dê permissões completas para o usuário do servidor web.</p>";
?>