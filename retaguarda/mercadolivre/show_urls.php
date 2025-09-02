<!DOCTYPE html>
<html>
<head>
    <title>URLs Mercado Livre</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .url-box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .url { font-family: monospace; background: white; padding: 10px; border-radius: 3px; word-break: break-all; }
        .environment { color: #666; font-size: 14px; }
        .copy-btn { background: #007cba; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
    <?php
    include 'ml_config.php';
    $config = getMLEnvironmentConfig();
    ?>
    
    <h1>🔗 URLs para Configurar no Mercado Livre</h1>
    
    <div class="environment">
        <strong>Ambiente atual:</strong> <?= $config['environment'] ?> 
        (<?= $config['domain'] ?>)
    </div>
    
    <div class="url-box">
        <h3>📥 Redirect URI (Callback)</h3>
        <div class="url" id="callback-url"><?= $config['callback_url'] ?></div>
        <button class="copy-btn" onclick="copyToClipboard('callback-url')">Copiar</button>
    </div>
    
    <div class="url-box">
        <h3>🔔 Notification URL (Webhook)</h3>
        <div class="url" id="webhook-url"><?= $config['webhook_url'] ?></div>
        <button class="copy-btn" onclick="copyToClipboard('webhook-url')">Copiar</button>
    </div>
    
    <div class="url-box">
        <h3>📋 Para alterar o ambiente:</h3>
        <p>Edite o arquivo: <code>retaguarda/mercadolivre/ml_config.php</code></p>
        <p>Altere apenas a linha: <code>define('ML_DOMAIN', 'SUA_URL_AQUI');</code></p>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                alert('URL copiada para a área de transferência!');
            }).catch(function(err) {
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('URL copiada para a área de transferência!');
            });
        }
    </script>
</body>
</html>
