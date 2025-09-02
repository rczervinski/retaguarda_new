<<<<<<< HEAD
<<<<<<< HEAD

function gravarVendedores() {
	
	var codigo = document.getElementById('codigo').value
	var nome = document.getElementById('nome').value;
	var comissao = document.getElementById('comissao').value;
	var inativo = document.getElementById('inativo').checked;
	var desc_max = document.getElementById('desc_max').value;
	var senha = document.getElementById('senha').value;
	
	$.ajax({
		url: 'vendedores_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarVendedores',
			codigo: codigo,
			nome: nome,
			comissao: comissao,
			inativo: inativo,
			desc_max: desc_max,
			senha: senha,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparVendedor();
			$('#userTable tbody').empty();
			$("#vendedor_principal").show();
			$("#vendedor_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparVendedor();
	$('#userTable tbody').empty();
	$("#vendedor_principal").show();
	$("#vendedor_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#vendedor_principal").show();
	$("#vendedor_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'vendedores_ajax.php',
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

function cadastro_vendedor(codigo) {
	$("#vendedor_principal").hide();
	$("#vendedor_cadastro").show();
	//Se for 
	if (codigo > 0) {
		$.ajax({
			url: 'vendedores_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirVendedores',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarVendedores(response);
			}
		});
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
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + nome + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_vendedor(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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

function limparVendedor() {
	document.getElementById('codigo').value = '';
	document.getElementById('nome').value = '';
	document.getElementById('comissao').value = '';
	document.getElementById('inativo').checked = false;
	document.getElementById('desc_max').value = '';
	document.getElementById('senha').value = '';
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
		document.getElementById('senha').value = response[i].senha;
	}
}
=======
=======
>>>>>>> 02873dae92d94b56acc454402418f6edbeae1cea

function gravarVendedores() {
	
	var codigo = document.getElementById('codigo').value
	var nome = document.getElementById('nome').value;
	var comissao = document.getElementById('comissao').value;
	var inativo = document.getElementById('inativo').checked;
	var desc_max = document.getElementById('desc_max').value;
	var senha = document.getElementById('senha').value;
	
	$.ajax({
		url: 'vendedores_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarVendedores',
			codigo: codigo,
			nome: nome,
			comissao: comissao,
			inativo: inativo,
			desc_max: desc_max,
			senha: senha,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparVendedor();
			$('#userTable tbody').empty();
			$("#vendedor_principal").show();
			$("#vendedor_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparVendedor();
	$('#userTable tbody').empty();
	$("#vendedor_principal").show();
	$("#vendedor_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#vendedor_principal").show();
	$("#vendedor_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'vendedores_ajax.php',
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

function cadastro_vendedor(codigo) {
	$("#vendedor_principal").hide();
	$("#vendedor_cadastro").show();
	//Se for 
	if (codigo > 0) {
		$.ajax({
			url: 'vendedores_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirVendedores',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarVendedores(response);
			}
		});
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
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + nome + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_vendedor(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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

function limparVendedor() {
	document.getElementById('codigo').value = '';
	document.getElementById('nome').value = '';
	document.getElementById('comissao').value = '';
	document.getElementById('inativo').checked = false;
	document.getElementById('desc_max').value = '';
	document.getElementById('senha').value = '';
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
		document.getElementById('senha').value = response[i].senha;
	}
}
