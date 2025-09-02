/**
 * 🖼️ Exemplo de Uso - Sistema de Sincronização de Imagens Nuvemshop
 * 
 * Este arquivo demonstra como usar o sistema de sincronização de imagens
 * implementado para a integração com a Nuvemshop.
 */

// ========================================
// 1. INICIALIZAÇÃO DOS COMPONENTES
// ========================================

// Inicializar o ProductUpdater com debug habilitado
const productUpdater = new ProductUpdater({
    debug: true,                                    // Logs detalhados
    useFetch: true,                                // Usar Fetch API (recomendado)
    proxyUrl: 'nuvemshop/nuvemshop_proxy.php'     // URL do proxy
});

// Inicializar o ImageManager (já incluído no ProductUpdater)
const imageManager = new ImageManager({
    debug: true,
    baseUrl: 'https://demo.gutty.app.br/upload/',  // Ajustar conforme seu domínio
    supportedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    maxImages: 4
});

// ========================================
// 2. EXEMPLO 1: SINCRONIZAÇÃO MANUAL
// ========================================

async function exemploSincronizacaoManual() {
    console.log('🔄 Exemplo 1: Sincronização Manual de Imagens');
    
    const productId = '123456789';          // ID do produto na Nuvemshop
    const codigoGtin = '7898933880010';     // Código GTIN do produto
    
    try {
        // Primeiro, buscar o produto atual para obter as imagens existentes
        const response = await fetch(`nuvemshop/nuvemshop_proxy.php?operation=get_product&product_id=${productId}`);
        const currentProduct = await response.json();
        
        if (currentProduct.error) {
            console.error('❌ Erro ao buscar produto:', currentProduct.error);
            return;
        }
        
        const currentImages = currentProduct.images || [];
        console.log('📷 Imagens atuais na Nuvemshop:', currentImages);
        
        // Executar sincronização
        await productUpdater.syncProductImages(
            productId,
            codigoGtin,
            currentImages,
            // Callback de sucesso
            (results) => {
                console.log('✅ Sincronização concluída com sucesso!');
                console.log('📊 Resultados:', results);
                
                // Exibir estatísticas
                console.log(`➕ Imagens adicionadas: ${results.added.length}`);
                console.log(`🔄 Imagens atualizadas: ${results.updated.length}`);
                console.log(`❌ Imagens removidas: ${results.removed.length}`);
                console.log(`⚠️ Erros: ${results.errors.length}`);
                
                if (results.errors.length > 0) {
                    console.log('🔍 Detalhes dos erros:');
                    results.errors.forEach(err => {
                        console.log(`   - ${err.action.type} posição ${err.action.position}: ${err.error}`);
                    });
                }
            },
            // Callback de erro
            (error) => {
                console.error('❌ Erro na sincronização:', error);
            }
        );
        
    } catch (error) {
        console.error('❌ Erro geral:', error);
    }
}

// ========================================
// 3. EXEMPLO 2: ATUALIZAÇÃO COM SINCRONIZAÇÃO AUTOMÁTICA
// ========================================

function exemploAtualizacaoComSincronizacao() {
    console.log('🔄 Exemplo 2: Atualização de Produto com Sincronização Automática');
    
    // Dados do produto para atualização
    const productData = {
        id: 123456789,                              // ID na Nuvemshop
        codigo_gtin: '7898933880010',               // Código GTIN (importante!)
        codigo_interno: 'PROD001',                  // Código interno
        descricao: 'Produto Exemplo Atualizado',
        descricao_detalhada: 'Descrição detalhada do produto atualizado',
        preco_venda: '29.90',
        peso: '0.5',
        altura: '10',
        largura: '15',
        comprimento: '20'
    };
    
    // Atualizar produto (sincronização de imagens é automática)
    productUpdater.updateProduct(
        productData,
        // Callback de sucesso
        (response) => {
            console.log('✅ Produto atualizado com sucesso!');
            console.log('📦 Resposta:', response);
            
            // Verificar se houve sincronização de imagens
            if (response.imageSync) {
                if (response.imageSync.error) {
                    console.log('⚠️ Erro na sincronização de imagens:', response.imageSync.error);
                } else {
                    console.log('🖼️ Sincronização de imagens realizada:');
                    console.log(`   ➕ Adicionadas: ${response.imageSync.added.length}`);
                    console.log(`   🔄 Atualizadas: ${response.imageSync.updated.length}`);
                    console.log(`   ❌ Removidas: ${response.imageSync.removed.length}`);
                }
            } else {
                console.log('ℹ️ Nenhuma sincronização de imagens foi necessária');
            }
        },
        // Callback de erro
        (error) => {
            console.error('❌ Erro ao atualizar produto:', error);
        }
    );
}

// ========================================
// 4. EXEMPLO 3: VERIFICAÇÃO DE IMAGENS LOCAIS
// ========================================

async function exemploVerificacaoImagensLocais() {
    console.log('🔍 Exemplo 3: Verificação de Imagens Locais');
    
    const codigoGtin = '7898933880010';
    
    try {
        // Verificar quais imagens existem localmente
        const localImages = await imageManager.checkProductImages(codigoGtin);
        
        console.log(`📷 Imagens encontradas localmente: ${localImages.length}`);
        
        if (localImages.length > 0) {
            localImages.forEach(img => {
                console.log(`   Posição ${img.position}: ${img.src}`);
            });
        } else {
            console.log('   Nenhuma imagem encontrada no diretório /upload/');
        }
        
        // Preparar imagens para API (formato Nuvemshop)
        const apiImages = await imageManager.prepareImagesForApi(codigoGtin);
        console.log('🔧 Imagens preparadas para API:', apiImages);
        
    } catch (error) {
        console.error('❌ Erro ao verificar imagens:', error);
    }
}

// ========================================
// 5. EXEMPLO 4: COMPARAÇÃO DE IMAGENS
// ========================================

async function exemploComparacaoImagens() {
    console.log('⚖️ Exemplo 4: Comparação de Imagens');
    
    const codigoGtin = '7898933880010';
    
    // Simular imagens atuais na Nuvemshop
    const currentImages = [
        {
            id: 145,
            src: 'https://demo.gutty.app.br/upload/7898933880010.jpg',
            position: 1,
            product_id: 123456789
        },
        {
            id: 146,
            src: 'https://demo.gutty.app.br/upload/7898933880010_2_old.png',
            position: 2,
            product_id: 123456789
        }
    ];
    
    try {
        // Verificar imagens locais
        const localImages = await imageManager.checkProductImages(codigoGtin);
        
        // Usar método privado para comparação (apenas para demonstração)
        // Em uso real, isso é feito automaticamente pelo syncProductImages
        console.log('📊 Comparando imagens...');
        console.log('   Imagens locais:', localImages);
        console.log('   Imagens Nuvemshop:', currentImages);
        
        // Simular resultado da comparação
        console.log('📋 Ações que seriam executadas:');
        console.log('   ➕ Adicionar: imagens locais que não existem na Nuvemshop');
        console.log('   🔄 Atualizar: imagens com URLs diferentes');
        console.log('   ❌ Remover: imagens da Nuvemshop que não existem localmente');
        
    } catch (error) {
        console.error('❌ Erro na comparação:', error);
    }
}

// ========================================
// 6. EXEMPLO 5: TRATAMENTO DE ERROS
// ========================================

async function exemploTratamentoErros() {
    console.log('🛡️ Exemplo 5: Tratamento de Erros');
    
    const productId = '999999999';          // ID inexistente
    const codigoGtin = '0000000000000';     // Código sem imagens
    
    try {
        // Tentar sincronizar com dados inválidos
        await productUpdater.syncProductImages(
            productId,
            codigoGtin,
            [],
            (results) => {
                console.log('✅ Sincronização concluída (inesperado):', results);
            },
            (error) => {
                console.log('⚠️ Erro capturado corretamente:', error.message);
                console.log('   O sistema continua funcionando normalmente');
            }
        );
        
    } catch (error) {
        console.log('🛡️ Erro tratado pelo try/catch:', error.message);
    }
}

// ========================================
// 7. FUNÇÕES UTILITÁRIAS
// ========================================

/**
 * Função para executar todos os exemplos
 */
async function executarTodosExemplos() {
    console.log('🚀 Executando todos os exemplos...\n');
    
    await exemploVerificacaoImagensLocais();
    console.log('\n' + '='.repeat(50) + '\n');
    
    await exemploComparacaoImagens();
    console.log('\n' + '='.repeat(50) + '\n');
    
    await exemploTratamentoErros();
    console.log('\n' + '='.repeat(50) + '\n');
    
    // Exemplos que requerem dados reais (comentados por segurança)
    // await exemploSincronizacaoManual();
    // exemploAtualizacaoComSincronizacao();
    
    console.log('✅ Todos os exemplos executados!');
}

/**
 * Função para testar conectividade com a API
 */
async function testarConectividade() {
    console.log('🔌 Testando conectividade com a API...');
    
    try {
        const response = await fetch('nuvemshop/nuvemshop_proxy.php?operation=test');
        const data = await response.json();
        
        if (data.error) {
            console.log('❌ Erro de conectividade:', data.error);
        } else {
            console.log('✅ Conectividade OK');
        }
    } catch (error) {
        console.log('❌ Erro de rede:', error.message);
    }
}

// ========================================
// 8. EXECUÇÃO DOS EXEMPLOS
// ========================================

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('📚 Exemplos de Uso - Sistema de Sincronização de Imagens');
    console.log('Para executar os exemplos, use as funções disponíveis:');
    console.log('- exemploVerificacaoImagensLocais()');
    console.log('- exemploComparacaoImagens()');
    console.log('- exemploTratamentoErros()');
    console.log('- executarTodosExemplos()');
    console.log('- testarConectividade()');
    console.log('\nPara exemplos com dados reais, descomente e ajuste os IDs nos exemplos 1 e 2.');
});

// Exportar funções para uso global
window.exemploSincronizacaoManual = exemploSincronizacaoManual;
window.exemploAtualizacaoComSincronizacao = exemploAtualizacaoComSincronizacao;
window.exemploVerificacaoImagensLocais = exemploVerificacaoImagensLocais;
window.exemploComparacaoImagens = exemploComparacaoImagens;
window.exemploTratamentoErros = exemploTratamentoErros;
window.executarTodosExemplos = executarTodosExemplos;
window.testarConectividade = testarConectividade;
