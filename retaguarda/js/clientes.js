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
function gravarClientes() {
	
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
	var classificacao = document.getElementById('classificacao').value;
	var numero = document.getElementById('numero').value;
	var municipio = document.getElementById('municipio').value;
	var uf = document.getElementById('uf').value;
	var senha = document.getElementById('senha').value;
	var limite = document.getElementById('limite').value;
	var mae = document.getElementById('mae').value;
	var pai = document.getElementById('pai').value;
	var conjuge = document.getElementById('conjuge').value;
	var ref1 = document.getElementById('ref1').value;
	var ref2 = document.getElementById('ref2').value;
	var fone_ref1 = document.getElementById('fone_ref1').value
	var fone_ref2 = document.getElementById('fone_ref2').value;
	var profissao = document.getElementById('profissao').value;
	var nascimento = document.getElementById('nascimento').value;
	var vencimento = document.getElementById('vencimento').value;
	var numeracao = document.getElementById('numeracao').value;
	var municipio_desc = document.getElementById('municipio_desc').value;
	var uf_desc = document.getElementById('uf_desc').value;

	$.ajax({
		url: 'clientes_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarClientes',
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
			classificacao: classificacao,
			numero: numero,
			municipio: municipio,
			uf: uf,
			senha: senha,
			limite: limite,
			mae: mae,
			pai: pai,
			conjuge: conjuge,
			ref1: ref1,
			ref2: ref2,
			profissao: profissao,
			nascimento: nascimento,
			vencimento: vencimento,
			numeracao: numeracao,
			municipio_desc: municipio_desc,
			uf_desc: uf_desc,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparCliente();
			$('#userTable tbody').empty();
			$("#cliente_principal").show();
			$("#cliente_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparCliente();
	$('#userTable tbody').empty();
	$("#cliente_principal").show();
	$("#cliente_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#cliente_principal").show();
	$("#cliente_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'clientes_ajax.php',
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

function cadastro_cliente(codigo) {
	$("#cliente_principal").hide();
	$("#cliente_cadastro").show();
	//Se for 
	if (codigo > 0) {
		$.ajax({
			url: 'clientes_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirClientes',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarClientes(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'clientes_ajax.php',
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
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_cliente(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'clientes_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoCliente',
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
					cadastro_cliente(codigo);
				}
			}
		});
		console.log(codigo);
	}
}
function limparCliente() {
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
	document.getElementById('classificacao').value = '';
	document.getElementById('numero').value = 0;
	document.getElementById('municipio').value = '';
	document.getElementById('uf').value = '';
	document.getElementById('senha').value = '';
	document.getElementById('limite').value = 0;
	document.getElementById('mae').value = '';
	document.getElementById('pai').value = '';
	document.getElementById('conjuge').value = '';
	document.getElementById('ref1').value = '';
	document.getElementById('ref2').value = '';
	document.getElementById('profissao').value = '';
	document.getElementById('nascimento').value = '';
	document.getElementById('vencimento').value = 1;
	document.getElementById('numeracao').value = '';
	document.getElementById('municipio_desc').value = '';
	document.getElementById('uf_desc').value = '';
	
}
function carregarClientes(response) {
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
		
		document.getElementById('classificacao').value = response[i].classificacao;
		document.getElementById('numero').value = response[i].numero;
		document.getElementById('municipio').value = response[i].municipio;
		document.getElementById('uf').value = response[i].uf;
		document.getElementById('senha').value = response[i].senha;
		document.getElementById('limite').value = response[i].limite;
		document.getElementById('mae').value = response[i].mae;
		document.getElementById('pai').value = response[i].pai;
		document.getElementById('conjuge').value = response[i].conjuge;
		document.getElementById('ref1').value = response[i].ref1;
		document.getElementById('ref2').value = response[i].ref2;
		document.getElementById('profissao').value = response[i].profissao;
		document.getElementById('nascimento').value = response[i].nascimento;
		document.getElementById('vencimento').value = response[i].vencimento;
		document.getElementById('numeracao').value = response[i].numeracao;
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
function gravarClientes() {
	
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
	var classificacao = document.getElementById('classificacao').value;
	var numero = document.getElementById('numero').value;
	var municipio = document.getElementById('municipio').value;
	var uf = document.getElementById('uf').value;
	var senha = document.getElementById('senha').value;
	var limite = document.getElementById('limite').value;
	var mae = document.getElementById('mae').value;
	var pai = document.getElementById('pai').value;
	var conjuge = document.getElementById('conjuge').value;
	var ref1 = document.getElementById('ref1').value;
	var ref2 = document.getElementById('ref2').value;
	var fone_ref1 = document.getElementById('fone_ref1').value
	var fone_ref2 = document.getElementById('fone_ref2').value;
	var profissao = document.getElementById('profissao').value;
	var nascimento = document.getElementById('nascimento').value;
	var vencimento = document.getElementById('vencimento').value;
	var numeracao = document.getElementById('numeracao').value;
	var municipio_desc = document.getElementById('municipio_desc').value;
	var uf_desc = document.getElementById('uf_desc').value;

	$.ajax({
		url: 'clientes_ajax.php',
		type: 'post',
		data: {
			request: 'inserirAtualizarClientes',
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
			classificacao: classificacao,
			numero: numero,
			municipio: municipio,
			uf: uf,
			senha: senha,
			limite: limite,
			mae: mae,
			pai: pai,
			conjuge: conjuge,
			ref1: ref1,
			ref2: ref2,
			profissao: profissao,
			nascimento: nascimento,
			vencimento: vencimento,
			numeracao: numeracao,
			municipio_desc: municipio_desc,
			uf_desc: uf_desc,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
			limparCliente();
			$('#userTable tbody').empty();
			$("#cliente_principal").show();
			$("#cliente_cadastro").hide();
		}
	});
}
function retornarPrincipal() {
	limparCliente();
	$('#userTable tbody').empty();
	$("#cliente_principal").show();
	$("#cliente_cadastro").hide();
}



$(document).ready(function() {
	$('.modal').modal();
});

$(document).ready(function() {
	$('.collapsible').collapsible();
});

$(document).ready(function() {
	$("#cliente_principal").show();
	$("#cliente_cadastro").hide();

	// Fetch all records
	$('#but_fetchall').click(function() {

		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'clientes_ajax.php',
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

function cadastro_cliente(codigo) {
	$("#cliente_principal").hide();
	$("#cliente_cadastro").show();
	//Se for 
	if (codigo > 0) {
		$.ajax({
			url: 'clientes_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirClientes',
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarClientes(response);
			}
		});
	}
}

function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'clientes_ajax.php',
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
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='cadastro_cliente(" + codigo + ")' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
			url: 'clientes_ajax.php',
			type: 'post',
			data: {
				request: 'consultarCodigoCliente',
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
					cadastro_cliente(codigo);
				}
			}
		});
		console.log(codigo);
	}
}
function limparCliente() {
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
	document.getElementById('classificacao').value = '';
	document.getElementById('numero').value = 0;
	document.getElementById('municipio').value = '';
	document.getElementById('uf').value = '';
	document.getElementById('senha').value = '';
	document.getElementById('limite').value = 0;
	document.getElementById('mae').value = '';
	document.getElementById('pai').value = '';
	document.getElementById('conjuge').value = '';
	document.getElementById('ref1').value = '';
	document.getElementById('ref2').value = '';
	document.getElementById('profissao').value = '';
	document.getElementById('nascimento').value = '';
	document.getElementById('vencimento').value = 1;
	document.getElementById('numeracao').value = '';
	document.getElementById('municipio_desc').value = '';
	document.getElementById('uf_desc').value = '';
	
}
function carregarClientes(response) {
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
		
		document.getElementById('classificacao').value = response[i].classificacao;
		document.getElementById('numero').value = response[i].numero;
		document.getElementById('municipio').value = response[i].municipio;
		document.getElementById('uf').value = response[i].uf;
		document.getElementById('senha').value = response[i].senha;
		document.getElementById('limite').value = response[i].limite;
		document.getElementById('mae').value = response[i].mae;
		document.getElementById('pai').value = response[i].pai;
		document.getElementById('conjuge').value = response[i].conjuge;
		document.getElementById('ref1').value = response[i].ref1;
		document.getElementById('ref2').value = response[i].ref2;
		document.getElementById('profissao').value = response[i].profissao;
		document.getElementById('nascimento').value = response[i].nascimento;
		document.getElementById('vencimento').value = response[i].vencimento;
		document.getElementById('numeracao').value = response[i].numeracao;
		document.getElementById('municipio_desc').value = response[i].municipio_desc;
		document.getElementById('uf_desc').value = response[i].uf_desc;
		

	}
}
