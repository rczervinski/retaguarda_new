<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate, Post-Check=0, Pre-Check=0">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache"> <META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Produtos</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<link rel="stylesheet" href="css/produtos_unified.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/form_buttons.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/ecommerce-icons.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/produtos-table.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/filtros-avancados.css?v=<?php echo time(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<?php include ("conexao.php");?>
<?php include ("produtos_class.php");?>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/js/materialize.min.js"></script>
<!-- Nova implementação para gerenciamento de variantes, categorias e imagens -->
<script type="text/javascript">
// Carregar scripts apenas se não foram carregados ainda
(function() {
    // Verificar se já estamos carregando scripts para evitar duplicação
    if (window.produtosScriptsLoading) {
        return;
    }
    window.produtosScriptsLoading = true;

    const scriptsToLoad = [
        // ✅ CORREÇÃO: Carregar dependências primeiro
        { src: 'nuvemshop/js/variant-manager.js?v=<?php echo time(); ?>', check: 'VariantManager' },
        { src: 'nuvemshop/js/category-manager.js?v=<?php echo time(); ?>', check: 'CategoryManager' },
        { src: 'nuvemshop/js/image-manager.js?v=<?php echo time(); ?>', check: 'ImageManager' },
        // ✅ ProductUpdater depois das dependências
        { src: 'nuvemshop/js/product-updater.js?v=<?php echo time(); ?>', check: 'ProductUpdater' },
        { src: 'js/product-image-crud.js?v=<?php echo time(); ?>', check: 'ProductImageCRUD' },
        { src: 'js/image-crud-interface.js?v=<?php echo time(); ?>', check: 'window.initializeImageCRUD' },
        { src: 'nuvemshop/js/nuvemshop-integration.js?v=<?php echo time(); ?>', check: 'window.nuvemshopIntegration' },
        { src: 'nuvemshop/js/teste-nuvemshop.js?v=<?php echo time(); ?>', check: 'window.testeNuvemshop' },
        { src: 'js/produtos.js?v=<?php echo time(); ?>&debug=1', check: 'window.produtosLoaded' },
        { src: 'js/filtros-avancados.js?v=<?php echo time(); ?>', check: 'window.aplicarFiltros' },
        { src: 'nuvemshop/js/sincronizacao_nuvemshop.js', check: 'sincronizarStatusProdutosNuvemshop' }
    ];

    let scriptsLoaded = 0;
    const totalScripts = scriptsToLoad.length;

    scriptsToLoad.forEach(function(script) {
        // Verificar se o script já foi carregado
        let isLoaded = false;

        try {
            if (script.check.includes('window.')) {
                isLoaded = eval(script.check);
            } else {
                isLoaded = typeof window[script.check] !== 'undefined';
            }
        } catch (e) {
            isLoaded = false;
        }

        if (!isLoaded) {
            const scriptElement = document.createElement('script');
            scriptElement.type = 'text/javascript';
            scriptElement.src = script.src;

            scriptElement.onload = function() {
                scriptsLoaded++;
                if (scriptsLoaded === totalScripts) {
                    window.produtosScriptsLoading = false;
                    console.log('Todos os scripts de produtos foram carregados');
                }
            };

            scriptElement.onerror = function() {
                console.warn('Erro ao carregar script:', script.src);
                scriptsLoaded++;
                if (scriptsLoaded === totalScripts) {
                    window.produtosScriptsLoading = false;
                }
            };

            document.head.appendChild(scriptElement);
        } else {
            scriptsLoaded++;
            if (scriptsLoaded === totalScripts) {
                window.produtosScriptsLoading = false;
            }
        }
    });
})();
</script>
<script type="text/javascript">
    // Script para melhorar a usabilidade
    $(document).ready(function() {

        // Fazer a tecla Enter funcionar na pesquisa
        $('#desc_pesquisa').keypress(function(e) {
            // Verificar se a tecla pressionada é Enter (código 13)
            if (e.which === 13) {
                e.preventDefault(); // Impedir o comportamento padrão do Enter
                $('#but_fetchall').click(); // Simular o clique no botão de pesquisa
            }
        });

        // Focar no campo de pesquisa ao carregar a página
        setTimeout(function() {
            $('#desc_pesquisa').focus();
        }, 500);

        // Adicionar efeito de hover nos botões
        $('.btn-floating').hover(
            function() {
                $(this).css('transform', 'scale(1.1)');
            },
            function() {
                $(this).css('transform', 'scale(1.0)');
            }
        );

        // Ajustar o botão de pesquisa em telas muito pequenas
        function adjustSearchButton() {
            if (window.innerWidth < 601) {
                // Em telas muito pequenas, mostrar apenas o ícone
                $('.search-button span').hide();
                $('.search-button').css({
                    'padding': '0',
                    'min-width': '36px',
                    'width': '36px'
                });
                $('.search-button i').css({
                    'margin': '0',
                    'width': '100%'
                });
            } else {
                // Em telas maiores, mostrar o texto "Buscar"
                $('.search-button span').show();
                $('.search-button').css({
                    'padding': '0 16px',
                    'min-width': '90px',
                    'width': 'auto'
                });
                $('.search-button i').css({
                    'margin-right': '8px'
                });
            }
        }

        // Executar o ajuste ao carregar a página
        adjustSearchButton();

        // Executar o ajuste quando a janela for redimensionada
        $(window).resize(function() {
            adjustSearchButton();
        });


    });

    // Função para testar o sistema de categorias
    function testarSistemaCategorias() {
        console.log('🧪 Testando Sistema de Categorias');

        // Verificar se CategoryManager está disponível
        if (typeof CategoryManager === 'undefined') {
            console.error('❌ CategoryManager não está carregado');
            alert('Erro: CategoryManager não está carregado');
            return;
        }

        // Criar instância do CategoryManager
        const categoryManager = new CategoryManager('nuvemshop/nuvemshop_proxy.php');

        // Testar normalização de strings
        console.log('🔤 Testando normalização de strings:');
        console.log('Original: "Roupas & Acessórios" → Normalizado:', categoryManager.normalizeString('Roupas & Acessórios'));
        console.log('Original: "PRINCIPAL" → Normalizado:', categoryManager.normalizeString('PRINCIPAL'));
        console.log('Original: "Camisetas" → Normalizado:', categoryManager.normalizeString('Camisetas'));

        // Testar cenários especiais
        console.log('🧪 Testando cenários especiais:');

        // Teste 1: Sem categoria
        console.log('Teste 1: Produto sem categoria');
        categoryManager.processProductCategories({ categoria: 'SEM_CATEGORIA', grupo: 'EQUIPAMENTOS' })
            .then(result => console.log('Resultado:', result))
            .catch(error => console.error('Erro:', error));

        // Teste 2: Categoria com sem grupo
        console.log('Teste 2: Categoria com sem grupo');
        categoryManager.processProductCategories({ categoria: 'PRINCIPAL', grupo: 'SEM_GRUPO' })
            .then(result => console.log('Resultado:', result))
            .catch(error => console.error('Erro:', error));

        // Teste 3: Categoria normal com grupo
        console.log('Teste 3: Categoria normal com grupo');
        categoryManager.processProductCategories({ categoria: 'PRINCIPAL', grupo: 'EQUIPAMENTOS' })
            .then(result => console.log('Resultado:', result))
            .catch(error => console.error('Erro:', error));

        // Testar busca de categorias do produto
        console.log('🔍 Testando busca de categorias do produto 74730:');

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                request: 'buscar_categorias_produto',
                codigo_interno: '74730'
            },
            success: function(response) {
                console.log('✅ Categorias encontradas:', response);

                // Testar processamento de categorias
                if (response && (response.categoria || response.grupo)) {
                    console.log('🔄 Testando processamento de categorias...');

                    categoryManager.processProductCategories(response)
                        .then(categoryIds => {
                            console.log('✅ Categorias processadas com sucesso:', categoryIds);
                            alert('✅ Teste concluído! Verifique o console para detalhes.');
                        })
                        .catch(error => {
                            console.error('❌ Erro ao processar categorias:', error);
                            alert('❌ Erro ao processar categorias: ' + error.message);
                        });
                } else {
                    console.log('⚠️ Produto não tem categorias definidas');
                    alert('⚠️ Produto 74730 não tem categorias definidas');
                }
            },
            error: function(xhr) {
                console.error('❌ Erro ao buscar categorias:', xhr);
                alert('❌ Erro ao buscar categorias. Verifique o console.');
            }
        });
    }
</script>
<body>
	<div class="container" id="produto_principal">
		<br>
		<div class="row" style="margin-top: 20px; margin-bottom: 20px;">
			<div class="col s12 m10 offset-m1 l8 offset-l2">
				<div class="card" style="box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px; margin-top: 0;">
					<div class="card-content" style="padding: 16px;">
						<div style="display: flex; align-items: center; margin: 0;">
							<div style="flex: 1; margin-right: 10px;">
								<div class="input-field" style="margin-top: 0; margin-bottom: 0;">
									<input type="text" id="desc_pesquisa" placeholder="Pesquisar produtos..." style="margin-bottom: 0; height: 3rem; border: none;" />
									<label for="desc_pesquisa" class="active" style="transform: translateY(-14px);"></label>
								</div>
							</div>
							<div style="width: auto; flex-shrink: 0; display: flex; gap: 5px;">
								<button class="btn waves-effect waves-light yellow darken-2 search-button"
									id="but_fetchall" title="Pesquisar" style="margin: 0; height: 36px; line-height: 36px; border-radius: 4px; min-width: 36px;">
									<i class="material-icons" style="margin-right: 0;">search</i>
									<span class="hide-on-small-and-down"></span>
								</button>
								<button class="btn waves-effect waves-light blue darken-1 filter-button modal-trigger"
									href="#modalFiltros" title="Filtros Avançados" style="margin: 0; height: 36px; line-height: 36px; border-radius: 4px; min-width: 36px;">
									<i class="material-icons" style="margin-right: 0;">filter_list</i>
									<span class="hide-on-small-and-down"></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row center-align" style="margin-bottom: 20px;">
			<div class="col s12">
				<a class="btn-floating btn-medium waves-effect green"
					onClick="cadastro_produto(0)" id="but_add" title="Adicionar produto" style="margin: 0 8px;">
					<i class="material-icons">add</i>
				</a>
				<a class="btn-floating btn-medium waves-effect blue"
					onClick="sincronizarStatusProdutosNuvemshop()" id="but_sync" title="Sincronizar Status e Estoque com E-commerce" style="margin: 0 8px;">
					<i class="material-icons">sync</i>
				</a>
				<a class="btn-floating btn-medium waves-effect orange"
					onClick="mostrarModalSelecionarProdutoParaDimensoes()" id="but_dimensions" title="Atualizar Dimensões de Variantes na Nuvemshop" style="margin: 0 8px;">
					<i class="material-icons">straighten</i>
				</a>
				<a class="btn-floating btn-medium waves-effect yellow darken-2"
					onClick="exportarProdutosSelecionadosML()" id="but_export_ml" title="Exportar Selecionados para Mercado Livre" style="margin: 0 8px;">
					<i class="material-icons">shopping_bag</i>
				</a>

			</div>
		</div>
		<!-- Indicador de carregamento -->
		<div id="loading" class="center-align">
			<div class="preloader-wrapper small active">
				<div class="spinner-layer spinner-blue-only">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>
			</div>
			<span style="margin-left: 10px;">Carregando...</span>
		</div>

		<!-- Paginação na parte superior -->
		<div class="row">
			<div class="col s12">
				<ul class="pagination center-align" id="paginacao_superior"></ul>
			</div>
		</div>

		<!-- Nova Tabela Moderna com Coluna Plataformas -->
		<div class="card">
			<div class="card-content" style="padding: 0;">
				<!-- Cabeçalho da Tabela -->
				<div class="produtos-table-header">
					<div class="produtos-header-row">
						<div class="produtos-col produtos-col-checkbox"><span>E-commerce</span></div>
						<div class="produtos-col produtos-col-codigo"><span>Código</span></div>
						<div class="produtos-col produtos-col-descricao"><span>Descrição</span></div>
						<div class="produtos-col produtos-col-origem"><span>Plataformas</span></div>
						<div class="produtos-col produtos-col-editar"><span>Editar</span></div>
					</div>
				</div>

				<!-- Container da Tabela com Scroll -->
				<div class="produtos-table-container" id="produtosTableContainer">
					<div class="produtos-table-body" id="produtosTableBody">
						<!-- Linhas serão inseridas aqui via JavaScript -->
					</div>
				</div>
			</div>
		</div>

		<!-- Paginação na parte inferior -->
		<div class="row">
			<div class="col s12">
				<ul class="pagination center-align" id="paginacao_inferior"></ul>
			</div>
		</div>
	</div>
	<div class="container" id="produto_cadastro">
		<form action="#">
			<ul class="collapsible">
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-2 white-text">
						<i class="material-icons">Abc</i>Informacoes Básicas
					</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s12 l4">
								<input type="hidden" id="codigo_interno" value="0" /> <i
									class="material-icons prefix">key</i> <input type="text"
									class="input-field" placeholder=""
									onfocusout="verificarCodigo();" id="codigo_gtin" /><label
									class="active" for="codigo_gtin">Código</label>
							</div>
							<div class="input-field col s12 l8">
								<input type="text" class="input-field" placeholder=""
									id="descricao" /><label class="active" for="descricao">Descrição</label>
							</div>
							<div class="input-field col s12 l12">
								<input type="text" class="input-field" placeholder=""
									id="descricao_detalhada" /><label class="active"
									for="descricao_detalhada">Descrição detalhada</label>
							</div>
						</div>
						<div class="row">
							<div class="col s10 l3">
								<label class="active">Grupo</label> <select id="grupo"
									class="browser-default">
								</select>
							</div>
							<div class="col s2 l1">
								<a href="#modalGrupo"
									class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="grupo_add"><i class="material-icons">add</i></a>
							</div>
							<div class="col s10 l3">
								<label class="active">Sub Grupo</label> <select
									class="browser-default" id="subgrupo">
								</select>
							</div>
							<div class="col s2 l1">
								<a href="#modalSubGrupo"
									class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="subgrupo_add"><i class="material-icons">add</i></a>
							</div>
							<div class="col s10 l3">
								<label class="active">Categoria</label> <select
									class="browser-default" id="categoria">
								</select>
							</div>
							<div class="col s2 l1">
								<a href="#modalCategoria"
									class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="categoria_add"><i class="material-icons">add</i></a>
							</div>
						</div>
						<div class="row">
							<div class="col s10 l2">
								<label class="active">Unidade</label><select
									class="browser-default" id="unidade">
								</select>
							</div>
							<div class="col s2 l1">
								<a href="#modalUnidade"
									class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="unidade_add"><i class="material-icons">add</i></a>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder=""
									id="preco_venda" value="0" /><label class="active"
									for="preco_venda">$ Venda</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder=""
									id="preco_compra" value="0" /><label class="active"
									for="preco_compra">$ Compra</label>
							</div>
							<div class="input-field col s10 l2">
								<input type="text" class="input-field" placeholder=""
									id="perc_lucro" value="0" /><label class="active"
									for="perc_lucro">% Lucro</label>
							</div>
							<div class="col s2 l1">
								<a
									class="btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"><i
									class="material-icons">calculate</i></a>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s4 l3">
								<input type="text" class="input-field" placeholder="" id="ncm" /><label
									class="active" for="ncm">NCM</label>
							</div>
							<div class="input-field col s4 l3">
								<input type="text" class="input-field" placeholder="" id="cest" /><label
									class="active" for="cest">CEST</label>
							</div>
							<div class="input-field col s4 l3">
								<input type="text" class="input-field" placeholder="" id="cfop"
									value="5102" /><label class="active" for="cfop">CFOP</label>
							</div>
							<div class="col s12 l3">
								<label class="active">Situação Tributária</label> <select
									class="browser-default" id="situacao_tributaria">
									<option value="" disabled selected>Selecione</option>
									<option value="101">101 - Tributação com permissão de
										crédito</option>
									<option value="102" selected>102 - Tributação sem permissão
										de crédito</option>
									<option value="103">103 - Isenção do ICMS para faixa de
										receita bruta</option>
									<option value="201">201 - Tributada COM permissão de crédito
										e com cobrança do ICMS POR ST</option>
									<option value="202">202 - Tributada SEM permissão de crédito
										e com cobrança do ICMS POR ST</option>
									<option value="203">203 - Isenção do ICMS para faixa de
										receita bruta e com cobrança do ICMS POR ST</option>
									<option value="300">300 - Imune</option>
									<option value="500">500 - ICMS cobrado anteriormente por ST ou
										por antecipação</option>
									<option value="900">900 - Outros</option>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_icms"
									placeholder="" value="0" /><label class="active"
									for="perc_icms">% ICMS</label>
							</div>
							<div class="col s10 l2">
								<input type="checkbox" valign="botton" id="produto_balanca" /><label
									class="active" for="produto_balanca">Prod.Balanca</label>
							</div>
							<div class="input-field col s2 l2">
								<input type="text" class="input-field" placeholder=""
									id="vadidade" value="0" /><label class="active" for="validade">Validade</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder="" disabled
									id="data_cadastro" /><label class="active" for="data_cadastro">Dt
									Cadastro</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" disabled placeholder="" class="input-field"
									id="data_alteracao" /><label class="active"
									for="data_alteracao">Dt Alteração</label>
							</div>
							<div class="col s10 l7">
								<label class="active">Fornecedor</label> <select
									class="browser-default" id="fornecedor"
									onChange="selecionouFornecedor()">
								</select>
							</div>
							<div class="col s2 l1">
								<a href="#modalFornecedor"
									class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="fornecedor_add"><i class="material-icons">add</i></a>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="vender_ecomerce" /><label
									class="active" for="vender_ecomerce">Vender no E-comerce</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="produto_producao" /><label
									class="active" for="produto_producao">Produto de producao</label>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-3 white-text"">
						<i class="material-icons">hive</i>Composição
					</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s4">
								<i class="material-icons prefix">key</i> <input type="text"
									class="input-field" id="codigo_gtin" /><label class="active"
									for="codigo_gtin">Codigo</label>
							</div>
							<div class="input-field col s6">
								<input type="text" class="input-field" id="descricao" /><label
									class="active" for="descricao">Descricao</label>
							</div>
							<div class="input-field col s2">
								<input type="text" class="input-field" id="qtde_composicao" /><label
									class="active" for="qtde_composicao">Qtde</label>
							</div>
							<div class="row">
								<table class="responsive-table striped" id='userTableComposicao'>
									<thead>
										<tr>
											<th>Código</th>
											<th>Descrição</th>
											<th>Qtde</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-4 white-text"">
						<i class="material-icons">square_foot</i>Outros
					</div>
					<div class="collapsible-body">
						<h6>Classificação Cliente</h6>
						<div class="row">
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_desc_a"
									placeholder="" value="0" /><label class="active"
									for="perc_desc_a">A %</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="val_desc_a"
									placeholder="" value="0" /><label class="active"
									for="val_desc_a">R$ A</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_desc_b"
									placeholder="" value="0" /><label class="active"
									for="perc_desc_b">B %</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="val_desc_b"
									placeholder="" value="0" /><label class="active"
									for="val_desc_b">R$ B</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_desc_c"
									placeholder="" value="0" /><label class="active"
									for="perc_desc_c">C %</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="val_desc_c"
									placeholder="" value="0" /><label class="active"
									for="val_desc_c">R$ C</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_desc_d"
									placeholder="" value="0" /><label class="active"
									for="perc_desc_d">D %</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="val_desc_d"
									placeholder="" value="0" /><label class="active"
									for="val_desc_d">R$ D</label>
							</div>
							<div class="input-field col s4 l2">
								<input type="text" class="input-field" id="perc_desc_e"
									placeholder="" value="0" /><label class="active"
									for="perc_desc_e">E %</label>
							</div>
							<div class="input-field col s10 l2">
								<input type="text" class="input-field" id="val_desc_e"
									placeholder="" value="0" /><label class="active"
									for="val_desc_e">R$ E</label>
							</div>
							<div class="col s2 l4">
								<a href=""
									class="btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="reclassificar_percentual"><i class="material-icons">calculate</i></a>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" class="input-field"
									id="aliquota_calculo_credito" value="0" placeholder="" /><label
									class="active" for="aliquota_calculo_credito">% Calculo do
									Credito</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="perc_dif" value="0"
									placeholder="" /><label class="active" for="perc_dif">%
									Diferimento</label>
							</div>
							<div class="col s6 l3">
								<label class="active">Modalidade BC ICMS</label> <select
									class="browser-default" id="mod_deter_bc_icms">
									<option value="" disabled selected>Selecione</option>
									<option value="Margem valor agregado" selected>Margem valor
										agregado</option>
									<option value="Pauta">Pauta</option>
									<option value="Preco Tabela Max.">Preco Tabela Max.</option>
									<option value="Valor operacao">Valor operacao</option>
								</select>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="perc_redu_icms"
									value="0" placeholder="" /><label class="active"
									for="perc_redu_icms">% Redu.BC.ICMS</label>
							</div>
						</div>
						<div class="row">
							<div class="col s6 l3">
								<label class="active">Modalidade BC ICMS ST</label> <select
									class="browser-default" id="mod_deter_bc_icms_st">
									<option value="" disabled selected>Selecione</option>
									<option value="Preco tabelado ou max sugerido">Preco tabelado
										ou max sugerido</option>
									<option value="Lista negativa">Lista negativa</option>
									<option value="Lista positiva">Lista positiva</option>
									<option value="Lista neutra">Lista neutra</option>
									<option value="Margem valor agregado" selected>Margem valor
										agregado</option>
									<option value="Pauta">Pauta</option>
								</select>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="tamanho" value="0"
									placeholder="" /><label class="active" for="tamanho" >Tamanho</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="date" class="datepicker activ" id="vencimento"
									placeholder="" /> <label class="active" for="vencimento">Vencimento</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliq_fcp_st"
									value="0" placeholder="" /><label class="active"
									for="aliq_fcp_st">%FCP ST</label>
							</div>
						</div>
						<div class="row">
							<div class="col s6 l3">
								<input type="checkbox" valign="botton"
									id="descricao_personalizada" /><label class="active"
									for="descricao_personalizada">Desscricao Personalizada</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="valorGelado"
									value="0" placeholder="" /><label class="active"
									for="valorGelado">Gelado</label>
							</div>
							<div class="input-field col s12 l6">
								<input type="text" class="input-field" id="prod_desc_etiqueta"
									value="0" placeholder="" /><label class="active"
									for="prod_desc_etiqueta">Descricao Etiqueta</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s10 l3">
								<input type="text" valign="input-field" id="novoCodigo"
									value="0" placeholder="" /><label class="active"
									for="novoCodigo">Novo Codigo</label>
							</div>
							<div class="col s2 l3">
								<a href=""
									class="btn-floating btn-small waves-effect waves-light #7986cb indigo lighten-2"
									id="alterarCodigo"><i class="material-icons">sync_alt</i></a>
							</div>
							<div class="col s6 l3">
								<input type="checkbox" valign="botton" id="inativo" /><label
									class="active" for="inativo">Produto Inativo</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" valign="input-field" id="aliq_fcp" value="0"
									placeholder="" /><label class="active" for="aliq_fcp">%FCP</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6">
								<input type="text" valign="input-field" id="qtde" value="0"
									placeholder="" /><label class="active" for="qtde">QTDE</label>
							</div>
							<div class="input-field col s6">
								<input type="text" valign="input-field" id="qtde_min" value="0"
									placeholder="" /><label class="active" for="qtde_min">QTDE
									Minima</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="comprimento" value="0" placeholder="" /><label class="active" for="comprimento" >Comprimento</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="largura" value="0" placeholder="" /><label class="active" for="largura" >Largura</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="altura" value="0" placeholder="" /><label class="active" for="altura" >Altura</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="peso" value="0" placeholder="" /><label class="active" for="peso" >Peso</label>
							</div>
						</div>
						<div class="row">
							<div>
								<ul class="tabs">
									<li class="tab"><a href="#PR" class="active black-text">PR</a></li>
									<li class="tab"><a href="#AC">AC</a></li>
									<li class="tab"><a href="#AL">AL</a></li>
									<li class="tab"><a href="#AP">AP</a></li>
									<li class="tab"><a href="#AM">AM</a></li>
									<li class="tab"><a href="#BA">BA</a></li>
									<li class="tab"><a href="#CE">CE</a></li>
									<li class="tab"><a href="#DF">DF</a></li>
									<li class="tab"><a href="#ES">ES</a></li>
									<li class="tab"><a href="#GO">GO</a></li>
									<li class="tab"><a href="#MA">MA</a></li>
									<li class="tab"><a href="#MT">MT</a></li>
									<li class="tab"><a href="#MS">MS</a></li>
									<li class="tab"><a href="#MG">MG</a></li>
									<li class="tab"><a href="#PA">PA</a></li>
									<li class="tab"><a href="#PB">PB</a></li>
									<li class="tab"><a href="#PE">PE</a></li>
									<li class="tab"><a href="#PI">PI</a></li>
									<li class="tab"><a href="#RR">RR</a></li>
									<li class="tab"><a href="#RO">RO</a></li>
									<li class="tab"><a href="#RJ">RJ</a></li>
									<li class="tab"><a href="#RN">RN</a></li>
									<li class="tab"><a href="#RS">RS</a></li>
									<li class="tab"><a href="#SC">SC</a></li>
									<li class="tab"><a href="#SP">SP</a></li>
									<li class="tab"><a href="#SE">SE</a></li>
									<li class="tab"><a href="#TO">TO</a></li>
								</ul>
							</div>
							<div id="PR" class="col s12">
								<div class="input-field col s4">
									<input type="text" valign="input-field" id="perc_redu_icms_st"
										value="0" placeholder="" /><label class="active"
										for="perc_redu_icms_st">% Red BC ICMS ST</label>
								</div>
								<div class="input-field col s4">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st" value="0" placeholder="" /><label
										class="active" for="perc_mv_adic_icms_st">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s4">
									<input type="text" valign="input-field" id="aliq_icms_st"
										value="0" placeholder="" /><label class="active"
										for="aliq_icms_st">% ICMS ST</label>
								</div>
							</div>
							<!--
							<div id="AC" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ac" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_ac">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ac" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ac">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ac" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_ac">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ac" value="0" placeholder=""/><label class="active"
										for="aliq_icms_ac">% ICMS</label>
								</div>
							</div>
							<div id="AL" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_al" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_al">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_al" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_al">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_al" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_al">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_al" value="0" placeholder=""/><label class="active"
										for="aliq_icms_al">% ICMS</label>
								</div>
							</div>
							<div id="AP" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ap" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_ap">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ap" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ap">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ap" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_ap">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ap" value="0" placeholder=""/><label class="active"
										for="aliq_icms_ap">% ICMS</label>
								</div>
							</div>
							<div id="AM" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_am" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_am">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_am" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_am">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_am" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_am">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_am" value="0" placeholder=""/><label class="active"
										for="aliq_icms_am">% ICMS</label>
								</div>
							</div>
							<div id="BA" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ba" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_ba">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ba" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ba">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ba" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_ba">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ba" value="0" placeholder=""/><label class="active"
										for="aliq_icms_ba">% ICMS</label>
								</div>
							</div>
							<div id="CE" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ce" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_ce">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ce" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ce">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ce" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_ce">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ce" value="0" placeholder=""/><label class="active"
										for="aliq_icms_ce">% ICMS</label>
								</div>
							</div>
							<div id="DF" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_df" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_df">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_df" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_df">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_df" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_df">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_df" value="0" placeholder=""/><label class="active"
										for="aliq_icms_df">% ICMS</label>
								</div>
							</div>
							<div id="ES" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_es" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_es">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_es" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_es">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_es" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_es">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_es" value="0" placeholder=""/><label class="active"
										for="aliq_icms_es">% ICMS</label>
								</div>
							</div>
							<div id="GO" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_go" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_go">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_go" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_go">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_go" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_go">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_go" value="0" placeholder=""/><label class="active"
										for="aliq_icms_go">% ICMS</label>
								</div>
							</div>
							<div id="MA" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ma" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_ma">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ma" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ma">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ma" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_ma">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ma" value="0" placeholder=""/><label class="active"
										for="aliq_icms_ma">% ICMS</label>
								</div>
							</div>
							<div id="MT" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_mt" value="0" placeholder=""/><label class="active"  for="perc_redu_icms_st_mt">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_mt" value="0" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_mt">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_mt" value="0" placeholder=""/><label class="active"
										for="aliq_icms_st_mt">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_mt" placeholder=""/><label class="active"
										for="aliq_icms_mt">% ICMS</label>
								</div>
							</div>
							<div id="MS" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ms" placeholder=""/><label class="active"  for="perc_redu_icms_st_ms">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ms" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ms">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ms" placeholder=""/><label class="active"
										for="aliq_icms_st_ms">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ms" placeholder=""/><label class="active"
										for="aliq_icms_ms">% ICMS</label>
								</div>
							</div>
							<div id="MG" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_mg" placeholder=""/><label class="active"  for="perc_redu_icms_st_mg">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_mg" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_mg">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_mg" placeholder=""/><label class="active"
										for="aliq_icms_st_mg">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_mg" placeholder=""/><label class="active"
										for="aliq_icms_mg">% ICMS</label>
								</div>
							</div>
							<div id="PA" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_pa" placeholder=""/><label class="active"  for="perc_redu_icms_st_pa">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_pa" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_pa">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_pa" placeholder=""/><label class="active"
										for="aliq_icms_st_pa">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_pa" placeholder=""/><label class="active"
										for="aliq_icms_pa">% ICMS</label>
								</div>
							</div>
							<div id="PB" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_pb" placeholder=""/><label class="active"  for="perc_redu_icms_st_pb">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_pb" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_pb">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_pb" placeholder=""/><label class="active"
										for="aliq_icms_st_pb">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_pb" placeholder=""/><label class="active"
										for="aliq_icms_pb">% ICMS</label>
								</div>
							</div>
							<div id="PE" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_pe" placeholder=""/><label class="active"  for="perc_redu_icms_st_pe">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_pe" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_pe">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_pe" placeholder=""/><label class="active"
										for="aliq_icms_st_pe">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_pe" placeholder=""/><label class="active"
										for="aliq_icms_pe">% ICMS</label>
								</div>
							</div>
							<div id="PI" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_pi" placeholder=""/><label class="active"  for="perc_redu_icms_st_pi">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_pi" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_pi">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_pi" placeholder=""/><label class="active"
										for="aliq_icms_st_pi">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_pi" placeholder=""/><label class="active"
										for="aliq_icms_pi">% ICMS</label>
								</div>
							</div>
							<div id="RR" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_rr" placeholder=""/><label class="active"  for="perc_redu_icms_st_rr">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_rr" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_rr">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_rr" placeholder=""/><label class="active"
										for="aliq_icms_st_rr">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_rr" placeholder=""/><label class="active"
										for="aliq_icms_rr">% ICMS</label>
								</div>
							</div>
							<div id="RO" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_ro" placeholder=""/><label class="active"  for="perc_redu_icms_st_ro">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_ro" placeholder=""/><label class="active"
										for="perc_mv_adic_icms_st_ro">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_ro" /><label class="active"
										for="aliq_icms_st_ro">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_ro" /><label class="active"
										for="aliq_icms_ro">% ICMS</label>
								</div>
							</div>
							<div id="RJ" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_rj" /><label class="active"  for="perc_redu_icms_st_rj">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_rj" /><label class="active"
										for="perc_mv_adic_icms_st_rj">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_rj" /><label class="active"
										for="aliq_icms_st_rj">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_rj" /><label class="active"
										for="aliq_icms_rj">% ICMS</label>
								</div>
							</div>
							<div id="RN" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_rn" /><label class="active"  for="perc_redu_icms_st_rn">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_rn" /><label class="active"
										for="perc_mv_adic_icms_st_rn">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_rn" /><label class="active"
										for="aliq_icms_st_rn">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_rn" /><label class="active"
										for="aliq_icms_rn">% ICMS</label>
								</div>
							</div>
							<div id="RS" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_rs" /><label class="active"  for="perc_redu_icms_st_rs">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_rs" /><label class="active"
										for="perc_mv_adic_icms_st_rs">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_rs" /><label class="active"
										for="aliq_icms_st_rs">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_rs" /><label class="active"
										for="aliq_icms_rs">% ICMS</label>
								</div>
							</div>
							<div id="SC" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_sc" /><label class="active"  for="perc_redu_icms_st_sc">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_sc" /><label class="active"
										for="perc_mv_adic_icms_st_sc">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_sc" /><label class="active"
										for="aliq_icms_st_sc">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_sc" /><label class="active"
										for="aliq_icms_sc">% ICMS</label>
								</div>
							</div>
							<div id="SP" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_sp" /><label class="active"  for="perc_redu_icms_st_sp">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_sp" /><label class="active"
										for="perc_mv_adic_icms_st_sp">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_sp" /><label class="active"
										for="aliq_icms_st_sp">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_sp" /><label class="active"
										for="aliq_icms_sp">% ICMS</label>
								</div>
							</div>
							<div id="SE" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_se" /><label class="active"  for="perc_redu_icms_st_se">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_se" /><label class="active"
										for="perc_mv_adic_icms_st_se">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_se" /><label class="active"
										for="aliq_icms_st_se">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_se" /><label class="active"
										for="aliq_icms_se">% ICMS</label>
								</div>
							</div>
							<div id="TO" class="col s12">
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_redu_icms_st_to" /><label class="active"  for="perc_redu_icms_st_to">%
										Red BC ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field"
										id="perc_mv_adic_icms_st_to" /><label class="active"
										for="perc_mv_adic_icms_st_to">% Marg Adic ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_st_to" /><label class="active"
										for="aliq_icms_st_to">% ICMS ST</label>
								</div>
								<div class="input-field col s3">
									<input type="text" valign="input-field" id="aliq_icms_to" /><label class="active"
										for="aliq_icms_to">% ICMS</label>
								</div>
							</div>
 -->
						</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-2 white-text"">
						<i class="material-icons">percent</i>IPI/PIS/Cofins
					</div>
					<div class="collapsible-body">
						<h6>IPI</h6>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="ipi_reducao_bc"
									value="0" placeholder="" /><label class="active"
									for="ipi_reducao_bc">% Redução BC IPI</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_ipi"
									value="0" placeholder="" /><label class="active"
									for="aliquota_ipi">Aliquota IPI</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="ipi_reducao_bc_st"
									value="0" placeholder="" /><label class="active"
									for="ipi_reducao_bc_st">% Red BC IPI ST</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_ipi_st"
									value="0" placeholder="" /><label class="active"
									for="aliquota_ipi_st">Aliquota IPI ST</label>
							</div>
							<div class="col s6 l7">
								<label class="active">CST</label> <select
									class="browser-default" id="cst_ipi">
									<option value="" disabled selected>Selecione</option>
									<option value="0" selected>00-Entrada com Recuperação de
										Crédito</option>
									<option value="1">01-Entrada Tributável com Alíquota Zero</option>
									<option value="2">02-Entrada Isenta</option>
									<option value="3">03-Entrada Não-Tributada</option>
									<option value="4">04-Entrada Imune</option>
									<option value="5">05-Entrada com Suspensão</option>
									<option value="49">49-Outras Entradas</option>
									<option value="50">50-Saída Tributada</option>
									<option value="51">51-Saída Tributável com Alíquota Zero</option>
									<option value="52">52-Saída Isenta</option>
									<option value="53">53-Saída Não-Tributada</option>
									<option value="54">54-Saída Imune</option>
									<option value="55">55-Saída com Suspensão</option>
									<option value="99">99-Outras Saídas</option>
								</select>
							</div>
							<div class="col s6 l5">
								<label class="active">Calcular por</label> <select
									class="browser-default" id="calculo_ipi">
									<option value="" disabled selected>Selecione</option>
									<option value="Aliquota" selected>Aliquota</option>
									<option value="Valor Unid.">Valor Unid.</option>
								</select>
							</div>
						</div>
					</div>
					<div class="collapsible-body">
						<h6>PIS</h6>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="pis_reducao_bc"
									value="0" placeholder="" /><label class="active"
									for="pis_reducao_bc">% Redução BC PIS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_pis"
									value="0" placeholder="" /><label class="active"
									for="aliquota_pis">Aliquota PIS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="pis_reducao_bc_st"
									value="0" placeholder="" /><label class="active"
									for="pis_reducao_bc_st">% Redução BC PIS ST</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_pis_st"
									value="0" placeholder="" /><label class="active"
									for="aliquota_pis_st">Aliquota PIS ST</label>
							</div>
						</div>
						<div class="row">
							<div class="col s6 l7">
								<label class="active">CST</label> <select
									class="browser-default" id="cst_pis">
									<option value="" disabled selected>Selecione</option>
									<option value="1">01-Operação Tributável com Alíquota
										Básica</option>
									<option value="2">02-Operação Tributável com Alíquota
										Diferenciada</option>
									<option value="3">03-Operação Tributável com Alíquota por
										Unidade de Medida de Produto</option>
									<option value="4">04-Operação Tributável Monofásica -
										Revenda de Alíquota Zero</option>
									<option value="5">05-Operação Tributável por Substituição
										Tributária</option>
									<option value="6">06-Operação Tributável a Alíquota Zero</option>
									<option value="7">07-Operação Isenta da Contribuição</option>
									<option value="8">08-Operação sem incidência da
										Contribuição</option>
									<option value="9">09-Operação com Suspensão da
										Contribuição</option>
									<option value="49">49-Outras Operações de Saída</option>
									<option value="50">50-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita Tributada no Mercado
										Interno</option>
									<option value="51">51-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita Não Tributada no Mercado
										Interno</option>
									<option value="52">52-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita de Exportação</option>
									<option value="53">53-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas e Não-Tributadas no Mercado
										interno</option>
									<option value="54">54-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas no Mercado Interno e de
										Exportação</option>
									<option value="55">55-Operação com Direito a Crédito -
										Vinculada a Receitas Não-Tributadas no Mercado Interno e de
										Exportação</option>
									<option value="56">56-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas e Não-Tributadas no Mercado
										Interno e de Exportação</option>
									<option value="60">60-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita Tributada no
										Mercado Interno</option>
									<option value="61">61-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita Não-Tributada
										no Mercadoo Interno</option>
									<option value="62">62-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita a Receita de
										Exportação</option>
									<option value="63">63-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas e Não-Tributadas
										no Mercado Interno</option>
									<option value="64">64-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas no Mercado Interno
										e de Exportação</option>
									<option value="65">65-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Não-Tributadas no Mercado
										Interno e de Exportação</option>
									<option value="66">66-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas e Não-Tributadas
										no Mercado Interno</option>
									<option value="67">67-Crédito Presumido - Outras Operações</option>
									<option value="70">70-Operação de Aquisição sem Direito a
										Crédito</option>
									<option value="71">71-Operação de Aquisição com Isenção</option>
									<option value="72">72-Operação de Aquisição com Suspensão</option>
									<option value="73">73-Operação de Aquisição com Alíquota
										Zero</option>
									<option value="74">74-Operação de Aquisição sem Incidência
										da Contribuição</option>
									<option value="75">75-Operação de Aquisição por
										Substituição Tributária</option>
									<option value="98">98-Outras Operações de Entrada</option>
									<option value="99" selected>99-Outras Operações</option>
								</select>
							</div>
							<div class="col s6 l5">
								<label class="active">Calcular por</label> <select
									class="browser-default" id="calculo_pis">
									<option value="" disabled selected>Selecione</option>
									<option value="Aliquota" selected>Aliquota</option>
									<option value="Valor Unid.">Valor Unid.</option>
								</select>
							</div>
						</div>
					</div>
					<div class="collapsible-body">
						<h6>COFINS</h6>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="cofins_reducao_bc"
									value="0" placeholder="" /><label class="active"
									for="cofins_reducao_bc">% Redução BC COFINS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_cofins"
									value="0" placeholder="" /><label class="active"
									for="aliquota_cofins">Aliquota COFINS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="cofins_reducao_bc_st"
									value="0" placeholder="" /><label class="active"
									for="cofins_reducao_bc_st">% Redução BC COFINS ST</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" id="aliquota_cofins_st"
									value="0" placeholder="" /><label class="active"
									for="aliquota_cofins_st">Aliquota COFINS ST</label>
							</div>
						</div>
						<div class="row">
							<div class="col s6 l7">
								<label class="active">CST</label> <select
									class="browser-default" id="cst_cofins">
									<option value="" disabled selected>Selecione</option>
									<option value="1">01-Operação Tributável com Alíquota
										Básica</option>
									<option value="2">02-Operação Tributável com Alíquota
										Diferenciada</option>
									<option value="3">03-Operação Tributável com Alíquota por
										Unidade de Medida de Produto</option>
									<option value="4">04-Operação Tributável Monofásica -
										Revenda de Alíquota Zero</option>
									<option value="5">05-Operação Tributável por Substituição
										Tributária</option>
									<option value="6">06-Operação Tributável a Alíquota Zero</option>
									<option value="7">07-Operação Isenta da Contribuição</option>
									<option value="8">08-Operação sem incidência da
										Contribuição</option>
									<option value="9">09-Operação com Suspensão da
										Contribuição</option>
									<option value="49">49-Outras Operações de Saída</option>
									<option value="50">50-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita Tributada no Mercado
										Interno</option>
									<option value="51">51-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita Não Tributada no Mercado
										Interno</option>
									<option value="52">52-Operação com Direito a Crédito -
										Vinculada Exclusivamente a Receita de Exportação</option>
									<option value="53">53-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas e Não-Tributadas no Mercado
										interno</option>
									<option value="54">54-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas no Mercado Interno e de
										Exportação</option>
									<option value="55">55-Operação com Direito a Crédito -
										Vinculada a Receitas Não-Tributadas no Mercado Interno e de
										Exportação</option>
									<option value="56">56-Operação com Direito a Crédito -
										Vinculada a Receitas Tributadas e Não-Tributadas no Mercado
										Interno e de Exportação</option>
									<option value="60">60-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita Tributada no
										Mercado Interno</option>
									<option value="61">61-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita Não-Tributada
										no Mercadoo Interno</option>
									<option value="62">62-Crédito Presumido - Operação de
										Aquisição Vinculada Exclusivamente a Receita a Receita de
										Exportação</option>
									<option value="63">63-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas e Não-Tributadas
										no Mercado Interno</option>
									<option value="64">64-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas no Mercado Interno
										e de Exportação</option>
									<option value="65">65-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Não-Tributadas no Mercado
										Interno e de Exportação</option>
									<option value="66">66-Crédito Presumido - Operação de
										Aquisição Vinculada a Receitas Tributadas e Não-Tributadas
										no Mercado Interno</option>
									<option value="67">67-Crédito Presumido - Outras Operações</option>
									<option value="70">70-Operação de Aquisição sem Direito a
										Crédito</option>
									<option value="71">71-Operação de Aquisição com Isenção</option>
									<option value="72">72-Operação de Aquisição com Suspensão</option>
									<option value="73">73-Operação de Aquisição com Alíquota
										Zero</option>
									<option value="74">74-Operação de Aquisição sem Incidência
										da Contribuição</option>
									<option value="75">75-Operação de Aquisição por
										Substituição Tributária</option>
									<option value="98">98-Outras Operações de Entrada</option>
									<option value="99" selected>99-Outras Operações</option>
								</select>
							</div>
							<div class="col s6 l5">
								<label class="active">Calcular por</label> <select
									class="browser-default" id="calculo_cofing">
									<option value="" disabled selected>Selecione</option>
									<option value="Aliquota" selected>Aliquota</option>
									<option value="Valor Unid.">Valor Unid.</option>
								</select>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-3 white-text"">
						<i class="material-icons">apps</i>Grade
					</div>
					<div class="collapsible-body">
						<!-- Novo layout melhorado -->
						<div class="row">
							<div class="col s12">
								<div class="card-panel light-grey lighten-5">
									<h6><i class="material-icons left">info_outline</i>Como usar</h6>
									<p>1. Digite o código GTIN da variante</p>
									<p>2. O sistema buscará automaticamente as informações do produto</p>
									<p>3. Preencha variação e característica</p>
									<p>4. Clique em + para adicionar à grade</p>
								</div>
							</div>
						</div>
						
						<div class="row">
							<div class="input-field col s12 m4">
								<i class="material-icons prefix">search</i>
								<input type="text" class="input-field" id="prod_gd_codigo_gtin"
									 placeholder="Ex: 1234567890123" onfocusout="verificarCodigoGrade();"/>
								<label class="active" for="prod_gd_codigo_gtin">Código GTIN *</label>
								<span class="helper-text">Digite o código para buscar automaticamente</span>
							</div>
							<div class="input-field col s12 m8">
								<i class="material-icons prefix">description</i>
								<input type="text" class="input-field" id="prod_gd_nome"
									 placeholder="Será preenchido automaticamente" readonly />
								<label class="active" for="prod_gd_nome">Descrição do Produto</label>
							</div>
						</div>
						
						<div class="row">
							<div class="input-field col s12 m4">
								<i class="material-icons prefix">category</i>
								<input type="text" class="input-field" id="prod_gd_variacao"
									 placeholder="Ex: Tamanho, Cor" />
								<label class="active" for="prod_gd_variacao">Variação *</label>
								<span class="helper-text">Tipo da variação</span>
							</div>
							<div class="input-field col s12 m4">
								<i class="material-icons prefix">label</i>
								<input type="text" class="input-field" id="prod_gd_caracteristica"
									 placeholder="Ex: GG, Azul" />
								<label class="active" for="prod_gd_caracteristica">Característica *</label>
								<span class="helper-text">Valor da variação</span>
							</div>
							<div class="col s12 m4">
								<div style="margin-top: 20px;">
									<a class="btn waves-effect waves-light green" onClick='gradeManager.adicionar_item_grade()' id='but_add'>
										<i class="material-icons left">add</i>Adicionar
									</a>
									<a class="btn waves-effect waves-light blue" id="btn-grade-avancada" style="margin-left: 10px;">
										<i class="material-icons left">settings</i>Avançado
									</a>
								</div>
							</div>
						</div>
						<div class="row">
                            <table class="responsive-table striped" id='userTableGrade'>
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th>Variação</th>
                                        <th>Característica</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
						</div>
					</div>
				</li>
				<li>
					<div class="collapsible-header #7986cb indigo lighten-4 white-text">
						<i class="material-icons">image</i>Imagens
					</div>
					<div class="collapsible-body">
						<!-- Interface CRUD de Imagens -->
						<div class="row">
							<!-- Preview da imagem selecionada -->
							<div class="col s12 m6">
								<div class="card">
									<div class="card-content">
										<span class="card-title">Preview da Imagem</span>
										<div id="image-preview-container" style="min-height: 300px; display: flex; align-items: center; justify-content: center; border: 2px dashed #ddd; border-radius: 8px;">
											<div id="image-preview-placeholder">
												<div style="text-align: center; color: #999;">
													<i class="material-icons large">image</i>
													<p>Selecione uma posição para visualizar</p>
												</div>
											</div>
											<img id="image-preview" style="display: none; max-width: 100%; max-height: 300px; border-radius: 4px;" />
										</div>

										<!-- Informações da imagem -->
										<div id="image-info" style="display: none; margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
											<p><strong>Arquivo:</strong> <span id="image-filename"></span></p>
											<p><strong>Tamanho:</strong> <span id="image-size"></span></p>
											<p><strong>Modificado:</strong> <span id="image-modified"></span></p>
										</div>
									</div>
								</div>
							</div>

							<!-- Seleção e gerenciamento de imagens -->
							<div class="col s12 m6">
								<div class="card">
									<div class="card-content">
										<span class="card-title">Gerenciar Imagens</span>

										<!-- Lista de posições -->
										<div id="image-positions-list">
											<!-- Será preenchido dinamicamente -->
										</div>

										<!-- Botões de ação -->
										<div style="text-align: center;">
											<input type="file" id="image-upload-input" accept="image/*" style="display: none; margin-bottom: 10px; margin-top: 10px;">
											<button class="btn blue waves-effect" id="upload-btn" onclick="triggerImageUpload()">
												<i class="material-icons left">cloud_upload</i>Enviar Imagem
											</button>
											<button class="btn red waves-effect" id="delete-btn" onclick="deleteSelectedImage(event); return false;" style="display: none; margin-bottom: 10px; margin-top: 10px;">
												<i class="material-icons left">delete</i>Excluir Imagem
											</button>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</li>

			</ul>
			<!-- Botões de ação no formulário -->
		</form>
	</div>
	<div class="form-buttons">
				<div>
					<a href="javascript:retornarPrincipal()" class="waves-effect waves-light btn blue" id="retornnar">
						<i class="material-icons">arrow_back</i>
						<span class="hide-on-small-only">Voltar</span>
					</a>
				</div>
				<div>
					<a class="waves-effect waves-light btn red" href="javascript:limparProdutos()">
						<i class="material-icons">clear</i>
						<span class="hide-on-small-only">Limpar</span>
					</a>
					<a href="javascript:gravarProdutos()" class="waves-effect waves-light btn green" id="gravarProdutos">
						<i class="material-icons">save</i>
						<span class="hide-on-small-only">Gravar</span>
					</a>
				</div>
			</div>
	<div id="modalGrupo" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="grupo_novo"><label
					class="active" for="grupo_novo">Grupo</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarGrupo(grupo_novo.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalSubGrupo" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="subgrupo_novo"><label
					class="active" for="subgrupo_novo">Sub Grupo</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarSubGrupo(subgrupo_novo.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalCategoria" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="categoria_novo"><label
					class="active" for="categoria_novo">Categoria</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarCategoria(categoria_novo.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalUnidade" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="unidade_novo"><label
					class="active" for="unidade_novo">Unidade</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarUnidade(unidade_novo.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalFornecedor" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="fornecedor_novo"><label
					class="active" for="fornecedor_novo">Fornecedor</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarFornecedor(fornecedor_novo.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>

	<!-- Modal de Filtros Avançados -->
	<div id="modalFiltros" class="modal modal-fixed-footer" style="max-height: 90%; width: 80%; max-width: 800px;">
		<div class="modal-content">
			<h4 style="margin-bottom: 20px;">
				<i class="material-icons" style="vertical-align: middle; margin-right: 10px;">filter_list</i>
				Filtros Avançados
			</h4>

			<!-- Seção E-commerce -->
			<div class="card" style="margin-bottom: 15px;">
				<div class="card-content" style="padding: 15px;">
					<h6 style="margin-bottom: 15px; color: #1976d2; margin-left: 10px;">
						<i class="material-icons" style="vertical-align: middle; margin-right: 8px; font-size: 20px;">cloud</i>
						E-commerce
					</h6>
					<div class="row" style="margin-bottom: 0;">
						<div class="col s12 m6 l3">
							<p style="margin: 5px 0;">
								<label>
									<input type="checkbox" id="filtro_nuvemshop" />
									<span>Nuvemshop</span>
								</label>
							</p>
						</div>
						<div class="col s12 m6 l3">
							<p style="margin: 5px 0;">
								<label>
									<input type="checkbox" id="filtro_mercadolivre" />
									<span>Mercado Livre</span>
								</label>
							</p>
						</div>
						<div class="col s12 m6 l3">
							<p style="margin: 5px 0;">
								<label>
									<input type="checkbox" id="filtro_shopee" />
									<span>Shopee</span>
								</label>
							</p>
						</div>
						<div class="col s12 m6 l3">
							<p style="margin: 5px 0;">
								<label>
									<input type="checkbox" id="filtro_americanas" />
									<span>Americanas</span>
								</label>
							</p>
						</div>
					</div>

					<!-- Subseção Nuvemshop -->
					<div id="nuvemshop_subsecao" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
						<h6 style="margin-bottom: 10px; color: #388e3c; font-size: 14px; margin-left: 10px;">
							<i class="material-icons" style="vertical-align: middle; margin-right: 5px; font-size: 16px;">cloud</i>
							Tipos de Produto Nuvemshop
						</h6>
						<div class="row" style="margin-bottom: 0;">
							<div class="col s12 m4">
								<p style="margin: 5px 0;">
									<label>
										<input type="checkbox" id="filtro_nuvem_normal" />
										<span>Produtos Normais</span>
									</label>
								</p>
							</div>
							<div class="col s12 m4">
								<p style="margin: 5px 0;">
									<label>
										<input type="checkbox" id="filtro_nuvem_vitrine" />
										<span>Produtos Pai (Vitrine)</span>
									</label>
								</p>
							</div>
							<div class="col s12 m4">
								<p style="margin: 5px 0;">
									<label>
										<input type="checkbox" id="filtro_nuvem_variante" />
										<span>Variantes</span>
									</label>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Seção Categorias -->
			<div class="card" style="margin-bottom: 15px;">
				<div class="card-content" style="padding: 15px;">
					<h6 style="margin-bottom: 15px; margin-left: 10px; color: #f57c00;">
						<i class="material-icons" style="vertical-align: middle; margin-right: 8px; font-size: 20px;">category</i>
						Categorias
					</h6>
					<div class="row" style="margin-bottom: 0;">
						<div class="col s12 m6">
							<label class="active">Categoria Principal</label>
							<select id="filtro_categoria" class="browser-default" style="margin-bottom: 10px;">
								<option value="">Todas as categorias</option>
							</select>
						</div>
						<div class="col s12 m6">
							<label class="active">Subcategoria (Grupo)</label>
							<select id="filtro_grupo" class="browser-default" style="margin-bottom: 10px;">
								<option value="">Todos os grupos</option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Seção Produtos Locais -->
			<div class="card" style="margin-bottom: 15px;">
				<div class="card-content" style="padding: 15px;">
					<h6 style="margin-bottom: 15px; margin-left: 10px; color: #7b1fa2;">
						<i class="material-icons" style="vertical-align: middle; margin-right: 8px; font-size: 20px;">computer</i>
						Produtos Locais
					</h6>
					<div class="row" style="margin-bottom: 0;">
						<div class="col s12">
							<p style="margin: 5px 0;">
								<label>
									<input type="checkbox" id="filtro_apenas_locais" />
									<span>Apenas produtos que não estão em nenhum e-commerce</span>
								</label>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal-footer"style=" display: flex; align-items: center; justify-content: right;">
			<a href="#!" id="limpar-filtros" onclick="limparFiltros()">Limpar</a>
			<a href="#!" id="aplicar-filtros" onclick="aplicarFiltros()">Aplicar Filtros</a>
		</div>
	</div>

	<!-- Modal de exportação ML -->
	<?php include 'mercadolivre/export_modal.html'; ?>

	<!-- Modal de erros detalhados ML -->
	<?php include 'mercadolivre/errors_modal.html'; ?>

	<!-- Modal Grade Avançada -->
	<div id="modal-grade-avancada" class="modal modal-fixed-footer" style="width: 95%; max-width: 1200px;">
		<div class="modal-content">
			<h4><i class="material-icons left">apps</i>Grade Avançada</h4>
			<p>Gerencie preços, estoque, dimensões e imagens por variante</p>
			
			<div id="grade-variants-container" class="row">
				<!-- Variantes serão carregadas aqui -->
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
			<a href="#!" class="waves-effect waves-green btn" id="btn-salvar-grade">
				<i class="material-icons left">save</i>Salvar Alterações
			</a>
		</div>
	</div>



	<!-- Modal Preview de Imagem -->
	<div id="modal-preview-imagem" class="modal" style="width: 60%; max-width: 600px;">
		<div class="modal-content center-align">
			<h4><i class="material-icons left">image</i>Preview da Imagem</h4>
			<img id="preview-image" style="max-width: 100%; max-height: 400px; border-radius: 8px;" />
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-close waves-effect waves-red btn-flat">Fechar</a>
		</div>
	</div>

	<!-- Incluir Grade Manager -->
	<script src="js/grade_manager.js"></script>

	<script>
		// Scripts específicos da página produtos.php (se necessário)

		// Inicializar modais ML quando documento estiver pronto
		$(document).ready(function() {
			// Inicializar modais usando Materialize CSS 0.100.1
			$('#modal_export_ml').modal();
			$('#modal_erros_ml_detalhado').modal();
			$('#modal-grade-avancada').modal();
			$('#modal-preview-imagem').modal();
		});

	</script>
</body>
</html>
