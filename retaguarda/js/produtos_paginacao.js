/**
 * Funções para paginação e carregamento de produtos
 */

// Carregar produtos ao iniciar a página
$(document).ready(function() {
    // Mostrar indicador de carregamento
    $("#loading").show();

    // Inicializar componentes do Materialize
    $('.modal').modal();

    // Carregar produtos automaticamente ao iniciar a página
    carregarProdutos(0, "");

    // Pesquisar ao clicar no botão de pesquisa
    $('#but_fetchall').click(function() {
        const val = document.getElementById('desc_pesquisa').value;
        carregarProdutos(0, val);
    });

    // Pesquisar ao pressionar Enter na caixa de pesquisa
    $('#desc_pesquisa').keypress(function(e) {
        if (e.which === 13) { // 13 é o código da tecla Enter
            e.preventDefault(); // Evitar o comportamento padrão do Enter
            const val = document.getElementById('desc_pesquisa').value;
            carregarProdutos(0, val);
        }
    });
});

/**
 * Carrega produtos com paginação
 * @param {number} pagina - Offset da página a ser carregada
 * @param {string} termo - Termo de pesquisa
 */
function carregarProdutos(pagina, termo) {
    // Mostrar indicador de carregamento
    $("#loading").show();

    // AJAX GET request
    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: {
            request: 'fetchall',
            pagina: pagina,
            desc_pesquisa: termo
        },
        dataType: 'json',
        success: function(response) {
            // Ocultar indicador de carregamento
            $("#loading").hide();

            // Atualizar tabela e paginação
            createRows(response);
        },
        error: function(_, __, exception) {
            // Ocultar indicador de carregamento
            $("#loading").hide();

            // Exibir mensagem de erro
            alert("Erro ao carregar produtos: " + exception);
        }
    });
}

/**
 * Cria as linhas da tabela e a paginação
 * @param {Array} response - Resposta da API
 */
function createRows(response) {
    var len = 0;
    $('#userTable tbody').empty(); // Empty <tbody>
    $("#paginacao_superior, #paginacao_inferior").empty();

    // Rolar para o topo da tabela
    $('.table-container').animate({ scrollTop: 0 }, 'fast');

    if (response != null) {
        len = response.length;
    }

    if (len > 0) {
        var quantos = response[0].quantos;
        var itensPorPagina = 50;
        var paginas = Math.ceil(quantos / itensPorPagina);
        var paginaAtual = Math.floor(response[0].pagina / itensPorPagina) + 1;

        // Criar paginação estilo Materialize
        criarPaginacao(paginas, paginaAtual, "#paginacao_superior");
        criarPaginacao(paginas, paginaAtual, "#paginacao_inferior");

        for (var i = 0; i < len; i++) {
            var codigo_gtin = response[i].codigo_gtin;
            var descricao = response[i].descricao;
            var codigo_interno = response[i].codigo_interno;
            var status = response[i].status || '';

            // Verificar se o produto já está selecionado manualmente pelo usuário
            var checked = (typeof produtosSelecionados !== 'undefined' && produtosSelecionados.includes(codigo_interno.toString())) ? 'checked' : '';

            // Adicionar ícone para produtos no e-commerce
            var ecommerceIcon = gerarIconeEcommerce(status);

            var tr_str = "<tr>" +
                "<td data-label='E-commerce'><input type='checkbox' id='prod_" + codigo_interno + "' class='produto-checkbox' " + checked + " onchange='atualizarSelecao(this)'/><label for='prod_" + codigo_interno + "'></label></td>" +
                "<td data-label='Código'>" + codigo_gtin + "</td>" +
                "<td data-label='Descrição'>" + ecommerceIcon + descricao + "</td>" +
                "<td data-label='Editar'><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_produto(" + codigo_interno + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
                "</tr>";
            $("#userTable tbody").append(tr_str);
        }
    } else {
        var tr_str = "<tr class='no-results'>" +
            "<td align='center' colspan='4' data-label=''>Sem registro.</td>" +
            "</tr>";
        $("#userTable tbody").append(tr_str);
    }
}

/**
 * Cria a paginação estilo Materialize
 * @param {number} totalPaginas - Total de páginas
 * @param {number} paginaAtual - Página atual
 * @param {string} seletor - Seletor CSS para o elemento de paginação
 */
function criarPaginacao(totalPaginas, paginaAtual, seletor) {
    var $paginacao = $(seletor);
    $paginacao.empty();

    // Se não houver páginas, não fazer nada
    if (totalPaginas <= 0) {
        return;
    }

    // Adicionar botão "Anterior"
    var $anterior = $('<li class="waves-effect"><a href="#!"><i class="material-icons">chevron_left</i></a></li>');
    if (paginaAtual <= 1) {
        $anterior.addClass('disabled');
    } else {
        $anterior.find('a').on('click', function(e) {
            e.preventDefault();
            clickPagina(paginaAtual - 1);
        });
    }
    $paginacao.append($anterior);

    // Determinar quais páginas mostrar
    var paginas = [];
    var maxPaginasVisiveis = 5;

    if (totalPaginas <= maxPaginasVisiveis) {
        // Mostrar todas as páginas se forem poucas
        for (var i = 1; i <= totalPaginas; i++) {
            paginas.push(i);
        }
    } else {
        // Mostrar páginas ao redor da página atual
        var inicio = Math.max(1, paginaAtual - Math.floor(maxPaginasVisiveis / 2));
        var fim = Math.min(totalPaginas, inicio + maxPaginasVisiveis - 1);

        // Ajustar o início se estiver muito próximo do fim
        if (fim - inicio + 1 < maxPaginasVisiveis) {
            inicio = Math.max(1, fim - maxPaginasVisiveis + 1);
        }

        // Adicionar primeira página e reticências se necessário
        if (inicio > 1) {
            paginas.push(1);
            if (inicio > 2) {
                paginas.push('...');
            }
        }

        // Adicionar páginas do meio
        for (var i = inicio; i <= fim; i++) {
            paginas.push(i);
        }

        // Adicionar última página e reticências se necessário
        if (fim < totalPaginas) {
            if (fim < totalPaginas - 1) {
                paginas.push('...');
            }
            paginas.push(totalPaginas);
        }
    }

    // Adicionar botões de página
    paginas.forEach(function(pagina) {
        if (pagina === '...') {
            $paginacao.append('<li class="disabled"><a href="#!">...</a></li>');
        } else {
            var $pagina = $('<li class="waves-effect"><a href="#!">' + pagina + '</a></li>');
            if (pagina === paginaAtual) {
                $pagina.removeClass('waves-effect').addClass('active blue');
            }
            $pagina.find('a').on('click', function(e) {
                e.preventDefault();
                if (pagina !== paginaAtual) {
                    clickPagina(pagina);
                }
            });
            $paginacao.append($pagina);
        }
    });

    // Adicionar botão "Próximo"
    var $proximo = $('<li class="waves-effect"><a href="#!"><i class="material-icons">chevron_right</i></a></li>');
    if (paginaAtual >= totalPaginas) {
        $proximo.addClass('disabled');
    } else {
        $proximo.find('a').on('click', function(e) {
            e.preventDefault();
            clickPagina(paginaAtual + 1);
        });
    }
    $paginacao.append($proximo);

    // Garantir que a paginação seja visível
    if (totalPaginas > 1) {
        $paginacao.parent().show();
    } else {
        $paginacao.parent().hide();
    }
}

/**
 * Carrega a página especificada
 * @param {number} valor - Número da página a ser carregada
 */
function clickPagina(valor) {

    // Mostrar indicador de carregamento
    $("#loading").show();

    // Rolar para o topo da tabela
    $('.table-container').animate({ scrollTop: 0 }, 'fast');

    // Calcular o offset para a paginação
    var offset = (valor - 1) * 50;

    // AJAX GET request
    const val = document.getElementById('desc_pesquisa').value;
    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: {
            request: 'fetchall',
            pagina: offset,
            desc_pesquisa: val
        },
        dataType: 'json',
        success: function(response) {
            // Ocultar indicador de carregamento
            $("#loading").hide();

            // Atualizar tabela e paginação
            createRows(response);
        },
        error: function(_, __, exception) {
            // Ocultar indicador de carregamento
            $("#loading").hide();

            // Exibir mensagem de erro
            alert("Erro ao carregar página: " + exception);
        }
    });
}

/**
 * Gera ícone de e-commerce baseado no status do produto
 * @param {string} status - Status do produto (ENS, ENSVI, ENSV, E)
 * @returns {string} HTML do ícone com tooltip
 */
function gerarIconeEcommerce(status) {
	if (!status) {
		return "";
	}

	var iconeHtml = "";

	switch (status) {
		case 'ENS':
			// Produto normal na Nuvemshop - Nuvem azul
			iconeHtml = "<i class='material-icons ecommerce-icon nuvemshop-normal' " +
				"title='Nuvemshop - Produto Normal' " +
				"data-tooltip='Produto disponível na Nuvemshop (sem variantes)'>cloud</i> ";
			break;

		case 'ENSVI':
			// Produto vitrine na Nuvemshop - Nuvem verde escura
			iconeHtml = "<i class='material-icons ecommerce-icon nuvemshop-vitrine' " +
				"title='Nuvemshop - Produto Vitrine' " +
				"data-tooltip='Produto vitrine na Nuvemshop (com múltiplas variantes)'>cloud</i> ";
			break;

		case 'ENSV':
			// Variante na Nuvemshop - Nuvem verde clara
			iconeHtml = "<i class='material-icons ecommerce-icon nuvemshop-variante' " +
				"title='Nuvemshop - Variante' " +
				"data-tooltip='Variante de produto na Nuvemshop'>cloud</i> ";
			break;



		default:
			// Status desconhecido
			return "";
	}

	return iconeHtml;
}