$(document).ajaxStart(function() {
	$("#loading").show();
});
$(document).ajaxStop(function() {
	$("#loading").hide();
});
$(document).ready(function() {
	$('.modal').modal();
});
$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#vendas_online_principal").show();
	console.log("Documento pronto - Iniciando carregamento de pedidos");

	// Verificar se as funções necessárias estão disponíveis
	if (typeof prepareAjaxData !== 'function') {
		console.error("ATENÇÃO: Função prepareAjaxData não está disponível. Verifique se utils.js foi carregado corretamente.");
		// Tentar carregar utils.js dinamicamente
		$.getScript("js/utils.js")
			.done(function() {
				console.log("utils.js carregado com sucesso");
			})
			.fail(function(_, __, exception) {
				console.error("Erro ao carregar utils.js:", exception);
			});
	}
});
function detalhesVenda(codigo_){
	$("#tableDetalheVenda tbody").empty();

	// Mostrar indicador de carregamento
	$("#loading").show();

	$.ajax({
		url: 'vendasonline_ajax.php',
		type: 'post',
		data: prepareAjaxData({ request: 'mostrarDetalhesVenda', codigo: codigo_ }),
		dataType: 'json',
		success: function(response) {
			// Ocultar indicador de carregamento
			$("#loading").hide();

			// Abrir modal
			try {
				// Verificar se o modal já foi inicializado
				if (typeof $('#modalDetalheVenda').modal === 'function') {
					// Tentar abrir o modal
					$('#modalDetalheVenda').modal('open');
				} else {
					// Se não estiver inicializado, inicializar e abrir
					console.log("Modal não inicializado, inicializando agora...");
					$('.modal').modal();
					$('#modalDetalheVenda').modal('open');
				}
			} catch (e) {
				console.error("Erro ao abrir modal:", e);
				// Tentar inicializar novamente
				setTimeout(function() {
					$('.modal').modal();
					$('#modalDetalheVenda').modal('open');
				}, 500);
			}

			// Log para depuração
			console.log("Resposta da API:", response);

			var len = 0;
			if (response != null) {
				len = response.length;
			}

			if (len > 0) {
				var total_produtos = 0;
				var frete = 0;
				var valor_pago_raw = 0;

				// Limpar informações anteriores
				$("#nome, #cpf, #endereco, #cep, #bairro, #municipio, #uf, #fone, #email").html("");
				$("#codigo_pedido, #codigo_externo, #data_pedido, #hora_pedido, #status_pedido, #payment_status, #origem_pedido").html("");
				$("#total_produtos, #frete, #forma_pgto, #valor_pago").html("");

				for (var i = 0; i < len; i++) {
					var codigo_gtin = response[i].codigo_gtin;
					var descricao = response[i].descricao;
					var qtde = parseFloat(response[i].qtde);
					var preco_venda_raw = parseFloat(response[i].preco_venda);
					var total_raw = parseFloat(response[i].total);

					// Log para depuração
					console.log("Item #" + i + ":", {
						codigo_gtin: codigo_gtin,
						descricao: descricao,
						qtde: qtde,
						preco_venda_raw: preco_venda_raw,
						total_raw: total_raw
					});

					// Formatar valores monetários
					var preco_venda = formatarMoeda(preco_venda_raw);
					var total = formatarMoeda(total_raw);
					var observacao = response[i].observacao;
					var nome = response[i].nome;
					var cpf = response[i].cpf;
					var endereco = response[i].endereco;
					var cep = response[i].cep;
					var bairro = response[i].bairro;
					var municipio = response[i].municipio;
					var uf = response[i].uf;
					var forma_pgto = response[i].forma_pgto;
					var fone = response[i].fone;
					var email = response[i].email;
					valor_pago_raw = parseFloat(response[i].valor_pago);
					var status = response[i].status;
					var payment_status = response[i].payment_status;
					var status_desc = response[i].status_desc;
					var codigo_externo = response[i].codigo_externo;
					var data = response[i].data;
					var hora = response[i].hora;
					var origem = response[i].origem;
					var codigo = codigo_; // Código do pedido passado como parâmetro

					// Acumular total dos produtos
					total_produtos += total_raw;

					// Adicionar linha na tabela
					var tr_str = "<tr>" +
					"<td data-label='Código'>" + codigo_gtin + "</td>" +
					"<td data-label='Descrição'>" + descricao + "</td>" +
					"<td data-label='Qtde'>" + qtde + "</td>" +
					"<td data-label='Preço'>" + preco_venda + "</td>" +
					"<td data-label='Total'>" + total + "</td>" +
					"<td data-label='Observação'>" + (observacao ? observacao : "") + "</td>" +
					"</tr>";
					$("#tableDetalheVenda tbody").append(tr_str);

					// Informações do cliente (apenas uma vez)
					if (i === 0) {
						$("#nome").html("<b>Nome:</b> " + nome);
						$("#cpf").html("<b>CPF:</b> " + cpf);
						$("#endereco").html("<b>Endereço:</b> " + endereco);
						$("#cep").html("<b>CEP:</b> " + cep);
						$("#bairro").html("<b>Bairro:</b> " + bairro);
						$("#municipio").html("<b>Cidade:</b> " + municipio);
						$("#uf").html("<b>UF:</b> " + uf);
						$("#fone").html("<b>Fone:</b> " + fone);
						$("#email").html("<b>E-mail:</b> " + email);

						// Informações do pedido
						$("#codigo_pedido").html("<b>Código Interno:</b> " + codigo);
						$("#codigo_externo").html("<b>Código Externo:</b> " + (codigo_externo ? codigo_externo : "N/A"));
						$("#data_pedido").html("<b>Data:</b> " + data);
						$("#hora_pedido").html("<b>Hora:</b> " + hora);
						$("#status_pedido").html("<b>Status:</b> " + (status_desc ? status_desc : status));
						$("#payment_status").html("<b>Status Pagamento:</b> " + (payment_status ? payment_status : "N/A"));
						$("#origem_pedido").html("<b>Origem:</b> " + origem);

						// Forma de pagamento
						$("#forma_pgto").html("<b>Forma de Pagamento:</b> " + forma_pgto);
					}
				}

				// Calcular frete (valor pago - total produtos)
				frete = valor_pago_raw - total_produtos;
				if (frete < 0) frete = 0;

				// Log para depuração
				console.log("Total produtos:", total_produtos);
				console.log("Valor pago:", valor_pago_raw);
				console.log("Frete calculado:", frete);

				// Formatar valor pago
				var valor_pago = formatarMoeda(valor_pago_raw);

				// Resumo do pagamento
				$("#total_produtos").html("<b>Total Produtos:</b> " + formatarMoeda(total_produtos));
				$("#frete").html("<b>Frete:</b> " + formatarMoeda(frete));
				$("#valor_pago").html("<b>Total Pago:</b> " + valor_pago);
			} else {
				// Nenhum produto encontrado
				$("#tableDetalheVenda tbody").append("<tr class='no-results'><td colspan='6' class='center-align' data-label=''>Nenhum produto encontrado para este pedido</td></tr>");
			}
		},
		error: function(_, __, exception) {
			// Ocultar indicador de carregamento
			$("#loading").hide();

			// Exibir mensagem de erro
			alert("Erro ao carregar detalhes do pedido: " + exception);
		}
	});
}
$(document).ready(function() {
	$('.modal').modal();
});

// Esta função foi movida para baixo no arquivo

// Esta função foi movida para baixo no arquivo
function createRows(response) {
	var len = 0;
	$('#userTable tbody').empty(); // Empty <tbody>
	$("#paginacao_superior, #paginacao_inferior").empty();

	if (response != null) {
		len = response.length;
	}

	if (len > 0) {
		var quantos = response[0].quantos;
		var itensPorPagina = 50;
		var paginas = Math.ceil(quantos / itensPorPagina);
		var paginaAtual = Math.floor(response[0].pagina / itensPorPagina) + 1;

		// Log para depuração
		console.log("Total de registros:", quantos);
		console.log("Itens por página:", itensPorPagina);
		console.log("Total de páginas:", paginas);
		console.log("Página atual:", paginaAtual);
		console.log("Offset atual:", response[0].pagina);

		// Criar paginação estilo Materialize
		criarPaginacao(paginas, paginaAtual, "#paginacao_superior");
		criarPaginacao(paginas, paginaAtual, "#paginacao_inferior");
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var data = response[i].data;
			var hora = response[i].hora;
			var nome = response[i].nome;
			var total_raw = parseFloat(response[i].total);
			var status = response[i].status;
			var qtd_produtos = parseInt(response[i].qtd_produtos || 0);
			var origem = response[i].origem || 'Desconhecida';

			// Log para depuração
			console.log("ITEM #" + i + " - Valor total dos produtos:", total_raw, "Tipo:", typeof total_raw);
			console.log("ITEM #" + i + " - Quantidade de produtos:", qtd_produtos, "Tipo:", typeof qtd_produtos);
			console.log("ITEM #" + i + " - Origem:", origem);

			// Formatar valor para exibição
			var total = isNaN(total_raw) ? "R$ 0,00" : formatarMoeda(total_raw);

			// Verificar se há produtos
			if (qtd_produtos === 0) {
				total = '<span style="color: red;" title="Pedido sem produtos na tabela ped_online_prod">' + total + ' ⚠️</span>';
			}

			// Adicionar ícone para a origem do pedido
			var origemIcon = '';
			var origemTitle = '';

			if (origem === 'nuvemshop') {
				origemIcon = '<i class="material-icons blue-text" title="Nuvemshop">cloud</i>';
				origemTitle = 'Nuvemshop';
			} else if (origem === 'ecommerce') {
				origemIcon = '<i class="material-icons green-text" title="E-commerce">shopping_cart</i>';
				origemTitle = 'E-commerce';
			} else {
				origemIcon = '<i class="material-icons grey-text" title="Origem desconhecida">help</i>';
				origemTitle = 'Desconhecida';
			}

			var tr_str = "<tr>" +
				"<td data-label='Codigo'>" + codigo + "</td>" +
				"<td data-label='Data'>" + data + "</td>" +
				"<td data-label='Hora'>" + hora + "</td>" +
				"<td data-label='Cliente'>" + nome + "</td>" +
				"<td data-label='Total'>" + total + "</td>" +
				"<td data-label='Status'>" + status + "</td>" +
				"<td data-label='Origem'>" + origemIcon + " " + (origemTitle ? origemTitle : '') + "</td>" +
				"<td class='action-buttons' data-label='Ações'>" +
					"<a class='btn-floating btn-small waves-effect blue' onClick='detalhesVenda(" + codigo + ")' title='Ver detalhes'><i class='material-icons'>search</i></a>" +
				"</td>" +
				"</tr>";
			$("#userTable tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr class='no-results'>" +
			"<td align='center' colspan='8' data-label=''>Sem registro.</td>" +
			"</tr>";
		$("#userTable tbody").append(tr_str);
	}
}
/**
 * Cria a paginação no estilo Materialize
 * @param {number} totalPaginas - Total de páginas
 * @param {number} paginaAtual - Página atual
 * @param {string} seletor - Seletor CSS para o elemento de paginação
 */
function criarPaginacao(totalPaginas, paginaAtual, seletor) {
	var $paginacao = $(seletor);
	$paginacao.empty();

	// Adicionar botão "Anterior"
	var $anterior = $('<li class="waves-effect"><a href="#!"><i class="material-icons">chevron_left</i></a></li>');
	if (paginaAtual <= 1) {
		$anterior.addClass('disabled');
	} else {
		$anterior.click(function(e) {
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
				$pagina.addClass('active blue');
			}
			$pagina.click(function(e) {
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
		$proximo.click(function(e) {
			e.preventDefault();
			clickPagina(paginaAtual + 1);
		});
	}
	$paginacao.append($proximo);
}

/**
 * Carrega a página especificada
 * @param {number} valor - Número da página a ser carregada
 */
function clickPagina(valor) {
	// Log para depuração
	console.log("Clicou na página:", valor);

	// Mostrar indicador de carregamento
	$("#loading").show();

	// Rolar para o topo da tabela
	$('.table-container').animate({ scrollTop: 0 }, 'fast');

	// Calcular o offset para a paginação
	var offset = (valor - 1) * 50;
	console.log("Offset calculado:", offset);

	// AJAX GET request
	$.ajax({
		url: 'vendasonline_ajax.php',
		type: 'post',
		data: prepareAjaxData({ request: 'fetchall', pagina: offset }),
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

// Função para emitir NFE (não implementada)
function emitirNFE(codigo){
	console.log("Emitir NFE para o código:", codigo);
	alert("Funcionalidade de emissão de NFE não implementada");
}

// Função para formatar valores monetários
function formatarMoeda(valor) {
	return "R$ " + parseFloat(valor).toFixed(2).replace(".", ",");
}

// Função para sincronizar pedidos
function sincronizarPedidos(automatico) {
	// Mostrar status de sincronização
	$("#sincronizacao_status").show();
	$("#sincronizacao_resultados").hide();
	$("#sincronizacao_texto").text("Sincronizando pedidos recentes...");

	console.log("Iniciando sincronização de pedidos... Automático:", automatico);

	// Fazer requisição AJAX para sincronizar pedidos
	$.ajax({
		url: 'nuvemshop/sincronizar_vendas.php',
		type: 'post',
		data: {auto_sync: automatico ? 'true' : 'false'}, // Indicar se é uma sincronização automática
		dataType: 'json',
		beforeSend: function() {
			console.log("Enviando requisição para sincronizar pedidos...");
		},
		success: function(response) {
			// Ocultar status de sincronização
			$("#sincronizacao_status").hide();

			// Exibir resultados da sincronização
			var detalhesHtml = '';

			// Verificar se a resposta tem o formato esperado
			if (response.success !== undefined) {
				// Formato da resposta do sincronizar_vendas.php
				var statusClass = response.success ? 'green-text' : 'red-text';
				var statusIcon = response.success ? 'check_circle' : 'error';

				detalhesHtml += '<ul class="collection">';
				detalhesHtml += '<li class="collection-item">';
				detalhesHtml += '<div class="valign-wrapper">';
				detalhesHtml += '<i class="material-icons ' + statusClass + ' left">' + statusIcon + '</i>';
				detalhesHtml += '<span><strong>Nuvemshop:</strong> ' + response.message + '</span>';
				detalhesHtml += '</div>';

				if (response.success && response.pedidos_novos !== undefined && response.pedidos_atualizados !== undefined) {
					detalhesHtml += '<div class="chip blue white-text">Novos: ' + response.pedidos_novos + '</div>';
					detalhesHtml += '<div class="chip orange white-text">Atualizados: ' + response.pedidos_atualizados + '</div>';
				}

				detalhesHtml += '</li>';
				detalhesHtml += '</ul>';
			} else if (response.servicos && response.servicos.length > 0) {
				// Formato da resposta do sincronizar_todos_pedidos.php
				detalhesHtml += '<ul class="collection">';

				$.each(response.servicos, function(_, servico) {
					var statusClass = servico.success ? 'green-text' : 'red-text';
					var statusIcon = servico.success ? 'check_circle' : 'error';

					detalhesHtml += '<li class="collection-item">';
					detalhesHtml += '<div class="valign-wrapper">';
					detalhesHtml += '<i class="material-icons ' + statusClass + ' left">' + statusIcon + '</i>';
					detalhesHtml += '<span><strong>' + servico.descricao + ':</strong> ' + servico.message + '</span>';
					detalhesHtml += '</div>';

					if (servico.success) {
						detalhesHtml += '<div class="chip blue white-text">Novos: ' + servico.pedidos_novos + '</div>';
						detalhesHtml += '<div class="chip orange white-text">Atualizados: ' + servico.pedidos_atualizados + '</div>';
					}

					detalhesHtml += '</li>';
				});

				detalhesHtml += '</ul>';
			} else {
				detalhesHtml += '<p>Nenhum serviço de integração ativo encontrado.</p>';
			}

			$("#sincronizacao_detalhes").html(detalhesHtml);
			$("#sincronizacao_resultados").show();

			// Se for sincronização automática, ocultar resultados após 5 segundos
			if (automatico) {
				setTimeout(function() {
					$("#sincronizacao_resultados").fadeOut(500);
				}, 5000);

				// Verificar se há novos pedidos ou pedidos atualizados
				if (response.success && (response.pedidos_novos > 0 || response.pedidos_atualizados > 0)) {
					// Mostrar toast com os resultados
					var mensagem = "Sincronização automática: " + response.pedidos_novos + " novos, " + response.pedidos_atualizados + " atualizados";

					// Verificar qual versão do Materialize está disponível
					if (typeof M !== 'undefined' && typeof M.toast === 'function') {
						M.toast({html: mensagem, displayLength: 5000, classes: 'blue'});
					} else if (typeof Materialize !== 'undefined' && typeof Materialize.toast === 'function') {
						Materialize.toast(mensagem, 5000, 'blue');
					} else {
						console.log("Toast não disponível:", mensagem);
					}

				}
			}

			// Carregar pedidos
			carregarPedidos();
		},
		error: function(_, __, exception) {
			// Ocultar status de sincronização
			$("#sincronizacao_status").hide();

			// Exibir mensagem de erro
			var errorMsg = "Erro ao sincronizar pedidos: " + exception;
			$("#sincronizacao_detalhes").html('<p class="red-text">' + errorMsg + '</p>');
			$("#sincronizacao_resultados").show();

			// Carregar pedidos mesmo com erro
			carregarPedidos();
		}
	});
}

// Função para carregar pedidos
function carregarPedidos() {
	// Mostrar indicador de carregamento
	$("#loading").show();

	$.ajax({
		url: 'vendasonline_ajax.php',
		type: 'post',
		data: prepareAjaxData({ request: 'fetchall', pagina: 0 }),
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
			alert("Erro ao carregar pedidos: " + exception);
		}
	});
}

// Função para sincronizar pedidos completos (últimos 30 dias)
function sincronizarPedidosCompleto() {
	// Mostrar status de sincronização
	$("#sincronizacao_status").show();
	$("#sincronizacao_resultados").hide();
	$("#sincronizacao_texto").text("Sincronizando todos os pedidos dos últimos 30 dias...");

	console.log("Iniciando sincronização completa de pedidos...");

	// Fazer requisição AJAX para sincronizar pedidos
	$.ajax({
		url: 'nuvemshop/sincronizar_vendas.php',
		type: 'post',
		data: {
			auto_sync: true,
			force_full_sync: 'true'
		},
		dataType: 'json',
		beforeSend: function() {
			console.log("Enviando requisição para sincronização completa de pedidos...");
		},
		success: function(response) {
			// Ocultar status de sincronização
			$("#sincronizacao_status").hide();

			// Exibir resultados da sincronização
			var detalhesHtml = '';

			// Verificar se a resposta tem o formato esperado
			if (response.success !== undefined) {
				// Formato da resposta do sincronizar_vendas.php
				var statusClass = response.success ? 'green-text' : 'red-text';
				var statusIcon = response.success ? 'check_circle' : 'error';

				detalhesHtml += '<ul class="collection">';
				detalhesHtml += '<li class="collection-item">';
				detalhesHtml += '<div class="valign-wrapper">';
				detalhesHtml += '<i class="material-icons ' + statusClass + ' left">' + statusIcon + '</i>';
				detalhesHtml += '<span><strong>Nuvemshop (Sincronização Completa):</strong> ' + response.message + '</span>';
				detalhesHtml += '</div>';

				if (response.success && response.pedidos_novos !== undefined && response.pedidos_atualizados !== undefined) {
					detalhesHtml += '<div class="chip blue white-text">Novos: ' + response.pedidos_novos + '</div>';
					detalhesHtml += '<div class="chip orange white-text">Atualizados: ' + response.pedidos_atualizados + '</div>';
				}

				detalhesHtml += '</li>';
				detalhesHtml += '</ul>';
			} else {
				detalhesHtml += '<p>Resposta inválida da API.</p>';
			}

			$("#sincronizacao_detalhes").html(detalhesHtml);
			$("#sincronizacao_resultados").show();

			// Carregar pedidos
			carregarPedidos();
		},
		error: function(_, __, exception) {
			// Ocultar status de sincronização
			$("#sincronizacao_status").hide();

			// Exibir mensagem de erro
			var errorMsg = "Erro ao sincronizar pedidos: " + exception;
			$("#sincronizacao_detalhes").html('<p class="red-text">' + errorMsg + '</p>');
			$("#sincronizacao_resultados").show();

			// Carregar pedidos mesmo com erro
			carregarPedidos();
		}
	});
}