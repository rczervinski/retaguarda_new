/**
 * Integração com a Nuvemshop
 *
 * Este arquivo fornece funções para integração com a Nuvemshop,
 * utilizando as classes VariantManager e ProductUpdater.
 */

// Inicializar o ProductUpdater
let productUpdater = null;

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    console.log("Inicializando integração com a Nuvemshop");

    // Verificar se as classes necessárias estão disponíveis
    if (typeof VariantManager === 'undefined' || typeof ProductUpdater === 'undefined') {
        console.error("Classes VariantManager e/ou ProductUpdater não encontradas. A integração não será inicializada.");
        return;
    }

    // Inicializar o ProductUpdater
    try {
        window.productUpdater = new ProductUpdater({
            debug: true,
            useFetch: true
        });

        // Também atribuir à variável global para compatibilidade
        productUpdater = window.productUpdater;

        console.log("Integração com a Nuvemshop inicializada com sucesso");
    } catch (e) {
        console.error("Erro ao inicializar ProductUpdater:", e);
        Materialize.toast('<i class="material-icons">error</i> Erro ao inicializar integração com a Nuvemshop', 5000, 'red');
    }
});

/**
 * Exporta um produto para a Nuvemshop
 * @param {string} codigo_interno Código interno do produto
 * @param {string} codigo_gtin Código GTIN do produto
 * @param {string} descricao Descrição do produto
 * @param {string} descricao_detalhada Descrição detalhada do produto
 * @param {string} preco_venda Preço de venda do produto
 * @param {string} peso Peso do produto
 * @param {string} altura Altura do produto
 * @param {string} largura Largura do produto
 * @param {string} comprimento Comprimento do produto
 * @param {Array} gradeResponse Resposta da grade de variações
 */
function exportarProdutoParaNuvemshop(
    codigo_interno,
    codigo_gtin,
    descricao,
    descricao_detalhada,
    preco_venda,
    peso,
    altura,
    largura,
    comprimento,
    gradeResponse
) {
    console.log("Exportando produto para a Nuvemshop:", codigo_gtin);

    // Verificar se o ProductUpdater foi inicializado
    if (!productUpdater) {
        console.error("ProductUpdater não inicializado. A exportação não será realizada.");
        Materialize.toast('<i class="material-icons">error</i> Erro ao exportar produto: integração não inicializada', 5000, 'red');
        return;
    }

    // Mostrar loading
    $("#loading").show();

    // Verificar se o produto já existe na Nuvemshop
    productUpdater.findProductBySku(
        codigo_gtin,
        // Callback de sucesso
        function(product) {
            if (product && product.id) {
                console.log("Produto encontrado na Nuvemshop, ID:", product.id);

                // Preparar dados para atualização
                const productData = {
                    id: product.id,
                    descricao: descricao,
                    descricao_detalhada: descricao_detalhada,
                    preco_venda: preco_venda,
                    peso: peso,
                    altura: altura,
                    largura: largura,
                    comprimento: comprimento,
                    codigo_gtin: codigo_gtin,
                    codigo_interno: codigo_interno
                };

                // Preparar variantes existentes e novas
                const existingVariants = product.variants || [];
                const newVariants = prepareNewVariants(
                    gradeResponse,
                    existingVariants,
                    preco_venda,
                    peso,
                    altura,
                    largura,
                    comprimento,
                    codigo_gtin
                );

                // Atualizar o produto e suas variantes
                productUpdater.updateProductWithVariants(
                    productData,
                    existingVariants,
                    newVariants,
                    // Callback de sucesso
                    function(result) {
                        console.log("Produto e variantes atualizados com sucesso:", result);

                        // Esconder loading
                        $("#loading").hide();

                        // Mostrar mensagem de sucesso
                        Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes atualizados com sucesso na Nuvemshop!', 4000, 'green');

                        // Auto-sincronização após sucesso
                        console.log('🔄 Iniciando auto-sincronização após atualização...');
                        setTimeout(function() {
                            if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
                                sincronizarStatusProdutosNuvemshop(true); // true = automático
                            }
                        }, 2000); // Aguarda 2 segundos para a Nuvemshop processar

                        // Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
                    },
                    // Callback de erro
                    function(error) {
                        console.error("Erro ao atualizar produto e variantes:", error);

                        // Esconder loading
                        $("#loading").hide();

                        // Mostrar mensagem de erro
                        Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar produto na Nuvemshop', 5000, 'red');
                    },
                    // Callback de progresso
                    function(type, current, total) {
                        console.log(`Progresso ${type}: ${current}/${total}`);
                    }
                );
            } else {
                console.log("Produto NÃO encontrado na Nuvemshop, criando novo...");

                // Preparar dados para criação
                const productData = {
                    descricao: descricao,
                    descricao_detalhada: descricao_detalhada,
                    preco_venda: preco_venda,
                    peso: peso,
                    altura: altura,
                    largura: largura,
                    comprimento: comprimento,
                    codigo_gtin: codigo_gtin,
                    codigo_interno: codigo_interno,
                    published: true
                };

                // Criar o produto
                productUpdater.createProduct(
                    productData,
                    // Callback de sucesso
                    function(result) {
                        console.log("Produto criado com sucesso:", result);

                        // Se tiver variações, criar as variantes
                        if (gradeResponse && gradeResponse.length > 0) {
                            // Preparar novas variantes
                            const newVariants = prepareNewVariants(
                                gradeResponse,
                                [],
                                preco_venda,
                                peso,
                                altura,
                                largura,
                                comprimento,
                                codigo_gtin
                            );

                            // Criar as variantes
                            productUpdater.variantManager.createNewVariants(
                                result.id,
                                newVariants,
                                // Callback de progresso
                                function(created, total) {
                                    console.log(`Progresso de criação de variantes: ${created}/${total}`);
                                },
                                // Callback de conclusão
                                function(successCount, errorCount) {
                                    console.log("Variantes criadas:", {
                                        success: successCount,
                                        error: errorCount
                                    });

                                    // Esconder loading
                                    $("#loading").hide();

                                    // Mostrar mensagem de sucesso
                                    Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes criados com sucesso na Nuvemshop!', 4000, 'green');

                                    // Auto-sincronização após sucesso
                                    console.log('🔄 Iniciando auto-sincronização após criação com variantes...');
                                    setTimeout(function() {
                                        if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
                                            sincronizarStatusProdutosNuvemshop(true); // true = automático
                                        }
                                    }, 3000); // Aguarda 3 segundos para a Nuvemshop processar variantes

                                    // Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
                                }
                            );
                        } else {
                            // Esconder loading
                            $("#loading").hide();

                            // Mostrar mensagem de sucesso
                            Materialize.toast('<i class="material-icons">check_circle</i> Produto criado com sucesso na Nuvemshop!', 4000, 'green');

                            // Auto-sincronização após sucesso
                            console.log('🔄 Iniciando auto-sincronização após criação simples...');
                            setTimeout(function() {
                                if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
                                    sincronizarStatusProdutosNuvemshop(true); // true = automático
                                }
                            }, 2000); // Aguarda 2 segundos para a Nuvemshop processar

                            // Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
                        }
                    },
                    // Callback de erro
                    function(error) {
                        console.error("Erro ao criar produto:", error);

                        // Esconder loading
                        $("#loading").hide();

                        // Mostrar mensagem de erro
                        Materialize.toast('<i class="material-icons">error</i> Erro ao criar produto na Nuvemshop', 5000, 'red');
                    }
                );
            }
        },
        // Callback de erro
        function(error) {
            console.error("Erro ao buscar produto:", error);

            // Esconder loading
            $("#loading").hide();

            // Mostrar mensagem de erro
            Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto na Nuvemshop', 5000, 'red');
        }
    );
}

/**
 * Prepara novas variantes para criação
 * @param {Array} gradeResponse Resposta da grade de variações
 * @param {Array} existingVariants Variantes existentes
 * @param {string} preco_venda Preço de venda
 * @param {string} peso Peso
 * @param {string} altura Altura
 * @param {string} largura Largura
 * @param {string} comprimento Comprimento
 * @param {string} codigo_gtin Código GTIN do produto principal
 * @returns {Array} Novas variantes
 */
function prepareNewVariants(
    gradeResponse,
    existingVariants,
    preco_venda,
    peso,
    altura,
    largura,
    comprimento,
    codigo_gtin
) {
    // Se não tiver grade, retornar array vazio
    if (!gradeResponse || !Array.isArray(gradeResponse) || gradeResponse.length === 0) {
        return [];
    }

    console.log("Preparando novas variantes a partir da grade:", gradeResponse);

    // Converter valores para números
    const pesoNum = parseFloat((peso || "0").replace(',', '.'));
    const alturaNum = parseFloat((altura || "0").replace(',', '.'));
    const larguraNum = parseFloat((largura || "0").replace(',', '.'));
    const comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

    // Array para armazenar as novas variantes
    const newVariants = [];

    // Set para evitar duplicatas
    const processedVariants = new Set();

    // Mapear variantes existentes para não criar duplicatas
    if (existingVariants && existingVariants.length > 0) {
        existingVariants.forEach(function(variant) {
            if (variant.sku) {
                processedVariants.add(variant.sku);
            }
        });
    }

    // Processar cada item da grade
    gradeResponse.forEach(function(item) {
        // Verificar se o item tem código GTIN e se é diferente do produto principal
        if (item.codigo_gtin && item.codigo_gtin !== codigo_gtin) {
            // Verificar se esta variante já existe
            if (!processedVariants.has(item.codigo_gtin)) {
                // Buscar estoque individual da variante
                console.log(`🔍 Buscando estoque individual para variante: ${item.codigo_gtin}`);

                $.ajax({
                    url: 'produtos_ajax.php',
                    type: 'POST',
                    data: {
                        request: 'obterQuantidadeProduto',
                        codigo_gtin: item.codigo_gtin
                    },
                    dataType: 'json',
                    async: false, // Síncrono para manter ordem
                    success: function(response) {
                        const estoqueVariante = response.success ? parseInt(response.qtde) : 0;
                        const precoVariante = response.success ? parseFloat(response.preco_venda.replace(',', '.')) : parseFloat(preco_venda.replace(',', '.'));
                        console.log(`📦 Dados da variante ${item.codigo_gtin}: Estoque=${estoqueVariante}, Preço=R$${precoVariante}`);

                        // Criar uma nova variante com dados individuais
                        const newVariant = {
                            price: precoVariante, // ✅ Preço individual da variante!
                            stock_management: true,
                            stock: estoqueVariante, // ✅ Estoque individual da variante!
                            weight: pesoNum,
                            depth: comprimentoNum,
                            width: larguraNum,
                            height: alturaNum,
                            sku: item.codigo_gtin
                        };

                        // Adicionar valores se tiver variação e característica
                        if (item.variacao || item.caracteristica) {
                            newVariant.values = [];

                            if (item.variacao) {
                                newVariant.values.push({
                                    pt: item.variacao.trim() || "Padrão"
                                });
                            }

                            if (item.caracteristica) {
                                newVariant.values.push({
                                    pt: item.caracteristica.trim() || "Padrão"
                                });
                            }
                        }

                        // Adicionar à lista de novas variantes
                        newVariants.push(newVariant);

                        // Marcar como processada
                        processedVariants.add(item.codigo_gtin);
                    },
                    error: function() {
                        console.warn(`⚠️ Erro ao buscar estoque da variante ${item.codigo_gtin}, usando estoque 0`);

                        // Em caso de erro, criar variante com estoque 0
                        const newVariant = {
                            price: parseFloat(preco_venda.replace(',', '.')),
                            stock_management: true,
                            stock: 0,
                            weight: pesoNum,
                            depth: comprimentoNum,
                            width: larguraNum,
                            height: alturaNum,
                            sku: item.codigo_gtin
                        };

                        // Adicionar valores se tiver variação e característica
                        if (item.variacao || item.caracteristica) {
                            newVariant.values = [];

                            if (item.variacao) {
                                newVariant.values.push({
                                    pt: item.variacao.trim() || "Padrão"
                                });
                            }

                            if (item.caracteristica) {
                                newVariant.values.push({
                                    pt: item.caracteristica.trim() || "Padrão"
                                });
                            }
                        }

                        // Adicionar à lista de novas variantes
                        newVariants.push(newVariant);

                        // Marcar como processada
                        processedVariants.add(item.codigo_gtin);
                    }
                });
            }
        }
    });

    console.log("Novas variantes preparadas:", newVariants);

    return newVariants;
}

/**
 * Atualiza o status do produto no banco de dados (DEPRECATED)
 * @param {string} codigo_interno Código interno do produto
 * @param {string} status Status do produto (E = Exportado)
 * @deprecated Esta função não deve mais ser usada. O ProductUpdater já define o status correto.
 */
function atualizarStatusProduto(codigo_interno, status) {
    console.log(`[DEPRECATED] atualizarStatusProduto chamada para ${codigo_interno} com status ${status} - ignorando pois ProductUpdater já definiu o status correto`);

    // Apenas remover o produto da lista de selecionados
    var index = produtosSelecionados.indexOf(codigo_interno.toString());
    if (index !== -1) {
        produtosSelecionados.splice(index, 1);
        console.log("Produto removido da lista de exportação:", codigo_interno);
    }
}

/**
 * Exporta produtos selecionados para a Nuvemshop
 */
function exportarProdutosSelecionados() {
    console.log("Exportando produtos selecionados:", produtosSelecionados);

    // Verificar se há produtos selecionados
    if (!produtosSelecionados || produtosSelecionados.length === 0) {
        Materialize.toast('<i class="material-icons">info</i> Nenhum produto selecionado para exportação', 4000, 'blue');
        return;
    }

    // Verificar se o ProductUpdater foi inicializado
    if (!productUpdater) {
        console.error("ProductUpdater não inicializado. A exportação não será realizada.");
        Materialize.toast('<i class="material-icons">error</i> Erro ao exportar produtos: integração não inicializada', 5000, 'red');
        return;
    }

    // Mostrar loading
    $("#loading").show();

    // Contadores para sucesso e falha
    let sucessos = 0;
    let falhas = 0;
    let total = produtosSelecionados.length;
    let processados = 0;

    // Processar cada produto selecionado
    produtosSelecionados.forEach(function(codigo_interno) {
        // Buscar dados do produto
        $.ajax({
            url: 'produtos_ajax.php',
            type: 'post',
            data: {
                request: 'selecionar_produto',
                codigo_interno: codigo_interno
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.length > 0) {
                    const produto = response[0];

                    // Buscar variações do produto
                    $.ajax({
                        url: 'produtos_ajax.php',
                        type: 'post',
                        data: {
                            request: 'selecionar_itens_grade',
                            codigo_interno: codigo_interno
                        },
                        dataType: 'json',
                        success: function(gradeResponse) {
                            // Exportar o produto para a Nuvemshop
                            exportarProdutoParaNuvemshop(
                                produto.codigo_interno,
                                produto.codigo_gtin,
                                produto.descricao,
                                produto.descricao_detalhada || '',
                                produto.preco_venda,
                                produto.peso || '0',
                                produto.altura || '0',
                                produto.largura || '0',
                                produto.comprimento || '0',
                                gradeResponse
                            );

                            // Incrementar contador de sucessos
                            sucessos++;
                            processados++;

                            // Verificar se todos os produtos foram processados
                            if (processados === total) {
                                finalizarExportacao(sucessos, falhas);
                            }
                        },
                        error: function() {
                            console.error("Erro ao buscar variações do produto:", codigo_interno);

                            // Incrementar contador de falhas
                            falhas++;
                            processados++;

                            // Verificar se todos os produtos foram processados
                            if (processados === total) {
                                finalizarExportacao(sucessos, falhas);
                            }
                        }
                    });
                } else {
                    console.error("Produto não encontrado:", codigo_interno);

                    // Incrementar contador de falhas
                    falhas++;
                    processados++;

                    // Verificar se todos os produtos foram processados
                    if (processados === total) {
                        finalizarExportacao(sucessos, falhas);
                    }
                }
            },
            error: function() {
                console.error("Erro ao buscar dados do produto:", codigo_interno);

                // Incrementar contador de falhas
                falhas++;
                processados++;

                // Verificar se todos os produtos foram processados
                if (processados === total) {
                    finalizarExportacao(sucessos, falhas);
                }
            }
        });
    });
}

// Substituir a função exportarProdutosSelecionados original
window.exportarProdutosSelecionados = exportarProdutosSelecionados;

// Substituir a função exportarProdutoParaNuvemshop original
window.exportarProdutoParaNuvemshop = exportarProdutoParaNuvemshop;
