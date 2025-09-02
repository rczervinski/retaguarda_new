<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate, Post-Check=0, Pre-Check=0">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache"> <META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Contas Pagar</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<script type="text/javascript" src="js/pagar.js"></script>
<body>
	<div class="container" id="pagar_principal">
		<br>
		<div class="row">
			<div class="col s3 l3">
                <label class="active" for="data1">Data Inicial</label>
                <input type="date" class="col s12 l12 datepicker activ" id="data1" placeholder="" />
            </div>
            <div class="col s3 l3">
                <label class="active" for="data2">Data Final</label>
                <input type="date" class="col s12 l12 datepicker activ" id="data2" placeholder="" />
            </div>
			
			<input type='hidden' id='codfor' />
			<div class="input-field col s3 l3">
				<label class="active" for='desc_pesquisa'>Fornecedor</label> <input type='text'
					id='desc_pesquisa' />
			</div>
			<div class="col s3 l3">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchfor'><i class="material-icons">person</i></a> 
			</div>
		</div>
		<div class="row">
			<div class="col s2">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchall'><i class="material-icons">search</i></a> 
					<a
					class="btn-floating btn-small waves-effect green"
					onClick='cadastrar_conta_pagar()' id='but_add'><i
					class="material-icons">add</i></a>
			</div>
		</div>
		<form action="#">
			<table class="responsive-table striped" id='userTable'>
				<thead>
					<tr>
						<th>Vencimento</th>
						<th>Fornecedor</th>
						<th>Total</th>
						<th>Tipo</th>
						<th>Doc.</th>
						<th>Parcela</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</form>
		<div row="s 12" id='paginacao'></div>
	</div>
	<div class="container" id="pagar_fornecedor">
		<div class="row">
			<form id="" action="#">
				<table class="table-wrapper responsive-table scroll striped"
					id='pagarforlistagem'>
					<thead>
						<tr>
							<th>Razao Social</th>
							<th>Fantasia</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
			<h5><a href="javascript:sairFornecedor()">Voltar</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:limparFornecedor()">Limpar</a></h5>
			<div row="s 12" id='paginacaoFor'></div>
		</div>
	</div>
	
	<div id="modalAddReceber" class="modal">
		<div class="container">
			<div class="row">
				<div class=" col s6 l6 input-field">
					<label class="active" for="pagar_valor">Valor</label><input class="input-field" type="text" id="receber_valor" placeholder="" >
				</div>
				<label class="active" for="pagar_vencimento">Vencimento</label><input type="date" class="col s6 l6 datepicker activ" id="receber_vencimento" placeholder="" />
			</div> 
			<div class="row">
				<label class="active" for="discriminacao">Discriminacao</label><input type="text" class=" col s12 l12 input-field" id="discriminacao" placeholder="" />
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarReceber(pagar_valor.value,receber_vencimento.value,discriminacao.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalReceberAvulso" class="modal">
		<div class="container">
			<div class="row">
				<div class=" col s12 l12 input-field">
					<label class="active" for="pagar_valor_avulso">Valor</label><input class="input-field" type="text" id="receber_valor_avulso" placeholder="" >
				</div>
			<div class="modal-footer">
				<a href="javascript:pagarAvulso(pagar_valor_avulso.value);"
					class="modal-close waves-effect waves-green btn-flat">Efetuar pagamento</a>
			</div>
			<br><br><br>
		</div>
	</div>
</body>
</html>
