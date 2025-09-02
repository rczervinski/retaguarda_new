// Script para monitorar redirecionamentos e recarregamentos
(function() {
  // Armazenar a URL original
  var originalUrl = window.location.href;
  
  // Monitorar mudanças na URL
  setInterval(function() {
    if (window.location.href !== originalUrl) {
      console.error("Redirecionamento detectado: " + window.location.href);
      // Registrar o redirecionamento
      $.post('debug_ajax.php', {
        action: 'redirect_detected',
        from: originalUrl,
        to: window.location.href
      });
      // Atualizar a URL original
      originalUrl = window.location.href;
    }
  }, 500);
  
  // Monitorar eventos de beforeunload
  window.addEventListener('beforeunload', function(e) {
    console.log("Evento beforeunload detectado");
    // Registrar o evento
    $.post('debug_ajax.php', {
      action: 'beforeunload_detected'
    });
  });
})();