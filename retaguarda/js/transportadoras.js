<<<<<<< HEAD
<<<<<<< HEAD
function consultarCep(){
	var cep=document.getElementById('cep').value
	$.ajax({
		url: 'https://viacep.com.br/ws/'+cep+'/json',
		type: 'get',
		dataType: 'json',
		success: function(response) {
			console.log(response);
			const myJSON = JSON.stringify(response);
			const obj=JSON.parse(myJSON);
			document.getElementById('logradouro').value=obj.logradouro;
			document.getElementById('bairro').value=obj.bairro;
			document.getElementById('municipio_desc').value=obj.localidade;
			document.getElementById('municipio').value=obj.ibge;
			document.getElementById('uf_desc').value=obj.uf;
			document.getElementById('uf').value=(obj.ibge).substring(0,2);
		}
	});
}
function gravarTransportadoras() {

	var codigo = document.getElementById('codigo').value
	var fantasia = document.getElementById('fantasia').value;
	var razao_social = document.getElementById('razao_social').value;
	var cpf_cnpj = document.getElementById('cpf_cnpj').value;
	var inscricao_rg = document.getElementById('inscricao_rg').value;
	var contato= document.getElementById('contato').value;
	var cep = document.getElementById('cep').value;
	var logradouro = document.getElementById('logradouro').value;
	var complemento = document.getElementById('complemento').value;
	var bairro = document.getElementById('bairro').value;
	var fone = document.getElementById('fone').value;
	var celular = document.getElementById('celular').value;
	var email = document.getElementById('email').value;
	var inativo = document.getElementById('inativo').checked;
	var numero = document.getElementById('numero').value;
	var municipio = document.getElementById('municipio').value;
	var uf = document.getElementById('uf').value;
	var municipio_desc = document.getElementById('municipio_desc').value;
	var uf_desc = document.getElementById('uf_desc').value;

	$.ajax({
		url: 'transportadoras_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarTransportadoras',
			codigo: codigo,
			fantasia: fantasia,
			razao_social: razao_social,
			cpf_cnpj: cpf_cnpj,
			inscricao_rg: inscricao_rg,
			contato: contato,
			cep: cep,
			logradouro: logradouro,
			complemento: complemento,
			bairro: bairro,
			fone: fone,
			celular: celular,
			email: email,
			inativo: inativo,
			numero: numero,
			municipio: municipio,
			uf: uf,
			municipio_desc: municipio_desc,
			uf_desc: uf_desc,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparTransportadora();
			$('#userTable tbody').empty();
			$("#transportadora_principal").show();
			$("#transportadora_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparTransportadora();
	$('#userTable tbody').empty();
	$("#transportadora_principal").show();
	$("#transportadora_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#transportadora_principal").show();
	$("#transportadora_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'transportadoras_ajax.php',
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

function cadastro_transportadora(codigo) {
	$("#transportadora_principal").hide();
	$("#transportadora_cadastro").show();
	//Se for 
 
	if (codigo > 0) {
		$.ajax({
			url: 'transportadoras_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirTransportadoras',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarTransportadoras(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'transportadoras_ajax.php',
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
			var fantasia = response[i].fantasia;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + fantasia + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_transportadora(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'transportadoras_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoTransportadora',
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
					cadastro_transportadora(codigo);
				}
			}
		});
		console.log(codigo);
	}
}
function limparTransportadora() {
	//aba inf basicas
	document.getElementById('codigo').value = '';
	document.getElementById('fantasia').value = '';
	document.getElementById('razao_social').value = '';
	document.getElementById('cpf_cnpj').value = '';
	document.getElementById('inscricao_rg').value = '';
	document.getElementById('contato').value = '';
	document.getElementById('cep').value = '';
	document.getElementById('logradouro').value = '';
	document.getElementById('complemento').value = '';
	document.getElementById('bairro').value = '';
	document.getElementById('fone').value = '';
	document.getElementById('celular').value = '';
	document.getElementById('email').value = '';
	document.getElementById('inativo').checked = false;
	document.getElementById('numero').value = 0;
	document.getElementById('municipio').value = '';
	document.getElementById('uf').value = '';
	document.getElementById('municipio_desc').value = '';
	document.getElementById('uf_desc').value = '';
	
}
function carregarTransportadoras(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('fantasia').value = response[i].fantasia;
		document.getElementById('razao_social').value = response[i].razao_social;
		document.getElementById('cpf_cnpj').value = response[i].cpf_cnpj;
		document.getElementById('inscricao_rg').value = response[i].inscricao_rg;
		document.getElementById('contato').value = response[i].contato;
		document.getElementById('cep').value = response[i].cep;
		document.getElementById('logradouro').value = response[i].logradouro;
		document.getElementById('complemento').value = response[i].complemento;
		document.getElementById('bairro').value = response[i].bairro;
		document.getElementById('fone').value = response[i].fone;
		document.getElementById('celular').value = response[i].celular;
		document.getElementById('email').value = response[i].email;
		
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		
		document.getElementById('numero').value = response[i].numero;
		document.getElementById('municipio').value = response[i].municipio;
		document.getElementById('uf').value = response[i].uf;
		document.getElementById('municipio_desc').value = response[i].municipio_desc;
		document.getElementById('uf_desc').value = response[i].uf_desc;
		

	}
}
=======
=======
>>>>>>> 02873dae92d94b56acc454402418f6edbeae1cea
function consultarCep(){
	var cep=document.getElementById('cep').value
	$.ajax({
		url: 'https://viacep.com.br/ws/'+cep+'/json',
		type: 'get',
		dataType: 'json',
		success: function(response) {
			console.log(response);
			const myJSON = JSON.stringify(response);
			const obj=JSON.parse(myJSON);
			document.getElementById('logradouro').value=obj.logradouro;
			document.getElementById('bairro').value=obj.bairro;
			document.getElementById('municipio_desc').value=obj.localidade;
			document.getElementById('municipio').value=obj.ibge;
			document.getElementById('uf_desc').value=obj.uf;
			document.getElementById('uf').value=(obj.ibge).substring(0,2);
		}
	});
}
function gravarTransportadoras() {

	var codigo = document.getElementById('codigo').value
	var fantasia = document.getElementById('fantasia').value;
	var razao_social = document.getElementById('razao_social').value;
	var cpf_cnpj = document.getElementById('cpf_cnpj').value;
	var inscricao_rg = document.getElementById('inscricao_rg').value;
	var contato= document.getElementById('contato').value;
	var cep = document.getElementById('cep').value;
	var logradouro = document.getElementById('logradouro').value;
	var complemento = document.getElementById('complemento').value;
	var bairro = document.getElementById('bairro').value;
	var fone = document.getElementById('fone').value;
	var celular = document.getElementById('celular').value;
	var email = document.getElementById('email').value;
	var inativo = document.getElementById('inativo').checked;
	var numero = document.getElementById('numero').value;
	var municipio = document.getElementById('municipio').value;
	var uf = document.getElementById('uf').value;
	var municipio_desc = document.getElementById('municipio_desc').value;
	var uf_desc = document.getElementById('uf_desc').value;

	$.ajax({
		url: 'transportadoras_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarTransportadoras',
			codigo: codigo,
			fantasia: fantasia,
			razao_social: razao_social,
			cpf_cnpj: cpf_cnpj,
			inscricao_rg: inscricao_rg,
			contato: contato,
			cep: cep,
			logradouro: logradouro,
			complemento: complemento,
			bairro: bairro,
			fone: fone,
			celular: celular,
			email: email,
			inativo: inativo,
			numero: numero,
			municipio: municipio,
			uf: uf,
			municipio_desc: municipio_desc,
			uf_desc: uf_desc,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparTransportadora();
			$('#userTable tbody').empty();
			$("#transportadora_principal").show();
			$("#transportadora_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparTransportadora();
	$('#userTable tbody').empty();
	$("#transportadora_principal").show();
	$("#transportadora_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#transportadora_principal").show();
	$("#transportadora_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'transportadoras_ajax.php',
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

function cadastro_transportadora(codigo) {
	$("#transportadora_principal").hide();
	$("#transportadora_cadastro").show();
	//Se for 
 
	if (codigo > 0) {
		$.ajax({
			url: 'transportadoras_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirTransportadoras',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarTransportadoras(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'transportadoras_ajax.php',
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
			var fantasia = response[i].fantasia;
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + fantasia + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_transportadora(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'transportadoras_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoTransportadora',
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
					cadastro_transportadora(codigo);
				}
			}
		});
		console.log(codigo);
	}
}
function limparTransportadora() {
	//aba inf basicas
	document.getElementById('codigo').value = '';
	document.getElementById('fantasia').value = '';
	document.getElementById('razao_social').value = '';
	document.getElementById('cpf_cnpj').value = '';
	document.getElementById('inscricao_rg').value = '';
	document.getElementById('contato').value = '';
	document.getElementById('cep').value = '';
	document.getElementById('logradouro').value = '';
	document.getElementById('complemento').value = '';
	document.getElementById('bairro').value = '';
	document.getElementById('fone').value = '';
	document.getElementById('celular').value = '';
	document.getElementById('email').value = '';
	document.getElementById('inativo').checked = false;
	document.getElementById('numero').value = 0;
	document.getElementById('municipio').value = '';
	document.getElementById('uf').value = '';
	document.getElementById('municipio_desc').value = '';
	document.getElementById('uf_desc').value = '';
	
}
function carregarTransportadoras(response) {
	var len = 0;
	if (response != null) {
		len = response.length;
	}
	for (var i = 0; i < len; i++) {
		document.getElementById('codigo').value = response[i].codigo;
		document.getElementById('fantasia').value = response[i].fantasia;
		document.getElementById('razao_social').value = response[i].razao_social;
		document.getElementById('cpf_cnpj').value = response[i].cpf_cnpj;
		document.getElementById('inscricao_rg').value = response[i].inscricao_rg;
		document.getElementById('contato').value = response[i].contato;
		document.getElementById('cep').value = response[i].cep;
		document.getElementById('logradouro').value = response[i].logradouro;
		document.getElementById('complemento').value = response[i].complemento;
		document.getElementById('bairro').value = response[i].bairro;
		document.getElementById('fone').value = response[i].fone;
		document.getElementById('celular').value = response[i].celular;
		document.getElementById('email').value = response[i].email;
		
		if (response[i].inativo == '1') {
			document.getElementById('inativo').checked = true;
		} else {
			document.getElementById('inativo').checked = false;
		}
		
		document.getElementById('numero').value = response[i].numero;
		document.getElementById('municipio').value = response[i].municipio;
		document.getElementById('uf').value = response[i].uf;
		document.getElementById('municipio_desc').value = response[i].municipio_desc;
		document.getElementById('uf_desc').value = response[i].uf_desc;
		

	}
}
