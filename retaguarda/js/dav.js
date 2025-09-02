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
function pesquisarClienteDav() {
	$("#dav_cliente").hide();
	$("#dav_cliente_pesquisa").show();
}
$(document).ready(function() {
	$("#dav_principal").show();
	$("#dav_cadastro").hide();
	$("#dav_cliente").hide();
	$("#dav_cliente_pesquisa").show();
	$("#dav_produto").show();
	$("#dav_produto_pesquisa").hide();
	carregar_vendedores();
	$('#but_fetchall').click(function() {
		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		const data1 = document.getElementById('data1').value;
		const data2 = document.getElementById('data2').value;
		$.ajax({
			url: 'dav_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val,data1 : data1, data2: data2 },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			}
		});
	});
	}
);

function cadastro_dav(codigo) {
	$("#dav_principal").hide();
	$("#dav_cadastro").show();

	//Se for edicao de nfe
	if (codigo > 0) {
		$.ajax({
			url: 'dav_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirDav',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarDav(response);
			}
		});
	}else{
		limparDav();
	}
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
			var codigo= response[i].codigo;
			var data = response[i].data;
			var hora = response[i].hora;
			var nome = response[i].nome;
			var total = response[i].total;
				var tr_str = "<tr>" +
					"<td>" + codigo + "</td>" +
					"<td>" + data + "</td>" +
					"<td>" + hora + "</td>" +
					"<td>" + nome+ "</td>" +
					"<td>" + total + "</td>" +
					"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_dav(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
					"</tr>";
			$("#userTable tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#userTable tbody").append(tr_str);
	}
}
$('#but_fetch_cliente').click(function() {
	// AJAX GET request
	const valor = document.getElementById('desc_pesquisa_cliente').value;
	console.log(valor);
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: { request: 'fetchallclientes', pagina: 0, desc_pesquisa_cliente: valor },
		dataType: 'json',
		success: function(response) {
			createRowsCliente(response);
		},
		error: function(jqxhr, status, exception) {
			alert(exception);
		}
	});
});

$('#but_fetch_dav_produto').click(function () {
	// AJAX GET request
	const valor = document.getElementById('desc_pesquisa_produto').value;
	console.log(valor);
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: { request: 'fetchalldavprodutos', pagina: 0, desc_pesquisa_produto: valor },
		dataType: 'json',
		success: function (response) {
			createRowsDavProduto(response);
		},
		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
});

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
function cadastro_cliente(codigo) {
	$("#dav_cliente_pesquisa").hide();
	$("#dav_cliente").show();
	if (codigo > 0) {
		$.ajax({
			url: 'nfe_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirCliente',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
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
function retornarClienteDav() {
	$('#userTableCliente tbody').empty();
	$("#dav_cliente").hide();
	$("#dav_cliente_pesquisa").show();
}

function pesquisarProdutoDav() {
	$("#dav_produto").hide();
	$("#dav_produto_pesquisa").show();
}

function createRowsDavProduto(response) {
	var len = 0;
	$('#userTableDavProduto tbody').empty(); // Empty <tbody>
	$("#paginacaoDavProduto").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPaginaProduto(" + pagina + ");' />";
			$("#paginacaoDavProduto").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='selecionarProdutoDav(" + codigo_gtin + ")' id='but_edit'><i class='material-icons'>arrow_right_alt</i></a></td>" +
				"</tr>";
			$("#userTableDavProduto tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td></td><td></td><td></td>" +
			"</tr>";
		$("#userTableDavProduto tbody").append(tr_str);
	}
}

function retornarDavProduto() {
	$("#dav_produto").show();
	$("#dav_produto_pesquisa").hide();
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
function clickPaginaProduto(valor) {
	const val = document.getElementById('desc_pesquisa_produto').value;
	console.log(valor);
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: { request: 'fetchalldavprodutos', pagina: (valor -1) * 50, desc_pesquisa_produto: val },
		dataType: 'json',
		success: function (response) {
			createRowsDavProduto(response);
		},
		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
}

function selecionarProdutoDav(codigo) {
	$("#dav_produto_pesquisa").hide();
	$("#dav_produto").show();
	//Se for  
	if (codigo > 0) {
		$.ajax({
			url: 'dav_ajax.php',
			type: 'post',
			data: {
				request: 'pegarDadosProdutoSelecionado',
				codigo_gtin: codigo,
			},
			dataType: 'json',
			success: function (response) {
				carregarItensProdutoDav(response);
			}
		});
	}
}

function carregarItensProdutoDav(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_gtin = response[i].codigo_gtin;
			var descricao = response[i].descricao;
			var preco_venda = response[i].preco_venda;
			document.getElementById('dav_prod_codigo').value = codigo_gtin;
			document.getElementById('dav_prod_descricao').value = descricao;
			document.getElementById('dav_prod_valunit').value = preco_venda;
			document.getElementById('dav_prod_complemento').focus();
		}
	}
}

function adicionarProdutoOrcamento(){
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: {
			request: 'adicionarProdutoOrcamento',
			codigo: document.getElementById('dav_codigo').value,
			vendedor: document.getElementById('dav_vendedor').value,
			cliente: document.getElementById('cli_codigo').value,
			codigo_gtin: document.getElementById('dav_prod_codigo').value,
			descricao: document.getElementById('dav_prod_descricao').value,
			complemento: document.getElementById('dav_prod_complemento').value,
			qtde: document.getElementById('dav_prod_qtde').value,
			valor_unit: document.getElementById('dav_prod_valunit').value
		},
		dataType: 'json',
		success: function (response) {
			createRowsItensDav(response);
			calcularTotalDav();
		},
		error: function (jqxhr, status, exception) {
			alert(exception);
		}
	});
}

function createRowsItensDav(response) {
	var len = 0;
	$('#davProdutoItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			document.getElementById('dav_codigo').value = response[i].codigo;
			if(response[i].data.length >2 ){
				document.getElementById('dataEmissao').value = response[i].data;
				document.getElementById('horaEmissao').value = response[i].hora;
			}
			
			
			var codigo_interno=response[i].codigo_interno;
			var codigo_gtin=response[i].codigo_gtin;
			var descricao=response[i].descricao;
			var quantidade=response[i].quantidade;
			var preco_venda=response[i].preco_venda;
			var total=response[i].total;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td>" + quantidade + "</td>" +
				"<td>" + preco_venda + "</td>" +
				"<td>" + total + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='deleta_produtodav(" + codigo_interno + ")' id='but_delete'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#davProdutoItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='19'>Sem registro.</td>" +
			"</tr>";
		$("#davProdutoItens tbody").append(tr_str);
	}
}
function deleta_produtodav(codigo) {
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: {
			request: 'deletaProdutoDav',
			codigo: codigo,
		},
		dataType: 'json',
		success: function (response) {
			createRowsItensDav2(response);
			calcularTotalDav();
			
		}
	});
}
function createRowsItensDav2(response) {
	var len = 0;
	$('#davProdutoItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_interno=response[i].codigo_interno;
			var codigo_gtin=response[i].codigo_gtin;
			var descricao=response[i].descricao;
			var quantidade=response[i].quantidade;
			var preco_venda=response[i].preco_venda;
			var total=response[i].total;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td>" + quantidade + "</td>" +
				"<td>" + preco_venda + "</td>" +
				"<td>" + total + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='deleta_produtodav(" + codigo_interno + ")' id='but_delete'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#davProdutoItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='19'>Sem registro.</td>" +
			"</tr>";
		$("#davProdutoItens tbody").append(tr_str);
	}
}


function gravarDav() {
	var dav_observacao=document.getElementById('dav_observacao').value;
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: {
			request: 'gravarDav',
			dav_codigo: document.getElementById('dav_codigo').value,
			cliente: document.getElementById('cli_codigo').value,
			vendedor: document.getElementById('dav_vendedor').value,
			dav_observacao: dav_observacao,
			dav_desconto: document.getElementById('dav_desconto').value
		},
		dataType: 'json',
		success: function (response) {
			calcularTotalDav();
		}
	});
}
function carregar_vendedores() {
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: { request: 'carregar_vendedores' },
		dataType: 'json',
		success: function(response) {
			carregarVendedores(response);
		}
	});
}
function carregarVendedores(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo_vendedor = response[i].codigo_vendedor;
			var nome = response[i].nome;
			var option_str = "<option value='" + codigo_vendedor + "'>" + nome + "</option>";
			$("#dav_vendedor").append(option_str);
		}
	}
}
function calcularTotalDav(){
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: {
			request: 'calcularTotalDav',
			dav_codigo: document.getElementById('dav_codigo').value
		},
		dataType: 'json',
		success: function (response) {
			document.getElementById('dav_subtotal').value=response[0].subtotal;
			document.getElementById('dav_desconto').value=response[0].desconto;
			document.getElementById('dav_total').value=response[0].total;
			
		}
	});
}
function imprimir(){
	var codigo=document.getElementById('dav_codigo').value;
	window.open("relatorios/orcamento.php?codigo="+codigo);
}

function carregarDav(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var dataEmissao = response[i].data;
			var horaEmissao = response[i].hora;
			var vendedor = response[i].vendedor;
			var cliente = response[i].cliente;
			var obs = response[i].obs;
			document.getElementById('dav_codigo').value = codigo;
			document.getElementById('dataEmissao').value = dataEmissao;
			document.getElementById('horaEmissao').value = horaEmissao;
			document.getElementById('dav_vendedor').value = vendedor; 
			document.getElementById('dav_observacao').value = obs; 
			cadastro_cliente(cliente);
			carregarProdutoDav(codigo);
			calcularTotalDav();
		}
	}
}

function retornarPrincipal() {
	limparDav();
	$('#userTable tbody').empty();
	$("#dav_principal").show();
	$("#dav_cadastro").hide();
}

function limparDav(){
	document.getElementById('dav_codigo').value = '';
	document.getElementById('dataEmissao').value = '';
	document.getElementById('horaEmissao').value = '';
	carregar_vendedores();
	document.getElementById('cli_codigo').value = '';
	document.getElementById('cli_razao_social').value = '';
	document.getElementById('cli_cpf_cnpj').value = '';
	document.getElementById('cli_logradouro').value = '';
	document.getElementById('cli_numero').value = '';
	document.getElementById('cli_complemento').value = '';
	document.getElementById('cli_bairro').value = '';
	document.getElementById('cli_cep').value = '';
	document.getElementById('cli_inscricao_rg').value = '';
	document.getElementById('cli_municipio').value = '';
	document.getElementById('cli_municipio_desc').value = '';
	document.getElementById('cli_uf').value = '';
	document.getElementById('cli_uf_desc').value = '';
	document.getElementById('cli_fone').value = '';
	$('#userTable tbody').empty();
	$("#paginacao").empty()
	/* $('#userTableCliente tbody').empty()
	$("#paginacaoCliente").empty(); */
	$('#userTableDavProduto tbody').empty(); // Empty <tbody>
	$('#davProdutoItens tbody').empty(); // Empty <tbody>
	
	$("#paginacaoDavProduto").empty();
	$("#dav_vendedor").empty();
	document.getElementById('dav_observacao').value = '';
	document.getElementById('dav_subtotal').value = '';
	document.getElementById('dav_desconto').value = '';
	document.getElementById('dav_total').value = '';
	$("#dav_cliente").hide();
	$("#dav_cliente_pesquisa").show();
	$("#dav_produto").show();
	$("#dav_produto_pesquisa").hide();
	
}

function carregarProdutoDav(codigo) {
	$.ajax({
		url: 'dav_ajax.php',
		type: 'post',
		data: {
			request: 'carregarProdutoDav',
			codigo: codigo
		},
		dataType: 'json',
		success: function (response) {
			createRowsItensDav2(response);
		}
	});
}