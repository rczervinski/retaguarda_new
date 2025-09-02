$(document).ajaxStart(function() {
	$("#loading").show();
});
$(document).ajaxStop(function() {
	$("#loading").hide();
});

function sairFornecedor(){
	$("#pagar_principal").show();
	$("#pagar_fornecedor").hide();
}
function limparFornecedor(){
	$("#pagar_principal").show();
	$("#pagar_fornecedor").hide();
	document.getElementById('desc_pesquisa').value="";
	document.getElementById('codfor').value="";
}

$(document).ready(function() {
	$("#pagar_principal").show();
	$("#pagar_fornecedor").hide();
	// Fetch all records
	const date =new Date();
	document.getElementById('data2').value=date.toISOString().split('T')[0];
	$('#but_fetchall').click(function() {
		// AJAX GET request
		const val = document.getElementById('desc_pesquisa').value;
		const data1 = document.getElementById('data1').value;
		const data2 = document.getElementById('data2').value;
		const codfor = document.getElementById('codfor').value;
		
		$.ajax({
			url: 'pagar_ajax.php',
			type: 'post',
			data: { request: 'fetchall', pagina: 0, codfor: codfor, data1: data1, data2: data2 },
			dataType: 'json',
			success: function(response) {
				
				createRows(response);
			},error: function(jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
	$('#but_fetchfor').click(function() {
		// AJAX GET request
		$("#pagar_principal").hide();
		$("#pagar_fornecedor").show();
		const val = document.getElementById('desc_pesquisa').value;
		$.ajax({
			url: 'pagar_ajax.php',
			type: 'post',
			data: { request: 'fetchfor', pagina: 0, desc_pesquisa: val },
			dataType: 'json',
			success: function(response) {
				createRowsFor(response);
			},error: function(jqxhr, status, exception) {
				alert(exception);
			}
		});
	});
	$('#clearfor').click(function() {
		document.getElementById('cod_for').value='';
		document.getElementById('desc_pesquisa').value='';
	});
});

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
			var vencimento = response[i].vencimento;
			var fantasia = response[i].fantasia;
			var valor = response[i].valor;
			var tipo =  response[i].tipo;
			var documento =  response[i].documento;
			var parcela =  response[i].parcela;
			
			var tr_str = "<tr>" +
				"<td>" + vencimento + "</td>" +
				"<td>" + fantasia + "</td>" +
				"<td>" + valor + "</td>" +
				"<td>" + tipo + "</td>" +
				"<td>" + documento + "</td>" +
				"<td>" + parcela + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='"+codigo+"' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
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

function selecionouFornecedor(codigo){
	$("#pagar_principal").show();
	$("#pagar_fornecedor").hide();
	$.ajax({
		url: 'pagar_ajax.php',
		type: 'post',
		data: { 
			request: 'selecionarFornecedor', 
			pagina: 0, 
			codigo: codigo 
		},
		dataType: 'json',
		success: function(response) {
			carregarFornecedor(response);
		},error: function(jqxhr, status, exception) {
			alert(exception);
		}
	});
}

function carregarFornecedor(response){
	if (response != null) {
		len = response.length;
	}
	if (len > 0) {
		var codigo = response[0].codigo;
		var razao_social = response[0].razao_social;
		document.getElementById('desc_pesquisa').value=razao_social;
		document.getElementById('codfor').value=codigo; 
	} 
}

function createRowsFor(response) {
	var len = 0;
	$('#pagarforlistagem tbody').empty(); // Empty <tbody>
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
			var razao_social = response[i].razao_social;
			var fantasia = response[i].fantasia;
			
			var tr_str = "<tr>" +
				"<td>" + razao_social + "</td>" +
				"<td>" + fantasia + "</td>" +
				"<td><a class='btn-floating btn-small waves-effect grey' onClick='selecionouFornecedor("+codigo+");' id='but_edit'><i class='material-icons'>edit</i></a></td>" +
				"</tr>";
			$("#pagarforlistagem tbody").append(tr_str);
		}
	} else {
		var tr_str = "<tr>" +
			"<td align='center' colspan='3'>Sem registro.</td>" +
			"</tr>";
		$("#pagarforlistagem tbody").append(tr_str);
	}
}



$(document).ready(function() {
	$('.modal').modal();
});


function clickPagina(valor) {
	// AJAX GET request
	const val = document.getElementById('desc_pesquisa').value;
	$.ajax({
		url: 'pagar_ajax.php',
		type: 'post',
		data: { request: 'fetchall', pagina: (valor - 1) * 50, desc_pesquisa: val },
		dataType: 'json',
		success: function(response) {
			createRows(response);
		}
	});
}

function selecionarConta(codigo, valor) {
	var selecionado = document.getElementById(codigo).checked;
	$.ajax({
		url: 'pagar_ajax.php',
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

function cadastrar_conta_pagar(){
	var codfor=document.getElementById('codfor').value;
	if(!codfor>0 ){
		alert("Favor selecionar o fornecedor para adicionar contas a pagar");
	}
}


