<<<<<<< HEAD
<<<<<<< HEAD

function buscarDescCodPromo() {

	var codigo_gtin = document.getElementById('promocao_prod_codigo').value;

	if (codigo_gtin == 0) {
		document.getElementById('promocao_prod_codigo').focus();
	} else {
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: {
				request: 'consultarProduto',
				codigo_gtin: codigo_gtin,
			},
			dataType: 'json',
			success: function(response) {
				document.getElementById('promocao_prod_descricao').value=response[0].descricao;
			}
		});
		console.log(codigo_gtin);
	}
}
function remove_item_promocao(codigo_gtin){
		$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'remover_item_promocao',
			codigo_gtin: codigo_gtin,
		},
		dataType: 'json',
		success: function(response) {
			if(response!="OK"){
				alert(response);
			}
			carregarItensPromocao();
		}
	});
}

function gravar_promocao() {
	var nome = document.getElementById('nome').value;
	var qtde = document.getElementById('qtde').value;
	var preco = document.getElementById('preco').value;
	var promocao_prod_codigo = document.getElementById('promocao_prod_codigo').value;
	var promocao_prod_descricao= document.getElementById('promocao_prod_descricao').value;

	$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarPromocao',
			nome: nome,
			qtde: qtde,
			preco: preco,
			promocao_prod_codigo: promocao_prod_codigo,
			promocao_prod_descricao: promocao_prod_descricao,
		},
		dataType: 'json',
		success: function(response) {
			if(response!="OK"){
				alert(response);
			}
			document.getElementById('promocao_prod_codigo').value = '';
			document.getElementById('promocao_prod_descricao').value = '';
			document.getElementById('promocao_prod_codigo').focus();
			carregarItensPromocao();
		}
	});
}

function carregarItensPromocao(){
		var nome=document.getElementById('nome').value;

		$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'carregarItensPromocao',
			nome: nome,
		},
		dataType: 'json',
		success: function(response) {
			createRowsPromocao(response);
		}
	});
}

function retornarPrincipal() {
	limparPromocao();
	$('#userTable tbody').empty();
	$('#promocaoProdutoItens tbody').empty();
	$("#promocao_principal").show();
	$("#promocao_cadastro").hide();
}


$(document).ready(function() {
	$("#promocao_principal").show();
	$("#promocao_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			}
		});
	});
}
);
function remove_promocao(nome){
	nome=nome.replace(/_/g," ");
	$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: { 
			request: 'remover_promocao',
			nome: nome 
		},
		dataType: 'json',
		success: function(response) {
			carregarPromocoes();
		}
	});
}

function carregarPromocoes(){
	const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			}
		});
}

function cadastro_promocao(nome) {
	$("#promocao_principal").hide();
	$("#promocao_cadastro").show();
	
	if (nome.length > 0) {
		nome=nome.replace(/_/g," ");
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirPromocoes',
				nome: nome,
			},
			dataType: 'json',
			success: function(response) {
				limparPromocao();
				carregarPromocoesCadastro(response);
				carregarItensPromocao();
			}
		});
	}
}

function carregarPromocoesCadastro(response){
	var len = 0;
	if (response != null) {
		len = response.length;
	}

	if (len > 0) {
		 document.getElementById('nome').value=response[0].nome;
		 document.getElementById('qtde').value=response[0].qtde;
		 document.getElementById('preco').value=response[0].preco;
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'vendedores_ajax.php',
		type: 'post',
		data: { request: 'fetchall', pagina: (valor - 1) * 50, desc_pesquisa: val },
		dataType: 'json',
		success: function(response) {
			createRows(response);
		}
	});
}

function createRowsPromocao(response) {
	var len = 0;
	$('#promocaoProdutoItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}

	if (len > 0) {


		for (var i = 0; i < len; i++) {
			var codigo_gtin=response[i].codigo_gtin ;
			var descricao=response[i].descricao ;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='remove_item_promocao(" + codigo_gtin + ")' id='but_remove'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#promocaoProdutoItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#promocaoProdutoItens tbody").append(tr_str);
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
			var codigo = response[i].codigo;
			var nome = response[i].nome;
			var nome_=nome.replace(/ /g,"_");
			var rm="remove_promocao('"+nome_+"')";
			var adic="cadastro_promocao('"+nome_+"')";
			var tr_str = "<tr>" +
				"<td>" + nome + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick="+adic+" id='but_edit'><i class='material-icons'>edit</i></a>&nbsp;&nbsp;<a class='btn-floating btn-small waves-effect grey' onClick="+rm+" id='but_remove'><i class='material-icons'>delete</i></a></td>" +
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

function verificarCodigo() {
	var codigo = document.getElementById('codigo').value;
	if (codigo == 0) {
		
	} else {
		$.ajax({
			url: 'vendedores_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoVendedor',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				var len = 0;
				if (response != null) {
					len = response.length;
				}
				for (var i = 0; i < len; i++) {
					var codigo = response[i].codigo;
					cadastro_vendedor(codigo);
				}
			}
		});
		console.log(codigo);
	}
}

function limparPromocao() {
	document.getElementById('nome').value = '';
	document.getElementById('qtde').value = '';
	document.getElementById('preco').value = '';
	document.getElementById('promocao_prod_codigo').value = '';
	document.getElementById('promocao_prod_descricao').value = '';
	$('#promocaoProdutoItens tbody').empty();
}
function carregarVendedores(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('nome').value = response[i].nome;
		document.getElementById('comissao').value = response[i].comissao;
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		document.getElementById('desc_max').value = response[i].desc_max;
	}
}
=======
=======
>>>>>>> 02873dae92d94b56acc454402418f6edbeae1cea

function buscarDescCodPromo() {

	var codigo_gtin = document.getElementById('promocao_prod_codigo').value;

	if (codigo_gtin == 0) {
		document.getElementById('promocao_prod_codigo').focus();
	} else {
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: {
				request: 'consultarProduto',
				codigo_gtin: codigo_gtin,
			},
			dataType: 'json',
			success: function(response) {
				document.getElementById('promocao_prod_descricao').value=response[0].descricao;
			}
		});
		console.log(codigo_gtin);
	}
}
function remove_item_promocao(codigo_gtin){
		$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'remover_item_promocao',
			codigo_gtin: codigo_gtin,
		},
		dataType: 'json',
		success: function(response) {
			if(response!="OK"){
				alert(response);
			}
			carregarItensPromocao();
		}
	});
}

function gravar_promocao() {
	var nome = document.getElementById('nome').value;
	var qtde = document.getElementById('qtde').value;
	var preco = document.getElementById('preco').value;
	var promocao_prod_codigo = document.getElementById('promocao_prod_codigo').value;
	var promocao_prod_descricao= document.getElementById('promocao_prod_descricao').value;

	$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarPromocao',
			nome: nome,
			qtde: qtde,
			preco: preco,
			promocao_prod_codigo: promocao_prod_codigo,
			promocao_prod_descricao: promocao_prod_descricao,
		},
		dataType: 'json',
		success: function(response) {
			if(response!="OK"){
				alert(response);
			}
			document.getElementById('promocao_prod_codigo').value = '';
			document.getElementById('promocao_prod_descricao').value = '';
			document.getElementById('promocao_prod_codigo').focus();
			carregarItensPromocao();
		}
	});
}

function carregarItensPromocao(){
		var nome=document.getElementById('nome').value;

		$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: {
			request: 'carregarItensPromocao',
			nome: nome,
		},
		dataType: 'json',
		success: function(response) {
			createRowsPromocao(response);
		}
	});
}

function retornarPrincipal() {
	limparPromocao();
	$('#userTable tbody').empty();
	$('#promocaoProdutoItens tbody').empty();
	$("#promocao_principal").show();
	$("#promocao_cadastro").hide();
}


$(document).ready(function() {
	$("#promocao_principal").show();
	$("#promocao_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			}
		});
	});
}
);
function remove_promocao(nome){
	nome=nome.replace(/_/g," ");
	$.ajax({
		url: 'promocao_ajax.php',
		type: 'post',
		data: { 
			request: 'remover_promocao',
			nome: nome 
		},
		dataType: 'json',
		success: function(response) {
			carregarPromocoes();
		}
	});
}

function carregarPromocoes(){
	const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			}
		});
}

function cadastro_promocao(nome) {
	$("#promocao_principal").hide();
	$("#promocao_cadastro").show();
	
	if (nome.length > 0) {
		nome=nome.replace(/_/g," ");
		$.ajax({
			url: 'promocao_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirPromocoes',
				nome: nome,
			},
			dataType: 'json',
			success: function(response) {
				limparPromocao();
				carregarPromocoesCadastro(response);
				carregarItensPromocao();
			}
		});
	}
}

function carregarPromocoesCadastro(response){
	var len = 0;
	if (response != null) {
		len = response.length;
	}

	if (len > 0) {
		 document.getElementById('nome').value=response[0].nome;
		 document.getElementById('qtde').value=response[0].qtde;
		 document.getElementById('preco').value=response[0].preco;
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'vendedores_ajax.php',
		type: 'post',
		data: { request: 'fetchall', pagina: (valor - 1) * 50, desc_pesquisa: val },
		dataType: 'json',
		success: function(response) {
			createRows(response);
		}
	});
}

function createRowsPromocao(response) {
	var len = 0;
	$('#promocaoProdutoItens tbody').empty(); // Empty <tbody>
	if (response != null) {
		len = response.length;
	}

	if (len > 0) {


		for (var i = 0; i < len; i++) {
			var codigo_gtin=response[i].codigo_gtin ;
			var descricao=response[i].descricao ;
			var tr_str = "<tr>" +
				"<td>" + codigo_gtin + "</td>" +
				"<td>" + descricao + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='remove_item_promocao(" + codigo_gtin + ")' id='but_remove'><i class='material-icons'>delete</i></a></td>" +
				"</tr>";
			$("#promocaoProdutoItens tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#promocaoProdutoItens tbody").append(tr_str);
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
			var codigo = response[i].codigo;
			var nome = response[i].nome;
			var nome_=nome.replace(/ /g,"_");
			var rm="remove_promocao('"+nome_+"')";
			var adic="cadastro_promocao('"+nome_+"')";
			var tr_str = "<tr>" +
				"<td>" + nome + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick="+adic+" id='but_edit'><i class='material-icons'>edit</i></a>&nbsp;&nbsp;<a class='btn-floating btn-small waves-effect grey' onClick="+rm+" id='but_remove'><i class='material-icons'>delete</i></a></td>" +
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

function verificarCodigo() {
	var codigo = document.getElementById('codigo').value;
	if (codigo == 0) {
		
	} else {
		$.ajax({
			url: 'vendedores_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoVendedor',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				var len = 0;
				if (response != null) {
					len = response.length;
				}
				for (var i = 0; i < len; i++) {
					var codigo = response[i].codigo;
					cadastro_vendedor(codigo);
				}
			}
		});
		console.log(codigo);
	}
}

function limparPromocao() {
	document.getElementById('nome').value = '';
	document.getElementById('qtde').value = '';
	document.getElementById('preco').value = '';
	document.getElementById('promocao_prod_codigo').value = '';
	document.getElementById('promocao_prod_descricao').value = '';
	$('#promocaoProdutoItens tbody').empty();
}
function carregarVendedores(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('nome').value = response[i].nome;
		document.getElementById('comissao').value = response[i].comissao;
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		document.getElementById('desc_max').value = response[i].desc_max;
	}
}
