<?php
/**
 * Script para verificar e corrigir as permissões dos arquivos no diretório da Nuvemshop
 */

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para obter o usuário do servidor web
function getWebServerUser() {
    // No macOS, o usuário do Apache geralmente é _www
    if (PHP_OS === 'Darwin') {
        return '_www';
    }
    
    // No Linux, pode ser www-data, apache, nginx, etc.
    $possibleUsers = ['www-data', 'apache', 'nginx', 'httpd', 'nobody'];
    
    // Verificar o processo atual
    $currentUser = posix_getpwuid(posix_geteuid());
    if ($currentUser && isset($currentUser['name'])) {
        return $currentUser['name'];
    }
    
    // Tentar obter do arquivo de configuração do Apache
    if (function_exists('shell_exec')) {
        $apacheUser = trim(shell_exec('ps aux | grep -E "apache|httpd" | grep -v root | head -1 | cut -d " " -f1'));
        if (!empty($apacheUser) && !in_array($apacheUser, ['grep', 'ps'])) {
            return $apacheUser;
        }
    }
    
    // Retornar um usuário padrão
    return $possibleUsers[0];
}

// Função para verificar e corrigir permissões
function verificarCorrigirPermissoes($diretorio, $webServerUser) {
    echo "<h2>Verificando permissões em: $diretorio</h2>";
    
    // Verificar se o diretório existe
    if (!is_dir($diretorio)) {
        echo "<p class='error'>O diretório não existe: $diretorio</p>";
        return false;
    }
    
    // Verificar permissões do diretório
    $dirPerms = substr(sprintf('%o', fileperms($diretorio)), -4);
    echo "<p>Permissões do diretório: $dirPerms</p>";
    
    // Verificar se o diretório é gravável
    if (!is_writable($diretorio)) {
        echo "<p class='warning'>O diretório não é gravável: $diretorio</p>";
        
        // Tentar corrigir as permissões
        if (function_exists('chmod')) {
            echo "<p>Tentando corrigir permissões do diretório...</p>";
            if (chmod($diretorio, 0755)) {
                echo "<p class='success'>Permissões do diretório corrigidas para 0755</p>";
            } else {
                echo "<p class='error'>Falha ao corrigir permissões do diretório</p>";
            }
        }
    } else {
        echo "<p class='success'>O diretório é gravável</p>";
    }
    
    // Verificar permissões dos arquivos
    $arquivos = scandir($diretorio);
    echo "<h3>Arquivos no diretório:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Arquivo</th><th>Permissões</th><th>Proprietário</th><th>Gravável</th><th>Ação</th></tr>";
    
    foreach ($arquivos as $arquivo) {
        if ($arquivo === '.' || $arquivo === '..') {
            continue;
        }
        
        $caminhoCompleto = $diretorio . '/' . $arquivo;
        $perms = substr(sprintf('%o', fileperms($caminhoCompleto)), -4);
        $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($caminhoCompleto))['name'] : 'desconhecido';
        $gravavel = is_writable($caminhoCompleto) ? 'Sim' : 'Não';
        
        echo "<tr>";
        echo "<td>$arquivo</td>";
        echo "<td>$perms</td>";
        echo "<td>$owner</td>";
        echo "<td>$gravavel</td>";
        
        if (!is_writable($caminhoCompleto)) {
            echo "<td>";
            if (function_exists('chmod')) {
                if (chmod($caminhoCompleto, 0644)) {
                    echo "<span class='success'>Permissões corrigidas para 0644</span>";
                } else {
                    echo "<span class='error'>Falha ao corrigir permissões</span>";
                }
            } else {
                echo "<span class='warning'>Função chmod não disponível</span>";
            }
            echo "</td>";
        } else {
            echo "<td><span class='success'>OK</span></td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Criar um arquivo de teste para verificar permissões de escrita
    $arquivoTeste = $diretorio . '/teste_permissao.txt';
    echo "<h3>Teste de criação de arquivo</h3>";
    
    try {
        $conteudo = "Teste de permissão: " . date('Y-m-d H:i:s');
        if (file_put_contents($arquivoTeste, $conteudo)) {
            echo "<p class='success'>Arquivo de teste criado com sucesso: $arquivoTeste</p>";
            
            // Remover o arquivo de teste
            if (unlink($arquivoTeste)) {
                echo "<p class='success'>Arquivo de teste removido com sucesso</p>";
            } else {
                echo "<p class='warning'>Não foi possível remover o arquivo de teste</p>";
            }
        } else {
            echo "<p class='error'>Não foi possível criar o arquivo de teste</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erro ao testar permissões: " . $e->getMessage() . "</p>";
    }
    
    return true;
}

// Obter o diretório atual
$diretorioAtual = __DIR__;

// Obter o usuário do servidor web
$webServerUser = getWebServerUser();

// Exibir informações do sistema
echo "<!DOCTYPE html>
<html>
<head>
    <title>Verificação de Permissões</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .info { background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Verificação de Permissões</h1>
    
    <div class='info'>
        <h2>Informações do Sistema</h2>
        <p><strong>Sistema Operacional:</strong> " . PHP_OS . "</p>
        <p><strong>Usuário do Servidor Web:</strong> " . $webServerUser . "</p>
        <p><strong>Usuário PHP Atual:</strong> " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'desconhecido') . "</p>
        <p><strong>Diretório Atual:</strong> " . $diretorioAtual . "</p>
        <p><strong>Diretório Temporário:</strong> " . sys_get_temp_dir() . " (Gravável: " . (is_writable(sys_get_temp_dir()) ? 'Sim' : 'Não') . ")</p>
        <p><strong>Diretório de Logs do PHP:</strong> " . ini_get('error_log') . "</p>
    </div>";

// Verificar e corrigir permissões
verificarCorrigirPermissoes($diretorioAtual, $webServerUser);

// Instruções para corrigir permissões manualmente
echo "<h2>Instruções para Corrigir Permissões Manualmente</h2>
<p>Se o script não conseguir corrigir as permissões automaticamente, você pode tentar os seguintes comandos no terminal:</p>
<pre>
# Para dar permissões de escrita ao diretório
chmod 755 $diretorioAtual

# Para dar permissões de escrita a todos os arquivos PHP
chmod 644 $diretorioAtual/*.php

# Para dar permissões de escrita a um arquivo específico
chmod 644 $diretorioAtual/error_log.txt

# Se você souber o usuário do servidor web, pode mudar o proprietário
# Substitua 'www-data' pelo usuário do seu servidor web
chown $webServerUser:$webServerUser $diretorioAtual
chown $webServerUser:$webServerUser $diretorioAtual/*.php
</pre>

<p><a href='javascript:history.back()'>Voltar</a></p>
</body>
</html>";
?>
