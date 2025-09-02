<<<<<<< HEAD
<<<<<<< HEAD

function gravarUsuarios() {
	
	var codigo = document.getElementById('codigo').value
	var usuario = document.getElementById('usuario').value;
	var senha = document.getElementById('senha').value;
	
	var operador = document.getElementById('operador').checked;
	var gerente = document.getElementById('gerente').checked;
	var supervisor = document.getElementById('supervisor').checked;
	var per_produtos = document.getElementById('per_produtos').checked;
	var per_clientes = document.getElementById('per_clientes').checked;
	var per_fornecedores = document.getElementById('per_fornecedores').checked;
	var per_transportadoras = document.getElementById('per_transportadoras').checked;
	var per_vendedores = document.getElementById('per_vendedores').checked;
	var per_usuarios = document.getElementById('per_usuarios').checked;
	var per_entregas = document.getElementById('per_entregas').checked;
	var per_compradores = document.getElementById('per_compradores').checked;
	var per_nfe = document.getElementById('per_nfe').checked;
	var per_orcamentos = document.getElementById('per_orcamentos').checked;
	var per_pedidos = document.getElementById('per_pedidos').checked;
	var per_compras = document.getElementById('per_compras').checked;
	var per_contas_pagar = document.getElementById('per_contas_pagar').checked;
	var per_contas_receber = document.getElementById('per_contas_receber').checked;
	var per_relatorios = document.getElementById('per_relatorios').checked;
	var per_configuracao = document.getElementById('per_configuracao').checked;
	var per_produtos_con = document.getElementById('per_produtos_con').checked;
	var inativo = document.getElementById('inativo').checked;
	

	$.ajax({
		url: 'usuarios_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarUsuarios',
			codigo: codigo,
			usuario: usuario,
			senha: senha,
			operador: operador,
			gerente: gerente,
			supervisor: supervisor,
			per_produtos: per_produtos,
			per_clientes: per_clientes,
			per_fornecedores: per_fornecedores,
			per_transportadoras: per_transportadoras,
			per_vendedores: per_vendedores,
			per_usuarios: per_usuarios,
			per_entregas: per_entregas,
			per_compradores: per_compradores,
			per_nfe: per_nfe,
			per_orcamentos: per_orcamentos,
			per_pedidos: per_pedidos,
			per_compras: per_compras,
			per_contas_pagar: per_contas_pagar,
			per_contas_receber: per_contas_receber,
			per_relatorios: per_relatorios,
			per_configuracao: per_configuracao,
			per_produtos_con: per_produtos_con,
			inativo: inativo,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparUsuario();
			$('#userTable tbody').empty();
			$("#usuario_principal").show();
			$("#usuario_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparUsuario();
	$('#userTable tbody').empty();
	$("#usuario_principal").show();
	$("#usuario_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#usuario_principal").show();
	$("#usuario_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'usuarios_ajax.php',
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

function cadastro_usuario(codigo) {

	$("#usuario_principal").hide();
	$("#usuario_cadastro").show();
	//Se for 
	
	if (codigo > 0) {
		$.ajax({
			url: 'usuarios_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirUsuarios',
				codigo: codigo
			},
			dataType: 'json',
			success: function(response) {
				console.log(response);
				carregarUsuarios(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'usuarios_ajax.php',
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
			var usuario = response[i].usuario;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + usuario + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_usuario(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'usuarios_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoUsuario',
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
					cadastro_usuario(codigo);
				}
			}
		});
		console.log(codigo);
	}
}

function limparUsuario() {
	document.getElementById('codigo').value = '';
	document.getElementById('usuario').value = '';
	document.getElementById('senha').value = '';
	document.getElementById('operador').checked = false;
	document.getElementById('supervisor').checked = false;
	document.getElementById('per_produtos').checked = false;
	document.getElementById('per_clientes').checked = false;
	document.getElementById('per_fornecedores').checked = false;
	document.getElementById('per_transportadoras').checked = false;
	document.getElementById('per_vendedores').checked = false;
	document.getElementById('per_usuarios').checked = false;
	document.getElementById('per_entregas').checked = false;
	document.getElementById('per_compradores').checked = false;
	document.getElementById('per_nfe').checked = false;
	document.getElementById('per_orcamentos').checked = false;
	document.getElementById('per_pedidos').checked = false;
	document.getElementById('per_compras').checked = false;
	document.getElementById('per_contas_pagar').checked = false;
	document.getElementById('per_contas_receber').checked = false;
	document.getElementById('per_relatorios').checked = false;
	document.getElementById('per_configuracao').checked = false;
	document.getElementById('per_produtos_con').checked = false;
	document.getElementById('inativo').checked = false;
	
	
}
function carregarUsuarios(response) {

	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('usuario').value = response[i].usuario;
		document.getElementById('senha').value = response[i].senha;
		
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		
		if (response[i].operador == '1') {
			document.getElementById('operador').checked = true;
		} else {
			document.getElementById('operador').checked = false;
		}
		
			if (response[i].supervisor == '1') {
			document.getElementById('supervisor').checked = true;
		} else {
			document.getElementById('supervisor').checked = false;
		}
		
				if (response[i].per_produtos == '1') {
			document.getElementById('per_produtos').checked = true;
		} else {
			document.getElementById('per_produtos').checked = false;
		}
		
				if (response[i].per_clientes == '1') {
			document.getElementById('per_clientes').checked = true;
		} else {
			document.getElementById('per_clientes').checked = false;
		}
		
				if (response[i].per_fornecedores == '1') {
			document.getElementById('per_fornecedores').checked = true;
		} else {
			document.getElementById('per_fornecedores').checked = false;
		}
	
	    		if (response[i].per_transportadoras == '1') {
			document.getElementById('per_transportadoras').checked = true;
		} else {
			document.getElementById('per_transportadoras').checked = false;
		}
		
				if (response[i].per_vendedores == '1') {
			document.getElementById('per_vendedores').checked = true;
		} else {
			document.getElementById('per_vendedores').checked = false;
		}
		
				if (response[i].per_usuarios == '1') {
			document.getElementById('per_usuarios').checked = true;
		} else {
			document.getElementById('per_usuarios').checked = false;
		}
		
				if (response[i].per_entregas == '1') {
			document.getElementById('per_entregas').checked = true;
		} else {
			document.getElementById('per_entregas').checked = false;
		}
		
				if (response[i].per_compradores == '1') {
			document.getElementById('per_compradores').checked = true;
		} else {
			document.getElementById('per_compradores').checked = false;
		}
		
				if (response[i].per_nfe == '1') {
			document.getElementById('per_nfe').checked = true;
		} else {
			document.getElementById('per_nfe').checked = false;
		}
		
				if (response[i].per_orcamentos == '1') {
			document.getElementById('per_orcamentos').checked = true;
		} else {
			document.getElementById('per_orcamentos').checked = false;
		}
		
				if (response[i].per_pedidos == '1') {
			document.getElementById('per_pedidos').checked = true;
		} else {
			document.getElementById('per_pedidos').checked = false;
		}
		
				if (response[i].per_compras == '1') {
			document.getElementById('per_compras').checked = true;
		} else {
			document.getElementById('per_compras').checked = false;
		}
		
				if (response[i].per_contas_pagar == '1') {
			document.getElementById('per_contas_pagar').checked = true;
		} else {
			document.getElementById('per_contas_pagar').checked = false;
		}
		
				if (response[i].per_contas_receber == '1') {
			document.getElementById('per_contas_receber').checked = true;
		} else {
			document.getElementById('per_contas_receber').checked = false;
		}
		
				if (response[i].per_relatorios == '1') {
			document.getElementById('per_relatorios').checked = true;
		} else {
			document.getElementById('per_relatorios').checked = false;
		}
		
				if (response[i].per_configuracao == '1') {
			document.getElementById('per_configuracao').checked = true;
		} else {
			document.getElementById('per_configuracao').checked = false;
		}
		
				if (response[i].per_produtos_con == '1') {
			document.getElementById('per_produtos_con').checked = true;
		} else {
			document.getElementById('per_produtos_con').checked = false;
		}
	}
}
=======
=======
>>>>>>> 02873dae92d94b56acc454402418f6edbeae1cea

function gravarUsuarios() {
	
	var codigo = document.getElementById('codigo').value
	var usuario = document.getElementById('usuario').value;
	var senha = document.getElementById('senha').value;
	
	var operador = document.getElementById('operador').checked;
	var gerente = document.getElementById('gerente').checked;
	var supervisor = document.getElementById('supervisor').checked;
	var per_produtos = document.getElementById('per_produtos').checked;
	var per_clientes = document.getElementById('per_clientes').checked;
	var per_fornecedores = document.getElementById('per_fornecedores').checked;
	var per_transportadoras = document.getElementById('per_transportadoras').checked;
	var per_vendedores = document.getElementById('per_vendedores').checked;
	var per_usuarios = document.getElementById('per_usuarios').checked;
	var per_entregas = document.getElementById('per_entregas').checked;
	var per_compradores = document.getElementById('per_compradores').checked;
	var per_nfe = document.getElementById('per_nfe').checked;
	var per_orcamentos = document.getElementById('per_orcamentos').checked;
	var per_pedidos = document.getElementById('per_pedidos').checked;
	var per_compras = document.getElementById('per_compras').checked;
	var per_contas_pagar = document.getElementById('per_contas_pagar').checked;
	var per_contas_receber = document.getElementById('per_contas_receber').checked;
	var per_relatorios = document.getElementById('per_relatorios').checked;
	var per_configuracao = document.getElementById('per_configuracao').checked;
	var per_produtos_con = document.getElementById('per_produtos_con').checked;
	var inativo = document.getElementById('inativo').checked;
	

	$.ajax({
		url: 'usuarios_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarUsuarios',
			codigo: codigo,
			usuario: usuario,
			senha: senha,
			operador: operador,
			gerente: gerente,
			supervisor: supervisor,
			per_produtos: per_produtos,
			per_clientes: per_clientes,
			per_fornecedores: per_fornecedores,
			per_transportadoras: per_transportadoras,
			per_vendedores: per_vendedores,
			per_usuarios: per_usuarios,
			per_entregas: per_entregas,
			per_compradores: per_compradores,
			per_nfe: per_nfe,
			per_orcamentos: per_orcamentos,
			per_pedidos: per_pedidos,
			per_compras: per_compras,
			per_contas_pagar: per_contas_pagar,
			per_contas_receber: per_contas_receber,
			per_relatorios: per_relatorios,
			per_configuracao: per_configuracao,
			per_produtos_con: per_produtos_con,
			inativo: inativo,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparUsuario();
			$('#userTable tbody').empty();
			$("#usuario_principal").show();
			$("#usuario_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparUsuario();
	$('#userTable tbody').empty();
	$("#usuario_principal").show();
	$("#usuario_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#usuario_principal").show();
	$("#usuario_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'usuarios_ajax.php',
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

function cadastro_usuario(codigo) {

	$("#usuario_principal").hide();
	$("#usuario_cadastro").show();
	//Se for 
	
	if (codigo > 0) {
		$.ajax({
			url: 'usuarios_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirUsuarios',
				codigo: codigo
			},
			dataType: 'json',
			success: function(response) {
				console.log(response);
				carregarUsuarios(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'usuarios_ajax.php',
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
			var usuario = response[i].usuario;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + usuario + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_usuario(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'usuarios_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoUsuario',
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
					cadastro_usuario(codigo);
				}
			}
		});
		console.log(codigo);
	}
}

function limparUsuario() {
	document.getElementById('codigo').value = '';
	document.getElementById('usuario').value = '';
	document.getElementById('senha').value = '';
	document.getElementById('operador').checked = false;
	document.getElementById('supervisor').checked = false;
	document.getElementById('per_produtos').checked = false;
	document.getElementById('per_clientes').checked = false;
	document.getElementById('per_fornecedores').checked = false;
	document.getElementById('per_transportadoras').checked = false;
	document.getElementById('per_vendedores').checked = false;
	document.getElementById('per_usuarios').checked = false;
	document.getElementById('per_entregas').checked = false;
	document.getElementById('per_compradores').checked = false;
	document.getElementById('per_nfe').checked = false;
	document.getElementById('per_orcamentos').checked = false;
	document.getElementById('per_pedidos').checked = false;
	document.getElementById('per_compras').checked = false;
	document.getElementById('per_contas_pagar').checked = false;
	document.getElementById('per_contas_receber').checked = false;
	document.getElementById('per_relatorios').checked = false;
	document.getElementById('per_configuracao').checked = false;
	document.getElementById('per_produtos_con').checked = false;
	document.getElementById('inativo').checked = false;
	
	
}
function carregarUsuarios(response) {

	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('usuario').value = response[i].usuario;
		document.getElementById('senha').value = response[i].senha;
		
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		
		if (response[i].operador == '1') {
			document.getElementById('operador').checked = true;
		} else {
			document.getElementById('operador').checked = false;
		}
		
			if (response[i].supervisor == '1') {
			document.getElementById('supervisor').checked = true;
		} else {
			document.getElementById('supervisor').checked = false;
		}
		
				if (response[i].per_produtos == '1') {
			document.getElementById('per_produtos').checked = true;
		} else {
			document.getElementById('per_produtos').checked = false;
		}
		
				if (response[i].per_clientes == '1') {
			document.getElementById('per_clientes').checked = true;
		} else {
			document.getElementById('per_clientes').checked = false;
		}
		
				if (response[i].per_fornecedores == '1') {
			document.getElementById('per_fornecedores').checked = true;
		} else {
			document.getElementById('per_fornecedores').checked = false;
		}
	
	    		if (response[i].per_transportadoras == '1') {
			document.getElementById('per_transportadoras').checked = true;
		} else {
			document.getElementById('per_transportadoras').checked = false;
		}
		
				if (response[i].per_vendedores == '1') {
			document.getElementById('per_vendedores').checked = true;
		} else {
			document.getElementById('per_vendedores').checked = false;
		}
		
				if (response[i].per_usuarios == '1') {
			document.getElementById('per_usuarios').checked = true;
		} else {
			document.getElementById('per_usuarios').checked = false;
		}
		
				if (response[i].per_entregas == '1') {
			document.getElementById('per_entregas').checked = true;
		} else {
			document.getElementById('per_entregas').checked = false;
		}
		
				if (response[i].per_compradores == '1') {
			document.getElementById('per_compradores').checked = true;
		} else {
			document.getElementById('per_compradores').checked = false;
		}
		
				if (response[i].per_nfe == '1') {
			document.getElementById('per_nfe').checked = true;
		} else {
			document.getElementById('per_nfe').checked = false;
		}
		
				if (response[i].per_orcamentos == '1') {
			document.getElementById('per_orcamentos').checked = true;
		} else {
			document.getElementById('per_orcamentos').checked = false;
		}
		
				if (response[i].per_pedidos == '1') {
			document.getElementById('per_pedidos').checked = true;
		} else {
			document.getElementById('per_pedidos').checked = false;
		}
		
				if (response[i].per_compras == '1') {
			document.getElementById('per_compras').checked = true;
		} else {
			document.getElementById('per_compras').checked = false;
		}
		
				if (response[i].per_contas_pagar == '1') {
			document.getElementById('per_contas_pagar').checked = true;
		} else {
			document.getElementById('per_contas_pagar').checked = false;
		}
		
				if (response[i].per_contas_receber == '1') {
			document.getElementById('per_contas_receber').checked = true;
		} else {
			document.getElementById('per_contas_receber').checked = false;
		}
		
				if (response[i].per_relatorios == '1') {
			document.getElementById('per_relatorios').checked = true;
		} else {
			document.getElementById('per_relatorios').checked = false;
		}
		
				if (response[i].per_configuracao == '1') {
			document.getElementById('per_configuracao').checked = true;
		} else {
			document.getElementById('per_configuracao').checked = false;
		}
		
				if (response[i].per_produtos_con == '1') {
			document.getElementById('per_produtos_con').checked = true;
		} else {
			document.getElementById('per_produtos_con').checked = false;
		}
	}
}
