$(document).ready(function() {
	$("#receber_principal").show();
	$("#receber_cadastro").hide();
	// Fetch all records
	$('#but_fetchall').click(function() {
		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'receber_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRows(response);
			},error: function(jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
});
$(document).ready(function() {
	$('.modal').modal();
});
function adicionarReceber(valor, vencimento, discriminacao) {
	var codigo = document.getElementById('codigo').value;
	var fantasia = document.getElementById('nome').value;
	$.ajax({
		url: 'receber_ajax.php',
		type: 'post',
		data: {
			request: 'adicionarReceber',
			codigo: codigo,
			valor: valor,
			vencimento: vencimento,
			discriminacao: discriminacao
		},
		dataType: 'json',
		success: function(response) {
			cadastro_receber(codigo, fantasia);
			montarSaldo();
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
			var fantasia_ = fantasia.replace(/ /g, "_");
			var adic = "cadastro_receber(" + codigo + ",'" + fantasia_ + "')";
			var tr_str = "<tr>" +
				"<td>" + codigo + "</td>" +
				"<td>" + fantasia + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick=" + adic + " id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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
function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'receber_ajax.php',
		type: 'post',
		data: { request: 'fetchall', pagina: (valor - 1) * 50, desc_pesquisa: val },
		dataType: 'json',
		success: function(response) {
			createRows(response);
		}
	});
}
function clickPaginaReceber(valor) {
	// AJAX GET request
	const val = document.getElementById('codigo').value;
	$.ajax({
		url: 'receber_ajax.php',
		type: 'post',
		data: {
			request: 'atribuirReceber',
			pagina: (valor - 1) * 50,
			codigo: val,
		},
		dataType: 'json',
		success: function(response) {
			carregarReceber(response);
		},
		error: function(jqxhr, status, exception) {
			alert(exception);
		}
	});
}
function cadastro_receber(codigo, fantasia) {
	$("#receber_principal").hide();
	$("#receber_cadastro").show();
	//Se for 
	if (codigo > 0) {
		document.getElementById('codigo').value = codigo;
		document.getElementById('nome').value = fantasia.replace(/_/g, " ");
		$.ajax({
			url: 'receber_ajax.php',
			type: 'post',
			data: {
				request: 'atribuirReceber',
				pagina: 0,
				codigo: codigo,
			},
			dataType: 'json',
			success: function(response) {
				carregarReceber(response);
				montarSaldo();
			}
		});
	}
}
function carregarReceber(response) {
	var len = 0;
	$('#receberContasListagem tbody').empty(); // Empty <tbody>
	$("#paginacaoContas").empty();
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var quantos = response[0].quantos;
		var paginas = quantos / 50;
		for (var j = 0; j < paginas; j++) {
			var pagina = Number(Number(j) + 1);
			var li_str = "<input type='button' id='btteste' value='" + pagina + "' onclick='clickPaginaReceber(" + pagina + ");' />";
			$("#paginacaoContas").append(li_str);
		}
		for (var i = 0; i < len; i++) {
			var codigo = response[i].codigo;
			var cliente = response[i].cliente;
			var venda = response[i].venda;
			var data = response[i].data;
			var credito = response[i].credito;
			var debito = response[i].debito;
			var juros = response[i].juros;
			var total = response[i].total;
			var historico = response[i].historico;
			var vencimento = response[i].vencimento;
			if (response[i].selecao.toString() == 'false') {
				var tr_str = "<tr>" +
					"<td>" + cliente + "</td>" +
					"<td>" + venda + "</td>" +
					"<td>" + data + "</td>" +
					"<td>" + credito + "</td>" +
					"<td>" + debito + "</td>" +
					"<td>" + juros + "</td>" +
					"<td>" + total + "</td>" +
					"<td>" + historico + "</td>" +
					"<td>" + vencimento + "</td>" +
					"<td><input type='checkbox'  onClick='selecionarConta(" + codigo + "," + total+ ")' class='filled-in' id='" + codigo + "'></input><label for=" + codigo + "></label></td>" +
					"</tr>";
			} else {
				var tr_str = "<tr>" +
					"<td>" + cliente + "</td>" +
					"<td>" + venda + "</td>" +
					"<td>" + data + "</td>" +
					"<td>" + credito + "</td>" +
					"<td>" + debito + "</td>" +
					"<td>" + juros + "</td>" +
					"<td>" + total + "</td>" +
					"<td>" + historico + "</td>" +
					"<td>" + vencimento + "</td>" +
					"<td><input type='checkbox' checked='checked' onClick='selecionarConta(" + codigo + "," + total + ")' class='filled-in' id='" + codigo + "'></input><label for=" + codigo + "></label></td>" +
					"</tr>";
			}
			$("#receberContasListagem tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#receberContasListagem tbody").append(tr_str);
	}
}
function selecionarConta(codigo, valor) {
	var selecionado = document.getElementById(codigo).checked;
	$.ajax({
		url: 'receber_ajax.php',
		type: 'post',
		data: {
			request: 'selecionarConta',
			pagina: 0,
			codigo: codigo,
			selecionado: selecionado,
			valor: valor,
		},
		dataType: 'json',
		success: function(response) {
		}
	});
}
function retornarPrincipal() {
	$('#receberContasListagem tbody').empty();
	$('#userTable tbody').empty();
	$("#receber_principal").show();
	$("#receber_cadastro").hide();
}
function pagarSelecionados() {
	$.ajax({
			url: 'receber_ajax.php',
			type: 'post',
			data: {
				request: 'pegarValorSelecionado',
			},
			dataType: 'json',
			success: function(response) {
				pagarSelecionadosComValor(response);
			}
		});
}
function pagarSelecionadosComValor(valor)
{
	
	var resultado = confirm("Confirma o pagamento das contas selecionadas no valor de R$"+valor+" ?");
	if (resultado == true) {
		var cliente = document.getElementById('codigo').value;
		var fantasia = document.getElementById('nome').value;
		$.ajax({
			url: 'receber_ajax.php',
			type: 'post',
			data: {
				request: 'pagarSelecionados',
				cliente: cliente,
			},
			dataType: 'json',
			success: function(response) {
				var codigo = document.getElementById('codigo').value;
				var fantasia = document.getElementById('nome').value;
				cadastro_receber(codigo, fantasia);
				montarSaldo();
				var recibo = confirm("Deseja recibo ?");
				if(recibo == true){
					window.open("relatorios/recibo.php?nome="+fantasia+"&valor="+valor);
				}
			}
		});
	}
}

function pagarAvulso(valor) {
	var resultado = confirm("Confirma o pagamento avulso no valor de R$ "+valor+" ?");
	if (resultado == true) {
		var cliente = document.getElementById('codigo').value;
		$.ajax({
			url: 'receber_ajax.php',
			type: 'post',
			data: {
				request: 'pagarAvulso',
				cliente: cliente,
				valor: valor,
			},
			dataType: 'json',
			success: function(response) {
				var codigo = document.getElementById('codigo').value;
				var fantasia = document.getElementById('nome').value;
				cadastro_receber(codigo, fantasia);
				montarSaldo();
				var recibo = confirm("Deseja recibo ?");
				if(recibo == true){
					window.open("relatorios/recibo.php?nome="+fantasia+"&valor="+valor);
				}
			}
		});
	}
}
function montarSaldo() {
	var cliente = document.getElementById('codigo').value;
	$.ajax({
		url: 'receber_ajax.php',
		type: 'post',
		data: {
			request: 'montarSaldo',
			cliente: cliente,
		},
		dataType: 'json',
		success: function(response) {
			document.getElementById('saldo').value=response;
		}
	});
}
function contasRecebidas(){
	var cliente = document.getElementById('codigo').value;
	window.location.href = 'relatorios/recebidas.php?cliente='+cliente;
}