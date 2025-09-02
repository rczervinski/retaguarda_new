/**
 * Arquivo de teste para a integração com a Nuvemshop
 *
 * Este arquivo contém funções para testar a integração com a Nuvemshop
 * usando as novas classes VariantManager e ProductUpdater.
 */

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    console.log("Inicializando teste de integração com a Nuvemshop");

    // Verificar se as classes necessárias estão disponíveis
    if (typeof VariantManager === 'undefined' || typeof ProductUpdater === 'undefined') {
        console.error("Classes VariantManager e/ou ProductUpdater não encontradas. O teste não será inicializado.");
        return;
    }

});



/**
 * Testa a integração com a Nuvemshop
 */
function testarIntegracao() {
    console.log("Iniciando teste de integração com a Nuvemshop");

    // Verificar se o ProductUpdater está disponível
    if (typeof ProductUpdater === 'undefined' || !window.productUpdater) {
        console.log("ProductUpdater não disponível, inicializando...");

        // Tentar inicializar o ProductUpdater
        try {
            window.productUpdater = new ProductUpdater({
                debug: true,
                useFetch: true
            });
            console.log("ProductUpdater inicializado com sucesso");
        } catch (e) {
            console.error("Erro ao inicializar ProductUpdater:", e);
            Materialize.toast('<i class="material-icons">error</i> Erro ao inicializar ProductUpdater', 5000, 'red');
            return;
        }
    }

    // Mostrar modal de teste
    mostrarModalTeste();
}

/**
 * Mostra um modal para testar a integração
 */
function mostrarModalTeste() {
    // Criar o modal
    var modalHtml = `
        <div id="modal-teste-nuvemshop" class="modal">
            <div class="modal-content">
                <h4>Teste de Integração com a Nuvemshop</h4>
                <p>Selecione o tipo de teste que deseja realizar:</p>
                <div class="row">
                    <div class="col s12">
                        <ul class="collection">
                            <li class="collection-item">
                                <input type="radio" id="teste-buscar" name="tipo-teste" value="buscar" checked>
                                <label for="teste-buscar">Buscar produto por SKU</label>
                                <div class="input-field" style="margin-top: 10px;">
                                    <input type="text" id="sku-buscar" placeholder="Digite o SKU do produto">
                                </div>
                            </li>
                            <li class="collection-item">
                                <input type="radio" id="teste-criar" name="tipo-teste" value="criar">
                                <label for="teste-criar">Criar produto de teste</label>
                            </li>
                            <li class="collection-item">
                                <input type="radio" id="teste-atualizar" name="tipo-teste" value="atualizar">
                                <label for="teste-atualizar">Atualizar produto existente</label>
                                <div class="input-field" style="margin-top: 10px;">
                                    <input type="text" id="sku-atualizar" placeholder="Digite o SKU do produto">
                                </div>
                            </li>
                            <li class="collection-item">
                                <input type="radio" id="teste-variantes" name="tipo-teste" value="variantes">
                                <label for="teste-variantes">Testar gerenciamento de variantes</label>
                                <div class="input-field" style="margin-top: 10px;">
                                    <input type="text" id="sku-variantes" placeholder="Digite o SKU do produto">
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
                <a href="#!" id="btn-executar-teste" class="waves-effect waves-light btn">Executar Teste</a>
            </div>
        </div>
    `;

    // Adicionar o modal ao corpo da página
    $('body').append(modalHtml);

    // Inicializar o modal
    $('#modal-teste-nuvemshop').modal({
        dismissible: true,
        opacity: 0.5,
        inDuration: 300,
        outDuration: 200,
        startingTop: '4%',
        endingTop: '10%',
        ready: function() {
            console.log("Modal de teste aberto");
        },
        complete: function() {
            console.log("Modal de teste fechado");
            // Remover o modal do DOM após fechar
            $('#modal-teste-nuvemshop').remove();
        }
    });

    // Abrir o modal
    $('#modal-teste-nuvemshop').modal('open');

    // Adicionar evento de clique ao botão de executar teste
    $('#btn-executar-teste').on('click', function() {
        var tipoTeste = $('input[name="tipo-teste"]:checked').val();

        switch (tipoTeste) {
            case 'buscar':
                var sku = $('#sku-buscar').val();
                if (!sku) {
                    Materialize.toast('<i class="material-icons">error</i> Digite o SKU do produto', 3000, 'red');
                    return;
                }
                testarBuscarProduto(sku);
                break;
            case 'criar':
                testarCriarProduto();
                break;
            case 'atualizar':
                var sku = $('#sku-atualizar').val();
                if (!sku) {
                    Materialize.toast('<i class="material-icons">error</i> Digite o SKU do produto', 3000, 'red');
                    return;
                }
                testarAtualizarProduto(sku);
                break;
            case 'variantes':
                var sku = $('#sku-variantes').val();
                if (!sku) {
                    Materialize.toast('<i class="material-icons">error</i> Digite o SKU do produto', 3000, 'red');
                    return;
                }
                testarGerenciamentoVariantes(sku);
                break;
        }

        // Fechar o modal
        $('#modal-teste-nuvemshop').modal('close');
    });
}

/**
 * Testa a busca de um produto por SKU
 * @param {string} sku SKU do produto
 */
function testarBuscarProduto(sku) {
    console.log("Testando busca de produto com SKU:", sku);

    // Mostrar loading
    $("#loading").show();

    // Buscar o produto
    window.productUpdater.findProductBySku(
        sku,
        // Callback de sucesso
        function(product) {
            console.log("Resultado da busca:", product);

            // Esconder loading
            $("#loading").hide();

            if (product && product.id) {
                Materialize.toast('<i class="material-icons">check_circle</i> Produto encontrado com sucesso!', 4000, 'green');

                // Mostrar detalhes do produto
                mostrarDetalhesModal(product);
            } else {
                Materialize.toast('<i class="material-icons">info</i> Produto não encontrado', 4000, 'blue');
            }
        },
        // Callback de erro
        function(error) {
            console.error("Erro ao buscar produto:", error);

            // Esconder loading
            $("#loading").hide();

            Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto', 5000, 'red');
        }
    );
}

/**
 * Testa a criação de um produto
 */
function testarCriarProduto() {
    console.log("Testando criação de produto");

    // Mostrar loading
    $("#loading").show();

    // Gerar um SKU único para o produto de teste
    var sku = "TESTE-" + Math.floor(Math.random() * 10000);

    // Dados do produto
    var productData = {
        codigo_gtin: sku,
        descricao: "Produto de Teste",
        descricao_detalhada: "Este é um produto de teste criado para verificar a integração com a Nuvemshop.",
        preco_venda: "10,00",
        peso: "0,5",
        altura: "10",
        largura: "10",
        comprimento: "10",
        qtdeProduto: 10,
        published: true
    };

    // Criar o produto
    window.productUpdater.createProduct(
        productData,
        // Callback de sucesso
        function(result) {
            console.log("Produto criado com sucesso:", result);

            // Esconder loading
            $("#loading").hide();

            Materialize.toast('<i class="material-icons">check_circle</i> Produto criado com sucesso! SKU: ' + sku, 4000, 'green');

            // Mostrar detalhes do produto
            setTimeout(function() {
                testarBuscarProduto(sku);
            }, 1000);
        },
        // Callback de erro
        function(error) {
            console.error("Erro ao criar produto:", error);

            // Esconder loading
            $("#loading").hide();

            Materialize.toast('<i class="material-icons">error</i> Erro ao criar produto', 5000, 'red');
        }
    );
}

/**
 * Testa a atualização de um produto
 * @param {string} sku SKU do produto
 */
function testarAtualizarProduto(sku) {
    console.log("Testando atualização de produto com SKU:", sku);

    // Mostrar loading
    $("#loading").show();

    // Buscar o produto primeiro
    window.productUpdater.findProductBySku(
        sku,
        // Callback de sucesso
        function(product) {
            if (product && product.id) {
                console.log("Produto encontrado, atualizando...");

                // Dados para atualização
                var updateData = {
                    id: product.id,
                    descricao: product.name.pt + " (Atualizado)",
                    descricao_detalhada: "Este produto foi atualizado em " + new Date().toLocaleString(),
                    preco_venda: "15,00"
                };

                // Atualizar o produto
                window.productUpdater.updateProduct(
                    updateData,
                    // Callback de sucesso
                    function(result) {
                        console.log("Produto atualizado com sucesso:", result);

                        // Esconder loading
                        $("#loading").hide();

                        Materialize.toast('<i class="material-icons">check_circle</i> Produto atualizado com sucesso!', 4000, 'green');

                        // Mostrar detalhes do produto atualizado
                        setTimeout(function() {
                            testarBuscarProduto(sku);
                        }, 1000);
                    },
                    // Callback de erro
                    function(error) {
                        console.error("Erro ao atualizar produto:", error);

                        // Esconder loading
                        $("#loading").hide();

                        Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar produto', 5000, 'red');
                    }
                );
            } else {
                console.log("Produto não encontrado");

                // Esconder loading
                $("#loading").hide();

                Materialize.toast('<i class="material-icons">info</i> Produto não encontrado', 4000, 'blue');
            }
        },
        // Callback de erro
        function(error) {
            console.error("Erro ao buscar produto:", error);

            // Esconder loading
            $("#loading").hide();

            Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto', 5000, 'red');
        }
    );
}

/**
 * Testa o gerenciamento de variantes
 * @param {string} sku SKU do produto
 */
function testarGerenciamentoVariantes(sku) {
    console.log("Testando gerenciamento de variantes para o produto com SKU:", sku);

    // Mostrar loading
    $("#loading").show();

    // Buscar o produto primeiro
    window.productUpdater.findProductBySku(
        sku,
        // Callback de sucesso
        function(product) {
            if (product && product.id) {
                console.log("Produto encontrado, gerenciando variantes...");

                // Dados do produto
                var productData = {
                    id: product.id,
                    descricao: product.name.pt,
                    preco_venda: "20,00",
                    peso: "0,5",
                    altura: "10",
                    largura: "10",
                    comprimento: "10",
                    qtdeProduto: 10
                };

                // Variantes existentes
                var existingVariants = product.variants || [];

                // Novas variantes
                var newVariants = [
                    {
                        price: 20.00,
                        stock_management: true,
                        stock: 10,
                        weight: 0.5,
                        depth: 10,
                        width: 10,
                        height: 10,
                        sku: sku + "-NOVA1",
                        values: [
                            {
                                pt: "Nova Variante 1"
                            }
                        ]
                    },
                    {
                        price: 25.00,
                        stock_management: true,
                        stock: 5,
                        weight: 0.5,
                        depth: 10,
                        width: 10,
                        height: 10,
                        sku: sku + "-NOVA2",
                        values: [
                            {
                                pt: "Nova Variante 2"
                            }
                        ]
                    }
                ];

                // Atualizar o produto e suas variantes
                window.productUpdater.updateProductWithVariants(
                    productData,
                    existingVariants,
                    newVariants,
                    // Callback de sucesso
                    function(result) {
                        console.log("Produto e variantes atualizados com sucesso:", result);

                        // Esconder loading
                        $("#loading").hide();

                        Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes atualizados com sucesso!', 4000, 'green');

                        // Mostrar detalhes do produto atualizado
                        setTimeout(function() {
                            testarBuscarProduto(sku);
                        }, 1000);
                    },
                    // Callback de erro
                    function(error) {
                        console.error("Erro ao atualizar produto e variantes:", error);

                        // Esconder loading
                        $("#loading").hide();

                        Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar produto e variantes', 5000, 'red');
                    },
                    // Callback de progresso
                    function(type, current, total) {
                        console.log(`Progresso ${type}: ${current}/${total}`);
                    }
                );
            } else {
                console.log("Produto não encontrado");

                // Esconder loading
                $("#loading").hide();

                Materialize.toast('<i class="material-icons">info</i> Produto não encontrado', 4000, 'blue');
            }
        },
        // Callback de erro
        function(error) {
            console.error("Erro ao buscar produto:", error);

            // Esconder loading
            $("#loading").hide();

            Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto', 5000, 'red');
        }
    );
}

/**
 * Mostra um modal com os detalhes do produto
 * @param {Object} product Produto
 */
function mostrarDetalhesModal(product) {
    // Criar o modal
    var modalHtml = `
        <div id="modal-detalhes-produto" class="modal modal-fixed-footer">
            <div class="modal-content">
                <h4>Detalhes do Produto</h4>
                <div class="row">
                    <div class="col s12">
                        <ul class="collection with-header">
                            <li class="collection-header"><h5>Informações Básicas</h5></li>
                            <li class="collection-item">ID: ${product.id}</li>
                            <li class="collection-item">Nome: ${product.name ? product.name.pt : 'N/A'}</li>
                        </ul>

                        <ul class="collection with-header">
                            <li class="collection-header"><h5>Variantes (${product.variants ? product.variants.length : 0})</h5></li>
                            ${product.variants && product.variants.length > 0 ?
                                product.variants.map(function(variant) {
                                    return `
                                        <li class="collection-item">
                                            <div>
                                                <strong>ID:</strong> ${variant.id}<br>
                                                <strong>SKU:</strong> ${variant.sku || 'N/A'}<br>
                                                <strong>Preço:</strong> R$ ${variant.price ? variant.price.toFixed(2).replace('.', ',') : 'N/A'}<br>
                                                <strong>Estoque:</strong> ${variant.stock || 'N/A'}<br>
                                                <strong>Valores:</strong> ${variant.values && variant.values.length > 0 ?
                                                    variant.values.map(function(value) {
                                                        return value.pt;
                                                    }).join(', ') : 'N/A'
                                                }
                                            </div>
                                        </li>
                                    `;
                                }).join('') :
                                '<li class="collection-item">Nenhuma variante encontrada</li>'
                            }
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-light btn">Fechar</a>
            </div>
        </div>
    `;

    // Adicionar o modal ao corpo da página
    $('body').append(modalHtml);

    // Inicializar o modal
    $('#modal-detalhes-produto').modal({
        dismissible: true,
        opacity: 0.5,
        inDuration: 300,
        outDuration: 200,
        startingTop: '4%',
        endingTop: '10%',
        ready: function() {
            console.log("Modal de detalhes aberto");
        },
        complete: function() {
            console.log("Modal de detalhes fechado");
            // Remover o modal do DOM após fechar
            $('#modal-detalhes-produto').remove();
        }
    });

    // Abrir o modal
    $('#modal-detalhes-produto').modal('open');
}
