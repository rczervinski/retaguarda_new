function arrumarDuplicidade(){

	var documento=prompt("Documento da nota pra corrigir","");
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'arrumarDuplicidade',
			documento: documento,
		},
		dataType: 'json',
		success: function(response) {
			if(response=="OK"){
				alert("Nota Corrigida");
			}else{
				alert(response);
			}
		}
	});
	
}

function pegarProtocoloViaRecibo(){

	var recibo=prompt("RECIBO DA NOTA","");
	$.ajax({
		url: 'nfexml.php',
		type: 'post',
		data: {
			request: 'pegarProtocoloViaRecibo',
			recibo: recibo,
		},
		dataType: 'json',
		success: function(response) {
			console.log(response);
		}
	});
	
}