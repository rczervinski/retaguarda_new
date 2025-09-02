/**
 * ProductUpdater - Classe para atualizar produtos na Nuvemshop
 *
 * Esta classe encapsula toda a lógica de atualização de produtos, incluindo:
 * - Atualização de produtos existentes
 * - Criação de novos produtos
 * - Gerenciamento de variantes
 */

class ProductUpdater {
    /**
     * Construtor
     * @param {Object} options Opções de configuração
     */
    constructor(options = {}) {
        this.debug = options.debug || false;
        this.proxyUrl = options.proxyUrl || 'nuvemshop/nuvemshop_proxy.php';
        this.useFetch = options.useFetch !== undefined ? options.useFetch : true;

        // ✅ VERIFICAÇÃO: Garantir que dependências estejam carregadas
        if (typeof VariantManager === 'undefined') {
            throw new Error('VariantManager não está carregado. Verifique se o script foi incluído antes do ProductUpdater.');
        }

        if (typeof CategoryManager === 'undefined') {
            throw new Error('CategoryManager não está carregado. Verifique se o script foi incluído antes do ProductUpdater.');
        }

        if (typeof ImageManager === 'undefined') {
            throw new Error('ImageManager não está carregado. Verifique se o script foi incluído antes do ProductUpdater.');
        }

        // Inicializar o gerenciador de variantes
        this.variantManager = new VariantManager({
            debug: this.debug,
            proxyUrl: this.proxyUrl,
            useFetch: this.useFetch
        });

        // Inicializar o gerenciador de categorias
        this.categoryManager = new CategoryManager(this.proxyUrl);

        // Inicializar o gerenciador de imagens
        this.imageManager = new ImageManager({
            debug: this.debug
        });

        this.log('ProductUpdater inicializado');
    }

    /**
     * Função de log
     * @param {string} message Mensagem a ser logada
     * @param {*} data Dados adicionais
     * @param {string} level Nível de log (log, warn, error)
     */
    log(message, data = null, level = 'log') {
        if (!this.debug && level === 'log') return;

        const prefix = '[ProductUpdater]';

        if (data !== null) {
            console[level](prefix, message, data);
        } else {
            console[level](prefix, message);
        }
    }

    /**
     * Atualiza um produto existente na Nuvemshop
     * NOTA: Imagens são removidas da atualização pois a API da Nuvemshop
     * não aceita imagens em updates (erro 422 - Validation error)
     * @param {Object} productData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     */
    async updateProduct(productData, onSuccess, onError) {
        this.log('Atualizando produto (SEM imagens)', productData);

        // Verificar se temos o ID do produto
        if (!productData.id) {
            this.log('ID do produto não fornecido', null, 'error');

            if (onError) {
                onError('ID do produto não fornecido');
            }

            return;
        }

        try {
            // NOTA: Imagens removidas da atualização - apenas para criação de produtos
            // A API da Nuvemshop não aceita imagens em updates (erro 422)
            let images = []; // Array vazio para manter compatibilidade

            // Se temos codigo_interno, buscar categorias para atualizar
            if (productData.codigo_interno) {
                this._getProductCategories(productData.codigo_interno, async (categories) => {
                    try {
                        // Processar categorias se existirem
                        let categoryIds = [];
                        if (categories && (categories.categoria || categories.grupo)) {
                            this.log('Processando categorias para atualização', categories);

                            const categoryData = {
                                categoria: categories.categoria || '',
                                grupo: categories.grupo || ''
                            };

                            categoryIds = await this.categoryManager.processProductCategories(categoryData);
                            this.log('Categorias processadas para atualização', categoryIds);
                        }

                        // Preparar dados para atualização do produto (SEM imagens)
                        const updateData = this._prepareProductData(productData);

                        // Adicionar categorias aos dados do produto
                        if (categoryIds && categoryIds.length > 0) {
                            updateData.categories = categoryIds;
                        }

                        // Usar Fetch API ou jQuery AJAX
                        if (this.useFetch) {
                            this._updateProductWithFetch(updateData, onSuccess, onError);
                        } else {
                            this._updateProductWithAjax(updateData, onSuccess, onError);
                        }

                    } catch (error) {
                        this.log('Erro ao processar categorias na atualização', error, 'error');

                        // Continuar sem categorias em caso de erro
                        const updateData = this._prepareProductData(productData);

                        if (this.useFetch) {
                            this._updateProductWithFetch(updateData, onSuccess, onError);
                        } else {
                            this._updateProductWithAjax(updateData, onSuccess, onError);
                        }
                    }
                }, (error) => {
                    this.log('Erro ao buscar categorias para atualização, continuando sem categorias', error, 'warn');

                    // Continuar sem categorias em caso de erro
                    const updateData = this._prepareProductData(productData);

                    if (this.useFetch) {
                        this._updateProductWithFetch(updateData, onSuccess, onError);
                    } else {
                        this._updateProductWithAjax(updateData, onSuccess, onError);
                    }
                });
            } else {
                // Sem codigo_interno, atualizar sem categorias
                const updateData = this._prepareProductData(productData);

                if (this.useFetch) {
                    this._updateProductWithFetch(updateData, onSuccess, onError);
                } else {
                    this._updateProductWithAjax(updateData, onSuccess, onError);
                }
            }

        } catch (error) {
            this.log('Erro ao verificar imagens do produto', error, 'error');

            // Continuar sem imagens em caso de erro
            if (productData.codigo_interno) {
                this._getProductCategories(productData.codigo_interno, async (categories) => {
                    try {
                        let categoryIds = [];
                        if (categories && (categories.categoria || categories.grupo)) {
                            const categoryData = {
                                categoria: categories.categoria || '',
                                grupo: categories.grupo || ''
                            };
                            categoryIds = await this.categoryManager.processProductCategories(categoryData);
                        }

                        const updateData = this._prepareProductData(productData);
                        if (categoryIds && categoryIds.length > 0) {
                            updateData.categories = categoryIds;
                        }

                        if (this.useFetch) {
                            this._updateProductWithFetch(updateData, onSuccess, onError);
                        } else {
                            this._updateProductWithAjax(updateData, onSuccess, onError);
                        }
                    } catch (error) {
                        const updateData = this._prepareProductData(productData);
                        if (this.useFetch) {
                            this._updateProductWithFetch(updateData, onSuccess, onError);
                        } else {
                            this._updateProductWithAjax(updateData, onSuccess, onError);
                        }
                    }
                }, (error) => {
                    const updateData = this._prepareProductData(productData);
                    if (this.useFetch) {
                        this._updateProductWithFetch(updateData, onSuccess, onError);
                    } else {
                        this._updateProductWithAjax(updateData, onSuccess, onError);
                    }
                });
            } else {
                const updateData = this._prepareProductData(productData);
                if (this.useFetch) {
                    this._updateProductWithFetch(updateData, onSuccess, onError);
                } else {
                    this._updateProductWithAjax(updateData, onSuccess, onError);
                }
            }
        }
    }

    /**
     * Prepara os dados do produto para criação ou atualização
     * @param {Object} productData Dados do produto
     * @param {Array} images Array de imagens (apenas para criação, não para atualização)
     * @returns {Object} Dados preparados
     * @private
     */
    _prepareProductData(productData, images = null) {
        // Criar uma cópia dos dados
        const updateData = { ...productData };

        // Remover propriedades que não devem ser enviadas
        delete updateData.variants;

        // Garantir que os campos obrigatórios estão presentes
        if (!updateData.name) {
            updateData.name = {
                pt: productData.descricao || 'Produto sem nome'
            };
        } else if (typeof updateData.name === 'string') {
            updateData.name = {
                pt: updateData.name
            };
        }

        if (!updateData.description) {
            updateData.description = {
                pt: productData.descricao_detalhada || ''
            };
        } else if (typeof updateData.description === 'string') {
            updateData.description = {
                pt: updateData.description
            };
        }

        // Garantir que o handle está presente
        if (!updateData.handle) {
            const descricao = productData.descricao || 'produto';
            updateData.handle = {
                pt: descricao.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            };
        }

        // Adicionar imagens se fornecidas (apenas para criação, não para atualização)
        if (images && images.length > 0) {
            updateData.images = images;
            this.log(`Imagens adicionadas aos dados do produto: ${images.length} imagens`);
        }

        // IMPORTANTE: Remover imagens se for uma atualização (API não aceita)
        if (productData.id) {
            delete updateData.images;
            this.log('Imagens removidas dos dados - atualização de produto existente');
        }

        return updateData;
    }

    /**
     * Atualiza um produto usando Fetch API
     * @param {Object} updateData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _updateProductWithFetch(updateData, onSuccess, onError) {
        // Preparar dados para envio
        const safeData = JSON.stringify(this.variantManager.safeClone(updateData));

        fetch(`${this.proxyUrl}?operation=update&product_id=${updateData.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: safeData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }

            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    this.log('Resposta não é JSON válido', text, 'warn');
                    return { success: true, message: 'Produto atualizado com sucesso' };
                }
            });
        })
        .then(async data => {
            this.log('Produto atualizado com sucesso', data);

            // Extrair apenas as propriedades essenciais
            const safeResponse = {
                id: data.id,
                success: true
            };

            // Sincronizar imagens se temos código GTIN
            if (updateData.codigo_gtin) {
                this.log('Iniciando sincronização de imagens após atualização do produto');

                try {
                    // Buscar dados atuais do produto para obter imagens
                    this.findProductByGtin(updateData.codigo_gtin,
                        async (currentProduct) => {
                            if (currentProduct && currentProduct.images) {
                                // Sincronizar imagens
                                await this.syncProductImages(
                                    updateData.id,
                                    updateData.codigo_gtin,
                                    currentProduct.images,
                                    (imageResults) => {
                                        this.log('Sincronização de imagens concluída', imageResults);

                                        if (onSuccess) {
                                            // Incluir resultados da sincronização de imagens na resposta
                                            safeResponse.imageSync = imageResults;
                                            onSuccess(safeResponse);
                                        }
                                    },
                                    (imageError) => {
                                        this.log('Erro na sincronização de imagens', imageError, 'warn');

                                        // Mesmo com erro nas imagens, considerar sucesso na atualização do produto
                                        if (onSuccess) {
                                            safeResponse.imageSync = { error: imageError.message };
                                            onSuccess(safeResponse);
                                        }
                                    }
                                );
                            } else {
                                this.log('Produto não encontrado para sincronização de imagens', null, 'warn');

                                if (onSuccess) {
                                    onSuccess(safeResponse);
                                }
                            }
                        },
                        (findError) => {
                            this.log('Erro ao buscar produto para sincronização de imagens', findError, 'warn');

                            // Mesmo com erro, considerar sucesso na atualização do produto
                            if (onSuccess) {
                                onSuccess(safeResponse);
                            }
                        }
                    );
                } catch (error) {
                    this.log('Erro geral na sincronização de imagens', error, 'warn');

                    // Mesmo com erro, considerar sucesso na atualização do produto
                    if (onSuccess) {
                        onSuccess(safeResponse);
                    }
                }
            } else {
                // Sem código GTIN, não sincronizar imagens
                if (onSuccess) {
                    onSuccess(safeResponse);
                }
            }
        })
        .catch(error => {
            this.log('Erro ao atualizar produto', error, 'error');

            if (onError) {
                onError(error);
            }
        });
    }

    /**
     * Atualiza um produto usando jQuery AJAX
     * @param {Object} updateData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _updateProductWithAjax(updateData, onSuccess, onError) {
        // Preparar dados para envio
        const safeData = JSON.stringify(this.variantManager.safeClone(updateData));

        $.ajax({
            url: `${this.proxyUrl}?operation=update&product_id=${updateData.id}`,
            type: 'POST',
            contentType: 'application/json',
            data: safeData,
            success: (response) => {
                // Extrair apenas as propriedades essenciais
                const safeResponse = {
                    id: response.id,
                    success: true
                };

                // Sincronizar imagens se temos código GTIN
                if (updateData.codigo_gtin) {
                    this.log('Iniciando sincronização de imagens após atualização do produto');

                    try {
                        // Buscar dados atuais do produto para obter imagens
                        this.findProductByGtin(updateData.codigo_gtin,
                            async (currentProduct) => {
                                if (currentProduct && currentProduct.images) {
                                    // Sincronizar imagens
                                    await this.syncProductImages(
                                        updateData.id,
                                        updateData.codigo_gtin,
                                        currentProduct.images,
                                        (imageResults) => {
                                            this.log('Sincronização de imagens concluída', imageResults);

                                            if (onSuccess) {
                                                // Incluir resultados da sincronização de imagens na resposta
                                                safeResponse.imageSync = imageResults;
                                                onSuccess(safeResponse);
                                            }
                                        },
                                        (imageError) => {
                                            this.log('Erro na sincronização de imagens', imageError, 'warn');

                                            // Mesmo com erro nas imagens, considerar sucesso na atualização do produto
                                            if (onSuccess) {
                                                safeResponse.imageSync = { error: imageError.message };
                                                onSuccess(safeResponse);
                                            }
                                        }
                                    );
                                } else {
                                    this.log('Produto não encontrado para sincronização de imagens', null, 'warn');

                                    if (onSuccess) {
                                        onSuccess(safeResponse);
                                    }
                                }
                            },
                            (findError) => {
                                this.log('Erro ao buscar produto para sincronização de imagens', findError, 'warn');

                                // Mesmo com erro, considerar sucesso na atualização do produto
                                if (onSuccess) {
                                    onSuccess(safeResponse);
                                }
                            }
                        );
                    } catch (error) {
                        this.log('Erro geral na sincronização de imagens', error, 'warn');

                        // Mesmo com erro, considerar sucesso na atualização do produto
                        if (onSuccess) {
                            onSuccess(safeResponse);
                        }
                    }
                } else {
                    // Sem código GTIN, não sincronizar imagens
                    if (onSuccess) {
                        onSuccess(safeResponse);
                    }
                }
            },
            error: function(xhr) {
                if (onError) {
                    onError(xhr);
                }
            }
        });
    }

    /**
     * Atualiza um produto e suas variantes
     * @param {Object} productData Dados do produto
     * @param {Array} existingVariants Variantes existentes
     * @param {Array} newVariants Novas variantes
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @param {Function} onProgress Callback para progresso
     */
    updateProductWithVariants(productData, existingVariants, newVariants, onSuccess, onError, onProgress) {
        this.log('Atualizando produto com variantes', {
            product: productData,
            existingVariants,
            newVariants
        });

        // Atualizar o produto
        this.updateProduct(productData,
            // Callback de sucesso
            (productResponse) => {
                this.log('Produto atualizado com sucesso, atualizando variantes', productResponse);

                // ✅ CORRIGIDO: Para produtos normais, usar dados do produto pai na variante virtual
                const baseVariantData = {
                    stock_management: true,
                    price: parseFloat(productData.preco_venda.replace(',', '.')),
                    stock: parseInt(productData.qtdeProduto) || 0,
                    weight: parseFloat(productData.peso) || 0,
                    height: parseFloat(productData.altura) || 0,
                    width: parseFloat(productData.largura) || 0,
                    depth: parseFloat(productData.comprimento) || 0
                };

                this.log('📏 Dados base para variante virtual:', baseVariantData);

                // Atualizar as variantes existentes
                this.variantManager.updateAllVariants(
                    productResponse.id,
                    existingVariants,
                    baseVariantData,
                    // Callback de progresso
                    (updated, total) => {
                        if (onProgress) {
                            onProgress('update', updated, total);
                        }
                    },
                    // Callback de conclusão
                    (successCount, errorCount) => {
                        this.log('Variantes existentes atualizadas', {
                            success: successCount,
                            error: errorCount
                        });

                        // Criar novas variantes
                        this.variantManager.createNewVariants(
                            productResponse.id,
                            newVariants,
                            // Callback de progresso
                            (created, total) => {
                                if (onProgress) {
                                    onProgress('create', created, total);
                                }
                            },
                            // Callback de conclusão
                            (successCount, errorCount) => {
                                this.log('Novas variantes criadas', {
                                    success: successCount,
                                    error: errorCount
                                });

                                if (onSuccess) {
                                    onSuccess({
                                        product: productResponse,
                                        updatedVariants: {
                                            success: successCount,
                                            error: errorCount
                                        },
                                        createdVariants: {
                                            success: successCount,
                                            error: errorCount
                                        }
                                    });
                                }
                            }
                        );
                    }
                );
            },
            // Callback de erro
            (error) => {
                this.log('Erro ao atualizar produto', error, 'error');

                if (onError) {
                    onError(error);
                }
            }
        );
    }

    /**
     * Cria um novo produto na Nuvemshop
     * @param {Object} productData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     */
    async createProduct(productData, onSuccess, onError) {
        this.log('Criando novo produto', productData);

        try {
            // Primeiro, verificar imagens do produto usando o código GTIN
            let images = [];
            if (productData.codigo_gtin) {
                this.log(`Verificando imagens para o código GTIN: ${productData.codigo_gtin}`);
                images = await this.imageManager.prepareImagesForApi(productData.codigo_gtin);
            }

            // Segundo, buscar categorias do produto
            this._getProductCategories(productData.codigo_interno, async (categories) => {
                try {
                    // Processar categorias se existirem
                    let categoryIds = [];
                    if (categories && (categories.categoria || categories.grupo)) {
                        this.log('Processando categorias do produto', categories);

                        const categoryData = {
                            categoria: categories.categoria || '',
                            grupo: categories.grupo || ''
                        };

                        categoryIds = await this.categoryManager.processProductCategories(categoryData);
                        this.log('Categorias processadas', categoryIds);
                    }

                    // Depois, verificar se o produto tem variantes na tabela produtos_gd
                    this._getProductVariants(productData.codigo_interno, (variants) => {
                        // Preparar dados para criação do produto (incluindo imagens)
                        const createData = this._prepareProductData(productData, images);

                        // Adicionar categorias aos dados do produto
                        if (categoryIds && categoryIds.length > 0) {
                            createData.categories = categoryIds;
                        }

                        if (variants && variants.length > 0) {
                            // Produto tem variantes - criar com atributos e todas as variantes
                            this.log('Produto tem variantes, criando com atributos', variants);
                            this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                        } else {
                            // Produto sem variantes - criar apenas com variante virtual
                            this.log('Produto sem variantes, criando com variante virtual');
                            this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                        }
                    }, onError);

                } catch (error) {
                    this.log('Erro ao processar categorias', error, 'error');

                    // Continuar sem categorias em caso de erro
                    this._getProductVariants(productData.codigo_interno, (variants) => {
                        const createData = this._prepareProductData(productData, images);

                        if (variants && variants.length > 0) {
                            this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                        } else {
                            this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                        }
                    }, onError);
                }
            }, (error) => {
                this.log('Erro ao buscar categorias, continuando sem categorias', error, 'warn');

                // Continuar sem categorias em caso de erro
                this._getProductVariants(productData.codigo_interno, (variants) => {
                    const createData = this._prepareProductData(productData, images);

                    if (variants && variants.length > 0) {
                        this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                    } else {
                        this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                    }
                }, onError);
            });

        } catch (error) {
            this.log('Erro ao verificar imagens do produto', error, 'error');

            // Continuar sem imagens em caso de erro
            this._getProductCategories(productData.codigo_interno, async (categories) => {
                try {
                    let categoryIds = [];
                    if (categories && (categories.categoria || categories.grupo)) {
                        const categoryData = {
                            categoria: categories.categoria || '',
                            grupo: categories.grupo || ''
                        };
                        categoryIds = await this.categoryManager.processProductCategories(categoryData);
                    }

                    this._getProductVariants(productData.codigo_interno, (variants) => {
                        const createData = this._prepareProductData(productData);
                        if (categoryIds && categoryIds.length > 0) {
                            createData.categories = categoryIds;
                        }

                        if (variants && variants.length > 0) {
                            this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                        } else {
                            this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                        }
                    }, onError);
                } catch (error) {
                    this._getProductVariants(productData.codigo_interno, (variants) => {
                        const createData = this._prepareProductData(productData);
                        if (variants && variants.length > 0) {
                            this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                        } else {
                            this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                        }
                    }, onError);
                }
            }, (error) => {
                this._getProductVariants(productData.codigo_interno, (variants) => {
                    const createData = this._prepareProductData(productData);
                    if (variants && variants.length > 0) {
                        this._createProductWithVariants(createData, productData, variants, onSuccess, onError);
                    } else {
                        this._createProductWithoutVariants(createData, productData, onSuccess, onError);
                    }
                }, onError);
            });
        }
    }

    /**
     * Busca categorias do produto na tabela produtos_ib
     * @param {number} codigoInterno Código interno do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _getProductCategories(codigoInterno, onSuccess, onError) {
        this.log(`Buscando categorias para o produto ${codigoInterno}`);

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                request: 'buscar_categorias_produto',
                codigo_interno: codigoInterno
            },
            success: (response) => {
                this.log('Categorias recebidas', response);

                if (onSuccess) {
                    onSuccess(response);
                }
            },
            error: (xhr) => {
                this.log('Erro ao buscar categorias', xhr, 'error');
                if (onError) {
                    onError(xhr);
                } else {
                    // Se não há callback de erro, chamar onSuccess com objeto vazio
                    if (onSuccess) {
                        onSuccess({ categoria: '', grupo: '' });
                    }
                }
            }
        });
    }

    /**
     * Busca variantes do produto na tabela produtos_gd
     * @param {number} codigoInterno Código interno do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _getProductVariants(codigoInterno, onSuccess, onError) {
        this.log(`Buscando variantes para o produto ${codigoInterno}`);

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                request: 'selecionar_itens_grade',
                codigo_interno: codigoInterno
            },
            success: (response) => {
                this.log('Resposta bruta recebida', response);

                // Garantir que response é um array
                let variants = [];

                if (typeof response === 'string') {
                    try {
                        variants = JSON.parse(response);
                    } catch (e) {
                        this.log('Erro ao fazer parse da resposta JSON', e, 'error');
                        variants = [];
                    }
                } else if (Array.isArray(response)) {
                    variants = response;
                } else if (response && typeof response === 'object') {
                    // Se for um objeto, pode ser que tenha uma propriedade com o array
                    variants = response.data || response.variants || [];
                } else {
                    variants = [];
                }

                this.log('Variantes processadas', variants);

                if (onSuccess) {
                    onSuccess(variants);
                }
            },
            error: (xhr) => {
                this.log('Erro ao buscar variantes', xhr, 'error');
                if (onError) {
                    onError(xhr);
                } else {
                    // Se não há callback de erro, chamar onSuccess com array vazio
                    if (onSuccess) {
                        onSuccess([]);
                    }
                }
            }
        });
    }

    /**
     * Formata string de variação (primeira letra maiúscula, resto minúscula)
     * @param {string} str String a ser formatada
     * @returns {string} String formatada
     * @private
     */
    _formatVariationString(str) {
        if (!str || typeof str !== 'string') return str;
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }

    /**
     * Cria um produto com variantes em uma única requisição
     * @param {Object} createData Dados base do produto
     * @param {Object} productData Dados originais do produto
     * @param {Array} variants Variantes da tabela produtos_gd
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _createProductWithVariants(createData, productData, variants, onSuccess, onError) {
        this.log('Criando produto com variantes', { createData, variants });

        // Validar se variants é um array válido
        if (!Array.isArray(variants) || variants.length === 0) {
            this.log('Variantes inválidas ou vazias, criando produto sem variantes', variants, 'warn');
            this._createProductWithoutVariants(createData, productData, onSuccess, onError);
            return;
        }

        // Agrupar variantes por tipo de variação e coletar todos os tipos únicos
        const attributeNames = new Set();

        variants.forEach(variant => {
            this.log(`🔍 DEBUG: Processando variante`, {
                codigo_gtin: variant.codigo_gtin,
                variacao: variant.variacao,
                caracteristica: variant.caracteristica
            });

            const variacao = this._formatVariationString(variant.variacao);
            this.log(`🔍 DEBUG: Variação formatada: "${variacao}"`);

            // ✅ CORREÇÃO: Só adicionar se variação não estiver vazia
            if (variacao && variacao.trim() !== '') {
                attributeNames.add(variacao);
            } else {
                this.log(`⚠️ AVISO: Variação vazia ignorada para ${variant.codigo_gtin}`, null, 'warn');
            }
        });

        // Converter para array ordenado para manter consistência
        const attributeNamesArray = Array.from(attributeNames).sort();

        this.log(`🔍 DEBUG: Atributos coletados:`, attributeNamesArray);

        // ✅ VERIFICAÇÃO: Se não há atributos válidos, usar atributo padrão
        if (attributeNamesArray.length === 0) {
            this.log(`⚠️ AVISO: Nenhum atributo válido encontrado, usando "Variação" como padrão`, null, 'warn');
            attributeNamesArray.push('Variação');
        }

        // Criar atributos do produto
        createData.attributes = attributeNamesArray.map(name => ({
            pt: name
        }));

        this.log(`✅ Atributos criados:`, createData.attributes);

        // ✅ CORRIGIDO: Criar variantes com dados individuais do banco
        createData.variants = variants.map(variant => {
            // Buscar dados individuais da variante no banco
            const dadosVariante = this._buscarDadosVarianteIndividual(variant.codigo_gtin);

            const variantData = {
                price: dadosVariante.preco || null,           // ✅ Preço individual ou null
                stock_management: true,
                stock: dadosVariante.estoque || 0,            // ✅ Estoque individual ou 0
                weight: dadosVariante.peso || null,           // ✅ Peso individual ou null
                depth: dadosVariante.comprimento || null,     // ✅ Comprimento individual ou null
                width: dadosVariante.largura || null,         // ✅ Largura individual ou null
                height: dadosVariante.altura || null,         // ✅ Altura individual ou null
                sku: productData.codigo_gtin,                 // ✅ SKU herdado do pai (para referência)
                barcode: variant.codigo_gtin                  // ✅ Barcode individual da variante
            };

            // ✅ CORRIGIDO: Adicionar valores correspondentes aos atributos
            variantData.values = attributeNamesArray.map(attrName => {
                const variantVariacao = this._formatVariationString(variant.variacao);

                this.log(`🔍 DEBUG: Mapeando valor para atributo "${attrName}"`, {
                    variantVariacao: variantVariacao,
                    caracteristica: variant.caracteristica,
                    match: variantVariacao === attrName
                });

                if (variantVariacao && variantVariacao === attrName) {
                    // Retornar a característica da variante (ex: "P", "M", "G")
                    return { pt: variant.caracteristica || 'Sem valor' };
                } else if (attrName === 'Variação') {
                    // Se usando atributo padrão, usar a característica diretamente
                    return { pt: variant.caracteristica || variant.codigo_gtin };
                }

                // Para outros atributos, usar valor padrão
                return { pt: 'Padrão' };
            });

            return variantData;
        });

        this.log('Produto preparado com atributos e variantes');

        // Usar Fetch API ou jQuery AJAX
        if (this.useFetch) {
            this._createProductWithFetch(createData, (response) => {
                // Marcar status após criação bem-sucedida
                this._marcarStatusProdutoComVariantes(productData, variants, response, onSuccess);
            }, onError);
        } else {
            this._createProductWithAjax(createData, (response) => {
                // Marcar status após criação bem-sucedida
                this._marcarStatusProdutoComVariantes(productData, variants, response, onSuccess);
            }, onError);
        }
    }

    /**
     * Cria um produto sem variantes (apenas variante virtual)
     * @param {Object} createData Dados base do produto
     * @param {Object} productData Dados originais do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _createProductWithoutVariants(createData, productData, onSuccess, onError) {
        this.log('Criando produto sem variantes');

        // ✅ CORRIGIDO: Adicionar variante virtual com dados individuais do banco
        const dadosVariante = this._buscarDadosVarianteIndividual(productData.codigo_gtin);

        createData.variants = [{
            price: dadosVariante.preco || parseFloat((productData.preco_venda || "0").replace(',', '.')),
            stock_management: true,
            stock: dadosVariante.estoque || parseInt(productData.qtdeProduto || window.qtdeProduto || 0),
            weight: dadosVariante.peso || parseFloat((productData.peso || "0").replace(',', '.')),
            depth: dadosVariante.comprimento || parseFloat((productData.comprimento || "0").replace(',', '.')),
            width: dadosVariante.largura || parseFloat((productData.largura || "0").replace(',', '.')),
            height: dadosVariante.altura || parseFloat((productData.altura || "0").replace(',', '.')),
            sku: productData.codigo_gtin,    // SKU = GTIN do produto
            barcode: productData.codigo_gtin // GTIN = código de barras (mesmo valor)
        }];

        // Usar Fetch API ou jQuery AJAX
        if (this.useFetch) {
            this._createProductWithFetch(createData, (response) => {
                // Marcar status após criação bem-sucedida
                this._marcarStatusProdutoSemVariantes(productData, response, onSuccess);
            }, onError);
        } else {
            this._createProductWithAjax(createData, (response) => {
                // Marcar status após criação bem-sucedida
                this._marcarStatusProdutoSemVariantes(productData, response, onSuccess);
            }, onError);
        }
    }

    /**
     * Cria um novo produto usando Fetch API
     * @param {Object} createData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _createProductWithFetch(createData, onSuccess, onError) {
        // Preparar dados para envio
        const safeData = JSON.stringify(this.variantManager.safeClone(createData));

        fetch(`${this.proxyUrl}?operation=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: safeData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }

            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    this.log('Resposta não é JSON válido', text, 'warn');
                    return { success: true, message: 'Produto criado com sucesso' };
                }
            });
        })
        .then(data => {
            this.log('Produto criado com sucesso', data);

            // Extrair apenas as propriedades essenciais
            const safeResponse = {
                id: data.id,
                success: true,
                variants: data.variants ? data.variants.map(v => ({
                    id: v.id,
                    sku: v.sku
                })) : []
            };

            if (onSuccess) {
                onSuccess(safeResponse);
            }
        })
        .catch(error => {
            this.log('Erro ao criar produto', error, 'error');

            if (onError) {
                onError(error);
            }
        });
    }

    /**
     * Cria um novo produto usando jQuery AJAX
     * @param {Object} createData Dados do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _createProductWithAjax(createData, onSuccess, onError) {
        // Preparar dados para envio
        const safeData = JSON.stringify(this.variantManager.safeClone(createData));

        $.ajax({
            url: `${this.proxyUrl}?operation=create`,
            type: 'POST',
            contentType: 'application/json',
            data: safeData,
            success: function(response) {
                // Extrair apenas as propriedades essenciais
                const safeResponse = {
                    id: response.id,
                    success: true,
                    variants: response.variants ? response.variants.map(v => ({
                        id: v.id,
                        sku: v.sku
                    })) : []
                };

                if (onSuccess) {
                    onSuccess(safeResponse);
                }
            },
            error: function(xhr) {
                if (onError) {
                    onError(xhr);
                }
            }
        });
    }

    /**
     * Busca dados individuais de uma variante no banco local
     * @param {string} codigo_gtin GTIN da variante
     * @returns {Object} Dados da variante {preco, estoque, peso, altura, largura, comprimento}
     * @private
     */
    _buscarDadosVarianteIndividual(codigo_gtin) {
        this.log(`Buscando dados individuais da variante: ${codigo_gtin}`);

        // Usar função existente do sistema
        if (typeof buscarDadosVariante === 'function') {
            const dados = buscarDadosVariante(codigo_gtin);
            this.log(`Dados encontrados para ${codigo_gtin}:`, dados);
            return dados;
        } else {
            this.log(`Função buscarDadosVariante não encontrada, retornando dados vazios`, null, 'warn');
            return {
                preco: null,
                estoque: 0,
                peso: null,
                altura: null,
                largura: null,
                comprimento: null
            };
        }
    }

    /**
     * Busca um produto na Nuvemshop pelo GTIN (que é usado como SKU)
     * @param {string} gtin GTIN do produto principal
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     */
    findProductByGtin(gtin, onSuccess, onError) {
        this.log(`Buscando produto com GTIN ${gtin}`);

        // Usar Fetch API ou jQuery AJAX
        if (this.useFetch) {
            this._findProductBySkuWithFetch(gtin, onSuccess, onError);
        } else {
            this._findProductBySkuWithAjax(gtin, onSuccess, onError);
        }
    }

    /**
     * Busca um produto na Nuvemshop pelo SKU (mantido para compatibilidade)
     * @param {string} sku SKU do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     */
    findProductBySku(sku, onSuccess, onError) {
        this.log(`Buscando produto com SKU ${sku}`);

        // Usar Fetch API ou jQuery AJAX
        if (this.useFetch) {
            this._findProductBySkuWithFetch(sku, onSuccess, onError);
        } else {
            this._findProductBySkuWithAjax(sku, onSuccess, onError);
        }
    }

    /**
     * Busca um produto pelo SKU usando Fetch API
     * @param {string} sku SKU do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _findProductBySkuWithFetch(sku, onSuccess, onError) {
        fetch(`${this.proxyUrl}?operation=search&sku=${sku}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }

            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    this.log('Resposta não é JSON válido', text, 'warn');
                    return null;
                }
            });
        })
        .then(data => {
            if (data && data.id) {
                this.log('Produto encontrado', data);

                // Extrair apenas as propriedades essenciais
                const safeResponse = {
                    id: data.id,
                    name: data.name,
                    images: data.images || [],
                    variants: data.variants ? data.variants.map(v => ({
                        id: v.id,
                        sku: v.sku,
                        price: v.price,
                        stock: v.stock,
                        values: v.values
                    })) : []
                };

                if (onSuccess) {
                    onSuccess(safeResponse);
                }
            } else {
                this.log('Produto não encontrado', null, 'warn');

                if (onSuccess) {
                    onSuccess(null);
                }
            }
        })
        .catch(error => {
            this.log('Erro ao buscar produto', error, 'error');

            if (onError) {
                onError(error);
            }
        });
    }

    /**
     * Busca um produto pelo SKU usando jQuery AJAX
     * @param {string} sku SKU do produto
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     * @private
     */
    _findProductBySkuWithAjax(sku, onSuccess, onError) {
        $.ajax({
            url: `${this.proxyUrl}?operation=search&sku=${sku}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.id) {
                    // Extrair apenas as propriedades essenciais
                    const safeResponse = {
                        id: response.id,
                        name: response.name,
                        images: response.images || [],
                        variants: response.variants ? response.variants.map(v => ({
                            id: v.id,
                            sku: v.sku,
                            price: v.price,
                            stock: v.stock,
                            values: v.values
                        })) : []
                    };

                    if (onSuccess) {
                        onSuccess(safeResponse);
                    }
                } else {
                    if (onSuccess) {
                        onSuccess(null);
                    }
                }
            },
            error: function(xhr) {
                if (onError) {
                    onError(xhr);
                }
            }
        });
    }

    /**
     * Marca status do produto com variantes após criação
     * @param {Object} productData Dados do produto
     * @param {Array} variants Variantes do produto
     * @param {Object} response Resposta da criação
     * @param {Function} onSuccess Callback original
     * @private
     */
    _marcarStatusProdutoComVariantes(productData, variants, response, onSuccess) {
        this.log('Marcando status do produto com variantes');

        // Marcar produto principal como ENSVI
        this._atualizarStatusProduto(productData.codigo_gtin, 'ENSVI', () => {
            this.log('Produto principal marcado como ENSVI');

            // Marcar cada variante como ENSV
            let variantesProcessadas = 0;
            const totalVariantes = variants.length;

            if (totalVariantes === 0) {
                // Se não há variantes, chamar callback de sucesso
                if (onSuccess) onSuccess(response);
                return;
            }

            variants.forEach(variant => {
                this._atualizarStatusProduto(variant.codigo_gtin, 'ENSV', () => {
                    variantesProcessadas++;

                    // Se todas as variantes foram processadas, chamar callback
                    if (variantesProcessadas === totalVariantes) {
                        if (onSuccess) onSuccess(response);
                    }
                });
            });
        });
    }

    /**
     * Marca status do produto sem variantes após criação
     * @param {Object} productData Dados do produto
     * @param {Object} response Resposta da criação
     * @param {Function} onSuccess Callback original
     * @private
     */
    _marcarStatusProdutoSemVariantes(productData, response, onSuccess) {

        // Marcar produto como ENS
        this._atualizarStatusProduto(productData.codigo_gtin, 'ENS', () => {
            if (onSuccess) onSuccess(response);
        });
    }

    /**
     * Atualiza status do produto no banco de dados
     * @param {string} gtin GTIN do produto
     * @param {string} status Status a ser definido (ENS, ENSVI, ENSV)
     * @param {Function} onSuccess Callback de sucesso
     * @private
     */
    _atualizarStatusProduto(gtin, status, onSuccess) {
        console.log(`🔄 Atualizando status do produto: GTIN=${gtin}, Status=${status}`);

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: {
                request: 'atualizarStatusEcommerce',
                codigo_gtin: gtin,
                status: status
            },
            dataType: 'json',
            success: (response) => {
                console.log(`✅ Resposta da atualização de status:`, response);
                if (response && response.success) {
                    console.log(`✅ Status atualizado com sucesso: GTIN=${gtin} → ${status}`);
                    if (onSuccess) onSuccess();
                } else {
                    console.warn(`⚠️ Erro na atualização de status: GTIN=${gtin}`, response);
                    // Mesmo com erro, continuar o fluxo
                    if (onSuccess) onSuccess();
                }
            },
            error: (xhr) => {
                console.error(`❌ Erro AJAX na atualização de status: GTIN=${gtin}`, xhr.responseText);
                // Mesmo com erro, continuar o fluxo
                if (onSuccess) onSuccess();
            }
        });
    }

    /**
     * Sincroniza imagens de um produto entre o sistema local e a Nuvemshop
     * @param {string} productId ID do produto na Nuvemshop
     * @param {string} codigo GTIN/código do produto local
     * @param {Array} currentImages Imagens atuais do produto na Nuvemshop
     * @param {Function} onSuccess Callback para sucesso
     * @param {Function} onError Callback para erro
     */
    async syncProductImages(productId, codigo, currentImages = [], onSuccess, onError) {
        this.log(`Iniciando sincronização de imagens para produto ${productId}, código ${codigo}`);

        try {
            // 1. Verificar imagens locais
            const localImages = await this.imageManager.checkProductImages(codigo);
            this.log(`Imagens locais encontradas: ${localImages.length}`, localImages);
            this.log(`Imagens atuais na Nuvemshop: ${currentImages.length}`, currentImages);

            // 2. Comparar e determinar ações
            const actions = this._compareImages(localImages, currentImages);
            this.log(`Ações determinadas:`, actions);

            // 3. Executar ações em ordem: remover → atualizar → adicionar
            const results = {
                removed: [],
                updated: [],
                added: [],
                errors: []
            };

            // Remover imagens que não existem mais localmente
            for (const action of actions.remove) {
                try {
                    await this._executeImageAction(productId, action);
                    results.removed.push(action);
                    this.log(`✅ Imagem removida: posição ${action.position}`);
                } catch (error) {
                    this.log(`❌ Erro ao remover imagem: posição ${action.position}`, error, 'error');
                    results.errors.push({ action, error: error.message });
                }
            }

            // Atualizar imagens existentes
            for (const action of actions.update) {
                try {
                    await this._executeImageAction(productId, action);
                    results.updated.push(action);
                    this.log(`✅ Imagem atualizada: posição ${action.position}`);
                } catch (error) {
                    this.log(`❌ Erro ao atualizar imagem: posição ${action.position}`, error, 'error');
                    results.errors.push({ action, error: error.message });
                }
            }

            // Adicionar novas imagens
            for (const action of actions.add) {
                try {
                    await this._executeImageAction(productId, action);
                    results.added.push(action);
                    this.log(`✅ Imagem adicionada: posição ${action.position}`);
                } catch (error) {
                    this.log(`❌ Erro ao adicionar imagem: posição ${action.position}`, error, 'error');
                    results.errors.push({ action, error: error.message });
                }
            }

            this.log(`Sincronização concluída:`, results);

            if (onSuccess) {
                onSuccess(results);
            }

        } catch (error) {
            this.log('Erro geral na sincronização de imagens', error, 'error');

            if (onError) {
                onError(error);
            }
        }
    }

    /**
     * Compara imagens locais com imagens da Nuvemshop e determina ações necessárias
     * @param {Array} localImages Imagens encontradas localmente
     * @param {Array} currentImages Imagens atuais na Nuvemshop
     * @returns {Object} Objeto com arrays de ações (add, update, remove)
     * @private
     */
    _compareImages(localImages, currentImages) {
        const actions = {
            add: [],
            update: [],
            remove: []
        };

        // Criar mapa das imagens atuais na Nuvemshop por posição
        const nuvemshopImageMap = {};
        currentImages.forEach(img => {
            nuvemshopImageMap[img.position] = img;
        });

        // Criar mapa das imagens locais por posição
        const localImageMap = {};
        localImages.forEach(img => {
            localImageMap[img.position] = img;
        });

        // Verificar imagens locais
        localImages.forEach(localImg => {
            const nuvemshopImg = nuvemshopImageMap[localImg.position];

            if (!nuvemshopImg) {
                // Imagem não existe na Nuvemshop - adicionar
                actions.add.push({
                    type: 'add',
                    position: localImg.position,
                    src: localImg.src
                });
            } else if (nuvemshopImg.src !== localImg.src) {
                // Imagem existe mas URL é diferente - atualizar
                actions.update.push({
                    type: 'update',
                    id: nuvemshopImg.id,
                    position: localImg.position,
                    src: localImg.src
                });
            }
            // Se src é igual, não precisa fazer nada
        });

        // Verificar imagens da Nuvemshop que não existem mais localmente
        currentImages.forEach(nuvemshopImg => {
            const localImg = localImageMap[nuvemshopImg.position];

            if (!localImg) {
                // Imagem existe na Nuvemshop mas não localmente - remover
                actions.remove.push({
                    type: 'remove',
                    id: nuvemshopImg.id,
                    position: nuvemshopImg.position
                });
            }
        });

        return actions;
    }

    /**
     * Executa uma ação de imagem (adicionar, atualizar ou remover)
     * @param {string} productId ID do produto na Nuvemshop
     * @param {Object} action Ação a ser executada
     * @returns {Promise} Promise da operação
     * @private
     */
    async _executeImageAction(productId, action) {
        const url = `${this.proxyUrl}?operation=image_${action.type}&product_id=${productId}`;

        let requestData = {};
        let method = 'POST';

        switch (action.type) {
            case 'add':
                requestData = {
                    src: action.src,
                    position: action.position
                };
                method = 'POST';
                break;

            case 'update':
                requestData = {
                    id: action.id,
                    src: action.src,
                    position: action.position
                };
                method = 'POST';
                break;

            case 'remove':
                requestData = {
                    id: action.id
                };
                method = 'POST';
                break;
        }

        if (this.useFetch) {
            return this._executeImageActionWithFetch(url, requestData, method);
        } else {
            return this._executeImageActionWithAjax(url, requestData, method);
        }
    }

    /**
     * Executa ação de imagem usando Fetch API
     * @param {string} url URL da requisição
     * @param {Object} data Dados da requisição
     * @param {string} method Método HTTP
     * @returns {Promise} Promise da operação
     * @private
     */
    async _executeImageActionWithFetch(url, data, method) {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        return await response.json();
    }

    /**
     * Executa ação de imagem usando jQuery AJAX
     * @param {string} url URL da requisição
     * @param {Object} data Dados da requisição
     * @param {string} method Método HTTP
     * @returns {Promise} Promise da operação
     * @private
     */
    _executeImageActionWithAjax(url, data, method) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: method,
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr) {
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.responseText}`));
                }
            });
        });
    }

    /**
     * Método de log interno
     * @param {string} message Mensagem
     * @param {*} data Dados opcionais
     * @param {string} level Nível do log (info, warn, error)
     * @private
     */
    log(message, data = null, level = 'info') {
        if (this.debug) {
            const timestamp = new Date().toISOString();
            const logMessage = `[${timestamp}] ProductUpdater: ${message}`;

            if (level === 'error') {
                console.error(logMessage, data);
            } else if (level === 'warn') {
                console.warn(logMessage, data);
            } else {
                console.log(logMessage, data);
            }
        }
    }
}
