$(document).ajaxStart(function () {
	$("#loading").show();
});
$(document).ajaxStop(function () {
	$("#loading").hide();
});
function carregarNumeracao(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var documento = response[i].documento;
			var serie = response[i].serie;
			var ie = response[i].ie;
			var tipo = response[i].tipo;
			var natureza = response[i].natureza;
			var emissao = response[i].emissao;
			var saida = response[i].saida;
			document.getElementById('documento').value = documento;
			document.getElementById('serie').value = serie;
			document.getElementById('cfop').value = natureza;
			document.getElementById('tipo').value = tipo;
			document.getElementById('emissao').value = emissao;
			document.getElementById('saida').value = saida;
		}
	}
}
function carregarNfe(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var documento = response[i].documento;
			var serie = response[i].serie;
			var cliente = response[i].cliente;
			var tipo = response[i].tipo;
			var natureza = response[i].natureza;
			var emissao = response[i].emissao;
			var saida = response[i].saida;
			var dados_adicionais = response[i].dados_adicionais;
			var dados_adicionais2 = response[i].dados_adicionais2;
			document.getElementById('documento').value = documento;
			document.getElementById('serie').value = serie;
			document.getElementById('cfop').value = natureza;
			document.getElementById('tipo').value = tipo;
			document.getElementById('emissao').value = emissao;
			document.getElementById('saida').value = saida;
			document.getElementById('nfe_inf_adicional').value = dados_adicionais;
			document.getElementById('nfe_inf_adicional2').value = dados_adicionais2;
			cadastro_cliente(cliente);
			carregarProdutoNfe(documento, serie);
			consultarFatura();
			consultarTransportadora();
		}
	}
}
function consultarFatura() {
	var documento = document.getElementById('documento').value;
	var serie = document.getElementById('serie').value;
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'consultarFatura',
			documento: document.getElementById('documento').value,
			serie: document.getElementById('serie').value,
		},
		dataType: 'json',
		success: function (response) {
			createRowsFat(response);
		}
	});
}
function consultarTransportadora() {
	var documento = document.getElementById('documento').value;
	var serie = document.getElementById('serie').value;
	var cod_transportadora = 0;
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'consultarTransportadora',
			documento: document.getElementById('documento').value,
			serie: document.getElementById('serie').value,
		},
		dataType: 'json',
		success: function (response) {
			var len = 0;
			if (response != null) {
				len = response.length;
			}
			if (len > 0) {
				$("#nfe_transportadora").show();
				$("#nfe_transportadora_pesquisa").hide();
				for (var i = 0; i < len; i++) {
					document.getElementById('nfe_tran_frete').value = response[i].por_conta;
					document.getElementById('nfe_tra_placa').value = response[i].placa;
					document.getElementById('nfe_tra_cod_antt').value = response[i].cod_antt;
					document.getElementById('nfe_tra_placa_uf').value = response[i].placa_uf;
					document.getElementById('nfe_tra_qtde').value = response[i].quantidade;
					document.getElementById('nfe_tra_especie').value = response[i].especie;
					document.getElementById('nfe_tra_marca').value = response[i].marca;
					document.getElementById('nfe_tra_numeracao').value = response[i].numeracao;
					document.getElementById('nfe_tra_pesobruto').value = response[i].peso_bruto;
					document.getElementById('nfe_tra_pesoliquido').value = response[i].peso_liquido;
					cod_transportadora = response[i].cod_transportadora;
				}
			}
			cadastro_transportadora(cod_transportadora);
		}
	});
}
function adicionarFatura() {
	var documento = document.getElementById('documento').value;
	var serie = document.getElementById('serie').value;
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'adicionarFatura',
			documento: document.getElementById('documento').value,
			serie: document.getElementById('serie').value,
			nfe_fat_numero: document.getElementById('nfe_fat_numero').value,
			nfe_fat_valor: document.getElementById('nfe_fat_valor').value,
			nfe_fat_vencimento: document.getElementById('nfe_fat_vencimento').value
		},
		dataType: 'json',
		success: function (response) {
			createRowsFat(response);
		}
	});
}
function gerarDanfe(codigo_interno) {
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'gerarDanfe',
			codigo_interno: codigo_interno
		},
		dataType: 'json',
		success: function (response) {
			if (response == "OK") {
				window.location.href = 'danfe.pdf';
			} else {
				alert(response)
			}
		}
	});
}
function cancelarNfe(codigo_interno) {
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'cancelarNfe',
			codigo_interno: codigo_interno
		},
		dataType: 'json',
		success: function (response) {
			if (response == "OK") {
				alert("Nota Cancelada")
			} else {
				alert(response)
			}
		}
	});
}
function gravarNfe() {
	var documento = document.getElementById('documento').value;
	var serie = document.getElementById('serie').value;
	var finalidade = document.getElementById('finalidade').value;
	//Grava NFE
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'gravarNFe',
			documento: documento,
			serie: serie,
			cfop: document.getElementById('cfop').value,
			tipo: document.getElementById('tipo').value,
			emissao: document.getElementById('emissao').value,
			saida: document.getElementById('saida').value,
			imposto_bc_icms: document.getElementById('imposto_bc_icms').value,
			imposto_val_icms: document.getElementById('imposto_val_icms').value,
			imposto_bc_icms_st: document.getElementById('imposto_bc_icms_st').value,
			imposto_val_icms_st: document.getElementById('imposto_val_icms_st').value,
			imposto_val_produtos: document.getElementById('imposto_val_produtos').value,
			imposto_val_frete: document.getElementById('imposto_val_frete').value,
			imposto_val_seguro: document.getElementById('imposto_val_seguro').value,
			imposto_val_descontos: document.getElementById('imposto_val_descontos').value,
			imposto_val_despesas: document.getElementById('imposto_val_despesas').value,
			imposto_val_ipi: document.getElementById('imposto_val_ipi').value,
			imposto_val_total: document.getElementById('imposto_val_total').value
		},
		dataType: 'json',
		success: function (response) {
			console.log(response);
		}
	});
	//XML para gerar XML
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'gerarXML',
			documento: documento,
			serie: serie,
			finalidade: finalidade,
		},
		dataType: 'json',
		success: function (response) {
			if (response == "OK") {
				window.location.href = 'danfe.pdf';
			} else {
				alert(response)
			}
		}
	});
}
function retornarPrincipal() {
	limparNfe();
	$('#userTable tbody').empty();
	$("#nfe_principal").show();
	$("#nfe_cadastro").hide();
}
function retornarClienteNfe() {
	$('#userTableCliente tbody').empty();
	$("#nfe_cliente").hide();
	$("#nfe_cliente_pesquisa").show();
}
function retornarTransportadoraNfe() {
	$('#userTableTransportadora tbody').empty();
	$("#nfe_transportadora").hide();
	$("#nfe_transportadora_pesquisa").show();
}
function limparNfe() {
}
$(document).ready(function () {
	$('.modal').modal();
});
$(document).ready(function () {
	$('.collapsible').collapsible();
});
$(document).ready(function () {
	$("#nfe_principal").show();
	$("#nfe_cadastro").hide();
	$("#nfe_cliente").hide();
	$("#nfe_transportadora").hide();
	$("#nfe_produto_pesquisa").hide();
	carregar_estabelecimento();
	// Fetch all records
	$('#but_fetchall').click(function () {
		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function (response) {
				createRows(response);
			}
		});
	});
	$('#but_fetch_cliente').click(function () {
		// AJAX GET request
		const valor = document.getElementById('desc_pesquisa_cliente').value;
		console.log(valor);
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: { request: 'fetchallclientes', pagina: 0, desc_pesquisa_cliente: valor },
			dataType: 'json',
			success: function (response) {
				createRowsCliente(response);
			},
			error: function (jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
	$('#but_fetch_transportadora').click(function () {
		// AJAX GET request
		const valor = document.getElementById('desc_pesquisa_transportadora').value;
		console.log(valor);
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'fetchalltransportadoras',
				pagina: 0,
				desc_pesquisa_transportadora: valor
			},
			dataType: 'json',
			success: function (response) {
				//	console.log(response);
				createRowsTransportadora(response);
			},
			error: function (jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
	$('#but_fetch_nfe_produto').click(function () {
		// AJAX GET request
		const valor = document.getElementById('desc_pesquisa_produto').value;
		console.log(valor);
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: { request: 'fetchallnfeprodutos', pagina: 0, desc_pesquisa_produto: valor },
			dataType: 'json',
			success: function (response) {
				createRowsNfeProduto(response);
			},
			error: function (jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
}
);
function carregarProdutoNfe(nf_numero, nf_serie) {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'carregarProdutoNfe',
			nf_numero: nf_numero,
			nf_serie: nf_serie,
		},
		dataType: 'json',
		success: function (response) {
			createRowsItensNfe(response);
			carregarImposto();
		}
	});
}
function cadastro_nfe(codigo) {
	$("#nfe_principal").hide();
	$("#nfe_cadastro").show();
	//Se for edicao de nfe
	if (codigo > 0) {
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirNfe',
				codigo: codigo,
			},
			dataType: 'json',
			success: function (response) {
				carregarNfe(response);
			}
		});
	} else { //Se for Adicao de NFe
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'pegar_numeracao',
			},
			dataType: 'json',
			success: function (response) {
				carregarNumeracao(response);
				nf_numero = document.getElementById('documento').value;
				nf_serie = document.getElementById('serie').value;
				carregarProdutoNfe(nf_numero, nf_serie);
			}
		});
	}
}
function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: { request: 'fetchall', pagina: (valor - 1) * 50, desc_pesquisa: val },
		dataType: 'json',
		success: function (response) {
			createRows(response);
		}
	});
}
function createRowsCliente(response) {
	var len = 0;
	$('#userTableCliente tbody').empty(); // Empty <tbody>
	$("#paginacaoCliente").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPagina(" + pagina + ");' />";
			$("#paginacaoCliente").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var razao_social = response[i].razao_social;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + razao_social + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_cliente(" + codigo + ")' id='but_edit'><i class='material-icons'>arrow_right_alt</i></a></td>" +
				"</tr>";
			$("#userTableCliente tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td></td><td></td><td></td>" +
			"</tr>";
		$("#userTableCliente tbody").append(tr_str);
	}
}
function createRowsTransportadora(response) {
	var len = 0;
	$('#userTableTransportadora tbody').empty(); // Empty <tbody>
	$("#paginacaoTransportadora").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPagina(" + pagina + ");' />";
			$("#paginacaoTransportadora").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var razao_social = response[i].razao_social;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + razao_social + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_transportadora(" + codigo + ")' id='but_edit'><i class='material-icons'>arrow_right_alt</i></a></td>" +
				"</tr>";
			$("#userTableTransportadora tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td></td><td></td><td></td>" +
			"</tr>";
		$("#userTableTransportadora tbody").append(tr_str);
	}
}
function createRowsNfeProduto(response) {
	var len = 0;
	$('#userTableNfeProduto tbody').empty(); // Empty <tbody>
	$("#paginacaoNfeProduto").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPaginaProduto(" + pagina + ");' />";
			$("#paginacaoNfeProduto").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='selecionarProdutoNfe(" + codigo_gtin + ")' id='but_edit'><i class='material-icons'>arrow_right_alt</i></a></td>" +
				"</tr>";
			$("#userTableNfeProduto tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td></td><td></td><td></td>" +
			"</tr>";
		$("#userTableNfeProduto tbody").append(tr_str);
	}
}
function editar_nfe(codigo_interno) {
	alert("Editar NFE");
}
function createRows(response) {
	var len = 0;
	$('#userTable tbody').empty(); // Empty <tbody>
	$("#paginacao").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPagina(" + pagina + ");' />";
			$("#paginacao").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo_interno = response[i].codigo_interno;
			var documento = response[i].documento;
			var serie = response[i].serie;
			var emissao = response[i].emissao;
			var hora = response[i].hora;
			var cliente = response[i].cliente;
			var status = response[i].status;
			if (status == 2) {
				var tr_str = "<tr>" +
					"<td>" + documento + "</td>" +
					"<td>" + serie + "</td>" +
					"<td>" + emissao + "</td>" +
					"<td>" + hora + "</td>" +
					"<td>" + cliente + "</td>" +
					"<td><a class='btn-floating btn-small waves-effect grey' onClick='gerarDanfe(" + codigo_interno + ")' id='but_edit'><i class='material-icons'>picture_as_pdf</i></a>&nbsp;<a class='btn-floating btn-small waves-effect grey' onClick='corrigirNfe(" + codigo_interno + ")' id='but_corr'><i class='material-icons'>build_circle</i></a>&nbsp;<a class='btn-floating btn-small waves-effect grey' onClick='cancelarNfe(" + codigo_interno + ")' id='but_canc'><i class='material-icons'>cancel</i></a></td>" +
					"</tr>";
			} else if (status == 3) {
				var tr_str = "<tr>" +
					"<td>" + documento + "</td>" +
					"<td>" + serie + "</td>" +
					"<td>" + emissao + "</td>" +
					"<td>" + hora + "</td>" +
					"<td>" + cliente + "</td>" +
					"<td>CANCELADA</td>" +
					"</tr>";
			} else {
				var tr_str = "<tr>" +
					"<td>" + documento + "</td>" +
					"<td>" + serie + "</td>" +
					"<td>" + emissao + "</td>" +
					"<td>" + hora + "</td>" +
					"<td>" + cliente + "</td>" +
					"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_nfe(" + codigo_interno + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
					"</tr>";
			}
			$("#userTable tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#userTable tbody").append(tr_str);
	}
}
function carregarProdutos(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
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
		if (response[i].status == 'E') {
			document.getElementById('vender_ecomerce').checked = true;
		} else {
			document.getElementById('vender_ecomerce').checked = false;
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
}
function carregarEstabelecimento(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var razao_social = response[i].razao_social;
			var cnpj = response[i].cnpj;
			var ie = response[i].ie;
			document.getElementById('razao_social').value = razao_social;
			document.getElementById('cnpj').value = cnpj;
			document.getElementById('ie').value = ie;
		}
	}
}
function carregar_estabelecimento() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: { request: 'carregar_estabelecimento' },
		dataType: 'json',
		success: function (response) {
			carregarEstabelecimento(response);
		}
	});
}
function cadastro_cliente(codigo) {
	$("#nfe_cliente_pesquisa").hide();
	$("#nfe_cliente").show();
	documento = document.getElementById('documento').value;
	serie = document.getElementById('serie').value;
	tipo = document.getElementById('tipo').value;
	if (codigo > 0) {
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirCliente',
				codigo: codigo,
				documento: documento,
				tipo: tipo,
				serie: serie,
			},
			dataType: 'json',
			success: function (response) {
				carregarCliente(response);
			}
		});
	}
}
function carregarCliente(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var cli_codigo = response[i].codigo;
			var cli_razao_social = response[i].razao_social;
			var cli_cpf_cnpj = response[i].cpf_cnpj;
			var cli_logradouro = response[i].logradouro;
			var cli_numero = response[i].numero;
			var cli_complemento = response[i].complemento;
			var cli_bairro = response[i].bairro;
			var cli_cep = response[i].cep;
			var cli_municipio = response[i].municipio;
			var cli_municipio_desc = response[i].municipio_desc;
			var cli_uf = response[i].uf;
			var cli_uf_desc = response[i].uf_desc;
			var cli_fone = response[i].fone;
			var cli_inscricao_rg = response[i].inscricao_rg;
			document.getElementById('cli_codigo').value = cli_codigo;
			document.getElementById('cli_razao_social').value = cli_razao_social;
			document.getElementById('cli_cpf_cnpj').value = cli_cpf_cnpj;
			document.getElementById('cli_logradouro').value = cli_logradouro;
			document.getElementById('cli_numero').value = cli_numero;
			document.getElementById('cli_complemento').value = cli_complemento;
			document.getElementById('cli_bairro').value = cli_bairro;
			document.getElementById('cli_cep').value = cli_cep;
			document.getElementById('cli_inscricao_rg').value = cli_inscricao_rg;
			document.getElementById('cli_fone').value = cli_fone;
			document.getElementById('cli_municipio').value = cli_municipio;
			document.getElementById('cli_municipio_desc').value = cli_municipio_desc;
			document.getElementById('cli_uf').value = cli_uf;
			document.getElementById('cli_uf_desc').value = cli_uf_desc;
		}
	}
}
function pesquisarProdutoNfe() {
	$("#nfe_produto").hide();
	$("#nfe_produto_pesquisa").show();
}
function retornarNfeProduto() {
	$("#nfe_produto").show();
	$("#nfe_produto_pesquisa").hide();
}
function carregarItensProdutoNfe(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var situacao_tributaria = response[i].situacao_tributaria;
			var aliquota_icms = response[i].aliquota_icms;
			var cfop = response[i].cfop;
			var preco_venda = response[i].preco_venda;
			document.getElementById('nfe_prod_codigo').value = codigo_gtin;
			document.getElementById('nfe_prod_descricao').value = descricao;
			document.getElementById('nfe_prod_st').value = situacao_tributaria;
			document.getElementById('nfe_prod_cfop').value = cfop;
			document.getElementById('nfe_prod_qtde').value = '1';
			document.getElementById('nfe_prod_valunit').value = preco_venda;
			document.getElementById('nfe_prod_icms').value = aliquota_icms;
			document.getElementById('nfe_prod_complemento').focus();
		}
	}
}
function selecionarProdutoNfe(codigo) {
	$("#nfe_produto_pesquisa").hide();
	$("#nfe_produto").show();
	//Se for  
	if (codigo > 0) {
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'pegarDadosProdutoSelecionado',
				codigo_gtin: codigo,
			},
			dataType: 'json',
			success: function (response) {
				carregarItensProdutoNfe(response);
			}
		});
	}
}
function perguntarICMS() {
	if (document.getElementById('nfe_prod_st').value == 900 || document.getElementById('nfe_prod_st').value == 90 || document.getElementById('nfe_prod_st').value == 20) {
		document.getElementById('percdev').value = document.getElementById('nfe_prod_icms').value;
		$('#modalicms').modal();
		$('#modalicms').modal('open');
	} else {
		adicionarProdutoNfe(0);
	}
}
function adicionarBCIcmsDevol(bcicmsdevol, percdev2) {
	adicionarProdutoNfe(percdev2, bcicmsdevol);
}
function adicionarPercIcmsDevol(percdev) {
	var val_unit = document.getElementById('nfe_prod_valunit').value;
	var qtde = document.getElementById('nfe_prod_qtde').value;
	var desconto = document.getElementById('nfe_prod_desconto').value;
	document.getElementById('bcicms_devol').value = (val_unit - desconto) * qtde;
	document.getElementById('percdev2').value = percdev;
	$('#modalbcicms').modal();
	$('#modalbcicms').modal('open');
}
function createRowsItensNfe(response) {
	var len = 0;
	$('#nfeProdutoItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var codigo_ncm = response[i].codigo_ncm;
			var cst_cson = response[i].cst_cson;
			var cfop = response[i].cfop;
			var unidade = response[i].unidade;
			var quantidade = response[i].quantidade;
			var preco_unitario = response[i].preco_unitario;
			var total = response[i].total;
			var bc_icms = response[i].bc_icms;
			var val_icms = response[i].val_icms;
			var val_ipi = response[i].val_ipi;
			var aliquota_icms = response[i].aliquota_icms;
			var aliquota_ipi = response[i].aliquota_ipi;
			var aliquota_icms_st = response[i].aliquota_icms_st;
			var bc_icms_st = response[i].bc_icms_st;
			var val_icms_st = response[i].val_icms_st;
			var desconto = response[i].desconto;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td>" + codigo_ncm + "</td>" +
				"<td>" + cst_cson + "</td>" +
				"<td>" + cfop + "</td>" +
				"<td>" + unidade + "</td>" +
				"<td>" + quantidade + "</td>" +
				"<td>" + preco_unitario + "</td>" +
				"<td>" + total + "</td>" +
				//				"<td>" + bc_icms + "</td>" +
				//				"<td>" + val_icms + "</td>" +
				//				"<td>" + val_ipi + "</td>" +
				//				"<td>" + aliquota_icms + "</td>" +
				//				"<td>" + aliquota_ipi + "</td>" +
				//				"<td>" + aliquota_icms_st + "</td>" +
				//				"<td>" + bc_icms_st + "</td>" +
				//				"<td>" + aliquota_icms_st + "</td>" +
				//				"<td>" + val_icms_st + "</td>" +
				//				"<td>" + desconto + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='deleta_produtonfe(" + codigo + ")' id='but_delete'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#nfeProdutoItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='19'>Sem registro.</td>" +
			"</tr>";
		$("#nfeProdutoItens tbody").append(tr_str);
	}
}
function adicionarProdutoNfe(percicmsdevol, bcicmsdevol) {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'adicionarProdutoNfe',
			nf_numero: document.getElementById('documento').value,
			tipo: document.getElementById('tipo').value,
			finalidade: document.getElementById('finalidade').value,
			nf_serie: document.getElementById('serie').value,
			cst_cson: document.getElementById('nfe_prod_st').value,
			cfop: document.getElementById('nfe_prod_cfop').value,
			qtde: document.getElementById('nfe_prod_qtde').value,
			complemento: document.getElementById('nfe_prod_complemento').value,
			desconto: document.getElementById('nfe_prod_desconto').value,
			valor_unit: document.getElementById('nfe_prod_valunit').value,
			percicmsdevol: percicmsdevol,
			bcicmsdevol: bcicmsdevol,
		},
		dataType: 'json',
		success: function (response) {
			carregarImposto();
			createRowsItensNfe(response);
		}
	});
}
function deleta_produtonfe(codigo) {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'deletaProdutoNfe',
			codigo: codigo,
		},
		dataType: 'json',
		success: function (response) {
			createRowsItensNfe(response);
			carregarImposto();
		}
	});
}
function atribuirImposto(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var bc_icms = response[i].bc_icms;
			var val_icms = response[i].val_icms;
			var bc_icms_st = response[i].bc_icms_st;
			var val_icms_st = 0;
			val_icms_st = response[i].val_icms_st;
			var val_prod = response[i].val_prod;
			var val_ipi = response[i].val_ipi;
			var seguro = 0;
			seguro = document.getElementById('imposto_val_seguro').value;
			var frete = 0;;
			frete = document.getElementById('imposto_val_frete').value;
			var despesas = 0;;
			despesas = document.getElementById('imposto_val_despesas').value;
			var descontos = 0;
			descontos = document.getElementById('imposto_val_descontos').value;
			document.getElementById('imposto_bc_icms').value = bc_icms;
			document.getElementById('imposto_val_icms').value = val_icms;
			document.getElementById('imposto_bc_icms_st').value = bc_icms_st;
			document.getElementById('imposto_val_icms_st').value = val_icms_st;
			document.getElementById('imposto_val_produtos').value = val_prod;
			document.getElementById('imposto_val_ipi').value = val_ipi;
			document.getElementById('imposto_val_total').value = parseFloat(val_prod) + parseFloat(val_icms_st) + parseFloat(val_ipi) + parseFloat(seguro) + parseFloat(frete) + parseFloat(despesas) - parseFloat(descontos);
		}
	}
}
function carregarImposto() {
	nf_numero = document.getElementById('documento').value;
	nf_serie = document.getElementById('serie').value;
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'carregar_imposto',
			nf_numero: nf_numero,
			nf_serie: nf_serie,
		},
		dataType: 'json',
		success: function (response) {
			atribuirImposto(response);
		}
	});
}
function createRowsFat(response) {
	var len = 0;
	$('#nfeFatItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_interno = response[i].codigo_interno;
			var numero = response[i].numero;
			var vencimento = response[i].vencimento;
			var valor = response[i].valor;
			var tr_str = "<tr>" +
				"<td>" + numero + "</td>" +
				"<td>" + vencimento + "</td>" +
				"<td>" + valor + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='deletar_fat(" + codigo_interno + ")' id='but_edit'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#nfeFatItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td></td><td></td><td></td>" +
			"</tr>";
		$("#nfeFatItens tbody").append(tr_str);
	}
}
function deletar_fat(codigo_interno) {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'deletar_fat',
			codigo_interno: codigo_interno,
		},
		dataType: 'json',
		success: function (response) {
			consultarFatura();
		}
	});
}
function cadastro_transportadora(codigo) {
	$("#nfe_transportadora_pesquisa").hide();
	$("#nfe_transportadora").show();
	if (codigo > 0) {
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirTransportadora',
				codigo: codigo
			},
			dataType: 'json',
			success: function (response) {
				carregarTransportadora(response);
			}
		});
	}
}
function carregarTransportadora(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var tra_codigo = response[i].codigo;
			var tra_razao_social = response[i].razao_social;
			var tra_cpf_cnpj = response[i].cpf_cnpj;
			var tra_logradouro = response[i].logradouro;
			var tra_numero = response[i].numero;
			var tra_uf = response[i].uf;
			var tra_uf_desc = response[i].uf_desc;
			var tra_municipio = response[i].municipio;
			var tra_municipio_desc = response[i].municipio_desc;
			var tra_inscricao_rg = response[i].inscricao_rg;
			document.getElementById('nfe_tra_codigo').value = tra_codigo;
			document.getElementById('nfe_tra_razao_social').value = tra_razao_social;
			document.getElementById('nfe_tra_cnpj').value = tra_cpf_cnpj;
			document.getElementById('nfe_tra_logradouro').value = tra_logradouro + " " + tra_numero;
			document.getElementById('nfe_tra_uf_desc').value = tra_uf_desc;
			document.getElementById('nfe_tra_municipio_desc').value = tra_municipio_desc;
			document.getElementById('nfe_tra_inscricao_rg').value = tra_inscricao_rg;
		}
	}
}
function gravarTransportadoraNfe() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'gravarTransportadoraNfe',
			nf_numero: document.getElementById('documento').value,
			nf_serie: document.getElementById('serie').value,
			cod_transportadora: document.getElementById('nfe_tra_codigo').value,
			por_conta: document.getElementById('nfe_tran_frete').value,
			placa: document.getElementById('nfe_tra_placa').value,
			cod_antt: document.getElementById('nfe_tra_cod_antt').value,
			placa_uf: document.getElementById('nfe_tra_placa_uf').value,
			quantidade: document.getElementById('nfe_tra_qtde').value,
			especie: document.getElementById('nfe_tra_especie').value,
			marca: document.getElementById('nfe_tra_marca').value,
			numeracao: document.getElementById('nfe_tra_numeracao').value,
			peso_liquido: document.getElementById('nfe_tra_pesoliquido').value,
			peso_bruto: document.getElementById('nfe_tra_pesobruto').value,
		},
		dataType: 'json',
		success: function (response) {
			console.log(response);
		}
	});
}
function limparTransportadoraNfe() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'limparTransportadoraNfe',
			nf_numero: document.getElementById('documento').value,
			nf_serie: document.getElementById('serie').value,
		},
		dataType: 'json',
		success: function (response) {
			document.getElementById('nfe_tra_codigo').value = '';
			document.getElementById('nfe_tran_frete').value = 9;
			document.getElementById('nfe_tra_placa').value = '';
			document.getElementById('nfe_tra_cod_antt').value = '';
			document.getElementById('nfe_tra_placa_uf').value = '';
			document.getElementById('nfe_tra_qtde').value = '';
			document.getElementById('nfe_tra_especie').value = '';
			document.getElementById('nfe_tra_marca').value = '';
			document.getElementById('nfe_tra_numeracao').value = '';
			document.getElementById('nfe_tra_pesoliquido').value = '';
			document.getElementById('nfe_tra_pesobruto').value = '';
			document.getElementById('nfe_tra_razao_social').value = '';
			document.getElementById('nfe_tra_cnpj').value = '';
			document.getElementById('nfe_tra_inscricao_rg').value = '';
			document.getElementById('nfe_tra_logradouro').value = '';
			document.getElementById('nfe_tra_uf_desc').value = '';
			document.getElementById('nfe_tra_municipio_desc').value = '';
		}
	});
}
function gravarDocRefNfe() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'gravarDocRefNfe',
			nf_numero: document.getElementById('documento').value,
			nf_serie: document.getElementById('serie').value,
			nfe_referencia_chave: document.getElementById('nfe_referencia_chave').value
		},
		dataType: 'json',
		success: function (response) {
		}
	});
}
function limparDocRefNfe() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'limparDocRefNfe',
			nf_numero: document.getElementById('documento').value,
			nf_serie: document.getElementById('serie').value,
		},
		dataType: 'json',
		success: function (response) {
			document.getElementById('nfe_referencia_chave').value = '';
		}
	});
}
function gravarInfAdicional() {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'gravarInfAdicional',
			documento: document.getElementById('documento').value,
			serie: document.getElementById('serie').value,
			nfe_inf_adicional: document.getElementById('nfe_inf_adicional').value,
			nfe_inf_adicional2: document.getElementById('nfe_inf_adicional2').value,
		},
		dataType: 'json',
		success: function (response) {
		}
	});
}
function corrigirNfe(codigo_interno) {
	$('#modalCorrecao').modal();
	$('#modalCorrecao').modal('open');
	document.getElementById('codigo_interno_corr').value = codigo_interno;
}
function efetuarCorrecao(correcao, codigo_interno) {
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'efetuarCorrecao',
			correcao: correcao,
			codigo_interno: codigo_interno,
		},
		dataType: 'json',
		success: function (response) {
			alert(response);
		}
	});
}
function gerarXML(data1, data2) {
	$.ajax({
		url: 'nfe_ajax.php',
		type: 'post',
		data: {
			request: 'gerarXML',
			data1: data1,
			data2: data2,
		},
		dataType: 'json',
		success: function (response) {
			if (response == "OK") {
				window.location.href = "file.zip";
			}
		}
	});
}

function clickPaginaProduto(valor) {
	const val = document.getElementById('desc_pesquisa_produto').value;
	console.log(valor);
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: { request: 'fetchalldavprodutos', pagina: (valor -1) * 50, desc_pesquisa_produto: val },
		dataType: 'json',
		success: function (response) {
			createRowsNfeProduto(response);
		},
		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
}