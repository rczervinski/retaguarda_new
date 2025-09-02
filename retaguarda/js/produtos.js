/**
 * Função para buscar dados individuais de uma variante (estoque, preço e dimensões)
 */
function buscarDadosVariante(codigo_gtin) {
	var dados = {
		estoque: 0,
		preco: 0,
		peso: 0,
		altura: 0,
		largura: 0,
		comprimento: 0
	};

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'obterQuantidadeProduto',
			codigo_gtin: codigo_gtin
		},
		dataType: 'json',
		async: true, // Assíncrono para melhor performance
		success: function(response) {
			if (response.success) {
				dados.estoque = parseInt(response.qtde) || 0;
				dados.preco = parseFloat(response.preco_venda.replace(',', '.')) || 0;
				dados.peso = parseFloat(response.peso) || 0;
				dados.altura = parseFloat(response.altura) || 0;
				dados.largura = parseFloat(response.largura) || 0;
				dados.comprimento = parseFloat(response.comprimento) || 0;
				console.log(`📦 Dados da variante ${codigo_gtin}: Estoque=${dados.estoque}, Preço=R$${dados.preco}, Dimensões=${dados.peso}kg, ${dados.altura}x${dados.largura}x${dados.comprimento}cm`);
			} else {
				console.warn(`⚠️ Erro ao buscar dados da variante ${codigo_gtin}: ${response.error}`);
			}
		},
		error: function() {
			console.warn(`⚠️ Erro AJAX ao buscar dados da variante ${codigo_gtin}`);
		}
	});

	return dados;
}

/**
 * Função para buscar apenas estoque (compatibilidade)
 */
function buscarEstoqueVariante(codigo_gtin) {
	return buscarDadosVariante(codigo_gtin).estoque;
}

/**
 * Função específica para buscar dimensões de uma variante (síncrona - para compatibilidade)
 */
function buscarDimensoesVariante(codigo_gtin) {
	var dimensoes = {
		peso: 0,
		altura: 0,
		largura: 0,
		comprimento: 0
	};

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'obterDimensoesVariante',
			codigo_gtin: codigo_gtin
		},
		dataType: 'json',
		async: false, // Síncrono para retornar o valor
		success: function(response) {
			if (response.success) {
				dimensoes.peso = parseFloat(response.peso) || 0;
				dimensoes.altura = parseFloat(response.altura) || 0;
				dimensoes.largura = parseFloat(response.largura) || 0;
				dimensoes.comprimento = parseFloat(response.comprimento) || 0;
				console.log(`📏 Dimensões da variante ${codigo_gtin}: ${dimensoes.peso}kg, ${dimensoes.altura}x${dimensoes.largura}x${dimensoes.comprimento}cm`);
			} else {
				console.warn(`⚠️ Erro ao buscar dimensões da variante ${codigo_gtin}: ${response.error}`);
			}
		},
		error: function() {
			console.warn(`⚠️ Erro AJAX ao buscar dimensões da variante ${codigo_gtin}`);
		}
	});

	return dimensoes;
}

/**
 * Função assíncrona para buscar dimensões de uma variante (melhor performance)
 */
function buscarDimensoesVarianteAsync(codigo_gtin, callback) {
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'obterDimensoesVariante',
			codigo_gtin: codigo_gtin
		},
		dataType: 'json',
		async: true,
		success: function(response) {
			var dimensoes = { peso: 0, altura: 0, largura: 0, comprimento: 0 };

			if (response.success) {
				dimensoes.peso = parseFloat(response.peso) || 0;
				dimensoes.altura = parseFloat(response.altura) || 0;
				dimensoes.largura = parseFloat(response.largura) || 0;
				dimensoes.comprimento = parseFloat(response.comprimento) || 0;
				console.log(`📏 Dimensões da variante ${codigo_gtin}: ${dimensoes.peso}kg, ${dimensoes.altura}x${dimensoes.largura}x${dimensoes.comprimento}cm`);
			} else {
				console.warn(`⚠️ Erro ao buscar dimensões da variante ${codigo_gtin}: ${response.error}`);
			}

			if (callback) callback(dimensoes);
		},
		error: function() {
			console.warn(`⚠️ Erro AJAX ao buscar dimensões da variante ${codigo_gtin}`);
			if (callback) callback({ peso: 0, altura: 0, largura: 0, comprimento: 0 });
		}
	});
}









/**
 * Função para mostrar modal de seleção de produto para atualização de dimensões
 */
function mostrarModalSelecionarProdutoParaDimensoes() {
	console.log('🔧 Abrindo modal de seleção de produto para atualização de dimensões');

	var modalContent = `
		<div class="modal-content">
			<h4><i class="material-icons left">straighten</i>Atualizar Dimensões de Variantes</h4>
			<p>Digite o GTIN do produto pai (status ENSVI) para atualizar as dimensões de suas variantes na Nuvemshop:</p>
			<div class="row">
				<div class="input-field col s12">
					<input id="gtin-produto-pai-dimensoes" type="text" class="validate" placeholder="Ex: 7891234567890">
					<label for="gtin-produto-pai-dimensoes">GTIN do Produto Pai</label>
				</div>
			</div>
			<div class="row">
				<div class="col s12">
					<p class="grey-text text-darken-1">
						<i class="material-icons left tiny">info</i>
						Esta função irá buscar o produto pai (ENSVI) pelo GTIN, encontrar suas variantes
						e permitir que você edite as dimensões localmente e/ou atualize na Nuvemshop.
					</p>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
			<a href="#!" class="waves-effect waves-green btn green" onclick="buscarProdutoPaiParaDimensoes()">
				<i class="material-icons left">search</i>Buscar Produto Pai
			</a>
		</div>
	`;

	// Criar e mostrar modal
	var modalId = 'modal-selecionar-produto-dimensoes';
	if ($('#' + modalId).length) {
		$('#' + modalId).remove();
	}

	$('body').append(`<div id="${modalId}" class="modal modal-fixed-footer">${modalContent}</div>`);

	// Aguardar Materialize estar disponível e inicializar modal
	function inicializarModal() {
		if (typeof Materialize !== 'undefined' && $.fn.modal) {
			$('#' + modalId).modal({
				dismissible: true,
				opacity: 0.5,
				inDuration: 300,
				outDuration: 200
			});

			// Adicionar evento Enter no campo de input
			$('#gtin-produto-pai-dimensoes').keypress(function(e) {
				if (e.which == 13) { // Enter
					buscarProdutoPaiParaDimensoes();
				}
			});

			// Abrir modal e focar no input
			$('#' + modalId).modal('open');
			setTimeout(function() {
				$('#gtin-produto-pai-dimensoes').focus();
			}, 500);
		} else {
			// Tentar novamente após 100ms
			setTimeout(inicializarModal, 100);
		}
	}

	inicializarModal();
}

/**
 * Função para buscar produto pai e abrir modal de dimensões
 */
function buscarProdutoPaiParaDimensoes() {
	var gtinProdutoPai = $('#gtin-produto-pai-dimensoes').val().trim();

	if (!gtinProdutoPai) {
		Materialize.toast('⚠️ Digite o GTIN do produto pai', 3000, 'orange');
		$('#gtin-produto-pai-dimensoes').focus();
		return;
	}

	console.log(`🔍 Buscando produto pai com GTIN: ${gtinProdutoPai}`);

	// Buscar produto pai no banco (deve ser ENSVI)
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'buscarProdutoPaiPorGtin',
			gtin: gtinProdutoPai
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.produto) {
				console.log('✅ Produto pai encontrado:', response.produto);

				// Fechar modal de seleção
				$('#modal-selecionar-produto-dimensoes').modal('close');

				// Abrir modal de gerenciamento de dimensões
				mostrarModalGerenciamentoDimensoes(response.produto);
			} else {
				Materialize.toast('⚠️ Produto pai não encontrado ou não é ENSVI', 4000, 'orange');
				$('#gtin-produto-pai-dimensoes').focus();
			}
		},
		error: function() {
			Materialize.toast('❌ Erro ao buscar produto pai', 4000, 'red');
		}
	});
}

/**
 * Função para mostrar modal de gerenciamento de dimensões de variantes
 */
function mostrarModalGerenciamentoDimensoes(produtoPai) {
	console.log(`🔧 Abrindo modal de gerenciamento de dimensões para produto pai: ${produtoPai.codigo_gtin}`);

	// Armazenar informações do produto pai para uso posterior
	window.currentProdutoPaiGtin = produtoPai.codigo_gtin;
	window.currentProdutoPaiInterno = produtoPai.codigo_interno;
	window.currentProdutoPaiDescricao = produtoPai.descricao;

	// Buscar variantes do produto pai
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'selecionar_itens_grade',
			codigo_interno: produtoPai.codigo_interno
		},
		dataType: 'json',
		success: function(gradeResponse) {
			if (gradeResponse && gradeResponse.length > 0) {
				// Criar conteúdo do modal
				var modalContent = `
					<div class="modal-content">
						<h4><i class="material-icons left">straighten</i>Gerenciar Dimensões de Variantes</h4>
						<p><strong>Produto Pai:</strong> ${produtoPai.codigo_gtin} - ${produtoPai.descricao}</p>
						<div class="divider"></div>
						<br>
						<h6>Variantes encontradas:</h6>
						<div class="collection">
				`;

				// Adicionar cada variante com botão de editar (carregamento assíncrono das dimensões)
				gradeResponse.forEach(function(variante) {
					if (variante.codigo_gtin) {
						modalContent += `
							<div class="collection-item" id="variante-${variante.codigo_gtin}">
								<div class="row valign-wrapper" style="margin-bottom: 0;">
									<div class="col s8">
										<span class="title"><strong>${variante.codigo_gtin}</strong></span>
										<p>${variante.descricao || variante.caracteristica || 'Sem descrição'}
										<br><small class="grey-text" id="dimensoes-${variante.codigo_gtin}">
											<i class="material-icons tiny">hourglass_empty</i> Carregando dimensões...
										</small></p>
									</div>
									<div class="col s4 right-align">
										<a class="btn-small waves-effect waves-light blue"
										   onclick="editarDimensoesVariante('${variante.codigo_gtin}', '${variante.descricao || variante.caracteristica}')">
											<i class="material-icons left">edit</i>Editar
										</a>
									</div>
								</div>
							</div>
						`;
					}
				});

				modalContent += `
						</div>
						<div class="row">
							<div class="col s12">
								<div class="card-panel blue lighten-5">
									<h6><i class="material-icons left">cloud_upload</i>Sincronizar com Nuvemshop</h6>
									<p>Após editar as dimensões localmente, você pode sincronizar com a Nuvemshop:</p>
									<a class="btn waves-effect waves-light green"
									   onclick="sincronizarDimensoesComNuvemshop('${produtoPai.codigo_gtin}')">
										<i class="material-icons left">sync</i>Sincronizar Todas
									</a>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#!" class="modal-close waves-effect waves-red btn-flat">Fechar</a>
					</div>
				`;

				// Criar e mostrar modal
				var modalId = 'modal-gerenciamento-dimensoes';
				if ($('#' + modalId).length) {
					$('#' + modalId).remove();
				}

				$('body').append(`<div id="${modalId}" class="modal modal-fixed-footer" style="max-height: 80%;">${modalContent}</div>`);

				// Aguardar Materialize e inicializar modal
				function inicializarModalGerenciamento() {
					if (typeof Materialize !== 'undefined' && $.fn.modal) {
						$('#' + modalId).modal({
							dismissible: true,
							opacity: 0.5,
							inDuration: 300,
							outDuration: 200
						});

						// Abrir modal
						$('#' + modalId).modal('open');
					} else {
						setTimeout(inicializarModalGerenciamento, 100);
					}
				}

				inicializarModalGerenciamento();

				// Carregar dimensões assincronamente após modal abrir
				setTimeout(function() {
					gradeResponse.forEach(function(variante) {
						if (variante.codigo_gtin) {
							buscarDimensoesVarianteAsync(variante.codigo_gtin, function(dimensoes) {
								$('#dimensoes-' + variante.codigo_gtin).html(`
									Dimensões: ${dimensoes.peso}kg,
									${dimensoes.altura}×${dimensoes.largura}×${dimensoes.comprimento}cm
								`);
							});
						}
					});
				}, 500);

			} else {
				Materialize.toast('⚠️ Nenhuma variante encontrada para este produto pai', 4000, 'orange');
			}
		},
		error: function() {
			Materialize.toast('❌ Erro ao buscar variantes do produto pai', 4000, 'red');
		}
	});
}

/**
 * Função para editar dimensões de uma variante específica
 */
function editarDimensoesVariante(codigo_gtin, descricao) {
	console.log(`✏️ Editando dimensões da variante: ${codigo_gtin}`);

	// Buscar dimensões atuais da variante
	var dimensoesAtuais = buscarDimensoesVariante(codigo_gtin);

	var modalContent = `
		<div class="modal-content">
			<h4><i class="material-icons left">edit</i>Editar Dimensões</h4>
			<p><strong>Variante:</strong> ${codigo_gtin} - ${descricao}</p>
			<div class="divider"></div>
			<br>
			<div class="row">
				<div class="input-field col s6">
					<input id="edit-peso" type="number" step="0.01" min="0" value="${dimensoesAtuais.peso}" class="validate">
					<label for="edit-peso" class="active">Peso (kg)</label>
				</div>
				<div class="input-field col s6">
					<input id="edit-altura" type="number" step="0.01" min="0" value="${dimensoesAtuais.altura}" class="validate">
					<label for="edit-altura" class="active">Altura (cm)</label>
				</div>
			</div>
			<div class="row">
				<div class="input-field col s6">
					<input id="edit-largura" type="number" step="0.01" min="0" value="${dimensoesAtuais.largura}" class="validate">
					<label for="edit-largura" class="active">Largura (cm)</label>
				</div>
				<div class="input-field col s6">
					<input id="edit-comprimento" type="number" step="0.01" min="0" value="${dimensoesAtuais.comprimento}" class="validate">
					<label for="edit-comprimento" class="active">Comprimento (cm)</label>
				</div>
			</div>
			<div class="row">
				<div class="col s12">
					<p class="grey-text">
						<i class="material-icons left tiny">info</i>
						As dimensões serão salvas no banco de dados local.
						Use a função "Sincronizar" para enviar para a Nuvemshop.
					</p>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
			<a href="#!" class="waves-effect waves-green btn green" onclick="salvarDimensoesVariante('${codigo_gtin}')">
				<i class="material-icons left">save</i>Salvar
			</a>
		</div>
	`;

	// Criar e mostrar modal
	var modalId = 'modal-editar-dimensoes';
	if ($('#' + modalId).length) {
		$('#' + modalId).remove();
	}

	$('body').append(`<div id="${modalId}" class="modal modal-fixed-footer">${modalContent}</div>`);

	// Aguardar Materialize e inicializar modal
	function inicializarModalEdicao() {
		if (typeof Materialize !== 'undefined' && $.fn.modal) {
			$('#' + modalId).modal({
				dismissible: true,
				opacity: 0.5,
				inDuration: 300,
				outDuration: 200
			});

			// Abrir modal
			$('#' + modalId).modal('open');
		} else {
			setTimeout(inicializarModalEdicao, 100);
		}
	}

	inicializarModalEdicao();
}

/**
 * Função para salvar dimensões de uma variante no banco de dados
 */
function salvarDimensoesVariante(codigo_gtin) {
	console.log(`💾 Salvando dimensões da variante: ${codigo_gtin}`);

	// Coletar valores dos campos
	var peso = $('#edit-peso').val();
	var altura = $('#edit-altura').val();
	var largura = $('#edit-largura').val();
	var comprimento = $('#edit-comprimento').val();

	// Validar valores
	if (peso < 0 || altura < 0 || largura < 0 || comprimento < 0) {
		Materialize.toast('⚠️ As dimensões não podem ser negativas', 4000, 'orange');
		return;
	}

	console.log(`📏 Salvando dimensões: ${peso}kg, ${altura}x${largura}x${comprimento}cm`);

	// Salvar no banco de dados
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'atualizarDimensoesVariante',
			codigo_gtin: codigo_gtin,
			peso: peso,
			altura: altura,
			largura: largura,
			comprimento: comprimento
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				console.log(`✅ Dimensões da variante ${codigo_gtin} salvas com sucesso`);
				Materialize.toast('✅ Dimensões salvas com sucesso!', 4000, 'green');

				// Fechar modal de edição
				$('#modal-editar-dimensoes').modal('close');

				// Atualizar apenas as dimensões no modal se estiver aberto (sem recarregar tudo)
				setTimeout(function() {
					if ($('#modal-gerenciamento-dimensoes').hasClass('open')) {
						// Atualizar apenas as dimensões da variante específica
						buscarDimensoesVarianteAsync(codigo_gtin, function(dimensoes) {
							$('#dimensoes-' + codigo_gtin).html(`
								Dimensões: ${dimensoes.peso}kg,
								${dimensoes.altura}×${dimensoes.largura}×${dimensoes.comprimento}cm
							`);
						});
					}
				}, 500);

			} else {
				console.error(`❌ Erro ao salvar dimensões da variante ${codigo_gtin}: ${response.error}`);
				Materialize.toast(`❌ Erro ao salvar: ${response.error}`, 4000, 'red');
			}
		},
		error: function() {
			console.error(`❌ Erro AJAX ao salvar dimensões da variante ${codigo_gtin}`);
			Materialize.toast('❌ Erro de comunicação ao salvar dimensões', 4000, 'red');
		}
	});
}

/**
 * Função para sincronizar dimensões de todas as variantes com a Nuvemshop
 */
function sincronizarDimensoesComNuvemshop(gtinProdutoPai) {
	console.log(`🔄 Iniciando sincronização de dimensões com Nuvemshop para produto pai: ${gtinProdutoPai}`);

	// Mostrar progresso
	var progressoHtml = `
		<div id="progresso-sincronizacao" class="card-panel">
			<h5><i class="material-icons left">sync</i>Sincronizando Dimensões com Nuvemshop</h5>
			<div class="progress">
				<div class="determinate" style="width: 0%"></div>
			</div>
			<p id="status-sincronizacao">Buscando produto na Nuvemshop...</p>
			<div id="log-sincronizacao" class="collection" style="max-height: 300px; overflow-y: auto;"></div>
		</div>
	`;

	// Adicionar progresso à página
	if ($('#progresso-sincronizacao').length) {
		$('#progresso-sincronizacao').remove();
	}
	$('main').prepend(progressoHtml);

	// Fechar modal de gerenciamento
	$('#modal-gerenciamento-dimensoes').modal('close');

	// Passo 1: Buscar produto na Nuvemshop usando SKU (GTIN do produto pai)
	$.ajax({
		url: 'produtos_ajax_sincronizacao.php',
		type: 'GET',
		data: {
			request: 'buscarProdutoPorSku',
			sku: gtinProdutoPai
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.produto) {
				console.log('✅ Produto encontrado na Nuvemshop:', response.produto);

				$('#log-sincronizacao').append(`
					<div class="collection-item">
						<i class="material-icons left green-text">check_circle</i>
						Produto encontrado na Nuvemshop: ${response.produto.name}
					</div>
				`);

				// Passo 2: Processar variantes
				if (response.produto.variants && response.produto.variants.length > 0) {
					processarVariantesParaSincronizacao(response.produto, gtinProdutoPai);
				} else {
					finalizarSincronizacao(false, 'Nenhuma variante encontrada no produto da Nuvemshop');
				}
			} else {
				finalizarSincronizacao(false, 'Produto não encontrado na Nuvemshop com SKU: ' + gtinProdutoPai);
			}
		},
		error: function() {
			finalizarSincronizacao(false, 'Erro ao buscar produto na Nuvemshop');
		}
	});
}

/**
 * Função para processar variantes para sincronização
 */
function processarVariantesParaSincronizacao(produtoNuvemshop, gtinProdutoPai) {
	console.log('🔄 Processando variantes para sincronização:', produtoNuvemshop.variants);

	$('#status-sincronizacao').text('Buscando variantes locais...');

	// Buscar variantes locais do produto pai
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'POST',
		data: {
			request: 'selecionar_itens_grade',
			codigo_interno: window.currentProdutoPaiInterno
		},
		dataType: 'json',
		success: function(variantesLocais) {
			if (variantesLocais && variantesLocais.length > 0) {
				console.log('✅ Variantes locais encontradas:', variantesLocais);

				$('#log-sincronizacao').append(`
					<div class="collection-item">
						<i class="material-icons left blue-text">info</i>
						Encontradas ${variantesLocais.length} variantes locais
					</div>
				`);

				// Mapear variantes da Nuvemshop por barcode
				var variantesNuvemshopMap = {};
				produtoNuvemshop.variants.forEach(function(variant) {
					if (variant.barcode) {
						variantesNuvemshopMap[variant.barcode] = variant;
					}
				});

				// Processar cada variante local
				var variantesParaAtualizar = [];
				variantesLocais.forEach(function(varianteLocal) {
					if (varianteLocal.codigo_gtin && variantesNuvemshopMap[varianteLocal.codigo_gtin]) {
						var varianteNuvemshop = variantesNuvemshopMap[varianteLocal.codigo_gtin];
						var dimensoesLocais = buscarDimensoesVariante(varianteLocal.codigo_gtin);

						variantesParaAtualizar.push({
							local: varianteLocal,
							nuvemshop: varianteNuvemshop,
							dimensoes: dimensoesLocais,
							product_id: produtoNuvemshop.id,
							variant_id: varianteNuvemshop.id
						});
					}
				});

				if (variantesParaAtualizar.length > 0) {
					console.log(`📦 ${variantesParaAtualizar.length} variantes serão atualizadas`);
					atualizarVariantesNuvemshop(variantesParaAtualizar);
				} else {
					finalizarSincronizacao(false, 'Nenhuma variante local corresponde às variantes da Nuvemshop');
				}
			} else {
				finalizarSincronizacao(false, 'Nenhuma variante local encontrada');
			}
		},
		error: function() {
			finalizarSincronizacao(false, 'Erro ao buscar variantes locais');
		}
	});
}

/**
 * Função para atualizar variantes na Nuvemshop
 */
function atualizarVariantesNuvemshop(variantesParaAtualizar) {
	console.log(`🔄 Atualizando ${variantesParaAtualizar.length} variantes na Nuvemshop`);

	var processadas = 0;
	var sucessos = 0;
	var erros = 0;
	var total = variantesParaAtualizar.length;

	function processarProximaVariante(index) {
		if (index >= total) {
			// Finalizado
			finalizarSincronizacao(sucessos > 0, `Concluído! ${sucessos} sucessos, ${erros} erros`);
			return;
		}

		var variante = variantesParaAtualizar[index];
		var progresso = Math.round((index / total) * 100);

		$('#status-sincronizacao').text(`Atualizando variante ${index + 1}/${total}: ${variante.local.codigo_gtin}`);
		$('.determinate').css('width', progresso + '%');

		// Preparar dados para atualização (apenas dimensões)
		var dadosAtualizacao = {
			weight: variante.dimensoes.peso,
			height: variante.dimensoes.altura,
			width: variante.dimensoes.largura,
			depth: variante.dimensoes.comprimento
		};

		console.log(`📏 Atualizando variante ${variante.local.codigo_gtin}:`, dadosAtualizacao);

		// Adicionar log
		$('#log-sincronizacao').append(`
			<div class="collection-item">
				<i class="material-icons left blue-text">sync</i>
				Atualizando ${variante.local.codigo_gtin}: ${dadosAtualizacao.weight}kg, ${dadosAtualizacao.height}×${dadosAtualizacao.width}×${dadosAtualizacao.depth}cm
			</div>
		`);

		// Atualizar na Nuvemshop
		$.ajax({
			url: 'produtos_ajax_sincronizacao.php',
			type: 'POST',
			data: {
				request: 'atualizarVarianteEspecifica',
				product_id: variante.product_id,
				variant_id: variante.variant_id,
				dados: JSON.stringify(dadosAtualizacao)
			},
			dataType: 'json',
			success: function(response) {
				processadas++;

				if (response.success) {
					sucessos++;
					$('#log-sincronizacao').append(`
						<div class="collection-item">
							<i class="material-icons left green-text">check_circle</i>
							${variante.local.codigo_gtin}: ✅ Atualizado com sucesso
						</div>
					`);
				} else {
					erros++;
					$('#log-sincronizacao').append(`
						<div class="collection-item">
							<i class="material-icons left red-text">error</i>
							${variante.local.codigo_gtin}: ❌ ${response.error || 'Erro desconhecido'}
						</div>
					`);
				}

				// Scroll para o final do log
				var logElement = $('#log-sincronizacao');
				if (logElement.length > 0) {
					logElement.scrollTop(logElement[0].scrollHeight);
				}

				// Processar próxima variante após um pequeno delay
				setTimeout(function() {
					processarProximaVariante(index + 1);
				}, 1000);
			},
			error: function() {
				processadas++;
				erros++;

				$('#log-sincronizacao').append(`
					<div class="collection-item">
						<i class="material-icons left red-text">error</i>
						${variante.local.codigo_gtin}: ❌ Erro de comunicação
					</div>
				`);

				// Scroll para o final do log
				var logElement = $('#log-sincronizacao');
				if (logElement.length > 0) {
					logElement.scrollTop(logElement[0].scrollHeight);
				}

				// Processar próxima variante após um pequeno delay
				setTimeout(function() {
					processarProximaVariante(index + 1);
				}, 1000);
			}
		});
	}

	// Iniciar processamento
	processarProximaVariante(0);
}

/**
 * Função para finalizar sincronização
 */
function finalizarSincronizacao(sucesso, mensagem) {
	console.log(`🏁 Sincronização finalizada: ${sucesso ? 'Sucesso' : 'Erro'} - ${mensagem}`);

	$('#status-sincronizacao').text(mensagem);
	$('.determinate').css('width', '100%');

	if (sucesso) {
		$('#log-sincronizacao').append(`
			<div class="collection-item">
				<i class="material-icons left green-text">check_circle</i>
				✅ ${mensagem}
			</div>
		`);
		Materialize.toast('✅ Sincronização concluída com sucesso!', 4000, 'green');
	} else {
		$('#log-sincronizacao').append(`
			<div class="collection-item">
				<i class="material-icons left red-text">error</i>
				❌ ${mensagem}
			</div>
		`);
		Materialize.toast('❌ Erro na sincronização: ' + mensagem, 4000, 'red');
	}

	// Remover progresso após alguns segundos
	setTimeout(function() {
		$('#progresso-sincronizacao').fadeOut(2000, function() {
			$(this).remove();
		});
	}, 5000);
}

/**
 * Mostra modal de sincronização para variantes e executa sincronização automática
 */
function mostrarModalSincronizacaoVariante(codigo_gtin) {
	console.log("🔄 Iniciando sincronização automática para variante:", codigo_gtin);

	// Mostrar toast informativo
	Materialize.toast('<i class="material-icons">sync</i> Iniciando sincronização automática de variantes...', 3000, 'blue');

	// Aguardar um pouco e chamar sincronização
	setTimeout(function() {
		if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
			console.log("✅ Chamando sincronização automática para variantes");
			sincronizarStatusProdutosNuvemshop(true); // true = automático
		} else {
			console.error("❌ Função de sincronização não encontrada");
			Materialize.toast('<i class="material-icons">error</i> Erro: Sistema de sincronização não disponível', 5000, 'red');
		}
	}, 1000);

	// Voltar à tela principal após iniciar sincronização
	setTimeout(function() {
		limparProdutos();
		$('#userTable tbody').empty();
		$("#produto_principal").show();
		$("#produto_cadastro").hide();
	}, 2000);
}

/**
 * Função para editar variante específica na Nuvemshop
 */
function editarVarianteEspecifica(barcode, dadosProduto) {
	console.log(`🔄 Editando variante específica: ${barcode}`);
	$("#loading").show();

	// 1. Buscar a variante na Nuvemshop pelo barcode
	$.ajax({
		url: 'produtos_ajax_sincronizacao.php',
		type: 'POST',
		data: {
			request: 'buscarVariantePorBarcode',
			barcode: barcode
		},
		dataType: 'json',
		success: function(response) {
			if (response.success && response.variante) {
				var variante = response.variante;
				console.log(`✅ Variante encontrada: Product ID ${variante.product_id}, Variant ID ${variante.variant_id}`);

				// 2. Preparar dados para atualização
				var dadosAtualizacao = {
					price: parseFloat(dadosProduto.preco_venda.replace(',', '.')),
					stock_management: true,
					weight: parseFloat((dadosProduto.peso || "0").replace(',', '.')),
					depth: parseFloat((dadosProduto.comprimento || "0").replace(',', '.')),
					width: parseFloat((dadosProduto.largura || "0").replace(',', '.')),
					height: parseFloat((dadosProduto.altura || "0").replace(',', '.'))
				};

				// 3. Buscar estoque atual da variante
				var dadosVariante = buscarDadosVariante(barcode);
				dadosAtualizacao.stock = dadosVariante.estoque;

				console.log(`📦 Dados para atualização:`, dadosAtualizacao);

				// 4. Atualizar a variante na Nuvemshop
				$.ajax({
					url: 'produtos_ajax_sincronizacao.php',
					type: 'POST',
					data: {
						request: 'atualizarVarianteEspecifica',
						product_id: variante.product_id,
						variant_id: variante.variant_id,
						dados: JSON.stringify(dadosAtualizacao)
					},
					dataType: 'json',
					success: function(updateResponse) {
						$("#loading").hide();

						if (updateResponse.success) {
							console.log(`✅ Variante atualizada com sucesso: ${barcode}`);
							Materialize.toast('<i class="material-icons">check_circle</i> Variante atualizada com sucesso na Nuvemshop!', 4000, 'green');

							// Auto-sincronização após sucesso
							console.log('🔄 Iniciando auto-sincronização após edição de variante...');
							setTimeout(function() {
								if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
									sincronizarStatusProdutosNuvemshop(true); // true = automático
								}
							}, 2000);
						} else {
							console.error(`❌ Erro ao atualizar variante: ${updateResponse.error}`);
							Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar variante: ' + updateResponse.error, 5000, 'red');
						}
					},
					error: function(xhr) {
						$("#loading").hide();
						console.error('❌ Erro AJAX ao atualizar variante:', xhr.responseText);
						Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar variante na Nuvemshop', 5000, 'red');
					}
				});
			} else {
				$("#loading").hide();
				console.error(`❌ Variante não encontrada: ${barcode}`);
				Materialize.toast('<i class="material-icons">error</i> Variante não encontrada na Nuvemshop', 5000, 'red');
			}
		},
		error: function(xhr) {
			$("#loading").hide();
			console.error('❌ Erro AJAX ao buscar variante:', xhr.responseText);
			Materialize.toast('<i class="material-icons">error</i> Erro ao buscar variante na Nuvemshop', 5000, 'red');
		}
	});
}

$(document).ajaxStart(function () {
	$("#loading").show();
});
$(document).ajaxStop(function () {
	$("#loading").hide();
});
function enviarImagens() {
	var file = $('#input1').prop("files")[0];
	var file2 = $('#input2').prop("files")[0];
	var file3 = $('#input3').prop("files")[0];
	var file4 = $('#input4').prop("files")[0];
	var file5 = $('#input5').prop("files")[0];
	var form = new FormData();
	form.append("image", file);
	form.append("codigo", document.getElementById('codigo_gtin').value);
	form.append("categoria", document.getElementById('categoria').value);
	form.append("image2", file2);
	form.append("image3", file3);
	form.append("image4", file4);
	form.append("image5", file5);
	$.ajax({
		url: 'uploadFile.php',
		type: 'POST',
		contentType: false,
		processData: false,
		data: form,
		success: function (result) {
			alert(result);
			document.getElementById("input1").value = '';
			document.getElementById("imagem1").value = '';
			document.getElementById("input2").value = '';
			document.getElementById("imagem2").value = '';
			document.getElementById("input3").value = '';
			document.getElementById("imagem3").value = '';
			document.getElementById("input4").value = '';
			document.getElementById("imagem4").value = '';
			document.getElementById("input5").value = '';
			document.getElementById("imagemCategoria").value = '';
		}
	});
}
function gravarProdutos(callback) {
	// verificar se estamos na tela principal ou na tela de edição
	if ($("#produto_principal").is(":visible")) {
		if (produtosSelecionados.length > 0) {
			console.log("Exportando produtos selecionados da tela principal:", produtosSelecionados);

			// mostrar toast informando que os produtos selecionados serão exportados
			Materialize.toast('Exportando ' + produtosSelecionados.length + ' produtos selecionados...', 3000, 'blue');

			exportarProdutosSelecionados();
		} else {
			Materialize.toast('Nenhum produto selecionado para exportar.', 3000, 'orange');
		}
		return;
	}

	//inserir produtoss
	//aba inf basicas
	var codigo_interno = document.getElementById('codigo_interno').value;
	var codigo_gtin = document.getElementById('codigo_gtin').value;
	var descricao = document.getElementById('descricao').value;
	var descricao_detalhada = document.getElementById('descricao_detalhada').value;
	var grupo = document.getElementById('grupo').value;
	var subgrupo = document.getElementById('subgrupo').value;
	var categoria = document.getElementById('categoria').value;
	var unidade = document.getElementById('unidade').value;
	var preco_venda = document.getElementById('preco_venda').value;
	var preco_compra = document.getElementById('preco_compra').value;
	var perc_lucro = document.getElementById('perc_lucro').value;
	var ncm = document.getElementById('ncm').value;
	var cest = document.getElementById('cest').value;
	var cfop = document.getElementById('cfop').value;
	var situacao_tributaria = document.getElementById('situacao_tributaria').value;
	var perc_icms = document.getElementById('perc_icms').value;
	var produto_balanca = document.getElementById('produto_balanca').checked;
	var validade = document.getElementById('vadidade').value;
	var data_cadastro = document.getElementById('data_cadastro').value;
	var data_alteracao = document.getElementById('data_alteracao').value;
	var vender_ecomerce = document.getElementById('vender_ecomerce').checked;
	var produto_producao = document.getElementById('produto_producao').checked;
	var codigo_fornecedor = document.getElementById('fornecedor').value;
	//aba outros
	var perc_desc_a = document.getElementById('perc_desc_a').value;
	var val_desc_a = document.getElementById('val_desc_a').value;
	var perc_desc_b = document.getElementById('perc_desc_b').value;
	var val_desc_b = document.getElementById('val_desc_b').value
	var perc_desc_c = document.getElementById('perc_desc_c').value;
	var val_desc_c = document.getElementById('val_desc_c').value;
	var perc_desc_d = document.getElementById('perc_desc_d').value;
	var val_desc_d = document.getElementById('val_desc_d').value;
	var perc_desc_e = document.getElementById('perc_desc_e').value;
	var val_desc_e = document.getElementById('val_desc_e').value;
	var aliquota_calculo_credito = document.getElementById('aliquota_calculo_credito').value;
	var perc_dif = document.getElementById('perc_dif').value;
	var mod_deter_bc_icms = document.getElementById('mod_deter_bc_icms').value;
	var perc_redu_icms = document.getElementById('perc_redu_icms').value;
	var mod_deter_bc_icms_st = document.getElementById('mod_deter_bc_icms_st').value;
	var tamanho = document.getElementById('tamanho').value;
	var comprimento = document.getElementById('comprimento').value;
	var largura = document.getElementById('largura').value;
	var altura = document.getElementById('altura').value;
	var peso = document.getElementById('peso').value;

	var vencimento = document.getElementById('vencimento').value;
	var descricao_personalizada = document.getElementById('descricao_personalizada').checked;
	var aliq_fcp_st = document.getElementById('aliq_fcp_st').value;
	var valorGelado = document.getElementById('valorGelado').value;
	var prod_desc_etiqueta = document.getElementById('prod_desc_etiqueta').value;
	var novoCodigo = document.getElementById('novoCodigo').value;
	var qtde = document.getElementById('qtde').value;
	var qtde_min = document.getElementById('qtde_min').value;
	var aliq_fcp = document.getElementById('aliq_fcp').value;
	var perc_redu_icms_st = document.getElementById('perc_redu_icms_st').value;
	var perc_mv_adic_icms_st = document.getElementById('perc_mv_adic_icms_st').value;
	var aliq_icms_st = document.getElementById('aliq_icms_st').value;
	var inativo = document.getElementById('inativo').checked;
	//aba IPI/PIS/COFINS
	//IPI
	var ipi_reducao_bc = document.getElementById('ipi_reducao_bc').value;
	var aliquota_ipi = document.getElementById('aliquota_ipi').value;
	var ipi_reducao_bc_st = document.getElementById('ipi_reducao_bc_st').value;
	var aliquota_ipi_st = document.getElementById('aliquota_ipi_st').value;
	var cst_ipi = document.getElementById('cst_ipi').value;
	var calculo_ipi = document.getElementById('calculo_ipi').value;
	//PIS
	var pis_reducao_bc = document.getElementById('pis_reducao_bc').value;
	var aliquota_pis = document.getElementById('aliquota_pis').value;
	var pis_reducao_bc_st = document.getElementById('pis_reducao_bc_st').value;
	var aliquota_pis_st = document.getElementById('aliquota_pis_st').value;
	var cst_pis = document.getElementById('cst_pis').value;
	var calculo_pis = document.getElementById('calculo_pis').value;
	//COFINS
	var cofins_reducao_bc = document.getElementById('cofins_reducao_bc').value;
	var aliquota_cofins = document.getElementById('aliquota_cofins').value;
	var cofins_reducao_bc_st = document.getElementById('cofins_reducao_bc_st').value;
	var aliquota_cofins_st = document.getElementById('aliquota_cofins_st').value;
	var cst_cofins = document.getElementById('cst_cofins').value;
	var calculo_cofins = document.getElementById('calculo_cofing').value;
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarProdutos',
			codigo_interno: codigo_interno,
			codigo_gtin: codigo_gtin,
			descricao: descricao,
			descricao_detalhada: descricao_detalhada,
			grupo: grupo,
			subgrupo: subgrupo,
			categoria: categoria,
			unidade: unidade,
			preco_venda: preco_venda,
			preco_compra: preco_compra,
			perc_lucro: perc_lucro,
			ncm: ncm,
			cest: cest,
			cfop: cfop,
			situacao_tributaria: situacao_tributaria,
			perc_icms: perc_icms,
			produto_balanca: produto_balanca,
			validade: validade,
			data_cadastro: data_cadastro,
			data_alteracao: data_alteracao,
			vender_ecomerce: vender_ecomerce,
			produto_producao: produto_producao,
			codigo_fornecedor: codigo_fornecedor,
			perc_desc_a: perc_desc_a,
			val_desc_a: val_desc_a,
			perc_desc_b: perc_desc_b,
			val_desc_b: val_desc_b,
			perc_desc_c: perc_desc_c,
			val_desc_c: val_desc_c,
			perc_desc_d: perc_desc_d,
			val_desc_d: val_desc_d,
			perc_desc_e: perc_desc_e,
			val_desc_e: val_desc_e,
			aliquota_calculo_credito: aliquota_calculo_credito,
			perc_dif: perc_dif,
			mod_deter_bc_icms: mod_deter_bc_icms,
			perc_redu_icms: perc_redu_icms,
			mod_deter_bc_icms_st: mod_deter_bc_icms_st,
			tamanho: tamanho,
			comprimento: comprimento,
			largura: largura,
			altura: altura,
			peso: peso,
			vencimento: vencimento,
			aliq_fcp_st: aliq_fcp_st,
			valorGelado: valorGelado,
			prod_desc_etiqueta: prod_desc_etiqueta,
			novoCodigo: novoCodigo,
			qtde: qtde,
			qtde_min: qtde_min,
			aliq_fcp: aliq_fcp,
			perc_redu_icms_st: perc_redu_icms_st,
			perc_mv_adic_icms_st: perc_mv_adic_icms_st,
			aliq_icms_st: aliq_icms_st,
			ipi_reducao_bc: ipi_reducao_bc,
			aliquota_ipi: aliquota_ipi,
			ipi_reducao_bc_st: ipi_reducao_bc_st,
			aliquota_ipi_st: aliquota_ipi_st,
			cst_ipi: cst_ipi,
			calculo_ipi: calculo_ipi,
			pis_reducao_bc: pis_reducao_bc,
			aliquota_pis: aliquota_pis,
			pis_reducao_bc_st: pis_reducao_bc_st,
			aliquota_pis_st: aliquota_pis_st,
			cst_pis: cst_pis,
			calculo_pis: calculo_pis,
			cofins_reducao_bc: cofins_reducao_bc,
			aliquota_cofins: aliquota_cofins,
			cofins_reducao_bc_st: cofins_reducao_bc_st,
			aliquota_cofins_st: aliquota_cofins_st,
			cst_cofins: cst_cofins,
			calculo_cofins: calculo_cofins,
		},
		dataType: 'json',
		success: function (response) {
			console.log("Resposta do servidor:", response);

			// Verificar se a resposta é uma string e tentar convertê-la para objeto
			if (typeof response === 'string') {
				try {
					response = JSON.parse(response);
				} catch (e) {
					console.error("Erro ao analisar resposta do servidor:", e);
				}
			}

			// Verificar se houve erro
			if (response && response.success === false) {
				Materialize.toast('<i class="material-icons">error</i> ' + response.error, 4000, 'red');
				$("#loading").hide();
				return;
			}

			// Se chegou aqui, o produto foi salvo com sucesso
			Materialize.toast('<i class="material-icons">check_circle</i> Produto salvo com sucesso!', 4000, 'green');

			// ✅ CALLBACK: Se foi chamado com callback (para auto-salvar), executar
			if (typeof callback === 'function') {
				console.log('✅ Executando callback com código:', response.codigo_interno || codigo_interno);
				callback(response.codigo_interno || codigo_interno);
				return; // Sair aqui quando chamado via callback
			}

			// ✅ VERIFICAÇÃO ESPECIAL: Para variantes (ENSV), usar sincronização automática
			if (window.currentProductStatus === 'ENSV') {
				console.log("🔄 Produto ENSV detectado, iniciando sincronização automática");
				mostrarModalSincronizacaoVariante(codigo_gtin);
				return; // Sair aqui para variantes
			}

			// Verificar se o produto está marcado para vender no e-commerce
			if (vender_ecomerce) {
				// Verificar se o produto já está na lista de selecionados
				if (!produtosSelecionados.includes(codigo_interno.toString())) {
					produtosSelecionados.push(codigo_interno.toString());
					console.log("Produto adicionado para exportação:", codigo_interno);
					Materialize.toast('<i class="material-icons">info</i> Produto adicionado à lista de exportação', 3000, 'blue');
				}

				// Para produtos normais marcados para e-commerce, perguntar se deseja exportar
				if (confirm("Deseja exportar o produto para o e-commerce agora?")) {
					// Buscar dados atualizados do banco antes de exportar
					console.log("🔄 Buscando dados atualizados do banco para exportação...");

					$.ajax({
						url: 'produtos_ajax.php',
						type: 'POST',
						data: {
							request: 'obterDadosCompletoProduto',
							codigo_interno: codigo_interno
						},
						dataType: 'json',
						success: function(dadosAtualizados) {
							if (dadosAtualizados.success && dadosAtualizados.produto) {
								var produto = dadosAtualizados.produto;
								console.log("✅ Dados atualizados obtidos:", produto);

								// Exportar com dados atualizados do banco
								exportarProdutoParaNuvemshop(
									produto.codigo_interno,
									produto.codigo_gtin,
									produto.descricao,
									produto.descricao_detalhada,
									produto.preco_venda,
									produto.peso,        // ← Valor atualizado do banco
									produto.altura,      // ← Valor atualizado do banco
									produto.largura,     // ← Valor atualizado do banco
									produto.comprimento  // ← Valor atualizado do banco
								);
							} else {
								console.warn("⚠️ Erro ao buscar dados atualizados, usando valores do formulário");
								// Fallback: usar valores do formulário
								exportarProdutoParaNuvemshop(
									codigo_interno,
									codigo_gtin,
									descricao,
									descricao_detalhada,
									preco_venda,
									peso,
									altura,
									largura,
									comprimento
								);
							}
						},
						error: function() {
							console.warn("⚠️ Erro AJAX ao buscar dados atualizados, usando valores do formulário");
							// Fallback: usar valores do formulário
							exportarProdutoParaNuvemshop(
								codigo_interno,
								codigo_gtin,
								descricao,
								descricao_detalhada,
								preco_venda,
								peso,
								altura,
								largura,
								comprimento
							);
						}
					});
				}

				// Aguardar um pouco antes de voltar à tela principal
				setTimeout(function() {
					limparProdutos();
					$('#userTable tbody').empty();
					$("#produto_principal").show();
					$("#produto_cadastro").hide();
				}, 2000);
			} else {
				// Remover da lista de selecionados se estiver
				var index = produtosSelecionados.indexOf(codigo_interno.toString());
				if (index !== -1) {
					produtosSelecionados.splice(index, 1);
					console.log("Produto removido da exportação:", codigo_interno);
					Materialize.toast('<i class="material-icons">info</i> Produto removido da lista de exportação', 3000, 'blue');
				}

				// Voltar à tela principal
				limparProdutos();
				$('#userTable tbody').empty();
				$("#produto_principal").show();
				$("#produto_cadastro").hide();
			}
		},
		error: function(xhr) {
			console.error("Erro ao salvar produto:", xhr.responseText);
			Materialize.toast('<i class="material-icons">error</i> Erro ao salvar produto. Verifique o console para mais detalhes.', 4000, 'red');
			$("#loading").hide();
		}
	});
}
function retornarPrincipal() {
	limparProdutos();
	$('#userTable tbody').empty();
	$("#produto_principal").show();
	$("#produto_cadastro").hide();
}
function limparProdutos() {
	//aba inf basicas
	document.getElementById('codigo_interno').value = 0;
	document.getElementById('codigo_gtin').value = 0;
	document.getElementById('descricao').value = '';
	document.getElementById('descricao_detalhada').value = '';
	document.getElementById('grupo').value = 'PRINCIPAL';
	document.getElementById('subgrupo').value = 'PRINCIPAL';
	document.getElementById('categoria').value = 'PRINCIPAL';
	document.getElementById('unidade').value = 'UN';
	document.getElementById('preco_venda').value = 0;
	document.getElementById('preco_compra').value = 0;
	document.getElementById('perc_lucro').value = 0;
	document.getElementById('ncm').value = '';
	document.getElementById('cest').value = '';
	document.getElementById('cfop').value = 5102;
	document.getElementById('situacao_tributaria').value = 102;
	document.getElementById('perc_icms').value = 0;
	document.getElementById('produto_balanca').checked = false;
	document.getElementById('vadidade').value = 0;
	document.getElementById('data_cadastro').value = '';
	document.getElementById('data_alteracao').value = '';
	document.getElementById('vender_ecomerce').checked = false;
	document.getElementById('produto_producao').checked = false;
	document.getElementById('fornecedor').value = 0;
	//aba outros
	document.getElementById('perc_desc_a').value = 0;
	document.getElementById('val_desc_a').value = 0;
	document.getElementById('perc_desc_b').value = 0;
	document.getElementById('val_desc_b').value = 0;
	document.getElementById('perc_desc_c').value = 0;
	document.getElementById('val_desc_c').value = 0;
	document.getElementById('perc_desc_d').value = 0;
	document.getElementById('val_desc_d').value = 0;
	document.getElementById('perc_desc_e').value = 0;
	document.getElementById('val_desc_e').value = 0;
	document.getElementById('aliquota_calculo_credito').value = 0;
	document.getElementById('perc_dif').value = 0;
	document.getElementById('mod_deter_bc_icms').value = 'Margem valor agregado';
	document.getElementById('perc_redu_icms').value = 0;
	document.getElementById('mod_deter_bc_icms_st').value = 'Margem valor agregado';
	document.getElementById('tamanho').value = 0;
	document.getElementById('comprimento').value = 0;
	document.getElementById('largura').value = 0;
	document.getElementById('altura').value = 0;
	document.getElementById('peso').value = 0;
	document.getElementById('vencimento').value = '';
	document.getElementById('descricao_personalizada').checked = false;
	document.getElementById('aliq_fcp_st').value = 0;
	document.getElementById('valorGelado').value = 0;
	document.getElementById('prod_desc_etiqueta').value = '';
	document.getElementById('novoCodigo').value = '';
	document.getElementById('qtde').value = 0;
	document.getElementById('qtde_min').value = 0;
	document.getElementById('aliq_fcp').value = 0;
	document.getElementById('perc_redu_icms_st').value = 0;
	document.getElementById('perc_mv_adic_icms_st').value = 0;
	document.getElementById('aliq_icms_st').value = 0;
	document.getElementById('inativo').checked = false;
	//aba IPI/PIS/COFINS
	//IPI
	document.getElementById('ipi_reducao_bc').value = 0;
	document.getElementById('aliquota_ipi').value = 0;
	document.getElementById('ipi_reducao_bc_st').value = 0;
	document.getElementById('aliquota_ipi_st').value = 0;
	document.getElementById('cst_ipi').value = 0;
	document.getElementById('calculo_ipi').value = 'Aliquota';
	//PIS
	document.getElementById('pis_reducao_bc').value = 0;
	document.getElementById('aliquota_pis').value = 0;
	document.getElementById('pis_reducao_bc_st').value = 0;
	document.getElementById('aliquota_pis_st').value = 0;
	document.getElementById('cst_pis').value = 99
	document.getElementById('calculo_pis').value = 'Aliquota';
	//COFINS
	document.getElementById('cofins_reducao_bc').value = 0;
	document.getElementById('aliquota_cofins').value = 0;
	document.getElementById('cofins_reducao_bc_st').value = 0;
	document.getElementById('aliquota_cofins_st').value = 0;
	document.getElementById('cst_cofins').value = 99;
	document.getElementById('calculo_cofing').value = 'Aliquota';
	//grade
	document.getElementById('prod_gd_codigo_gtin').value = '';
	document.getElementById('prod_gd_nome').value = '';
	document.getElementById('prod_gd_variacao').value = '';
	document.getElementById('prod_gd_caracteristica').value = '';

	// Reinicializar o collapsible para garantir que as abas funcionem corretamente
	// Verificar se Materialize está carregado antes de usar collapsible
	if (typeof $ !== 'undefined' && $.fn.collapsible) {
		$('.collapsible').collapsible();
	}
}
function selecionouFornecedor() {
	//alert(document.getElementById('fornecedor').value);
}
function adicionarGrupo(grupo) {
	var str_grupo = "<option value='" + grupo.toUpperCase() + "' selected>" + grupo.toUpperCase() + "</option>";
	$("#grupo").append(str_grupo);
}
function adicionarSubGrupo(subgrupo) {
	var str_subgrupo = "<option value='" + subgrupo.toUpperCase() + "' selected>" + subgrupo.toUpperCase() + "</option>";
	$("#subgrupo").append(str_subgrupo);
}
function adicionarCategoria(categoria) {
	var str_categoria = "<option value='" + categoria.toUpperCase() + "' selected>" + categoria.toUpperCase() + "</option>";
	$("#categoria").append(str_categoria);
}
function adicionarUnidade(unidade) {
	var str_unidade = "<option value='" + unidade.toUpperCase() + "' selected>" + unidade.toUpperCase() + "</option>";
	$("#unidade").append(str_unidade);
}
function adicionarFornecedor(fornecedor) {
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'adicionarFornecedor', razao_social: fornecedor },
		dataType: 'json',
		success: function (response) {
			carregar_combos();
			//alert(response); //back
		}
	});
}
function adicionarCategoria(categoria) {
	var str_categoria = "<option value='" + categoria.toUpperCase() + "' selected>" + categoria.toUpperCase() + "</option>";
	$("#categoria").append(str_categoria);
}
$(document).ready(function () {
	// Aguardar Materialize carregar antes de inicializar modais
	waitForMaterialize(function() {
		$('.modal').modal();
		$('.collapsible').collapsible();
	});
});


// Função para aguardar o carregamento do Materialize
function waitForMaterialize(callback) {
	if (typeof Materialize !== 'undefined' && $.fn.collapsible) {
		callback();
	} else {
		setTimeout(() => waitForMaterialize(callback), 100);
	}
}

$(document).ready(function () {
	$("#produto_principal").show();
	$("#produto_cadastro").hide();

	// Aguardar Materialize carregar antes de inicializar
	waitForMaterialize(function() {
		// Inicializar componentes do Materialize
		$('.collapsible').collapsible();
		$('select').material_select();

		carregar_combos();

		// Carregar produtos automaticamente de A-Z
		fetchall();
	});

	// Fetch all records
	$('#but_fetchall').click(function () {
		fetchall();
	});
}
);
function cadastro_produto(codigo) {
	$("#produto_principal").hide();
	$("#produto_cadastro").show();
	$("#loading").show();

	// Aguardar Materialize carregar antes de continuar
	waitForMaterialize(function() {
		// Inicializar collapsible se ainda não foi inicializado
		if ($.fn.collapsible) {
			$('.collapsible').collapsible();
		}
	});

	//Se for um código válido
	if (codigo > 0) {
		$.ajax({
			url: 'produtos_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirProdutos',
				codigo_interno: codigo,
			},
			dataType: 'json',
			success: function (response) {
				$("#loading").hide();

				// Verificar se a resposta é válida
				if (!response || response.length === 0) {
					console.error("Resposta vazia ao carregar produto:", codigo);
					Materialize.toast('<i class="material-icons">error</i> Erro ao carregar produto. Produto não encontrado.', 4000, 'red');
					retornarPrincipal();
					return;
				}

				try {
					carregarDadosProduto(response);
					carregarImagens();
					selecionar_itens_grade();

					// Reinicializar o collapsible para garantir que as abas funcionem corretamente
					if (typeof $ !== 'undefined' && $.fn.collapsible) {
						$('.collapsible').collapsible();
					}

					// Não adicionar automaticamente à lista de selecionados
					// mesmo que o produto esteja marcado para e-commerce
				} catch (e) {
					console.error("Erro ao processar dados do produto:", e);
					Materialize.toast('<i class="material-icons">error</i> Erro ao processar dados do produto.', 4000, 'red');
					retornarPrincipal();
				}
			},
			error: function (xhr) {
				$("#loading").hide();
				console.error("Erro ao carregar produto:", xhr.responseText);
				Materialize.toast('<i class="material-icons">error</i> Erro ao carregar produto. Verifique o console para mais detalhes.', 4000, 'red');
				retornarPrincipal();
			}
		});
	} else {
		// Novo produto, apenas limpar o formulário
		$("#loading").hide();
		limparProdutos();
	}
}
function carregarImagens() {
	var d = new Date();
	var foto_1 = "../upload/" + document.getElementById('codigo_gtin').value + ".jpg";
	var foto_2 = "../upload/" + document.getElementById('codigo_gtin').value + "_2.jpg";
	var foto_3 = "../upload/" + document.getElementById('codigo_gtin').value + "_3.jpg";
	var foto_4 = "../upload/" + document.getElementById('codigo_gtin').value + "_4.jpg";
	var foto_5 = "../upload/" + document.getElementById('categoria').value + ".jpg";
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'arquivoExiste',
			arquivo1: foto_1,
			arquivo2: foto_2,
			arquivo3: foto_3,
			arquivo4: foto_4,
			arquivo5: foto_5,
		},
		dataType: 'json',
		success: function (response) {
			var src1 = document.getElementById("foto1");
			var src2 = document.getElementById("foto2");
			var src3 = document.getElementById("foto3");
			var src4 = document.getElementById("foto4");
			var src5 = document.getElementById("foto5");

			// Verificar se os elementos existem antes de tentar modificá-los
			if (src1) src1.innerHTML = "";
			if (src2) src2.innerHTML = "";
			if (src3) src3.innerHTML = "";
			if (src4) src4.innerHTML = "";
			if (src5) src5.innerHTML = "";
			if (response.substring(0, 1) == "1") {
				var img = document.createElement("img");
				img.src = foto_1 + "?" + d.getTime();
				img.height = 100;
				img.width = 100;
				img.className = "circle responsive-img";
				src1.appendChild(img);
			}
			if (response.substring(1, 2) == "1") {
				var img = document.createElement("img");
				img.src = foto_2 + "?" + d.getTime();
				img.height = 100;
				img.width = 100;
				img.className = "circle responsive-img";
				src2.appendChild(img);
			}
			if (response.substring(2, 3) == "1") {
				var img = document.createElement("img");
				img.src = foto_3 + "?" + d.getTime();
				img.height = 100;
				img.width = 100;
				img.className = "circle responsive-img";
				src3.appendChild(img);
			}
			if (response.substring(3, 4) == "1") {
				var img = document.createElement("img");
				img.src = foto_4 + "?" + d.getTime();
				img.height = 100;
				img.width = 100;
				img.className = "circle responsive-img";
				src4.appendChild(img);
			}
			if (response.substring(4, 5) == "1") {
				var img = document.createElement("img");
				img.src = foto_5 + "?" + d.getTime();
				img.height = 100;
				img.width = 100;
				img.className = "circle responsive-img";
				src5.appendChild(img);
			}
		}
	});
}
function carregar_combos() {
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'carregar_grupo' },
		dataType: 'json',
		success: function (response) {
			carregarGrupo(response);
		}
	});
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'carregar_subgrupo' },
		dataType: 'json',
		success: function (response) {
			carregarSubGrupo(response);
		}
	});
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'carregar_categoria' },
		dataType: 'json',
		success: function (response) {
			carregarCategoria(response);
		}
	});
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'carregar_unidade' },
		dataType: 'json',
		success: function (response) {
			carregarUnidade(response);
		}
	});
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: { request: 'carregar_fornecedor' },
		dataType: 'json',
		success: function (response) {
			carregarFornecedor(response);
		}
	});
}
function clickPagina(valor) {
	fetchall((valor - 1) * 50);
}

// Função unificada para buscar produtos com suporte a filtros
function fetchall(pagina = 0) {
	const val = document.getElementById('desc_pesquisa').value;

	// Obter filtros ativos se a função estiver disponível
	let filtros = {};
	if (typeof obterFiltrosAtivos === 'function') {
		filtros = obterFiltrosAtivos();
	}

	// Preparar dados da requisição
	const requestData = {
		request: 'fetchall',
		pagina: pagina,
		desc_pesquisa: val
	};

	// Adicionar filtros se existirem
	if (Object.keys(filtros).length > 0) {
		requestData.filtros = JSON.stringify(filtros);
	}

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: requestData,
		dataType: 'json',
		success: function (response) {
			createRows(response);
		},
		error: function(xhr, status, error) {
			console.error('Erro ao buscar produtos:', error);
			// Tentar novamente sem filtros em caso de erro
			if (Object.keys(filtros).length > 0) {
				console.log('Tentando novamente sem filtros...');
				$.ajax({
					url: 'produtos_ajax.php',
					type: 'post',
					data: {
						request: 'fetchall',
						pagina: pagina,
						desc_pesquisa: val
					},
					dataType: 'json',
					success: function (response) {
						createRows(response);
					}
				});
			}
		}
	});
}
function carregarGrupo(response) {
	// Limpar o select e adicionar opção "Sem grupo"
	$("#grupo").empty();
	var option_sem_grupo = "<option value='SEM_GRUPO'>Sem grupo</option>";
	$("#grupo").append(option_sem_grupo);

	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var grupo = response[i].grupo;
			// Só adicionar se não for vazio ou nulo
			if (grupo && grupo.trim() !== '') {
				var option_str = "<option value='" + grupo + "'>" + grupo + "</option>";
				$("#grupo").append(option_str);
			}
		}
	}
}
function carregarSubGrupo(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var subgrupo = response[i].subgrupo;
			var option_str = "<option value='" + subgrupo + "'>" + subgrupo + "</option>";
			$("#subgrupo").append(option_str);
		}
	}
}
function carregarCategoria(response) {
	// Limpar o select e adicionar opção "Sem categoria"
	$("#categoria").empty();
	var option_sem_categoria = "<option value='SEM_CATEGORIA'>Sem categoria</option>";
	$("#categoria").append(option_sem_categoria);

	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var categoria = response[i].categoria;
			// Só adicionar se não for vazio ou nulo
			if (categoria && categoria.trim() !== '') {
				var option_str = "<option value='" + categoria + "'>" + categoria + "</option>";
				$("#categoria").append(option_str);
			}
		}
	}
}
function carregarUnidade(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var unidade = response[i].unidade;
			var option_str = "<option value='" + unidade + "'>" + unidade + "</option>";
			$("#unidade").append(option_str);
		}
	}
}
function carregarFornecedor(response) {
	$("#fornecedor").empty();
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	var option_str = "<option value='" + 0 + "' selected>SEM FORNECEDOR</option>";
	$("#fornecedor").append(option_str);
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var razao_social = response[i].razao_social;
			var codigo = response[i].codigo;
			var option_str = "<option value='" + codigo + "'>" + razao_social + "</option>";
			$("#fornecedor").append(option_str);
		}
	}
}
/**
 * Cria as linhas da nova tabela de produtos moderna
 * @param {Array} response - Resposta da API
 */
function createRows(response) {
    var len = 0;
    var container = $('#produtosTableBody');

    container.empty();
    $("#paginacao").empty();

    if (response != null) {
        len = response.length;
    }

    if (len > 0) {
        // Criar paginação moderna estilo Materialize
        var quantos = response[0].quantos;
        var itensPorPagina = 50;
        var paginas = Math.ceil(quantos / itensPorPagina);
        var paginaAtual = Math.floor(response[0].pagina / itensPorPagina) + 1;

        // Criar paginação estilo Materialize
        criarPaginacaoModerna(paginas, paginaAtual, "#paginacao_superior");
        criarPaginacaoModerna(paginas, paginaAtual, "#paginacao_inferior");

        // Criar linhas dos produtos
        for (var i = 0; i < len; i++) {
            var codigo_gtin = response[i].codigo_gtin;
            var descricao = response[i].descricao;
            var codigo_interno = response[i].codigo_interno;
            var status = response[i].status || '';

            // Verificar se o produto já está selecionado
            var checked = (typeof produtosSelecionados !== 'undefined' && produtosSelecionados.includes(codigo_interno.toString())) ? 'checked' : '';

            // Gerar ícone de origem
            var origemIcon = gerarIconeOrigemModerna(status);

            // Criar linha da nova tabela
            var rowHtml = criarLinhaProduto(codigo_interno, codigo_gtin, descricao, origemIcon, checked);
            container.append(rowHtml);
        }

    } else {
        // Estado vazio
        var emptyHtml = '<div class="produtos-empty">' +
            '<i class="material-icons">inventory_2</i>' +
            '<h6>Nenhum produto encontrado</h6>' +
            '<p>Tente ajustar os filtros de busca</p>' +
            '</div>';
        container.html(emptyHtml);
    }

}

/**
 * Cria uma linha individual da tabela de produtos
 */
function criarLinhaProduto(codigo_interno, codigo_gtin, descricao, origemIcon, checked) {
    return '<div class="produtos-row">' +
        '<div class="produtos-cell produtos-cell-checkbox">' +
            '<div class="produtos-checkbox">' +
                '<input type="checkbox" id="prod_' + codigo_interno + '" class="produto-checkbox" ' + checked + ' onchange="atualizarSelecao(this)"/>' +
                '<label for="prod_' + codigo_interno + '"></label>' +
            '</div>' +
        '</div>' +
        '<div class="produtos-cell produtos-cell-codigo">' + codigo_gtin + '</div>' +
        '<div class="produtos-cell produtos-cell-descricao">' + descricao + '</div>' +
        '<div class="produtos-cell produtos-cell-origem">' + origemIcon + '</div>' +
        '<div class="produtos-cell produtos-cell-editar">' +
            '<button class="produtos-btn-edit" onclick="cadastro_produto(' + codigo_interno + ')" title="Editar produto">' +
                '<i class="material-icons">edit</i>' +
            '</button>' +
        '</div>' +
    '</div>';
}

/**
 * Gera ícone moderno para coluna origem
 */
function gerarIconeOrigemModerna(status) {
    // Limpar espaços em branco do status
    var statusLimpo = status ? status.toString().trim() : '';

    if (!statusLimpo) {
        return '<i class="material-icons origem-icon local" title="Produto Local">computer</i>';
    }

    switch (statusLimpo) {
        case 'ENS':
            return '<i class="material-icons origem-icon nuvemshop-normal" title="Nuvemshop - Produto Normal">cloud</i>';
        case 'ENSVI':
            return '<i class="material-icons origem-icon nuvemshop-vitrine" title="Nuvemshop - Produto Vitrine">cloud</i>';
        case 'ENSV':
            return '<i class="material-icons origem-icon nuvemshop-variante" title="Nuvemshop - Variante">cloud</i>';
        case 'E':
            return '<i class="material-icons origem-icon nuvemshop-legacy" title="Nuvemshop - Status Antigo">cloud</i>';
        default:
            return '<i class="material-icons origem-icon local" title="Produto Local">computer</i>';
    }
}

/**
 * Cria a paginação moderna estilo Materialize
 * @param {number} totalPaginas - Total de páginas
 * @param {number} paginaAtual - Página atual
 * @param {string} seletor - Seletor CSS para o elemento de paginação
 */
function criarPaginacaoModerna(totalPaginas, paginaAtual, seletor) {
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
}

// Função criarPaginacao removida - usando a versão do produtos_paginacao.js

function verificarCodigo() {
	var codigo_gtin = document.getElementById('codigo_gtin').value;
	document.getElementById('codigo_interno').value = 0;
	if (codigo_gtin == 0) {
		document.getElementById('codigo_gtin').focus();
	} else {
		$.ajax({
			url: 'produtos_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoProduto',
				codigo_gtin: codigo_gtin,
			},
			dataType: 'json',
			success: function (response) {
				var len = 0;
				if (response != null) {
					len = response.length;
				}
				for (var i = 0; i < len; i++) {
					var codigo_interno = response[i].codigo_interno;
					cadastro_produto(codigo_interno);
				}
			}
		});
		console.log(codigo_gtin);
	}
}
function carregarDadosProduto(response) {
	console.log("Carregando dados do produto:", response);

	var len = 0;
	if (response != null) {
		len = response.length;
	}

	if (len === 0) {
		console.error("Nenhum dado de produto encontrado para carregar");
		Materialize.toast('<i class="material-icons">warning</i> Nenhum dado de produto encontrado', 4000, 'orange');
		return;
	}

	try {
		for (var i = 0; i < len; i++) {
		document.getElementById('codigo_interno').value = response[i].codigo_interno;
		document.getElementById('codigo_gtin').value = response[i].codigo_gtin;
		document.getElementById('descricao').value = response[i].descricao;
		document.getElementById('descricao_detalhada').value = response[i].descricao_detalhada;
		document.getElementById('preco_venda').value = response[i].preco_venda;
		document.getElementById('preco_compra').value = response[i].preco_compra;
		document.getElementById('perc_lucro').value = response[i].perc_lucro;
		document.getElementById('ncm').value = response[i].codigo_ncm;
		document.getElementById('cest').value = response[i].cest;
		document.getElementById('cfop').value = response[i].cfop;
		document.getElementById('perc_icms').value = response[i].aliquota_icms;
		if (response[i].produto_balanca == '1') {
			document.getElementById('produto_balanca').checked = true;
		} else {
			document.getElementById('produto_balanca').checked = false;
		}
		document.getElementById('vadidade').value = response[i].validade;
		document.getElementById('data_cadastro').value = response[i].dt_cadastro;
		document.getElementById('data_alteracao').value = response[i].dt_ultima_alteracao;
		document.getElementById('grupo').value = response[i].grupo;
		document.getElementById('subgrupo').value = response[i].subgrupo;
		document.getElementById('categoria').value = response[i].categoria;
		document.getElementById('unidade').value = response[i].unidade;
		// Verificar se o produto tem status 'E' (e-commerce)e
		if (response[i].status.substring(0,1) == 'E') {
			document.getElementById('vender_ecomerce').checked = true;
			// Não adicionar automaticamente à lista de selecionados
			// O usuário precisa marcar explicitamente o checkbox para adicionar à fila
		} else {
			document.getElementById('vender_ecomerce').checked = false;
		}

		// ✅ ARMAZENAR STATUS REAL EM VARIÁVEL GLOBAL PARA USO POSTERIOR
		window.currentProductStatus = response[i].status;
		if (response[i].producao == '1') {
			document.getElementById('produto_producao').checked = true;
		} else {
			document.getElementById('produto_producao').checked = false;
		}
		document.getElementById('situacao_tributaria').value = response[i].situacao_tributaria;
		document.getElementById('fornecedor').value = response[i].codfor;
		document.getElementById('perc_desc_a').value = response[i].perc_desc_a;
		document.getElementById('val_desc_a').value = response[i].val_desc_a;
		document.getElementById('perc_desc_b').value = response[i].perc_desc_b;
		document.getElementById('val_desc_b').value = response[i].val_desc_b;
		document.getElementById('perc_desc_c').value = response[i].perc_desc_c;
		document.getElementById('val_desc_c').value = response[i].val_desc_c;
		document.getElementById('perc_desc_d').value = response[i].perc_desc_d;
		document.getElementById('val_desc_d').value = response[i].val_desc_d;
		document.getElementById('perc_desc_e').value = response[i].perc_desc_e;
		document.getElementById('val_desc_e').value = response[i].val_desc_e;
		document.getElementById('aliquota_calculo_credito').value = response[i].aliquota_calculo_credito;
		document.getElementById('perc_dif').value = response[i].perc_dif;
		document.getElementById('mod_deter_bc_icms').value = response[i].mod_deter_bc_icms;
		document.getElementById('perc_redu_icms').value = response[i].perc_redu_icms;
		document.getElementById('mod_deter_bc_icms_st').value = response[i].mod_deter_bc_icms_st;
		document.getElementById('tamanho').value = response[i].tamanho;
		document.getElementById('comprimento').value = response[i].comprimento;
		document.getElementById('largura').value = response[i].largura;
		document.getElementById('altura').value = response[i].altura;
		document.getElementById('peso').value = response[i].peso;
		document.getElementById('vencimento').value = response[i].vencimento;
		document.getElementById('aliq_fcp_st').value = response[i].aliq_fcp_st;
		if (response[i].descricao_personalizada == '1') {
			document.getElementById('descricao_personalizada').checked = true;
		} else {
			document.getElementById('descricao_personalizada').checked = false;
		}
		document.getElementById('valorGelado').value = response[i].valorGelado;
		document.getElementById('prod_desc_etiqueta').value = response[i].prod_desc_etiqueta;
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		document.getElementById('aliq_fcp').value = response[i].aliq_fcp;
		document.getElementById('qtde').value = response[i].qtde;
		document.getElementById('qtde_min').value = response[i].qtde_min;
		document.getElementById('perc_redu_icms_st').value = response[i].perc_redu_icms_st;
		document.getElementById('perc_mv_adic_icms_st').value = response[i].perc_mv_adic_icms_st;
		document.getElementById('aliq_icms_st').value = response[i].aliq_icms_st;
		//IPI PIS COFINS
		document.getElementById('ipi_reducao_bc').value = response[i].ipi_reducao_bc;
		document.getElementById('aliquota_ipi').value = response[i].aliquota_ipi;
		document.getElementById('ipi_reducao_bc_st').value = response[i].ipi_reducao_bc_st;
		document.getElementById('aliquota_ipi_st').value = response[i].aliquota_ipi_st;
		document.getElementById('cst_ipi').value = response[i].cst_ipi;
		document.getElementById('calculo_ipi').value = response[i].calculo_ipi;
		document.getElementById('pis_reducao_bc').value = response[i].pis_reducao_bc;
		document.getElementById('aliquota_pis').value = response[i].aliquota_pis;
		document.getElementById('pis_reducao_bc_st').value = response[i].pis_reducao_bc_st;
		document.getElementById('aliquota_pis_st').value = response[i].aliquota_pis_st;
		document.getElementById('cst_pis').value = response[i].cst_pis;
		document.getElementById('calculo_pis').value = response[i].calculo_pis;
		document.getElementById('cofins_reducao_bc').value = response[i].cofins_reducao_bc;
		document.getElementById('aliquota_cofins').value = response[i].aliquota_cofins;
		document.getElementById('cofins_reducao_bc_st').value = response[i].cofins_reducao_bc_st;
		document.getElementById('aliquota_cofins_st').value = response[i].aliquota_cofins_st;
		document.getElementById('cst_cofins').value = response[i].cst_cofins;
		document.getElementById('calculo_cofing').value = response[i].calculo_cofins;
	}
	} catch (e) {
		console.error("Erro ao processar dados do produto:", e);
		Materialize.toast('<i class="material-icons">error</i> Erro ao processar dados do produto', 4000, 'red');
	}
}
function verificarCodigoGrade() {
	var prod_gd_codigo_gtin = document.getElementById('prod_gd_codigo_gtin').value;

	// 🆕 NOVA IMPLEMENTAÇÃO: Usar API otimizada do Next.js
	if (!prod_gd_codigo_gtin || prod_gd_codigo_gtin.length < 3) {
		return;
	}

	console.log('🔍 Buscando produto por GTIN (Nova API):', prod_gd_codigo_gtin);

	// Usar a nova API otimizada
	fetch(`/api/produtos/buscar-completo?gtin=${prod_gd_codigo_gtin}`)
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				const produto = data.data;
				console.log('✅ Produto encontrado com nova API:', produto);
				
				// Preencher campos básicos
				document.getElementById('prod_gd_nome').value = produto.descricao;
				
				// 🆕 MOSTRAR INFORMAÇÕES COMPLETAS
				var infoCompleta = `
					<div class="card-panel light-blue lighten-5" style="margin-top: 10px;">
						<h6><i class="material-icons left">info</i>Produto Encontrado</h6>
						<p><strong>Descrição:</strong> ${produto.descricao}</p>
						<p><strong>Preço:</strong> R$ ${produto.preco_venda.toFixed(2)}</p>
						<p><strong>Estoque:</strong> ${produto.estoque} unidades</p>
						<p><strong>Dimensões:</strong> ${produto.dimensoes.comprimento}x${produto.dimensoes.largura}x${produto.dimensoes.altura} cm</p>
						<p><strong>Peso:</strong> ${produto.dimensoes.peso} kg</p>
					</div>
				`;
				
				// Remover info anterior se existir
				var infoAnterior = document.getElementById('info-produto-grade');
				if (infoAnterior) {
					infoAnterior.remove();
				}
				
				// Adicionar nova info
				var container = document.getElementById('prod_gd_nome').parentElement.parentElement;
				var infoDiv = document.createElement('div');
				infoDiv.id = 'info-produto-grade';
				infoDiv.innerHTML = infoCompleta;
				container.appendChild(infoDiv);
				
				Materialize.toast('<i class="material-icons">check_circle</i> Produto encontrado!', 3000, 'green');
			} else {
				console.log('❌ Produto não encontrado:', data.error);
				Materialize.toast('<i class="material-icons">error</i> ' + data.error, 4000, 'red');
				
				// Limpar campo nome se produto não encontrado
				document.getElementById('prod_gd_nome').value = '';
			}
		})
		.catch(error => {
			console.error('❌ Erro ao buscar produto:', error);
			Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto', 4000, 'red');
		});
}

function adicionar_item_grade() {
	var prod_gd_codigo_gtin = document.getElementById('prod_gd_codigo_gtin').value;
	var prod_gd_nome = document.getElementById('prod_gd_nome').value;
	var prod_gd_variacao = document.getElementById('prod_gd_variacao').value;
	var prod_gd_caracteristica = document.getElementById('prod_gd_caracteristica').value;
	var codigo_interno = document.getElementById('codigo_interno').value;

	console.log('🔄 Adicionando item à grade:', {
		codigo_interno: codigo_interno,
		codigo_gtin: prod_gd_codigo_gtin,
		nome: prod_gd_nome,
		variacao: prod_gd_variacao,
		caracteristica: prod_gd_caracteristica
	});

	// ✅ VERIFICAÇÃO: Se produto não foi salvo (codigo_interno = 0), salvar primeiro
	if (!codigo_interno || codigo_interno == '0') {
		console.log('⚠️ Produto não foi salvo ainda (codigo_interno = 0), salvando primeiro...');

		// ✅ VERIFICAÇÃO: Usuário deve ter preenchido pelo menos o código GTIN
		var codigo_gtin_usuario = document.getElementById('codigo_gtin').value;
		if (!codigo_gtin_usuario || codigo_gtin_usuario.trim() === '') {
			console.error('❌ Erro: Código GTIN não preenchido');
			Materialize.toast('<i class="material-icons">error</i> Preencha o código do produto antes de adicionar grade', 4000, 'red');
			return;
		}

		Materialize.toast('<i class="material-icons">save</i> Salvando produto antes de adicionar grade...', 3000, 'orange');

		// Salvar produto primeiro
		gravarProdutos(function(novoCodigoInterno) {
			if (novoCodigoInterno && novoCodigoInterno != '0') {
				console.log('✅ Produto salvo com codigo_interno:', novoCodigoInterno);

				// Atualizar campo codigo_interno
				document.getElementById('codigo_interno').value = novoCodigoInterno;

				// Agora adicionar item à grade com código correto
				setTimeout(() => {
					adicionar_item_grade_interno(novoCodigoInterno, prod_gd_codigo_gtin, prod_gd_nome, prod_gd_variacao, prod_gd_caracteristica);
				}, 500);
			} else {
				console.error('❌ Erro ao salvar produto - codigo_interno ainda é 0');
				Materialize.toast('<i class="material-icons">error</i> Erro ao salvar produto. Verifique se preencheu os campos obrigatórios.', 4000, 'red');
			}
		});
		return;
	}

	// Produto já foi salvo, continuar normalmente
	adicionar_item_grade_interno(codigo_interno, prod_gd_codigo_gtin, prod_gd_nome, prod_gd_variacao, prod_gd_caracteristica);
}

// Função interna para adicionar item à grade
function adicionar_item_grade_interno(codigo_interno, prod_gd_codigo_gtin, prod_gd_nome, prod_gd_variacao, prod_gd_caracteristica) {
	console.log('🔄 Adicionando item à grade (interno):', {
		codigo_interno: codigo_interno,
		codigo_gtin: prod_gd_codigo_gtin,
		nome: prod_gd_nome,
		variacao: prod_gd_variacao,
		caracteristica: prod_gd_caracteristica
	});

	// Validação básica
	if (!prod_gd_codigo_gtin || prod_gd_codigo_gtin.trim() === '') {
		console.error('❌ Erro: Código GTIN vazio');
		Materialize.toast('<i class="material-icons">error</i> Código GTIN é obrigatório', 3000, 'red');
		return;
	}

	if (!codigo_interno || codigo_interno.trim() === '') {
		console.error('❌ Erro: Código interno vazio');
		Materialize.toast('<i class="material-icons">error</i> Produto deve ser salvo antes de adicionar variações', 3000, 'red');
		return;
	}

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'adicionar_item_grade',
			codigo_interno: codigo_interno,
			codigo_gtin: prod_gd_codigo_gtin,
			variacao: prod_gd_variacao,
			caracteristica: prod_gd_caracteristica,
			descricao: prod_gd_nome,
		},
		dataType: 'json',
		success: function (response) {
			console.log('✅ Resposta do servidor:', response);

			if (response.success) {
				console.log('✅ Item adicionado à grade com sucesso');
				Materialize.toast('<i class="material-icons">check</i> Item adicionado à grade', 2000, 'green');

				// Limpar campos
				document.getElementById('prod_gd_codigo_gtin').value = '';
				document.getElementById('prod_gd_nome').value = '';
				document.getElementById('prod_gd_variacao').value = '';
				document.getElementById('prod_gd_caracteristica').value = '';

				// Recarregar lista
				selecionar_itens_grade();
			} else {
				console.error('❌ Erro do servidor:', response.error);
				Materialize.toast('<i class="material-icons">error</i> ' + response.error, 4000, 'red');
			}
		},
		error: function (jqxhr, status, exception) {
			console.error('❌ Erro AJAX:', {
				status: status,
				exception: exception,
				responseText: jqxhr.responseText
			});
			Materialize.toast('<i class="material-icons">error</i> Erro ao adicionar item: ' + exception, 4000, 'red');
		}
	});
}

function selecionar_itens_grade() {
	var codigo_interno = document.getElementById('codigo_interno').value;
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'selecionar_itens_grade',
			codigo_interno: codigo_interno,

		},
		dataType: 'json',
		success: function (response) {
			createRowsGrade(response);
		},

		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
}
function createRowsGrade(response) {
	var len = 0;
	$('#userTableGrade tbody').empty();

	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var variacao = response[i].variacao;
			var caracteristica = response[i].caracteristica;
			var codigo = response[i].codigo;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td>" + variacao + "</td>" +
				"<td>" + caracteristica + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='deleta_grade(" + codigo + ")' id='but_grid_delete'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#userTableGrade tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='4'>Sem registro.</td>" +
			"</tr>";
		$("#userTableGrade tbody").append(tr_str);
	}
}


function deleta_grade(codigo) {

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'deleta_grade',
			codigo: codigo,
		},
		dataType: 'json',
		success: function (response) {
			selecionar_itens_grade();
		},
		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
}
function exportarProdutos() {
	console.log("Iniciando exportação de produtos...");

	// pegando dados do form do front-end
	var codigo_interno = document.getElementById('codigo_interno').value;
	var codigo_gtin = document.getElementById('codigo_gtin').value;
	var descricao = document.getElementById('descricao').value;
	var descricao_detalhada = document.getElementById('descricao_detalhada').value;
	var preco_venda = document.getElementById('preco_venda').value;
	var preco_compra = document.getElementById('preco_compra').value;
	var categoria = document.getElementById('categoria').value;
	var peso = document.getElementById('peso').value || "0";
	var altura = document.getElementById('altura').value || "0";
	var largura = document.getElementById('largura').value || "0";
	var comprimento = document.getElementById('comprimento').value || "0";

	if (!codigo_gtin || codigo_gtin === "0") {
		alert("É necessário informar um código de barras (GTIN) válido para exportar o produto.");
		return;
	}

	$.ajax({
		url: 'nuvemshop/nuvemshop_proxy.php?operation=search&sku=' + codigo_gtin,
		type: 'GET',
		dataType: 'json',
		success: function (response) {
			console.log("Resposta da busca por SKU:", response);

			if (response && response.id) {
				console.log("Produto encontrado na Nuvemshop, ID:", response.id);

				var updateData = {
					name: {
						pt: descricao
					},
					description: {
						pt: cleanHTML(descricao_detalhada)
					},
					handle: {
						pt: descricao.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
					},
					published: true
				};

				$.ajax({
					url: 'nuvemshop/nuvemshop_proxy.php?operation=update&product_id=' + response.id,
					type: 'POST',
					contentType: 'application/json',
					data: JSON.stringify(updateData),
					success: function (updateResponse) {
						console.log("Produto atualizado com sucesso:", updateResponse);

						// atualizar as variantes individualmente
						if (response.variants && response.variants.length > 0) {
							// atualizar a primeira variante com os novos dados
							var variantData = {
								price: parseFloat(preco_venda.replace(',', '.')),
								stock_management: true,
								stock: parseInt(window.qtdeProduto) || 0,
								weight: pesoNum,
								depth: comprimentoNum,
								width: larguraNum,
								height: alturaNum
							};

							if (variant.values && variant.values.length > 0) {
								// Processar os valores diretamente
								try {
									var safeValuesJson = JSON.stringify(variant.values);
									variantData.values = JSON.parse(safeValuesJson);
								} catch (e) {
									console.error("Erro ao processar valores da variante:", e);
									// Manter os valores originais
									variantData.values = variant.values;
								}
							}

							$.ajax({
								url: 'nuvemshop/nuvemshop_proxy.php?operation=update_variant&product_id=' + product_id + '&variant_id=' + variante.id,
								type: 'POST',
								contentType: 'application/json',
								data: JSON.stringify(variantData),
								success: function(response) {
									console.log("Variante atualizada com sucesso");
								},
								error: function(xhr) {
									console.error("Erro ao atualizar variante:", xhr.responseText);
								}
							});
						} else {
							alert("Produto atualizado com sucesso na Nuvemshop!");
						}
					},
					error: function (xhr, status, error) {
						console.error("Erro ao atualizar produto:", xhr.responseText);
						alert("Erro ao atualizar produto na Nuvemshop: " + (xhr.responseText || error));
					}
				});
			} else {
				// Produto não existe, criar novo (com variantes)
				console.log("Produto não encontrado na Nuvemshop, criando novo...");

				var newProductData = {
					name: {
						pt: descricao
					},
					description: {
						pt: cleanHTML(descricao_detalhada)
					},
					handle: {
						pt: descricao.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
					},
					published: true,
					variants: [{
						price: preco_venda.replace(',', '.'),
						stock_management: false,
						weight: peso,
						depth: comprimento,
						width: largura,
						height: altura,
						sku: codigo_gtin
					}]
				};

				// Buscar variações (tamanhos)
				$.ajax({
					url: 'produtos_ajax.php',
					type: 'POST',
					data: {
						request: 'selecionar_itens_grade',
						codigo_interno: codigo_interno,
					},
					dataType: 'json',
					success: function (gradeResponse) {
						console.log("Itens de grade obtidos:", gradeResponse);
						if (gradeResponse && gradeResponse.length > 0) {
							newProductData.variants = [];
							gradeResponse.forEach(function (item) {
								newProductData.variants.push({
									price: preco_venda.replace(',', '.'),
									stock_management: false,
									weight: peso,
									depth: comprimento,
									width: largura,
									height: altura,
									sku: item.codigo_gtin || codigo_gtin + "-" + item.variacao
								});
							});
						}

						// criar o novo produto
						$.ajax({
							url: 'nuvemshop/nuvemshop_proxy.php',
							type: 'POST',
							contentType: 'application/json',
							data: JSON.stringify(newProductData),
							success: function (createResponse) {
								console.log("Produto criado com sucesso:", createResponse);
								alert("Produto criado com sucesso na Nuvemshop!");
							},
							error: function (xhr, status, error) {
								console.error("Erro ao criar produto:", xhr.responseText);
								alert("Erro ao criar produto na Nuvemshop: " + (xhr.responseText || error));
							}
						});
					},
					error: function (xhr, status, error) {
						console.error("Erro ao obter variações:", error);
						alert("Erro ao carregar variações do produto.");
					}
				});
			}
		},
		error: function (xhr, status, error) {
			console.error("Erro ao verificar produto na Nuvemshop:", xhr.responseText);
			alert("Erro ao verificar produto na Nuvemshop: " + (xhr.responseText || error));
		}
	});
}

function cleanHTML(html) {
	const tmp = document.createElement("div");
	tmp.innerHTML = html;
	return tmp.textContent || tmp.innerText || "";
}

function exportarProdutoParaNuvemshop(codigo_interno, codigo_gtin, descricao, descricao_detalhada,
	preco_venda, peso, altura, largura, comprimento) {
	console.log("Exportando produto para Nuvemshop: " + codigo_gtin);

	// Verificar se este produto acabou de ser atualizado
	if (window.ultimoProdutoAtualizado === codigo_gtin) {
		return;
	}

	// Verificar se alguma variante deste produto acabou de ser atualizada
	if (window.ultimoProdutoAtualizado && window.ultimoProdutoAtualizado !== codigo_gtin) {

		// Usar uma variável para controlar se devemos continuar
		var shouldContinue = true;

		// Verificar se este SKU é uma variante do produto que acabou de ser atualizado
		$.ajax({
			url: 'nuvemshop/nuvemshop_proxy.php?operation=search&sku=' + window.ultimoProdutoAtualizado,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response && response.id && response.variants && response.variants.length > 0) {
					// Verificar se alguma variante tem o SKU que estamos tentando exportar
					for (var i = 0; i < response.variants.length; i++) {
						if (response.variants[i].sku === codigo_gtin) {
							console.log("ATENÇÃO: " + codigo_gtin + " é uma variante do produto " + window.ultimoProdutoAtualizado + ". Ignorando exportação para evitar duplicação.");
							$("#loading").hide();
							shouldContinue = false;
							break;
						}
					}
				}
			},
			error: function(xhr) {
				console.error("Erro ao verificar se é variante:", xhr.responseText);
			}
		});

		// Se não devemos continuar, retornar
		if (!shouldContinue) {
			return;
		}
	}

	// ✅ NOVA VERIFICAÇÃO: Se produto é variante (ENSV), editar variante ao invés de criar produto
	var status = $("#status").val();
	if (status === 'ENSV') {
		console.log("🔄 Produto é variante (ENSV) - editando variante existente ao invés de criar produto");
		editarVarianteEspecifica(codigo_gtin, {
			descricao: descricao,
			descricao_detalhada: descricao_detalhada,
			preco_venda: preco_venda,
			peso: peso,
			altura: altura,
			largura: largura,
			comprimento: comprimento
		});
		return;
	}

	$("#loading").show();

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

			// Usar implementação antiga como fallback
			usarImplementacaoAntiga();
			return;
		}
	}

	// Usar a nova implementação com ProductUpdater
	console.log("Usando ProductUpdater para exportar produto");

	// Obter a grade do produto
	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'selecionar_itens_grade',
			codigo_interno: codigo_interno,
		},
		dataType: 'json',
		success: function (gradeResponse) {
			// Obter apenas a quantidade do produto (sem sobrescrever dimensões)
			$.ajax({
				url: 'produtos_ajax.php',
				type: 'post',
				data: {
					request: 'obterQuantidadeProduto',
					codigo_gtin: codigo_gtin
				},
				dataType: 'json',
				success: function(qtdeResponse) {
					const qtdeProduto = qtdeResponse.qtde || 0;

					// ✅ CORREÇÃO: Usar dimensões dos parâmetros (já atualizadas) ao invés das do banco
					console.log(`📏 Usando dimensões atualizadas dos parâmetros: ${peso}kg, ${altura}x${largura}x${comprimento}cm`);

					// Preparar dados do produto
					const productData = {
						codigo_interno: codigo_interno,
						codigo_gtin: codigo_gtin,
						descricao: descricao,
						descricao_detalhada: descricao_detalhada,
						preco_venda: preco_venda,
						peso: peso,        // ✅ Usar parâmetro atualizado
						altura: altura,    // ✅ Usar parâmetro atualizado
						largura: largura,  // ✅ Usar parâmetro atualizado
						comprimento: comprimento, // ✅ Usar parâmetro atualizado
						qtdeProduto: qtdeProduto,
						published: true
					};

					// Buscar o produto na Nuvemshop
					window.productUpdater.findProductBySku(
						codigo_gtin,
						// Callback de sucesso
						function(product) {
							if (product && product.id) {
								console.log("Produto encontrado na Nuvemshop, ID:", product.id);

								// Adicionar ID ao produto
								productData.id = product.id;

								// Preparar variantes existentes e novas
								const existingVariants = product.variants || [];

								// Preparar novas variantes
								const newVariants = [];

								// Set para evitar duplicatas
								const processedVariants = new Set();

								// ✅ CORRIGIDO: Mapear variantes existentes por barcode para não criar duplicatas
								if (existingVariants && existingVariants.length > 0) {
									existingVariants.forEach(function(variant) {
										// Usar barcode se disponível, senão usar sku como fallback
										const identificador = variant.barcode || variant.sku;
										if (identificador) {
											processedVariants.add(identificador);
											console.log(`🔍 Variante existente mapeada: ${identificador}`);
										}
									});
								}

								// Processar cada item da grade
								if (gradeResponse && gradeResponse.length > 0) {
									// Converter valores para números
									const pesoNum = parseFloat((peso || "0").replace(',', '.'));
									const alturaNum = parseFloat((altura || "0").replace(',', '.'));
									const larguraNum = parseFloat((largura || "0").replace(',', '.'));
									const comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

									gradeResponse.forEach(function(item) {
										// Verificar se o item tem código GTIN e se é diferente do produto principal
										if (item.codigo_gtin && item.codigo_gtin !== codigo_gtin) {
											// Verificar se esta variante já existe
											if (!processedVariants.has(item.codigo_gtin)) {
												// ✅ CORRIGIDO: Buscar dados individuais da variante pelo GTIN
												console.log(`🔍 Buscando dados individuais para GTIN: ${item.codigo_gtin}`);
												const dadosVariante = buscarDadosVariante(item.codigo_gtin);
												console.log(`📦 Dados da variante ${item.codigo_gtin}:`, dadosVariante);

												// Criar uma nova variante com dados individuais (incluindo dimensões próprias)
												const newVariant = {
													price: dadosVariante.preco || parseFloat(preco_venda.replace(',', '.')),
													stock_management: true,
													stock: dadosVariante.estoque || 0,
													weight: dadosVariante.peso > 0 ? dadosVariante.peso : pesoNum,
													depth: dadosVariante.comprimento > 0 ? dadosVariante.comprimento : comprimentoNum,
													width: dadosVariante.largura > 0 ? dadosVariante.largura : larguraNum,
													height: dadosVariante.altura > 0 ? dadosVariante.altura : alturaNum,
													sku: codigo_gtin,           // ✅ SKU = código do produto pai
													barcode: item.codigo_gtin   // ✅ Barcode = GTIN da variante individual
												};

												// ✅ CORRIGIDO: Adicionar apenas a característica no values (não a variação)
												if (item.caracteristica) {
													newVariant.values = [{
														pt: item.caracteristica.trim() || "Padrão"
													}];
												}

												// Adicionar à lista de novas variantes
												newVariants.push(newVariant);

												// Marcar como processada
												processedVariants.add(item.codigo_gtin);
											}
										}
									});
								}

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

										// Mostrar mensagem de sucesso
										Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes atualizados com sucesso na Nuvemshop!', 4000, 'green');

										// Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
										// Apenas remover o produto da lista de selecionados
										var index = produtosSelecionados.indexOf(codigo_interno.toString());
										if (index !== -1) {
											produtosSelecionados.splice(index, 1);
											console.log("Produto removido da lista de exportação após atualização bem-sucedida:", codigo_interno);
										}

										// Definir uma flag global para evitar que o produto seja exportado novamente
										window.ultimoProdutoAtualizado = codigo_gtin;

										// Definir um timeout para limpar a flag após 5 segundos
										setTimeout(function() {
											window.ultimoProdutoAtualizado = null;
										}, 5000);
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

								// Criar o produto
								window.productUpdater.createProduct(
									productData,
									// Callback de sucesso
									function(result) {
										console.log("Produto criado com sucesso:", result);

										// Esconder loading
										$("#loading").hide();

										// Mostrar mensagem de sucesso
										Materialize.toast('<i class="material-icons">check_circle</i> Produto criado com sucesso na Nuvemshop!', 4000, 'green');

					// Auto-sincronização após criação
					console.log('🔄 Iniciando auto-sincronização após criação de produto...');
					setTimeout(function() {
						if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
							sincronizarStatusProdutosNuvemshop(true); // true = automático
						}
					}, 2000);

										// Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
										// Apenas remover o produto da lista de selecionados
										var index = produtosSelecionados.indexOf(codigo_interno.toString());
										if (index !== -1) {
											produtosSelecionados.splice(index, 1);
											console.log("Produto removido da lista de exportação após criação bem-sucedida:", codigo_interno);
										}

										// Definir uma flag global para evitar que o produto seja exportado novamente
										window.ultimoProdutoAtualizado = codigo_gtin;

										// Definir um timeout para limpar a flag após 5 segundos
										setTimeout(function() {
											window.ultimoProdutoAtualizado = null;
										}, 5000);
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
				},
				error: function(xhr) {
					console.error("Erro ao obter quantidade do produto:", xhr.responseText);

					// Esconder loading
					$("#loading").hide();

					// Mostrar mensagem de erro
					Materialize.toast('<i class="material-icons">error</i> Erro ao obter quantidade do produto', 5000, 'red');
				}
			});
		},
		error: function (xhr, _status, errorThrown) {
			console.error("Erro ao carregar grade do produto:", xhr.responseText || errorThrown);
			$("#loading").hide();
			Materialize.toast('<i class="material-icons">error</i> Erro ao carregar informações do produto!', 4000, 'red');
		}
	});

	// Função para usar a implementação antiga como fallback
	function usarImplementacaoAntiga() {
		console.log("Usando implementação antiga como fallback");

		$.ajax({
			url: 'produtos_ajax.php',
			type: 'post',
			data: {
				request: 'selecionar_itens_grade',
				codigo_interno: codigo_interno,
			},
			dataType: 'json',
			success: function (gradeResponse) {
				// Verifica se o produto já existe na Nuvemshop
				$.ajax({
					url: 'nuvemshop/nuvemshop_proxy.php?operation=search&sku=' + codigo_gtin,
					type: 'GET',
					dataType: 'json',
					success: function (response) {
						console.log("Resposta da busca por SKU:", response);
						if (response.error) {
							console.error("Erro retornado pela API/Proxy:", response.error);
							$("#loading").hide();
							Materialize.toast('<i class="material-icons">error</i> Erro interno: ' + response.error, 6000, 'red');
							return;
						}

						if (response && response.id) {
							console.log("Produto encontrado na Nuvemshop, ID:", response.id);

							// Atualizar produto existente
							atualizarProdutoNuvemshop(response.id, codigo_gtin, descricao, descricao_detalhada,
								preco_venda, peso, altura, largura, comprimento,
								response.variants, gradeResponse);

						} else {
							console.log("Produto NÃO encontrado na Nuvemshop, criando novo...");
							// Produto não existe, cria novo
							criarProdutoNuvemshop(codigo_gtin, descricao, descricao_detalhada,
								preco_venda, peso, altura, largura, comprimento,
								gradeResponse);
						}
					},
					error: function (xhr, _status, errorThrown) {
						console.error("Erro ao verificar produto na Nuvemshop:", xhr.responseText || errorThrown);
						$("#loading").hide();
						Materialize.toast('<i class="material-icons">error</i> Erro ao buscar produto na Nuvemshop!', 4000, 'red');
					}
				});
			},
			error: function (xhr, _status, errorThrown) {
				console.error("Erro ao carregar grade do produto:", xhr.responseText || errorThrown);
				$("#loading").hide();
				Materialize.toast('<i class="material-icons">error</i> Erro ao carregar informações do produto!', 4000, 'red');
			}
		});
	}
}

// Array para armazenar os IDs dos produtos selecionados
var produtosSelecionados = [];


// Função para atualizar a seleção quando um checkbox é alterado
function atualizarSelecao(checkbox) {
	var codigo_interno = parseInt(checkbox.id.replace('prod_', ''));

	if (checkbox.checked) {
		// Adicionar à lista de selecionados se não estiver
		if (!produtosSelecionados.includes(codigo_interno)) {
			produtosSelecionados.push(codigo_interno);
			console.log("Produto adicionado para exportação:", codigo_interno);
		}
	} else {
		// Remover da lista de selecionados se estiver
		var index = produtosSelecionados.indexOf(codigo_interno);
		if (index !== -1) {
			produtosSelecionados.splice(index, 1);
			console.log("Produto removido da exportação:", codigo_interno);
		}
	}
}

// Modificar a função exportarProdutosSelecionados para usar as funções unificadas
function exportarProdutosSelecionados(mostrarMensagemInicial = true) {
	if (produtosSelecionados.length === 0) {
		if (mostrarMensagemInicial) {
			Materialize.toast('Selecione pelo menos um produto para exportar!', 4000, 'red');
		}
		return;
	}

	console.log("Exportando produtos selecionados:", produtosSelecionados);

	// Verificar primeiro se a integração com a Nuvemshop está ativa
	$.ajax({
		url: 'integracao_ajax.php',
		type: 'post',
		data: {
			request: 'testarConexaoNuvemshop'
		},
		dataType: 'json',
		success: function (response) {
			if (!response.success) {
				$("#loading").hide();
				Materialize.toast('<i class="material-icons">error</i> Nuvemshop desativada ou não configurada. Ative a integração antes de exportar produtos.', 5000, 'red');
				return;
			}

			// Contador para feedback ao usuário
			let totalProcessados = 0;
			let sucessos = 0;
			let falhas = 0;
			const totalProdutos = produtosSelecionados.length;

			// Mostrar indicador de carregamento
			$("#loading").show();

			// Mostrar toast de início se necessário
			if (mostrarMensagemInicial) {
				Materialize.toast('Iniciando exportação de ' + totalProdutos + ' produtos...', 3000, 'blue');
			}

			produtosSelecionados.forEach(function (codigo_interno) {
				$.ajax({
					url: 'produtos_ajax.php',
					type: 'post',
					data: {
						request: 'obterDadosProduto',
						codigo_interno: codigo_interno
					},
					dataType: 'json',
					success: function (response) {
						if (response && response.length > 0) {
							var produto = response[0];

							// Verificar se o produto já existe na Nuvemshop antes de exportar
							$.ajax({
								url: 'nuvemshop/nuvemshop_proxy.php?operation=search&sku=' + produto.codigo_gtin,
								type: 'GET',
								dataType: 'json',
								success: function (nuvemResponse) {
									if (nuvemResponse.error) {
										console.error("Erro na Nuvemshop:", nuvemResponse.error);
										falhas++;
										Materialize.toast('<i class="material-icons">error</i> Erro: ' + nuvemResponse.error, 5000, 'red');
									} else {
										exportarProdutoParaNuvemshop(
											produto.codigo_interno,
											produto.codigo_gtin,
											produto.descricao,
											produto.descricao_detalhada,
											produto.preco_venda,
											produto.peso,
											produto.altura,
											produto.largura,
											produto.comprimento
										);
										sucessos++;
									}

									totalProcessados++;
									if (totalProcessados === totalProdutos) {
										finalizarExportacao(sucessos, falhas);
									}
								},
								error: function (xhr) {
									console.error("Erro ao verificar produto na Nuvemshop:", xhr.responseText);
									falhas++;
									totalProcessados++;
									if (totalProcessados === totalProdutos) {
										finalizarExportacao(sucessos, falhas);
									}
								}
							});
						} else {
							falhas++;
							totalProcessados++;
							if (totalProcessados === totalProdutos) {
								finalizarExportacao(sucessos, falhas);
							}
						}
					},
					error: function () {
						falhas++;
						totalProcessados++;
						if (totalProcessados === totalProdutos) {
							finalizarExportacao(sucessos, falhas);
						}
					}
				});
			});
		},
		error: function () {
			$("#loading").hide();
			Materialize.toast('<i class="material-icons">error</i> Erro ao verificar status da integração com a Nuvemshop', 5000, 'red');
		}
	});
}

// Função auxiliar para finalizar o processo de exportação
function finalizarExportacao(sucessos, falhas) {
	$("#loading").hide();

	if (falhas === 0 && sucessos > 0) {
		Materialize.toast('<i class="material-icons">check_circle</i> Exportação concluída com sucesso!', 5000, 'green');
		// Limpar a lista de produtos selecionados após exportação bem-sucedida
		produtosSelecionados = [];
		// Desmarcar todas as checkboxes
		$('input[type=checkbox][id^="prod_"]').prop('checked', false);
	} else if (sucessos === 0) {
		Materialize.toast('<i class="material-icons">error</i> Falha na exportação. Nenhum produto foi exportado.', 5000, 'red');
	} else {
		Materialize.toast('<i class="material-icons">warning</i> Exportação parcial: ' + sucessos + ' produtos exportados, ' + falhas + ' falhas.', 5000, 'orange');
	}
}



// Função unificada para criar um novo produto na Nuvemshop
function criarProdutoNuvemshop(codigo_gtin, descricao, descricao_detalhada,
	preco_venda, peso, altura, largura, comprimento,
	gradeResponse) {
	console.log("Criando novo produto na Nuvemshop:", descricao);

	// Obter a quantidade do produto do banco de dados
	var codigoGtinProduto = codigo_gtin; // Armazenar em variável local para uso no AJAX
	console.log("Buscando quantidade para o produto com GTIN:", codigoGtinProduto);

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'obterQuantidadeProduto',
			codigo_gtin: codigoGtinProduto
		},
		dataType: 'json',
		success: function(response) {
			window.qtdeProduto = response.qtde || 0;
			console.log("Quantidade do produto obtida:", window.qtdeProduto);
		},
		error: function(xhr) {
			console.error("Erro ao obter quantidade do produto:", xhr.responseText);
			window.qtdeProduto = 0;
		}
	});

	// Dados básicos do produto
	var newProductData = {
		name: {
			pt: descricao
		},
		description: {
			pt: cleanHTML(descricao_detalhada)
		},
		handle: {
			pt: descricao.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
		},
		published: true
	};

	// Verificar se existem variações do produto
	if (gradeResponse && gradeResponse.length > 0) {
		console.log("Processando variações do produto:", gradeResponse);

		// Coletar atributos (variação e característica)
		var attributes = [];
		var hasVariacao = false;
		var hasCaracteristica = false;

		// Verificar se temos variação e/ou característica
		gradeResponse.forEach(function(item) {
			if (item.variacao && item.variacao.trim() !== '') {
				hasVariacao = true;
			}
			if (item.caracteristica && item.caracteristica.trim() !== '') {
				hasCaracteristica = true;
			}
		});

		// Adicionar atributos ao produto
		if (hasVariacao) {
			attributes.push({
				pt: "Variação"
			});
		}

		if (hasCaracteristica) {
			attributes.push({
				pt: "Característica"
			});
		}

		// Adicionar atributos ao produto se houver
		if (attributes.length > 0) {
			newProductData.attributes = attributes;
		}

		// Preparar as variantes
		var variants = [];

		// Adicionar a variante principal
		// Converter valores para números
		var pesoNum = parseFloat((peso || "0").replace(',', '.'));
		var alturaNum = parseFloat((altura || "0").replace(',', '.'));
		var larguraNum = parseFloat((largura || "0").replace(',', '.'));
		var comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

		console.log("Valores dimensionais para variante principal:", {
			peso: pesoNum,
			altura: alturaNum,
			largura: larguraNum,
			comprimento: comprimentoNum
		});

		var variantePrincipal = {
			price: parseFloat(preco_venda.replace(',', '.')),
			stock_management: true,
			stock: parseInt(window.qtdeProduto) || 0,
			weight: pesoNum,
			depth: comprimentoNum,
			width: larguraNum,
			height: alturaNum,
			sku: codigo_gtin
		};

		// Se temos atributos, adicionar valores para a variante principal
		if (attributes.length > 0) {
			var valoresPrincipais = [];

			// Para cada atributo, adicionar um valor padrão
			if (hasVariacao) {
				// Usar o primeiro valor de variação disponível
				var primeiraVariacao = null;
				for (var i = 0; i < gradeResponse.length; i++) {
					if (gradeResponse[i].variacao && gradeResponse[i].variacao.trim() !== '') {
						primeiraVariacao = gradeResponse[i].variacao.trim();
						break;
					}
				}
				valoresPrincipais.push({
					pt: primeiraVariacao || "Padrão"
				});
			}

			if (hasCaracteristica) {
				// Usar o primeiro valor de característica disponível
				var primeiraCaracteristica = null;
				for (var i = 0; i < gradeResponse.length; i++) {
					if (gradeResponse[i].caracteristica && gradeResponse[i].caracteristica.trim() !== '') {
						primeiraCaracteristica = gradeResponse[i].caracteristica.trim();
						break;
					}
				}
				valoresPrincipais.push({
					pt: primeiraCaracteristica || "Padrão"
				});
			}

			// Adicionar os valores à variante principal
			variantePrincipal.values = valoresPrincipais;
		}

		// Adicionar a variante principal
		variants.push(variantePrincipal);

		// Processar as variantes adicionais
		var variantesProcessadas = new Set(); // Para evitar duplicatas

		// Adicionar a chave da variante principal para evitar duplicatas
		if (variantePrincipal.values) {
			var chavePrincipal = JSON.stringify(variantePrincipal.values);
			variantesProcessadas.add(chavePrincipal);
		}

		// Processar as variantes adicionais
		gradeResponse.forEach(function(item) {
			if (item.codigo_gtin && item.codigo_gtin !== codigo_gtin) {
				// Buscar dados individuais da variante (estoque e preço)
				var dadosVariante = buscarDadosVariante(item.codigo_gtin);

				// Criar uma nova variante com dados individuais (incluindo dimensões próprias)
				var novaVariante = {
					price: dadosVariante.preco, // ✅ Preço individual da variante!
					stock_management: true,
					stock: dadosVariante.estoque, // ✅ Estoque individual da variante!
					weight: dadosVariante.peso > 0 ? dadosVariante.peso : pesoNum,
					depth: dadosVariante.comprimento > 0 ? dadosVariante.comprimento : comprimentoNum,
					width: dadosVariante.largura > 0 ? dadosVariante.largura : larguraNum,
					height: dadosVariante.altura > 0 ? dadosVariante.altura : alturaNum,
					sku: item.codigo_gtin
				};

				// Se temos atributos, adicionar valores para a variante
				if (attributes.length > 0) {
					var valores = [];
					var atributosOrdenados = [];

					// Primeiro, vamos ordenar os atributos para garantir consistência
					attributes.forEach(function(attr) {
						if (attr.pt === "Variação") {
							atributosOrdenados[0] = attr;
						} else if (attr.pt === "Característica") {
							atributosOrdenados[1] = attr;
						}
					});

					// Remover elementos undefined
					atributosOrdenados = atributosOrdenados.filter(function(attr) {
						return attr !== undefined;
					});

					// Agora, para cada atributo na ordem correta, adicionar o valor correspondente
					atributosOrdenados.forEach(function(attr) {
						if (attr.pt === "Variação") {
							valores.push({
								pt: item.variacao && item.variacao.trim() !== '' ? item.variacao.trim() : "Padrão"
							});
						} else if (attr.pt === "Característica") {
							valores.push({
								pt: item.caracteristica && item.caracteristica.trim() !== '' ? item.caracteristica.trim() : "Padrão"
							});
						}
					});

					// Verificar se esta combinação de valores já foi processada
					var chaveVariante = JSON.stringify(valores);

					if (!variantesProcessadas.has(chaveVariante)) {
						// Adicionar os valores à nova variante
						novaVariante.values = valores;

						// Manter o campo name para a criação inicial do produto
						// Não precisamos removê-lo aqui porque estamos criando o produto do zero

						// Criar uma cópia profunda da variante para evitar referências compartilhadas
						var novaVarianteCopia = JSON.parse(JSON.stringify(novaVariante));

						variants.push(novaVarianteCopia);
						variantesProcessadas.add(chaveVariante);
					}
				} else {
					// Se não temos atributos, adicionar a variante sem valores
					variants.push(novaVariante);
				}
			}
		});

		// Adicionar as variantes ao produto
		newProductData.variants = variants;
	} else {
		// Sem variações, usar apenas a variante principal
		// Converter valores para números
		var pesoNum = parseFloat((peso || "0").replace(',', '.'));
		var alturaNum = parseFloat((altura || "0").replace(',', '.'));
		var larguraNum = parseFloat((largura || "0").replace(',', '.'));
		var comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

		console.log("Valores dimensionais para variante única:", {
			peso: pesoNum,
			altura: alturaNum,
			largura: larguraNum,
			comprimento: comprimentoNum
		});

		newProductData.variants = [{
			price: parseFloat(preco_venda.replace(',', '.')),
			stock_management: true,
			stock: parseInt(window.qtdeProduto) || 0,
			weight: pesoNum,
			depth: comprimentoNum,
			width: larguraNum,
			height: alturaNum,
			sku: codigo_gtin
		}];
	}

	// Preparar dados para envio
	var safeProductData;
	try {
		console.log("Preparando dados para criação do produto");
		safeProductData = JSON.stringify(newProductData);
		console.log("Dados preparados com sucesso");
	} catch (e) {
		console.error("Erro ao preparar dados para criação:", e);
		$("#loading").hide();
		Materialize.toast('<i class="material-icons">error</i> Erro ao preparar dados para criação do produto', 5000, 'red');
		return;
	}

	// Criar o produto na Nuvemshop
	$.ajax({
		url: 'nuvemshop/nuvemshop_proxy.php',
		type: 'POST',
		contentType: 'application/json',
		data: safeProductData,
		success: function (createResponse) {
			$("#loading").hide();
			console.log("Resposta ao criar produto:", createResponse);

			// Verificar se a resposta é válida
			if (!createResponse) {
				Materialize.toast('<i class="material-icons">error</i> Erro: Resposta vazia do servidor', 5000, 'red');
				return;
			}

			// Processar resposta diretamente
			var safeResponse = createResponse;

			// Se a resposta for um objeto complexo, processá-la
			if (typeof createResponse === 'object' && Object.keys(createResponse).length > 0) {
				try {
					var safeResponseJson = JSON.stringify(createResponse);
					safeResponse = JSON.parse(safeResponseJson);
					console.log("Resposta ao criar produto processada com segurança");
				} catch (parseError) {
					console.error("Erro ao processar resposta:", parseError);
					// Continuar com a resposta original
					safeResponse = createResponse;
				}
			}

			// Verificar se a resposta contém erro
			if (safeResponse && safeResponse.error) {
				Materialize.toast('<i class="material-icons">error</i> Erro: ' + safeResponse.error, 5000, 'red');
				return;
			}

			// Verificar se a resposta é um objeto vazio
			if ($.isEmptyObject(safeResponse)) {
				Materialize.toast('<i class="material-icons">error</i> Erro: Resposta vazia do servidor', 5000, 'red');
				return;
			}

			// Verificar se a resposta contém um ID (indicando sucesso real)
			if (createResponse.id) {
				// Extrair o código interno do SKU
				var codigo_interno = null;

				// Procurar o código interno nas variantes do produto
				if (gradeResponse && gradeResponse.length > 0) {
					for (var i = 0; i < gradeResponse.length; i++) {
						if (gradeResponse[i].codigo_interno) {
							codigo_interno = gradeResponse[i].codigo_interno;
							break;
						}
					}
				}

				// Se não encontrou nas variantes, tentar extrair do SKU principal
				if (!codigo_interno) {
					// Buscar o produto pelo código GTIN
					$.ajax({
						url: 'produtos_ajax.php',
						type: 'post',
						data: {
							request: 'buscarProdutoPorGtin',
							codigo_gtin: codigo_gtin
						},
						dataType: 'json',
						success: function(response) {
							if (response && response.length > 0) {
								var produtoCodigoInterno = response[0].codigo_interno;

								// Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
								// Apenas remover o produto da lista de selecionados
								var index = produtosSelecionados.indexOf(produtoCodigoInterno.toString());
								if (index !== -1) {
									produtosSelecionados.splice(index, 1);
									console.log("Produto removido da lista de exportação após exportação bem-sucedida:", produtoCodigoInterno);
								}
							}
						}
					});
				} else {
					// Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
					// Apenas remover o produto da lista de selecionados
					var index = produtosSelecionados.indexOf(codigo_interno.toString());
					if (index !== -1) {
						produtosSelecionados.splice(index, 1);
						console.log("Produto removido da lista de exportação após exportação bem-sucedida:", codigo_interno);
					}
				}

				Materialize.toast('<i class="material-icons">check_circle</i> Produto criado com sucesso na Nuvemshop!', 4000, 'green');
			} else {
				// Se não tiver ID, não é um sucesso
				Materialize.toast('<i class="material-icons">warning</i> Resposta inesperada ao criar produto', 5000, 'orange');
				console.error("Resposta sem ID ao criar produto:", createResponse);
			}
		},
		error: function (xhr) {
			$("#loading").hide();
			console.error("Erro ao criar produto:", xhr.responseText);

			try {
				var response = JSON.parse(xhr.responseText);
				if (response && response.error) {
					Materialize.toast('<i class="material-icons">error</i> Erro: ' + response.error, 5000, 'red');
				} else {
					Materialize.toast('<i class="material-icons">error</i> Erro ao criar produto na Nuvemshop', 5000, 'red');
				}
			} catch (e) {
				// Se não conseguir analisar a resposta JSON
				Materialize.toast('<i class="material-icons">error</i> Erro ao criar produto na Nuvemshop', 5000, 'red');
			}
		}
	});
}


// Usando implementação local simples sem utils.js

// Função unificada para atualizar um produto existente na Nuvemshop
function atualizarProdutoNuvemshop(product_id, codigo_gtin, descricao, descricao_detalhada,
	preco_venda, peso, altura, largura, comprimento,
	existingVariants, gradeResponse) {
	console.log("Atualizando produto na Nuvemshop, ID:", product_id);

	// Obter a quantidade do produto do banco de dados usando o código GTIN passado como parâmetro
	console.log("Buscando quantidade para o produto com GTIN:", codigo_gtin);

	$.ajax({
		url: 'produtos_ajax.php',
		type: 'post',
		data: {
			request: 'obterQuantidadeProduto',
			codigo_gtin: codigo_gtin
		},
		dataType: 'json',
		success: function(response) {
			window.qtdeProduto = response.qtde || 0;
			console.log("Quantidade do produto obtida:", window.qtdeProduto);
		},
		error: function(xhr) {
			console.error("Erro ao obter quantidade do produto:", xhr.responseText);
			window.qtdeProduto = 0;
		}
	});

	// Dados básicos do produto para atualização
	var updateData = {
		name: {
			pt: descricao
		},
		description: {
			pt: cleanHTML(descricao_detalhada)
		},
		handle: {
			pt: descricao.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
		},
		published: true
	};

	// Verificar se existem variações do produto
	if (gradeResponse && gradeResponse.length > 0) {
		console.log("Processando variações do produto para atualização:", gradeResponse);

		// Coletar atributos (variação e característica)
		var attributes = [];
		var hasVariacao = false;
		var hasCaracteristica = false;

		// Verificar se temos variação e/ou característica
		gradeResponse.forEach(function(item) {
			if (item.variacao && item.variacao.trim() !== '') {
				hasVariacao = true;
			}
			if (item.caracteristica && item.caracteristica.trim() !== '') {
				hasCaracteristica = true;
			}
		});

		// Verificar se o produto já tem atributos na Nuvemshop
		var hasExistingVariacao = false;
		var hasExistingCaracteristica = false;

		// Verificar os atributos existentes no produto
		if (existingVariants && existingVariants.length > 0) {
			// Verificar se temos acesso aos atributos do produto
			if (existingVariants[0].product && existingVariants[0].product.attributes) {
				console.log("Produto já tem atributos na Nuvemshop:", existingVariants[0].product.attributes);

				// Verificar quais atributos já existem
				existingVariants[0].product.attributes.forEach(function(attr) {
					if (attr.pt === "Variação") {
						hasExistingVariacao = true;
					} else if (attr.pt === "Característica") {
						hasExistingCaracteristica = true;
					}
				});
			} else {
				// Se não temos acesso direto aos atributos do produto, verificar pelas variantes
				console.log("Verificando atributos pelas variantes existentes");

				// Se alguma variante tem valores, assumimos que o produto tem atributos
				existingVariants.forEach(function(variant) {
					if (variant.values && variant.values.length > 0) {
						// Verificar os valores para determinar os atributos
						// Baseado no número de valores, determinamos quais atributos existem
						if (variant.values.length >= 1) {
							hasExistingVariacao = true;
						}
						if (variant.values.length >= 2) {
							hasExistingCaracteristica = true;
						}
					}
				});
			}

			console.log("Atributos existentes detectados:", {
				variacao: hasExistingVariacao,
				caracteristica: hasExistingCaracteristica
			});
		}

		// Combinar atributos existentes com novos atributos
		if (hasVariacao || hasExistingVariacao) {
			attributes.push({
				pt: "Variação"
			});
		}

		if (hasCaracteristica || hasExistingCaracteristica) {
			attributes.push({
				pt: "Característica"
			});
		}

		// Adicionar atributos ao produto se houver
		if (attributes.length > 0) {
			updateData.attributes = attributes;
			console.log("Atualizando produto com atributos:", attributes);
		}
	}

	// Preparar dados para envio
	var safeUpdateData;
	try {
		console.log("Preparando dados para atualização do produto");
		safeUpdateData = JSON.stringify(updateData);

		console.log("Dados preparados com sucesso");
	} catch (e) {
		console.error("Erro ao preparar dados para atualização:", e);

		// Tentar uma abordagem alternativa
		try {
			console.log("Tentando abordagem alternativa para serialização");

			// Criar uma cópia simplificada do objeto
			var simpleUpdateData = {
				name: updateData.name,
				description: updateData.description || "",
				price: updateData.price,
				stock_management: updateData.stock_management,
				stock: updateData.stock
			};

			// Adicionar atributos se existirem
			if (updateData.attributes && updateData.attributes.length > 0) {
				simpleUpdateData.attributes = [];
				updateData.attributes.forEach(function(attr) {
					simpleUpdateData.attributes.push({
						pt: attr.pt
					});
				});
			}

			safeUpdateData = JSON.stringify(simpleUpdateData);
			console.log("Dados simplificados preparados com sucesso");
		} catch (fallbackError) {
			console.error("Erro na abordagem alternativa:", fallbackError);
			$("#loading").hide();
			Materialize.toast('<i class="material-icons">error</i> Erro ao preparar dados para atualização', 5000, 'red');
			return;
		}
	}

	// Atualizar o produto principal
	$.ajax({
		url: 'nuvemshop/nuvemshop_proxy.php?operation=update&product_id=' + product_id,
		type: 'POST',
		contentType: 'application/json',
		data: safeUpdateData,
		success: function (updateResponse) {
			try {
				console.log("Processando resposta da atualização do produto");

				// Verificar se a resposta é válida
				if (!updateResponse) {
					$("#loading").hide();
					Materialize.toast('<i class="material-icons">error</i> Erro: Resposta vazia do servidor', 5000, 'red');
					return;
				}

				// Processar a resposta de forma simples
				var safeResponse;

				try {
					// Se a resposta for uma string, tentar converter para objeto
					if (typeof updateResponse === 'string') {
						try {
							updateResponse = JSON.parse(updateResponse);
						} catch (parseError) {
							// Continuar com a string
						}
					}

					// Se for um objeto, extrair propriedades essenciais
					if (typeof updateResponse === 'object' && updateResponse !== null) {
						// Extrair apenas as propriedades essenciais
						safeResponse = {};
						if (updateResponse.id) safeResponse.id = updateResponse.id;
						if (updateResponse.error) safeResponse.error = updateResponse.error;
						if (updateResponse.code) safeResponse.code = updateResponse.code;
						if (updateResponse.message) safeResponse.message = updateResponse.message;

						console.log("Resposta processada com sucesso");
					} else {
						// Fallback: extrair manualmente as propriedades essenciais
						safeResponse = {};

						// Se a resposta for um objeto, extrair apenas as propriedades essenciais
						if (typeof updateResponse === 'object' && updateResponse !== null) {
							// Extrair apenas as propriedades essenciais que precisamos verificar
							if (updateResponse.id) safeResponse.id = updateResponse.id;
							if (updateResponse.error) safeResponse.error = updateResponse.error;
							if (updateResponse.code) safeResponse.code = updateResponse.code;
							if (updateResponse.message) safeResponse.message = updateResponse.message;
						} else {
							// Se não for um objeto, usar a resposta original
							safeResponse = updateResponse;
						}
					}

					console.log("Resposta ao atualizar produto processada com segurança");
				} catch (processError) {
					console.error("Erro ao processar resposta:", processError);

					// Fallback: extrair manualmente as propriedades essenciais
					safeResponse = {};

					// Se a resposta for um objeto, extrair apenas as propriedades essenciais
					if (typeof updateResponse === 'object' && updateResponse !== null) {
						if (updateResponse.id) safeResponse.id = updateResponse.id;
						if (updateResponse.error) safeResponse.error = updateResponse.error;
						if (updateResponse.code) safeResponse.code = updateResponse.code;
						if (updateResponse.message) safeResponse.message = updateResponse.message;
					} else {
						// Se não for um objeto, usar a resposta original
						safeResponse = updateResponse;
					}
				}

				// Verificar se a resposta contém erro
				if (safeResponse && safeResponse.error) {
					$("#loading").hide();
					Materialize.toast('<i class="material-icons">error</i> Erro: ' + safeResponse.error, 5000, 'red');
					return;
				}

				// Verificar se a resposta é um objeto vazio
				if ($.isEmptyObject(safeResponse)) {
					$("#loading").hide();
					Materialize.toast('<i class="material-icons">error</i> Erro: Resposta vazia do servidor', 5000, 'red');
					return;
				}
			} catch (e) {
				console.error("Erro ao processar resposta da API:", e);
				$("#loading").hide();
				Materialize.toast('<i class="material-icons">error</i> Erro ao processar resposta da API', 5000, 'red');
				return;
			}

			// Verificar se a resposta contém um ID (indicando sucesso real)
			if (safeResponse && safeResponse.id) {
				// Não atualizar status aqui - ProductUpdater já fez isso com base nas variantes
				// Apenas remover o produto da lista de selecionados
				var index = produtosSelecionados.indexOf(codigo_interno.toString());
				if (index !== -1) {
					produtosSelecionados.splice(index, 1);
					console.log("Produto removido da lista de exportação após atualização bem-sucedida:", codigo_interno);
				}

				// Só mostra sucesso se tiver ID e não tiver variantes para atualizar
				if (!existingVariants || existingVariants.length === 0) {
					$("#loading").hide();
					Materialize.toast('<i class="material-icons">check_circle</i> Produto atualizado com sucesso na Nuvemshop!', 4000, 'green');
				}

				// Se tiver variantes existentes e variações do produto
				if (existingVariants && existingVariants.length > 0 && gradeResponse && gradeResponse.length > 0) {
					// Converter valores para números
					var pesoNum = parseFloat((peso || "0").replace(',', '.'));
					var alturaNum = parseFloat((altura || "0").replace(',', '.'));
					var larguraNum = parseFloat((largura || "0").replace(',', '.'));
					var comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

					console.log("Valores dimensionais para atualização de variantes:", {
						peso: pesoNum,
						altura: alturaNum,
						largura: larguraNum,
						comprimento: comprimentoNum
					});

					// Contador para controlar quando todas as variantes foram atualizadas
					var totalVariantes = 0; // Inicialmente 0, vamos incrementar conforme necessário
					var variantesAtualizadas = 0;

					// Verificar se devemos atualizar as variantes existentes
					var atualizarVariantesExistentes = false;

					// Verificar se alguma variante existente corresponde às variações no banco de dados
					var variantesParaAtualizar = [];

					// Mapear TODAS as variantes existentes para atualização
					if (existingVariants.length > 0) {
						// Atualizar todas as variantes existentes com os novos valores dimensionais
						existingVariants.forEach(function(variant) {
							// Preparar os dados para atualização
							var variantData = {
								price: parseFloat(preco_venda.replace(',', '.')),
								stock_management: true,
								stock: parseInt(window.qtdeProduto) || 0,
								weight: pesoNum,
								depth: comprimentoNum,
								width: larguraNum,
								height: alturaNum
							};

							// Se a variante tem valores, preservá-los
							if (variant.values && variant.values.length > 0) {
								variantData.values = variant.values;
							}

							// Adicionar à lista de variantes para atualizar
							variantesParaAtualizar.push({
								id: variant.id,
								data: variantData
							});

							atualizarVariantesExistentes = true;
							totalVariantes++;

							console.log("Variante para atualizar:", {
								id: variant.id,
								sku: variant.sku,
								data: variantData
							});
						});
					}

					// Se não temos variantes para atualizar, mostrar mensagem de sucesso
					if (!atualizarVariantesExistentes) {
						$("#loading").hide();
						Materialize.toast('<i class="material-icons">check_circle</i> Produto atualizado com sucesso na Nuvemshop!', 4000, 'green');
					} else {
						// Atualizar as variantes existentes
						variantesParaAtualizar.forEach(function(variante) {
							// Log detalhado dos dados que serão enviados
							console.log("Enviando dados para atualizar variante " + variante.id + ":", variante.data);

							// Preparar dados para envio
							var safeVariantData;
							try {
								console.log("Preparando dados para atualização da variante");
								safeVariantData = JSON.stringify(variante.data);

								console.log("Dados da variante preparados com sucesso");
							} catch (e) {
								console.error("Erro ao preparar dados para atualização da variante:", e);

								// Tentar uma abordagem mais simples
								try {
									console.log("Tentando abordagem alternativa para serialização da variante");

									// Criar um objeto simplificado com apenas as propriedades essenciais
									var simpleVariantData = {
										price: variante.data.price,
										stock: variante.data.stock,
										stock_management: variante.data.stock_management,
										weight: variante.data.weight,
										depth: variante.data.depth,
										width: variante.data.width,
										height: variante.data.height
									};

									// Se tiver valores, incluí-los
									if (variante.data.values) {
										// Criar cópias simples dos valores
										simpleVariantData.values = [];
										variante.data.values.forEach(function(value) {
											if (typeof value === 'object' && value !== null) {
												var simpleValue = {};
												if (value.pt) simpleValue.pt = value.pt;
												simpleVariantData.values.push(simpleValue);
											} else {
												simpleVariantData.values.push(value);
											}
										});
									}

									safeVariantData = JSON.stringify(simpleVariantData);
									console.log("Dados simplificados da variante preparados com sucesso");
								} catch (simplifyError) {
									console.error("Erro ao preparar dados simplificados da variante:", simplifyError);

									// Tentar uma abordagem ainda mais simples
									try {
										console.log("Tentando abordagem ultra-simplificada para serialização da variante");

										// Criar um objeto ultra-simplificado apenas com preço e estoque
										var ultraSimpleVariantData = {
											price: variante.data.price || 0,
											stock: variante.data.stock || 0,
											stock_management: true
										};

										safeVariantData = JSON.stringify(ultraSimpleVariantData);
										console.log("Dados ultra-simplificados da variante preparados com sucesso");
									} catch (ultraSimplifyError) {
										console.error("Erro ao preparar dados ultra-simplificados da variante:", ultraSimplifyError);

										// Incrementar contador e continuar
										variantesAtualizadas++;

										// Verificar se todas as variantes foram atualizadas
										if (variantesAtualizadas === totalVariantes) {
											$("#loading").hide();
											Materialize.toast('<i class="material-icons">warning</i> Produto atualizado, mas algumas variantes não foram atualizadas', 5000, 'orange');
										}

										return; // Pular esta variante
									}
								}
							}

							$.ajax({
								url: 'nuvemshop/nuvemshop_proxy.php?operation=update_variant&product_id=' + product_id + '&variant_id=' + variante.id,
								type: 'POST',
								contentType: 'application/json',
								data: safeVariantData,
								success: function (variantResponse) {
									// Evitar processamento excessivo da resposta
									console.log("Variante " + variante.id + " atualizada com sucesso");

									// Extrair apenas o ID da resposta para evitar problemas
									var variantId = null;
									if (variantResponse && typeof variantResponse === 'object' && variantResponse.id) {
										variantId = variantResponse.id;
									}

									console.log("ID da variante atualizada: " + variantId);

									variantesAtualizadas++;

									// Verificar se todas as variantes foram atualizadas
									if (variantesAtualizadas === totalVariantes) {
										$("#loading").hide();
										Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes atualizados com sucesso na Nuvemshop!', 4000, 'green');

										// Auto-sincronização após atualização com variantes
										console.log('🔄 Iniciando auto-sincronização após atualização de produto com variantes...');
										setTimeout(function() {
											if (typeof sincronizarStatusProdutosNuvemshop === 'function') {
												sincronizarStatusProdutosNuvemshop(true); // true = automático
											}
										}, 3000);
									}
								},
								error: function (xhr) {
									console.error("Erro ao atualizar variante " + variante.id + ":", xhr.responseText);
									try {
										var response = JSON.parse(xhr.responseText);
										console.error("Detalhes do erro:", response);

										// Verificar se o erro é relacionado aos valores
										if (response && response.errors && response.errors.values) {
											console.error("Erro específico nos valores da variante:", response.errors.values);
										}
									} catch (e) {
										// Continua silenciosamente
									}

									variantesAtualizadas++;

									// Verificar se todas as variantes foram atualizadas
									if (variantesAtualizadas === totalVariantes) {
										$("#loading").hide();
										Materialize.toast('<i class="material-icons">warning</i> Produto atualizado, mas algumas variantes não foram atualizadas', 5000, 'orange');
									}
								}
							});
						});
					}

					// Verificar se existem variantes adicionais para criar
					var variantesAdicionais = [];
					var variantesProcessadas = new Set(); // Para evitar duplicatas

					// Primeiro, mapeamos as variantes existentes para não criar duplicatas
					if (existingVariants && existingVariants.length > 0) {
						existingVariants.forEach(function(variant) {
							if (variant.values && variant.values.length > 0) {
								var chave = JSON.stringify(variant.values);
								variantesProcessadas.add(chave);
							}
						});
					}

					// Agora processamos as novas variantes
					gradeResponse.forEach(function(item) {
						if (item.codigo_gtin && item.codigo_gtin !== codigo_gtin) {
							// Verificar se esta variante já existe pelo SKU
							var varianteExistente = false;

							// Verificar em TODAS as variantes existentes, não apenas a partir da segunda
							if (existingVariants && existingVariants.length > 0) {
								for (var i = 0; i < existingVariants.length; i++) {
									if (existingVariants[i].sku === item.codigo_gtin) {
										console.log("Variante com SKU " + item.codigo_gtin + " já existe, não será criada novamente");
										varianteExistente = true;
										break;
									}
								}
							}

							if (!varianteExistente) {
								console.log("Nova variante com SKU " + item.codigo_gtin + " será criada");

								// Buscar dados individuais da variante (estoque e preço)
								var dadosVariante = buscarDadosVariante(item.codigo_gtin);

								// Criar uma nova variante com dados individuais (incluindo dimensões próprias)
								var novaVariante = {
									price: dadosVariante.preco, // ✅ Preço individual da variante!
									stock_management: true,
									stock: dadosVariante.estoque, // ✅ Estoque individual da variante!
									weight: dadosVariante.peso > 0 ? dadosVariante.peso : pesoNum,
									depth: dadosVariante.comprimento > 0 ? dadosVariante.comprimento : comprimentoNum,
									width: dadosVariante.largura > 0 ? dadosVariante.largura : larguraNum,
									height: dadosVariante.altura > 0 ? dadosVariante.altura : alturaNum,
									sku: item.codigo_gtin
								};

								console.log("Preparando nova variante com dados dimensionais:", {
									sku: item.codigo_gtin,
									weight: pesoNum,
									depth: comprimentoNum,
									width: larguraNum,
									height: alturaNum
								});

								// Se temos atributos, adicionar valores para a variante
								if (updateData.attributes && updateData.attributes.length > 0) {
									var valores = [];
									var atributosOrdenados = [];

									// Primeiro, vamos ordenar os atributos para garantir consistência
									updateData.attributes.forEach(function(attr) {
										if (attr.pt === "Variação") {
											atributosOrdenados[0] = attr;
										} else if (attr.pt === "Característica") {
											atributosOrdenados[1] = attr;
										}
									});

									// Remover elementos undefined
									atributosOrdenados = atributosOrdenados.filter(function(attr) {
										return attr !== undefined;
									});

									// Agora, para cada atributo na ordem correta, adicionar o valor correspondente
									atributosOrdenados.forEach(function(attr) {
										if (attr.pt === "Variação") {
											valores.push({
												pt: item.variacao && item.variacao.trim() !== '' ? item.variacao.trim() : "Padrão"
											});
										} else if (attr.pt === "Característica") {
											valores.push({
												pt: item.caracteristica && item.caracteristica.trim() !== '' ? item.caracteristica.trim() : "Padrão"
											});
										}
									});

									// Verificar se esta combinação de valores já foi processada
									var chaveVariante = JSON.stringify(valores);

									if (!variantesProcessadas.has(chaveVariante)) {
										// Adicionar os valores à nova variante
										novaVariante.values = valores;

										// Remover o campo name para evitar que seja criado como produto separado
										delete novaVariante.name;

										// Criar uma cópia profunda da variante para evitar referências compartilhadas
										var novaVarianteCopia = JSON.parse(JSON.stringify(novaVariante));

										variantesAdicionais.push(novaVarianteCopia);
										variantesProcessadas.add(chaveVariante);
										console.log("Nova combinação de valores adicionada:", {
											sku: item.codigo_gtin,
											valores: valores,
											chave: chaveVariante
										});
									} else {
										console.log("Combinação de valores já existe, não será adicionada:", {
											sku: item.codigo_gtin,
											valores: valores,
											chave: chaveVariante
										});
									}
								} else {
									// Se não temos atributos, adicionar a variante sem valores
									console.log("ATENÇÃO: Tentando adicionar variante sem atributos definidos no produto. Isso pode causar problemas na Nuvemshop.");

									// Remover o campo name para evitar que seja criado como produto separado
									delete novaVariante.name;

									variantesAdicionais.push(novaVariante);
								}
							}
						}
					});

					// Criar as variantes adicionais
					if (variantesAdicionais.length > 0) {
						// Incrementar o contador de variantes apenas se já temos variantes para atualizar
						if (atualizarVariantesExistentes) {
							totalVariantes += variantesAdicionais.length;
						} else {
							// Se não temos variantes para atualizar, começamos do zero
							totalVariantes = variantesAdicionais.length;
							variantesAtualizadas = 0;
						}

						variantesAdicionais.forEach(function(novaVariante) {
							// Garantir que os valores dimensionais estejam presentes
							if (!novaVariante.weight) novaVariante.weight = pesoNum;
							if (!novaVariante.depth) novaVariante.depth = comprimentoNum;
							if (!novaVariante.width) novaVariante.width = larguraNum;
							if (!novaVariante.height) novaVariante.height = alturaNum;

							// Log detalhado dos dados que serão enviados
							console.log("Enviando dados para criar nova variante para o produto " + product_id + ":", novaVariante);

							// Verificar se o endpoint create_variant existe
							$.ajax({
								url: 'nuvemshop/nuvemshop_proxy.php?operation=create_variant&product_id=' + product_id,
								type: 'POST',
								contentType: 'application/json',
								data: JSON.stringify(novaVariante),
								success: function (variantResponse) {
									// Evitar processamento excessivo da resposta
									console.log("Nova variante criada com sucesso para o produto " + product_id);

									// Extrair apenas o ID da resposta para evitar problemas
									var variantId = null;
									var hasError = false;

									// Verificar se a resposta é uma string e tentar convertê-la para objeto
									if (typeof variantResponse === 'string') {
										try {
											var parsedResponse = JSON.parse(variantResponse);
											if (parsedResponse && parsedResponse.id) {
												variantId = parsedResponse.id;
											}
											if (parsedResponse && parsedResponse.name && Array.isArray(parsedResponse.name)) {
												hasError = true;
											}
										} catch (e) {
											// Continua silenciosamente
										}
									} else if (variantResponse && typeof variantResponse === 'object') {
										if (variantResponse.id) {
											variantId = variantResponse.id;
										}
										if (variantResponse.name && Array.isArray(variantResponse.name)) {
											hasError = true;
										}
									}

									console.log("ID da nova variante: " + variantId);

									// Verificar se a resposta contém um erro
									if (hasError) {
										console.error("Erro ao criar variante");
										// Não tentamos novamente para evitar duplicação
										console.log("Variante não criada devido a erro. Continuando com as próximas variantes.");
									}

									// Incrementar o contador apenas uma vez, independentemente do resultado
									variantesAtualizadas++;

									// Verificar se todas as variantes foram atualizadas
									if (variantesAtualizadas === totalVariantes) {
										$("#loading").hide();
										Materialize.toast('<i class="material-icons">check_circle</i> Produto e variantes atualizados com sucesso na Nuvemshop!', 4000, 'green');

										// Definir uma flag global para evitar que o produto seja exportado novamente
										window.ultimoProdutoAtualizado = codigo_gtin;

										// Definir um timeout para limpar a flag após 5 segundos
										setTimeout(function() {
											window.ultimoProdutoAtualizado = null;
										}, 5000);
									}
								},
								error: function (xhr) {
									console.error("Erro ao criar nova variante:", xhr.responseText);
									try {
										var response = JSON.parse(xhr.responseText);
										console.error("Detalhes do erro:", response);

										// Apenas logar o erro, não tentar novamente para evitar duplicação
										if (response && response.description) {
											console.error("Descrição do erro:", response.description);
											console.log("Variante não criada devido a erro. Continuando com as próximas variantes.");
										}
									} catch (e) {
										// Continua silenciosamente
										console.error("Erro ao analisar resposta:", e);
									}

									// Incrementar o contador apenas uma vez, independentemente do resultado
									variantesAtualizadas++;

									// Verificar se todas as variantes foram atualizadas
									if (variantesAtualizadas === totalVariantes) {
										$("#loading").hide();
										Materialize.toast('<i class="material-icons">warning</i> Produto atualizado, mas algumas variantes não foram criadas', 5000, 'orange');

										// Definir uma flag global para evitar que o produto seja exportado novamente
										window.ultimoProdutoAtualizado = codigo_gtin;

										// Definir um timeout para limpar a flag após 5 segundos
										setTimeout(function() {
											window.ultimoProdutoAtualizado = null;
										}, 5000);
									}
								}
							});
						});
					} else if (!atualizarVariantesExistentes) {
						// Se não temos variantes para atualizar nem criar, mostrar mensagem de sucesso
						$("#loading").hide();
						Materialize.toast('<i class="material-icons">check_circle</i> Produto atualizado com sucesso na Nuvemshop!', 4000, 'green');

						// Definir uma flag global para evitar que o produto seja exportado novamente
						window.ultimoProdutoAtualizado = codigo_gtin;

						// Definir um timeout para limpar a flag após 5 segundos
						setTimeout(function() {
							window.ultimoProdutoAtualizado = null;
						}, 5000);
					}
				} else if (existingVariants && existingVariants.length > 0) {
					// Se não tiver variações, mas tiver variantes existentes, atualizar apenas a primeira
					var variantId = existingVariants[0].id;

					// Converter valores para números
					var pesoNum = parseFloat((peso || "0").replace(',', '.'));
					var alturaNum = parseFloat((altura || "0").replace(',', '.'));
					var larguraNum = parseFloat((largura || "0").replace(',', '.'));
					var comprimentoNum = parseFloat((comprimento || "0").replace(',', '.'));

					console.log("Valores dimensionais para atualização de variante única:", {
						peso: pesoNum,
						altura: alturaNum,
						largura: larguraNum,
						comprimento: comprimentoNum
					});

					var variantData = {
						price: parseFloat(preco_venda.replace(',', '.')),
						stock_management: false,
						weight: pesoNum,
						depth: comprimentoNum,
						width: larguraNum,
						height: alturaNum
					};

					$.ajax({
						url: 'nuvemshop/nuvemshop_proxy.php?operation=update_variant&product_id=' + product_id + '&variant_id=' + variantId,
						type: 'POST',
						contentType: 'application/json',
						data: JSON.stringify(variantData),
						success: function (variantResponse) {
							$("#loading").hide();
							console.log("Resposta ao atualizar variante:", variantResponse);

							if (variantResponse && variantResponse.error) {
								Materialize.toast('<i class="material-icons">error</i> Erro: ' + variantResponse.error, 5000, 'red');
							} else if (variantResponse && variantResponse.id) {
								// Só mostra sucesso após atualizar a variante com sucesso
								Materialize.toast('<i class="material-icons">check_circle</i> Produto e variante atualizados com sucesso na Nuvemshop!', 4000, 'green');
							} else {
								Materialize.toast('<i class="material-icons">warning</i> Produto atualizado, mas resposta inesperada ao atualizar variante', 5000, 'orange');
							}
						},
						error: function (xhr) {
							$("#loading").hide();
							console.error("Erro ao atualizar variante:", xhr.responseText);
							try {
								var response = JSON.parse(xhr.responseText);
								if (response && response.error) {
									Materialize.toast('<i class="material-icons">error</i> Erro: ' + response.error, 5000, 'red');
								} else {
									Materialize.toast('<i class="material-icons">error</i> Produto atualizado, mas erro ao atualizar variante', 5000, 'red');
								}
							} catch (e) {
								Materialize.toast('<i class="material-icons">error</i> Produto atualizado, mas erro ao atualizar variante', 5000, 'red');
							}
						}
					});
				}
			} else {
				// Se não tiver ID, não é um sucesso
				$("#loading").hide();
				Materialize.toast('<i class="material-icons">warning</i> Resposta inesperada ao atualizar produto', 5000, 'orange');
				console.error("Resposta sem ID ao atualizar produto:", updateResponse);
			}
		},
		error: function (xhr) {
			$("#loading").hide();
			console.error("Erro ao atualizar produto:", xhr.responseText);
			try {
				var response = JSON.parse(xhr.responseText);
				if (response && response.error) {
					Materialize.toast('<i class="material-icons">error</i> Erro: ' + response.error, 5000, 'red');
				} else {
					Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar produto na Nuvemshop', 5000, 'red');
				}
			} catch (e) {
				Materialize.toast('<i class="material-icons">error</i> Erro ao atualizar produto na Nuvemshop', 5000, 'red');
			}
		}
	});
}

// Modificar o evento do checkbox "selecionar todos"
$(document).ready(function () {
	$(document).on('change', '#selecionar_todos_produtos', function () {
		var isChecked = $(this).is(':checked');
		$('.produto-checkbox').prop('checked', isChecked);

		// Atualizar o array de selecionados
		if (isChecked) {
			// Adicionar todos os produtos visíveis ao array
			$('.produto-checkbox').each(function () {
				var codigo_interno = $(this).attr('id').replace('prod_', '');
				if (!produtosSelecionados.includes(codigo_interno)) {
					produtosSelecionados.push(codigo_interno);
				}
			});
		} else {
			// Remover apenas os produtos visíveis do array
			$('.produto-checkbox').each(function () {
				var codigo_interno = $(this).attr('id').replace('prod_', '');
				var index = produtosSelecionados.indexOf(codigo_interno);
				if (index !== -1) {
					produtosSelecionados.splice(index, 1);
				}
			});
		}
	});

	console.log("Produtos selecionados após selecionar/deselecionar todos:", produtosSelecionados);
});

// Adicionar evento para o checkbox "vender_ecomerce"
$(document).on('change', '#vender_ecomerce', function () {
	// Registrar a mudança no console
	console.log("Checkbox 'Vender no E-commerce' alterado para: " + (this.checked ? "marcado" : "desmarcado"));

	// Obter o código interno do produto
	var codigo_interno = document.getElementById('codigo_interno').value;

	// Verificar se o código interno é válido
	if (codigo_interno && codigo_interno !== "0") {
		// Adicionar ou remover o produto da lista de selecionados para exportação
		if (this.checked) {
			// Adicionar à lista de selecionados se não estiver
			if (!produtosSelecionados.includes(codigo_interno.toString())) {
				produtosSelecionados.push(codigo_interno.toString());
				console.log("Produto adicionado para exportação:", codigo_interno);
				Materialize.toast('<i class="material-icons">info</i> Produto adicionado à lista de exportação', 3000, 'blue');
			}
		} else {
			// Remover da lista de selecionados se estiver
			var index = produtosSelecionados.indexOf(codigo_interno.toString());
			if (index !== -1) {
				produtosSelecionados.splice(index, 1);
				console.log("Produto removido da exportação:", codigo_interno);
				Materialize.toast('<i class="material-icons">info</i> Produto removido da lista de exportação', 3000, 'blue');
			}
		}
	} else {
		console.log("Código interno inválido, não é possível adicionar à lista de exportação");
	}
});

// Função createRows duplicada removida - usando a versão unificada no início do arquivo

// Função para atualizar a seleção quando um checkbox é alterado
function atualizarSelecao(checkbox) {
	var codigo_interno = checkbox.id.replace('prod_', '');

	if (checkbox.checked) {
		// Adicionar à lista de selecionados se não estiver
		if (!produtosSelecionados.includes(codigo_interno)) {
			produtosSelecionados.push(codigo_interno);
			console.log("Produto adicionado para exportação:", codigo_interno);
		}
	} else {
		// Remover da lista de selecionados se estiver
		var index = produtosSelecionados.indexOf(codigo_interno);
		if (index !== -1) {
			produtosSelecionados.splice(index, 1);
			console.log("Produto removido da exportação:", codigo_interno);
		}
	}
}

// Função para exportar um único produto para a Nuvemshop
function exportarProduto() {
	var codigo_interno = $("#codigo_interno").val();
	var codigo_gtin = $("#codigo_gtin").val();
	var descricao = $("#descricao").val();
	var descricao_detalhada = $("#descricao_detalhada").val();
	var preco_venda = $("#preco_venda").val();
	var peso = $("#peso").val();
	var altura = $("#altura").val();
	var largura = $("#largura").val();
	var comprimento = $("#comprimento").val();

	// Validações básicas
	if (!codigo_gtin || codigo_gtin === "0") {
		Materialize.toast('<i class="material-icons">error</i> É necessário informar um código de barras (GTIN) válido para exportar o produto.', 4000, 'red');
		return;
	}

	if (!descricao) {
		Materialize.toast('<i class="material-icons">error</i> É necessário informar a descrição do produto.', 4000, 'red');
		return;
	}

	if (!preco_venda) {
		Materialize.toast('<i class="material-icons">error</i> É necessário informar o preço de venda do produto.', 4000, 'red');
		return;
	}

	// Verificar se a integração com a Nuvemshop está ativa
	$.ajax({
		url: 'integracao_ajax.php',
		type: 'post',
		data: {
			request: 'testarConexaoNuvemshop'
		},
		dataType: 'json',
		success: function (response) {
			if (!response.success) {
				Materialize.toast('<i class="material-icons">error</i> Nuvemshop desativada ou não configurada. Ative a integração antes de exportar produtos.', 5000, 'red');
				return;
			}

			// Se a integração estiver ativa, exporta o produto
			exportarProdutoParaNuvemshop(
				codigo_interno,
				codigo_gtin,
				descricao,
				descricao_detalhada,
				preco_venda,
				peso,
				altura,
				largura,
				comprimento
			);
		},
		error: function () {
			Materialize.toast('<i class="material-icons">error</i> Erro ao verificar status da integração com a Nuvemshop', 5000, 'red');
		}
	});
}

// Função auxiliar para limpar HTML potencialmente perigoso
function cleanHTML(html) {
	if (!html) return '';

	// Remove tags de script e iframe
	var cleaned = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
		.replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '');

	// Sanitiza outros elementos potencialmente perigosos
	return cleaned;
}

// Função auxiliar para finalizar o processo de exportação
function finalizarExportacao(sucessos, falhas) {
	$("#loading").hide();

	if (falhas === 0 && sucessos > 0) {
		Materialize.toast('<i class="material-icons">check_circle</i> Exportação concluída com sucesso!', 5000, 'green');
		// Limpar a lista de produtos selecionados após exportação bem-sucedida
		produtosSelecionados = [];
		// Desmarcar todas as checkboxes
		$('input[type=checkbox][id^="prod_"]').prop('checked', false);
	} else if (sucessos === 0) {
		Materialize.toast('<i class="material-icons">error</i> Falha na exportação. Nenhum produto foi exportado.', 5000, 'red');
	} else {
		Materialize.toast('<i class="material-icons">warning</i> Exportação parcial: ' + sucessos + ' produtos exportados, ' + falhas + ' falhas.', 5000, 'orange');
	}
}

/**
 * Gera ícone simples para coluna origem
 * @param {string} status - Status do produto (ENS, ENSVI, ENSV, E)
 * @returns {string} HTML do ícone
 */
function gerarIconeOrigem(status) {
	if (!status) {
		// Produto local
		return "<i class='material-icons' style='color: #424242; font-size: 18px;' title='Produto Local'>computer</i>";
	}

	// NOVO SISTEMA: Esta função está obsoleta para e-commerce
	// Use getEcommerceIcons() para mostrar ícones das plataformas

	// Para compatibilidade, ainda verificamos o status antigo
	switch (status) {
		case 'ENS':
		case 'ENSVI':
		case 'ENSV':
		case 'E':
			// Status antigo ainda no campo status - mostrar aviso
			return "<i class='material-icons' style='color: #FF9800; font-size: 18px;' title='⚠️ Status antigo - Precisa migração'>warning</i>";

		default:
			// Produto local por padrão
			return "<i class='material-icons' style='color: #424242; font-size: 18px;' title='Produto Local'>computer</i>";
	}
}

/**
 * NOVA FUNÇÃO: Gera ícones baseados nos campos específicos de e-commerce
 * @param {Object} produto - Objeto produto com campos ns, ml, shopee
 * @returns {string} HTML com ícones das plataformas
 */
function getEcommerceIcons(produto) {
	let icons = [];

	// Nuvemshop
	if (produto.ns) {
		switch (produto.ns) {
			case 'ENS':
				icons.push("<i class='material-icons' style='color: #2196F3; font-size: 18px;' title='Nuvemshop - Produto Normal'>cloud</i>");
				break;
			case 'ENSVI':
				icons.push("<i class='material-icons' style='color: #2E7D32; font-size: 18px;' title='Nuvemshop - Produto Vitrine'>cloud</i>");
				break;
			case 'ENSV':
				icons.push("<i class='material-icons' style='color: #4CAF50; font-size: 18px;' title='Nuvemshop - Variante'>cloud</i>");
				break;
			case 'E':
				icons.push("<i class='material-icons' style='color: #757575; font-size: 18px;' title='Nuvemshop - Status Migrado'>cloud</i>");
				break;
		}
	}

	// Mercado Livre
	if (produto.ml) {
		switch (produto.ml) {
			case 'ML':
				icons.push("<i class='material-icons' style='color: #FFE135; font-size: 18px;' title='Mercado Livre - Produto Normal'>shopping_bag</i>");
				break;
			case 'MLVI':
				icons.push("<i class='material-icons' style='color: #FF6F00; font-size: 18px;' title='Mercado Livre - Produto Vitrine'>shopping_bag</i>");
				break;
			case 'MLV':
				icons.push("<i class='material-icons' style='color: #FFA000; font-size: 18px;' title='Mercado Livre - Variante'>shopping_bag</i>");
				break;
		}
	}

	// Shopee
	if (produto.shopee) {
		switch (produto.shopee) {
			case 'SH':
				icons.push("<i class='material-icons' style='color: #EE4D2D; font-size: 18px;' title='Shopee - Produto Normal'>store</i>");
				break;
			case 'SHVI':
				icons.push("<i class='material-icons' style='color: #C62828; font-size: 18px;' title='Shopee - Produto Vitrine'>store</i>");
				break;
			case 'SHV':
				icons.push("<i class='material-icons' style='color: #D32F2F; font-size: 18px;' title='Shopee - Variante'>store</i>");
				break;
		}
	}

	return icons.join(' ');
}

/**
 * NOVA FUNÇÃO: Exporta produtos selecionados para Mercado Livre
 */
function exportarProdutosSelecionadosML() {
	if (produtosSelecionados.length === 0) {
		Materialize.toast('Selecione pelo menos um produto para exportar para o Mercado Livre!', 4000, 'red');
		return;
	}

	console.log("Exportando produtos selecionados para ML:", produtosSelecionados);

	// Verificar se a integração com o ML está ativa
	$.ajax({
		url: 'ml_ajax.php',
		type: 'post',
		data: {
			request: 'testarConexaoMercadoLivre'
		},
		dataType: 'json',
		success: function (response) {
			if (!response.success) {
				Materialize.toast('<i class="material-icons">error</i> Mercado Livre desativado ou não configurado. Configure a integração primeiro.', 5000, 'red');
				return;
			}

			// Se a integração estiver ativa, exportar produtos
			exportarProdutosParaML();
		},
		error: function () {
			Materialize.toast('<i class="material-icons">error</i> Erro ao verificar status da integração com o Mercado Livre', 5000, 'red');
		}
	});
}

/**
 * Exporta produtos para Mercado Livre
 */
function exportarProdutosParaML() {
	let sucessos = 0;
	let falhas = 0;
	let totalProdutos = produtosSelecionados.length;
	let totalProcessados = 0;

	Materialize.toast(`<i class="material-icons">cloud_upload</i> Exportando ${totalProdutos} produto(s) para o Mercado Livre...`, 4000, 'blue');

	// Buscar dados completos dos produtos selecionados
	produtosSelecionados.forEach(function(codigoInterno) {
		// Buscar dados do produto no banco
		$.ajax({
			url: 'produtos_ajax.php',
			type: 'post',
			data: {
				request: 'buscarProduto',
				codigo_interno: codigoInterno
			},
			dataType: 'json',
			success: function(produtoData) {
				console.log('=== DEBUG PRODUTO PARA ML ===');
				console.log('Dados do produto:', produtoData);
				console.log('GTIN:', produtoData.codigo_gtin);
				console.log('Preço:', produtoData.preco_venda);
				console.log('Estoque:', produtoData.estoque);
				console.log('Descrição:', produtoData.descricao);

				if (produtoData && produtoData.codigo_gtin) {
					// Primeiro fazer preview da exportação
					$.ajax({
						url: 'mercadolivre/preview_export.php',
						type: 'post',
						data: {
							codigo_gtin: produtoData.codigo_gtin
						},
						dataType: 'json',
						success: function(response) {
							if (response.success) {
								console.log(`✅ Preview gerado para produto ${produtoData.codigo_gtin}`);
								console.log('Preview:', response);

								// Mostrar modal com preview
								mostrarModalExportML(response);

							} else {
								console.error(`❌ Erro no preview do produto ${produtoData.codigo_gtin}:`);
								console.error('Erro:', response.error);
								falhas++;
							}

							totalProcessados++;
							if (totalProcessados === totalProdutos) {
								if (falhas > 0) {
									Materialize.toast(`❌ ${falhas} produto(s) com erro no preview`, 4000, 'red');
								}
							}
						},
						error: function(xhr, status, error) {
							console.error(`❌ Erro AJAX ao exportar produto ${produtoData.codigo_gtin}:`);
							console.error('Status:', status);
							console.error('Error:', error);
							console.error('XHR Status:', xhr.status);
							console.error('Response Text:', xhr.responseText);

							// Tentar fazer parse do JSON de erro
							try {
								const errorData = JSON.parse(xhr.responseText);
								console.error('Erro parseado:', errorData);
							} catch (e) {
								console.error('Não foi possível fazer parse do erro');
							}

							falhas++;

							totalProcessados++;
							if (totalProcessados === totalProdutos) {
								finalizarExportacaoML(sucessos, falhas);
							}
						}
					});
				} else {
					console.error(`❌ Produto ${codigoInterno} não tem GTIN válido`);
					falhas++;

					totalProcessados++;
					if (totalProcessados === totalProdutos) {
						finalizarExportacaoML(sucessos, falhas);
					}
				}
			},
			error: function() {
				console.error(`❌ Erro ao buscar dados do produto ${codigoInterno}`);
				falhas++;

				totalProcessados++;
				if (totalProcessados === totalProdutos) {
					finalizarExportacaoML(sucessos, falhas);
				}
			}
		});
	});
}

/**
 * Finaliza exportação para ML
 */
function finalizarExportacaoML(sucessos, falhas) {
	const total = sucessos + falhas;

	if (sucessos > 0) {
		Materialize.toast(`<i class="material-icons">check_circle</i> ${sucessos}/${total} produtos exportados com sucesso para o Mercado Livre!`, 5000, 'green');
	}

	if (falhas > 0) {
		Materialize.toast(`<i class="material-icons">error</i> ${falhas}/${total} produtos falharam na exportação para o Mercado Livre`, 5000, 'red');
	}

	// Recarregar tabela para mostrar novos status
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

	// Limpar seleção
	produtosSelecionados = [];
	atualizarContadorSelecao();
}

/**
 * Atualiza contador de produtos selecionados
 */
function atualizarContadorSelecao() {
	const contador = produtosSelecionados.length;
	console.log(`Produtos selecionados: ${contador}`);

	// Atualizar interface se houver elemento contador
	const elementoContador = document.getElementById('contador_selecao');
	if (elementoContador) {
		elementoContador.textContent = contador;
	}
}

/**
 * Mostra modal com preview da exportação ML
 */
function mostrarModalExportML(previewData) {
	console.log('Mostrando modal de export ML:', previewData);

	// Preencher dados do produto
	$('#ml_produto_titulo').text(previewData.produto.titulo);
	$('#ml_produto_preco').text(previewData.produto.preco.toFixed(2));
	$('#ml_produto_estoque').text(previewData.produto.estoque);

	// Preencher categoria sugerida
	const prediction = previewData.prediction;
	$('#ml_categoria_nome').text(prediction.category_name);
	$('#ml_categoria_id').text(prediction.category_id);
	$('#ml_categoria_confianca').text(Math.round(prediction.confidence || 0));

	// Mostrar outras opções de categoria
	const outrasCategoriasHtml = previewData.all_predictions.slice(1).map(pred =>
		`<div class="ml-category-option" data-category-id="${pred.category_id}">
			<strong>${pred.category_name}</strong> (${pred.category_id})
			<br><small>Confiança: ${Math.round(pred.confidence || 0)}%</small>
		</div>`
	).join('');
	$('#ml_categorias_lista').html(outrasCategoriasHtml);

	// Mostrar requisitos e problemas
	const issues = previewData.issues || [];
	const problemasHtml = issues.map(issue =>
		`<div class="ml-issue-${issue.type}">
			<strong>${issue.field}:</strong> ${issue.message}
		</div>`
	).join('');
	$('#ml_problemas_lista').html(problemasHtml);

	// Mostrar campos obrigatórios editáveis
	mostrarCamposObrigatorios(previewData);

	// Mostrar configuração de preço se necessário
	mostrarConfiguracaoPreco(previewData);

	// Atualizar preview de atributos
	atualizarPreviewAtributos(previewData);

	// ✅ NOVO: Carregar campos específicos da categoria
	carregarCamposCategoria(previewData.prediction.category_id, previewData.produto);

	// Configurar botão de exportar (sempre permitir, validação será feita dinamicamente)
	const btnExport = $('#btn_confirmar_export_ml');
	btnExport.removeClass('disabled').addClass('green');
	btnExport.off('click').on('click', function() {
		confirmarExportacaoML(previewData);
	});

	// Abrir modal
	$('#modal_export_ml').modal('open');
}

/**
 * Carrega campos específicos da categoria
 */
function carregarCamposCategoria(categoryId, produto) {
	console.log('Carregando campos para categoria:', categoryId);

	$.ajax({
		url: 'mercadolivre/get_category_info.php',
		type: 'GET',
		data: { category_id: categoryId },
		dataType: 'json',
		success: function(response) {
			console.log('Informações da categoria:', response);

			// Atualizar mensagem
			$('#ml_category_message').html(`<small>${response.message}</small>`);

			// Limpar container
			const container = $('#ml_category_fields_container');
			container.empty();

			// Adicionar campos
			if (response.fields && response.fields.length > 0) {
				const row = $('<div class="row"></div>');

				response.fields.forEach(function(field, index) {
					const colSize = response.fields.length <= 2 ? 's6' : 's4';
					const fieldId = `ml_field_${field.id.toLowerCase()}`;

					let fieldHtml = '';

					if (field.type === 'list') {
						// Campo select
						fieldHtml = `
							<div class="input-field col ${colSize}">
								<select id="${fieldId}">
									<option value="">Selecione...</option>
								</select>
								<label for="${fieldId}">${field.name} ${field.required ? '*' : ''}</label>
								<span class="helper-text">${field.description}</span>
							</div>
						`;
					} else {
						// Campo input
						fieldHtml = `
							<div class="input-field col ${colSize}">
								<input id="${fieldId}" type="text" maxlength="255">
								<label for="${fieldId}">${field.name} ${field.required ? '*' : ''}</label>
								<span class="helper-text">Ex: ${field.examples.join(', ')}</span>
							</div>
						`;
					}

					row.append(fieldHtml);
				});

				container.append(row);

				// Inicializar campos do Materialize
				container.find('select').material_select();
				Materialize.updateTextFields();

				// Preencher valores sugeridos
				preencherValoresSugeridos(response.fields, produto);
			}
		},
		error: function(xhr, status, error) {
			console.error('Erro ao carregar campos da categoria:', error);
			$('#ml_category_message').html('<small style="color: red;">Erro ao carregar campos da categoria. Usando campos padrão.</small>');
		}
	});
}

/**
 * Preenche valores sugeridos nos campos
 */
function preencherValoresSugeridos(fields, produto) {
	fields.forEach(function(field) {
		const fieldId = `ml_field_${field.id.toLowerCase()}`;
		let suggestion = '';

		// Gerar sugestões baseadas no produto
		switch (field.id) {
			case 'MANUFACTURER':
			case 'BRAND':
				suggestion = ''; // Deixar vazio para usuário preencher
				break;
			case 'PRODUCT_NAME':
				suggestion = produto.titulo || '';
				break;
			case 'NET_WEIGHT':
				// Tentar extrair peso do título
				const weightMatch = produto.titulo.match(/(\d+)\s*(g|kg|ml|l)/i);
				if (weightMatch) {
					suggestion = weightMatch[1] + ' ' + weightMatch[2].toLowerCase();
				}
				break;
		}

		if (suggestion) {
			$(`#${fieldId}`).val(suggestion);
		}
	});

	// Atualizar labels
	Materialize.updateTextFields();
}

/**
 * Mostra campos obrigatórios editáveis
 */
function mostrarCamposObrigatorios(previewData) {
	const requiredAttributes = previewData.requirements.required_attributes || [];

	if (requiredAttributes.length === 0) {
		$('#ml_campos_obrigatorios').hide();
		return;
	}

	let camposHtml = '';

	requiredAttributes.forEach(attr => {
		const fieldId = `ml_campo_${attr.id}`;
		const savedValue = getSavedAttributeValue(previewData.produto.gtin, attr.id);

		if (attr.id === 'VEGETABLE_TYPE') {
			// Campo específico para tipo de vegetal
			camposHtml += `
				<div class="ml-campo-obrigatorio">
					<label for="${fieldId}">${attr.name} *</label>
					<select id="${fieldId}" class="browser-default">
						<option value="">Selecione...</option>
						<option value="Abobrinha" ${savedValue === 'Abobrinha' ? 'selected' : ''}>Abobrinha</option>
						<option value="Abóbora" ${savedValue === 'Abóbora' ? 'selected' : ''}>Abóbora</option>
						<option value="Alface" ${savedValue === 'Alface' ? 'selected' : ''}>Alface</option>
						<option value="Brócolis" ${savedValue === 'Brócolis' ? 'selected' : ''}>Brócolis</option>
						<option value="Cenoura" ${savedValue === 'Cenoura' ? 'selected' : ''}>Cenoura</option>
						<option value="Outros" ${savedValue === 'Outros' ? 'selected' : ''}>Outros</option>
					</select>
				</div>
			`;
		} else if (attr.id === 'VEGETABLE_VARIETY') {
			// Campo específico para variedade
			camposHtml += `
				<div class="ml-campo-obrigatorio">
					<label for="${fieldId}">${attr.name} *</label>
					<input type="text" id="${fieldId}" value="${savedValue}" placeholder="Ex: Italiana, Japonesa, Orgânica...">
				</div>
			`;
		} else {
			// Campo genérico
			camposHtml += `
				<div class="ml-campo-obrigatorio">
					<label for="${fieldId}">${attr.name} *</label>
					<input type="text" id="${fieldId}" value="${savedValue}" placeholder="Digite ${attr.name.toLowerCase()}">
				</div>
			`;
		}
	});

	$('#ml_campos_form').html(camposHtml);
	$('#ml_campos_obrigatorios').show();

	// Adicionar listeners para atualizar preview
	$('#ml_campos_form input, #ml_campos_form select').on('change keyup', function() {
		atualizarPreviewAtributos(previewData);
	});
}

/**
 * Renderiza campos obrigatórios vindos da API de exportação (need_attributes)
 */
function renderRequiredFieldsFromResponse(requiredFields, previewData) {
    let camposHtml = '';
    requiredFields.forEach(field => {
        const fieldId = `ml_campo_${field.id}`;
        const label = field.name || field.id;
        if (field.allowed_values && Array.isArray(field.allowed_values) && field.allowed_values.length > 0) {
            // Select com valores permitidos (usa name como option)
            const options = ['<option value="">Selecione...</option>']
                .concat(field.allowed_values.map(v => `<option value="${v.name}">${v.name}</option>`))
                .join('');
            camposHtml += `
                <div class="ml-campo-obrigatorio">
                    <label for="${fieldId}">${label} *</label>
                    <select id="${fieldId}" class="browser-default">${options}</select>
                </div>
            `;
        } else {
            // Input texto padrão
            const placeholder = field.description ? ` placeholder="${field.description}"` : '';
            camposHtml += `
                <div class="ml-campo-obrigatorio">
                    <label for="${fieldId}">${label} *</label>
                    <input type="text" id="${fieldId}"${placeholder}>
                </div>
            `;
        }
    });

    $('#ml_campos_form').html(camposHtml);
    $('#ml_campos_obrigatorios').show();

    // Listeners para atualizar preview
    $('#ml_campos_form input, #ml_campos_form select').on('change keyup', function() {
        atualizarPreviewAtributos(previewData);
    });
}

/**
 * Mostra configuração de preço
 */
function mostrarConfiguracaoPreco(previewData) {
	const precoAtual = previewData.produto.preco;
	const issues = previewData.issues || [];
	const precoIssue = issues.find(issue => issue.field === 'preco' && issue.type === 'error');

	if (!precoIssue) {
		$('#ml_config_preco').hide();
		return;
	}

	// Extrair preço mínimo da mensagem
	const match = precoIssue.message.match(/(\d+)/);
	const precoMinimo = match ? parseFloat(match[1]) : 0;

	$('#ml_preco_atual').text(precoAtual.toFixed(2));
	$('#ml_preco_requisito').html(`<strong>Mínimo exigido:</strong> R$ ${precoMinimo.toFixed(2)}`);
	$('#ml_preco_helper').text(`Categoria exige preço mínimo de R$ ${precoMinimo.toFixed(2)}`);

	// Definir valor sugerido
	const precoSugerido = Math.max(precoAtual, precoMinimo);
	$('#ml_preco_ajustado').val(precoSugerido.toFixed(2));

	// Validação em tempo real
	$('#ml_preco_ajustado').on('input', function() {
		const valor = parseFloat($(this).val()) || 0;
		if (valor >= precoMinimo) {
			$(this).removeClass('ml-preco-invalido').addClass('ml-preco-valido');
			$('#ml_preco_helper').text('✅ Preço válido').css('color', 'green');
		} else {
			$(this).removeClass('ml-preco-valido').addClass('ml-preco-invalido');
			$('#ml_preco_helper').text(`❌ Mínimo: R$ ${precoMinimo.toFixed(2)}`).css('color', 'red');
		}
		atualizarPreviewAtributos(previewData);
	});

	$('#ml_config_preco').show();
}

/**
 * Atualiza preview dos atributos
 */
function atualizarPreviewAtributos(previewData) {
	const prediction = previewData.prediction;
	let atributos = [...(prediction.attributes || [])];

	// Adicionar campos preenchidos pelo usuário
	const requiredAttributes = previewData.requirements.required_attributes || [];
	requiredAttributes.forEach(attr => {
		const fieldId = `ml_campo_${attr.id}`;
		const valor = $(`#${fieldId}`).val();
		if (valor) {
			// Remover atributo existente se houver
			atributos = atributos.filter(a => a.id !== attr.id);
			// Adicionar novo valor
			atributos.push({
				id: attr.id,
				value_name: valor
			});
		}
	});

	// Mostrar preview
	const atributosHtml = atributos.map(attr =>
		`<span class="ml-attribute">${attr.id}: ${attr.value_name}</span>`
	).join('');
	$('#ml_atributos_preview').html(atributosHtml);

	// Verificar se pode exportar
	verificarPodeExportar(previewData);
}

/**
 * Verifica se pode exportar com os dados atuais
 */
function verificarPodeExportar(previewData) {
	let podeExportar = true;

	// Verificar preço
	const precoAjustado = parseFloat($('#ml_preco_ajustado').val()) || previewData.produto.preco;
	const precoIssue = (previewData.issues || []).find(issue => issue.field === 'preco' && issue.type === 'error');
	if (precoIssue) {
		const match = precoIssue.message.match(/(\d+)/);
		const precoMinimo = match ? parseFloat(match[1]) : 0;
		if (precoAjustado < precoMinimo) {
			podeExportar = false;
		}
	}

	// Verificar campos obrigatórios
	const requiredAttributes = previewData.requirements.required_attributes || [];
	requiredAttributes.forEach(attr => {
		const fieldId = `ml_campo_${attr.id}`;
		const valor = $(`#${fieldId}`).val();
		if (!valor || valor.trim() === '') {
			podeExportar = false;
		}
	});

	// Atualizar botão
	const btnExport = $('#btn_confirmar_export_ml');
	if (podeExportar) {
		btnExport.removeClass('disabled').addClass('green');
	} else {
		btnExport.addClass('disabled').removeClass('green');
	}
}

/**
 * Busca valor salvo de atributo
 */
function getSavedAttributeValue(gtin, attributeId) {
	// Por enquanto retorna vazio, depois implementar busca no banco
	return '';
}

/**
 * Confirma e executa a exportação
 */
function confirmarExportacaoML(previewData) {
	// DEBUG: Log do previewData
	console.log('=== DEBUG confirmarExportacaoML ===');
	console.log('previewData completo:', previewData);
	console.log('previewData.produto:', previewData.produto);
	console.log('previewData.produto.gtin:', previewData.produto.gtin);

	// Coletar dados do formulário
	const dadosExportacao = {
		codigo_gtin: previewData.produto.gtin,
		action: 'export',
		preco_ajustado: $('#ml_preco_ajustado').val(),
		atributos_customizados: {}
	};

	// ✅ NOVO: Coletar campos específicos da categoria
	$('#ml_category_fields_container input, #ml_category_fields_container select').each(function() {
		const fieldId = $(this).attr('id');
		const fieldValue = $(this).val().trim();

		if (fieldId && fieldValue) {
			// Manter ID original para o backend processar corretamente
			dadosExportacao[fieldId] = fieldValue;
		}
	});

	console.log('dadosExportacao:', dadosExportacao);

	// Coletar atributos preenchidos
	const requiredAttributes = previewData.requirements.required_attributes || [];
	requiredAttributes.forEach(attr => {
		const fieldId = `ml_campo_${attr.id}`;
		const valor = $(`#${fieldId}`).val();
		if (valor) {
			dadosExportacao.atributos_customizados[attr.id] = valor;
		}
	});

	console.log('Dados para exportação:', dadosExportacao);

	$('#modal_export_ml').modal('close');

	// Executar exportação real
	console.log('Enviando AJAX para:', 'mercadolivre/export_product.php');
	console.log('Dados sendo enviados:', dadosExportacao);

	$.ajax({
		url: 'mercadolivre/export_product.php',
		type: 'POST',
		data: dadosExportacao,
		dataType: 'json',
		contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
		success: function(response) {
			if (response.success) {
				Materialize.toast(`✅ Produto exportado com sucesso para ML!`, 4000, 'green');
				console.log('Exportação bem-sucedida:', response);
			} else {
				// Se faltar atributos obrigatórios, reabrir modal e renderizar campos
				if (response.need_attributes) {
					Materialize.toast(response.message || 'Preencha os atributos obrigatórios.', 5000, 'orange');
					try {
						$('#modal_export_ml').modal('open');
						if (Array.isArray(response.required_fields) && response.required_fields.length > 0) {
							renderRequiredFieldsFromResponse(response.required_fields, previewData);
							// Atualizar preview com possíveis valores
							atualizarPreviewAtributos(previewData);
						}
					} catch (e) {
						console.error('Erro ao reabrir modal/renderizar campos obrigatórios:', e);
					}
					return;
				}

				// NOVO: Mostrar erros inteligentes
				mostrarErrosInteligentes(response);
				console.error('Erro na exportação:', response);
			}
		},
		error: function(xhr, status, error) {
			Materialize.toast(`❌ Erro na exportação: ${error}`, 6000, 'red');
			console.error('Erro AJAX na exportação:', xhr.responseText);
		}
	});
}

/**
 * Mostra erros inteligentes com soluções
 */
function mostrarErrosInteligentes(response) {
	const mappedErrors = response.mapped_errors || [];

	if (mappedErrors.length === 0) {
		Materialize.toast(`❌ Erro na exportação: ${response.error}`, 6000, 'red');
		return;
	}

	// Log detalhado para debug
	console.group('🧠 Análise Inteligente de Erros ML');
	mappedErrors.forEach(error => {
		console.log(`${error.title} (${error.source}):`, error);
	});
	console.groupEnd();

	// SEMPRE mostrar modal detalhado para transparência total
	mostrarModalErrosDetalhados(mappedErrors, response);

	// Toast resumido para feedback rápido
	const errorCount = mappedErrors.filter(e => e.type === 'error').length;
	const warningCount = mappedErrors.filter(e => e.type === 'warning').length;

	let toastMsg = '';
	if (errorCount > 0) {
		toastMsg = `❌ ${errorCount} erro(s) encontrado(s)`;
	}
	if (warningCount > 0) {
		toastMsg += (toastMsg ? ' e ' : '⚠️ ') + `${warningCount} aviso(s)`;
	}
	toastMsg += ' - Veja detalhes no modal';

	Materialize.toast(toastMsg, 4000, errorCount > 0 ? 'red' : 'orange');
}

/**
 * Modal com detalhes completos dos erros
 */
function mostrarModalErrosDetalhados(mappedErrors, fullResponse) {
	// Calcular estatísticas
	const errorCount = mappedErrors.filter(e => e.type === 'error').length;
	const warningCount = mappedErrors.filter(e => e.type === 'warning').length;
	const fixableCount = mappedErrors.filter(e => e.auto_fixable).length;

	// Atualizar contadores
	$('#ml_erros_count').text(errorCount);
	$('#ml_warnings_count').text(warningCount);
	$('#ml_fixable_count').text(fixableCount);

	// Gerar HTML dos problemas
	let problemasHtml = '';

	mappedErrors.forEach((error, index) => {
		const icon = getErrorIcon(error.type);
		const cardClass = `ml-problema-card ml-problema-${error.type}`;
		const priorityClass = `ml-priority-${error.priority}`;

		problemasHtml += `
			<div class="${cardClass}">
				<div class="card-content">
					<div class="ml-problema-header">
						<span class="ml-problema-icon">${icon}</span>
						<h6 class="ml-problema-title">${error.title}</h6>
						<span class="ml-problema-source">${getSourceLabel(error.source)}</span>
						<span class="ml-problema-source ${priorityClass}">${error.priority}</span>
					</div>

					<div class="ml-problema-description">
						${error.description}
					</div>

					<div class="ml-problema-solution">
						<strong>💡 Solução:</strong> ${error.solution}
					</div>

					${error.auto_fixable ? '<div class="ml-auto-fixable">✅ Auto-corrigível</div>' : ''}

					<div class="ml-problema-original">
						<strong>Erro original:</strong><br>
						<code>${error.original.code}</code><br>
						<small>${error.original.message}</small>
					</div>
				</div>
			</div>
		`;
	});

	$('#ml_problemas_detalhados').html(problemasHtml);

	// Informações de debug
	const debugHtml = `
		<div class="row">
			<div class="col s6">
				<h6>Resposta do Mercado Livre:</h6>
				<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px; max-height: 200px; overflow-y: auto;">${JSON.stringify(fullResponse.ml_response, null, 2)}</pre>
			</div>
			<div class="col s6">
				<h6>Dados Enviados:</h6>
				<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px; max-height: 200px; overflow-y: auto;">${JSON.stringify(fullResponse.debug_info.sent_data, null, 2)}</pre>
			</div>
		</div>
		<div class="row">
			<div class="col s12">
				<h6>Análise Completa:</h6>
				<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px; max-height: 150px; overflow-y: auto;">${JSON.stringify(mappedErrors, null, 2)}</pre>
			</div>
		</div>
	`;

	$('#ml_debug_info').html(debugHtml);

	// Configurar botão "Tentar Novamente"
	$('#btn_tentar_novamente_ml').off('click').on('click', function() {
		$('#modal_erros_ml_detalhado').modal('close');
		// Reabrir modal de exportação para correções
		$('#modal_export_ml').modal('open');
	});

	// Inicializar collapsible
	$('.collapsible').collapsible();

	// Abrir modal
	$('#modal_erros_ml_detalhado').modal('open');
}

/**
 * Retorna ícone baseado no tipo de erro
 */
function getErrorIcon(type) {
	switch(type) {
		case 'error': return '❌';
		case 'warning': return '⚠️';
		case 'info': return 'ℹ️';
		default: return '❓';
	}
}

/**
 * Retorna label da fonte do mapeamento
 */
function getSourceLabel(source) {
	switch(source) {
		case 'manual_mapping': return 'Mapeado';
		case 'auto_analysis': return 'Auto-análise';
		case 'fallback': return 'Genérico';
		default: return source;
	}
}

/**
 * Gera ícone de e-commerce baseado no status do produto (função mantida para compatibilidade)
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

		case 'E':
			// Status antigo - Nuvem cinza (compatibilidade)
			iconeHtml = "<i class='material-icons ecommerce-icon nuvemshop-legacy' " +
				"title='Nuvemshop - Status Antigo' " +
				"data-tooltip='Produto na Nuvemshop (status antigo)'>cloud</i> ";
			break;

		default:
			// Status desconhecido
			return "";
	}

	return iconeHtml;
}

// Flag para indicar que produtos.js foi carregado
window.produtosLoaded = true;
