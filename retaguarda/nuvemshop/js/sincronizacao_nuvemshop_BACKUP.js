/**
 * Funções para sincronização com a Nuvemshop
 */

// Mapa para armazenar a relação entre códigos internos e IDs da Nuvemshop
// Usar window para evitar declaração duplicada
window.mapaProdutosNuvemshop = window.mapaProdutosNuvemshop || {};

// Variáveis para controle do timer automático
window.timerSincronizacao = window.timerSincronizacao || null;
let proximaSincronizacao = null;
const INTERVALO_SINCRONIZACAO = 60 * 60 * 1000; // 1 hora em milissegundos

/**
 * Sincroniza o status e estoque dos produtos com a Nuvemshop
 * Pode ser chamada manualmente ou automaticamente pelo timer
 */
function sincronizarStatusProdutosNuvemshop(automatico = false) {
    // Mostrar indicador de carregamento
    $("#loading").show();

    // Mostrar mensagem de sincronização
    const tipoSincronizacao = automatico ? 'automática' : 'manual';
    const icone = automatico ? 'schedule' : 'sync';
    Materialize.toast(`<i class="material-icons">${icone}</i> Sincronização ${tipoSincronizacao} - status e estoque com a Nuvemshop...`, 4000, automatico ? 'orange' : 'blue');

    // Criar indicador de progresso se não existir
    if (!document.getElementById('sync-progress-container')) {
        const progressHtml = `
            <div id="sync-progress-container" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                 background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                 z-index: 9999; min-width: 400px; text-align: center; display: none;">
                <h5 style="margin-top: 0;">Sincronizando Produtos</h5>
                <div id="sync-progress-bar" style="width: 100%; background-color: #e0e0e0; border-radius: 4px; height: 20px; margin: 10px 0;">
                    <div id="sync-progress-fill" style="height: 100%; background-color: #2196F3; border-radius: 4px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <div id="sync-progress-text">Iniciando sincronização...</div>
                <div id="sync-progress-details" style="font-size: 12px; color: #666; margin-top: 10px;"></div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', progressHtml);
    }

    // Mostrar indicador de progresso
    document.getElementById('sync-progress-container').style.display = 'block';
    document.getElementById('sync-progress-fill').style.width = '10%';
    document.getElementById('sync-progress-text').textContent = 'Conectando com a Nuvemshop...';
    document.getElementById('sync-progress-details').textContent = 'Preparando sincronização';

    // Primeiro, fazer um teste simples para verificar se o endpoint funciona
    console.log("🧪 Testando endpoint básico primeiro...");
    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: {
            request: 'testeSincronizacao'
        },
        dataType: 'json',
        timeout: 10000, // 10 segundos para o teste
        success: function(response) {
            console.log("✅ Teste básico funcionou:", response);

            // Atualizar progresso
            document.getElementById('sync-progress-fill').style.width = '20%';
            document.getElementById('sync-progress-text').textContent = 'Iniciando sincronização...';

            // Agora chamar a sincronização real
            iniciarSincronizacaoReal();
        },
        error: function(xhr, status, error) {
            console.error("❌ Teste básico falhou:", status, error);
            console.error("Response:", xhr.responseText);

            $("#loading").hide();
            const progressContainer = document.getElementById('sync-progress-container');
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }

            Materialize.toast('<i class="material-icons">error</i> Erro no teste básico: ' + error, 5000, 'red');
        }
    });
}

function iniciarSincronizacaoReal() {
    console.log("🚀 Iniciando sincronização real...");

    // Chamar sincronização completa (status + estoque)
    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: {
            request: 'sincronizarStatusEEstoque'
        },
        dataType: 'json',
        timeout: 300000, // 5 minutos de timeout
        success: function(response) {
            $("#loading").hide();

            // Esconder indicador de progresso
            const progressContainer = document.getElementById('sync-progress-container');
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }

            if (response.success) {
                console.log("Status e estoque dos produtos sincronizados com sucesso");
                console.log("Detalhes da sincronização:", response);

                // Mostrar resultado da sincronização
                let mensagem = '';
                if (response.status_atualizados > 0 || response.estoque_atualizados > 0) {
                    let partes = [];
                    if (response.status_atualizados > 0) {
                        partes.push(response.status_atualizados + ' status atualizados');
                    }
                    if (response.estoque_atualizados > 0) {
                        partes.push(response.estoque_atualizados + ' estoques sincronizados');
                    }
                    mensagem = partes.join(', ');
                    Materialize.toast('<i class="material-icons">info</i> ' + mensagem, 5000, 'green');
                } else {
                    Materialize.toast('<i class="material-icons">check_circle</i> Status e estoque já estão sincronizados', 4000, 'blue');
                }

                // Mostrar log detalhado no console
                if (response.log) {
                    console.log("Log da sincronização:");
                    if (Array.isArray(response.log)) {
                        response.log.forEach(function(logEntry) {
                            console.log("- " + logEntry);
                        });
                    } else {
                        console.log("Log (não é array):", response.log);
                    }
                }

                // Recarregar a tabela de produtos
                const val = document.getElementById('desc_pesquisa').value || '';
                $.ajax({
                    url: 'produtos_ajax.php',
                    type: 'post',
                    data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
                    dataType: 'json',
                    success: function(response) {
                        createRows(response);
                    }
                });
            } else {
                console.error("Erro ao sincronizar status e estoque dos produtos:", response.error);
                Materialize.toast('<i class="material-icons">error</i> Erro ao sincronizar status e estoque: ' + response.error, 5000, 'red');

                // Mostrar log de erro no console
                if (response.log) {
                    console.log("Log de erro:");
                    if (Array.isArray(response.log)) {
                        response.log.forEach(function(logEntry) {
                            console.log("- " + logEntry);
                        });
                    } else {
                        console.log("Log de erro (não é array):", response.log);
                    }
                }
            }
        },
        error: function(xhr, status, error) {
            $("#loading").hide();

            // Esconder indicador de progresso
            const progressContainer = document.getElementById('sync-progress-container');
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }

            let errorMessage = 'Erro desconhecido';
            let detailedError = '';

            if (status === 'timeout') {
                errorMessage = 'Timeout - A sincronização demorou muito para responder';
                detailedError = 'A sincronização pode estar processando muitos produtos. Tente novamente ou contate o suporte.';
            } else if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    errorMessage = errorResponse.error || 'Erro no servidor';
                    detailedError = `Arquivo: ${errorResponse.file || 'N/A'}, Linha: ${errorResponse.line || 'N/A'}`;
                } catch (e) {
                    errorMessage = 'Erro de comunicação com o servidor';
                    detailedError = xhr.responseText.substring(0, 200) + (xhr.responseText.length > 200 ? '...' : '');
                }
            } else {
                errorMessage = `Erro ${xhr.status}: ${error}`;
                detailedError = `Status: ${status}, Código HTTP: ${xhr.status}`;
            }

            console.error("Erro ao sincronizar status e estoque dos produtos:");
            console.error("- Mensagem:", errorMessage);
            console.error("- Detalhes:", detailedError);
            console.error("- Status:", status);
            console.error("- Resposta completa:", xhr.responseText);

            Materialize.toast(`<i class="material-icons">error</i> ${errorMessage}`, 8000, 'red');

            // Mostrar detalhes em um modal ou alert se necessário
            if (detailedError) {
                console.log("Detalhes do erro:", detailedError);
            }
        }
    });

    // Reiniciar timer após sincronização (manual ou automática)
    reiniciarTimerSincronizacao();
}

/**
 * Inicia o timer automático de sincronização
 */
function iniciarTimerSincronizacao() {
    // Limpar timer existente se houver
    if (window.timerSincronizacao) {
        clearInterval(window.timerSincronizacao);
    }

    // Calcular próxima sincronização
    proximaSincronizacao = new Date(Date.now() + INTERVALO_SINCRONIZACAO);

    // Configurar novo timer
    window.timerSincronizacao = setInterval(function() {
        console.log('🕐 Timer automático: Iniciando sincronização...');
        sincronizarStatusProdutosNuvemshop(true); // true = automático
    }, INTERVALO_SINCRONIZACAO);

    // Atualizar interface
    atualizarStatusTimer();

    console.log('⏰ Timer de sincronização iniciado. Próxima sincronização:', proximaSincronizacao.toLocaleString('pt-BR'));
}

/**
 * Para o timer automático de sincronização
 */
function pararTimerSincronizacao() {
    if (window.timerSincronizacao) {
        clearInterval(window.timerSincronizacao);
        window.timerSincronizacao = null;
        proximaSincronizacao = null;

        // Atualizar interface
        atualizarStatusTimer();

        console.log('⏹️ Timer de sincronização parado');
        Materialize.toast('<i class="material-icons">pause</i> Sincronização automática desativada', 3000, 'orange');
    }
}

/**
 * Reinicia o timer (usado após sincronização manual ou automática)
 */
function reiniciarTimerSincronizacao() {
    if (window.timerSincronizacao) {
        iniciarTimerSincronizacao();
        console.log('🔄 Timer de sincronização reiniciado');
    }
}

/**
 * Atualiza o status do timer na interface
 */
function atualizarStatusTimer() {
    const botaoSync = $('#but_sync');

    if (window.timerSincronizacao && proximaSincronizacao) {
        const agora = new Date();
        const tempoRestante = proximaSincronizacao - agora;
        const minutosRestantes = Math.ceil(tempoRestante / (1000 * 60));

        // Atualizar título do botão
        botaoSync.attr('title', `Sincronizar Status e Estoque com E-commerce\nPróxima sincronização automática em ${minutosRestantes} minutos`);

        // Adicionar indicador visual
        if (!botaoSync.find('.timer-indicator').length) {
            botaoSync.append('<div class="timer-indicator" style="position: absolute; top: -5px; right: -5px; background: orange; color: white; border-radius: 50%; width: 12px; height: 12px; font-size: 8px; display: flex; align-items: center; justify-content: center;">⏰</div>');
        }
    } else {
        // Remover indicador visual
        botaoSync.find('.timer-indicator').remove();
        botaoSync.attr('title', 'Sincronizar Status e Estoque com E-commerce');
    }
}

/**
 * Inicializar timer quando a página carregar
 */
$(document).ready(function() {
    // Iniciar timer automático
    iniciarTimerSincronizacao();

    // Atualizar status do timer a cada minuto
    setInterval(atualizarStatusTimer, 60000);
});

/**
 * Obtém a lista de produtos da Nuvemshop
 */
function obterProdutosNuvemshop() {
    $.ajax({
        url: 'integracao_ajax.php',
        type: 'post',
        data: {
            request: 'listarProdutosNuvemshop'
        },
        dataType: 'json',
        success: function(response) {
            if (!response || !response.success) {
                // Mostrar mensagem de erro mais detalhada
                var errorMsg = response && response.error ? response.error : "Erro desconhecido";
                console.error("Erro ao obter produtos da Nuvemshop:", errorMsg);
                Materialize.toast('<i class="material-icons">error</i> Erro ao obter produtos da Nuvemshop: ' + errorMsg, 4000, 'red');
                $("#loading").hide();
                return;
            }

            // Processar a lista de produtos
            processarProdutosNuvemshop(response.produtos);
        },
        error: function(xhr, status, error) {
            console.error("Erro ao obter produtos da Nuvemshop:", error);
            console.error("Status:", status);
            console.error("Resposta:", xhr.responseText);

            try {
                var response = JSON.parse(xhr.responseText);
                console.error("Resposta JSON:", response);
            } catch (e) {
                console.error("Resposta não é um JSON válido");
            }

            Materialize.toast('<i class="material-icons">error</i> Erro ao obter produtos da Nuvemshop', 4000, 'red');
            $("#loading").hide();
        }
    });
}

/**
 * Processa a lista de produtos da Nuvemshop e atualiza o status no banco de dados
 * @param {Array} produtos - Lista de produtos da Nuvemshop
 */
function processarProdutosNuvemshop(produtos) {
    // Verificar se produtos é válido
    if (!produtos) {
        console.error("Produtos é null ou undefined");
        $("#loading").hide();
        Materialize.toast('<i class="material-icons">error</i> Nenhum produto recebido da Nuvemshop', 4000, 'red');
        return;
    }

    if (!Array.isArray(produtos)) {
        console.error("Produtos não é um array:", produtos);
        $("#loading").hide();
        Materialize.toast('<i class="material-icons">error</i> Formato inválido de produtos da Nuvemshop', 4000, 'red');
        return;
    }

    // Limpar o mapa de produtos
    window.mapaProdutosNuvemshop = {};

    // Log para depuração
    console.log("Total de produtos recebidos da Nuvemshop:", produtos.length);

    // Processar produtos e suas variantes para criar mapa
    produtos.forEach(function(produto) {
        // Verificar se o produto tem código SKU
        if (produto.sku) {
            window.mapaProdutosNuvemshop[produto.sku] = {
                produtoId: produto.id,
                tipo: 'produto'
            };
            console.log("Produto principal encontrado:", produto.sku);
        }

        // Processar variantes
        if (produto.variants && produto.variants.length > 0) {
            console.log(`Produto ${produto.sku || produto.id} tem ${produto.variants.length} variantes`);

            produto.variants.forEach(function(variante) {
                if (variante.sku) {
                    window.mapaProdutosNuvemshop[variante.sku] = {
                        produtoId: produto.id,
                        varianteId: variante.id,
                        tipo: 'variante'
                    };
                    console.log("Variante encontrada:", variante.sku);
                } else {
                    console.log("Variante sem SKU encontrada para o produto:", produto.sku || produto.id);
                }
            });
        }
    });

    console.log("Total de produtos/variantes mapeados:", Object.keys(window.mapaProdutosNuvemshop).length);

    // Atualizar o status dos produtos no banco de dados com dados completos
    atualizarStatusProdutos(produtos);
}

/**
 * Atualiza o status dos produtos no banco de dados
 * @param {Array} produtosNuvemshop - Lista completa de produtos da Nuvemshop
 */
function atualizarStatusProdutos(produtosNuvemshop) {
    // Verificar se há produtos para sincronizar
    if (!produtosNuvemshop || produtosNuvemshop.length === 0) {
        console.log("Nenhum produto para sincronizar");
        $("#loading").hide();
        Materialize.toast('<i class="material-icons">info</i> Nenhum produto encontrado na Nuvemshop para sincronizar', 4000, 'blue');
        return;
    }

    // Mostrar mensagem de sincronização
    Materialize.toast('<i class="material-icons">sync</i> Sincronizando produtos com a Nuvemshop...', 3000, 'blue');

    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: {
            request: 'sincronizarStatusProdutos',
            produtos: JSON.stringify(produtosNuvemshop)
        },
        dataType: 'json',
        success: function(response) {
            $("#loading").hide();

            if (response.success) {
                console.log("Status dos produtos sincronizado com sucesso");
                console.log("Detalhes da sincronização:", response);

                // Se houver produtos atualizados, recarregar a tabela
                if (response.atualizados > 0) {
                    Materialize.toast('<i class="material-icons">info</i> ' + response.atualizados + ' produtos tiveram seu status de e-commerce atualizado', 4000, 'green');

                    // Recarregar a tabela de produtos
                    const val = document.getElementById('desc_pesquisa').value || '';
                    $.ajax({
                        url: 'produtos_ajax.php',
                        type: 'post',
                        data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
                        dataType: 'json',
                        success: function(response) {
                            createRows(response);
                        }
                    });
                } else {
                    Materialize.toast('<i class="material-icons">check_circle</i> Produtos já estão sincronizados com a Nuvemshop', 4000, 'blue');
                }
            } else {
                console.error("Erro ao sincronizar status dos produtos:", response.error);
                Materialize.toast('<i class="material-icons">error</i> Erro ao sincronizar status dos produtos: ' + response.error, 4000, 'red');
            }
        },
        error: function(xhr) {
            $("#loading").hide();
            console.error("Erro ao sincronizar status dos produtos:", xhr.responseText);
            Materialize.toast('<i class="material-icons">error</i> Erro ao sincronizar status dos produtos', 4000, 'red');
        }
    });
}

// Exportar funções
window.sincronizarStatusProdutosNuvemshop = sincronizarStatusProdutosNuvemshop;
