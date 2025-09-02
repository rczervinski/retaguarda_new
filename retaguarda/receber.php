<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate, Post-Check=0, Pre-Check=0">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache"> <META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Contas Receber</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<script type="text/javascript" src="js/receber.js"></script>
<body>
	<div class="container" id="receber_principal">
		<br>
		<div class="row">
			<div class="input-field col s6">
				<label for='desc_pesquisa'>Pesquisa</label> <input type='text'
					id='desc_pesquisa' />
			</div>
			<div class="col s6">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchall'><i class="material-icons">search</i></a> 
			</div>
		</div>
		<form action="#">
			<table class="responsive-table striped" id='userTable'>
				<thead>
					<tr>
						<th>Codigo</th>
						<th>Nome</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</form>
		<div row="s 12" id='paginacao'></div>
	</div>
	<div class="container" id="receber_cadastro">
		<br>
		<div class="row">
			<div class="input-field col s3 l3">
				<label for="codigo" class="active">Codigo</label><input type="text" class="input-field" placeholder="" id="codigo" disabled />
			</div>
			<div class="input-field col s9 l9">
				<label for="nome" class="active">Nome</label><input type="text" class="input-field" placeholder="" id="nome" disabled />
			</div>
		</div>
		<div class="row">
			<div class="col s8 l8">
				<a href="#modalAddReceber" class="modal-trigger btn-floating btn-small waves-effect waves-light #7986cb green" id="receber_add"><i class="material-icons">add</i></a>
			</div>
			<div class="input-field col s4 l4">
				<label for="saldo" class="active">Saldo</label><input type="text" class="input-field" placeholder="" id="saldo" disabled/>
			</div>
		</div>
		<div class="row">
			<form id="" action="#">
				<table class="table-wrapper responsive-table scroll striped"
					id='receberContasListagem'>
					<thead>
						<tr>
							<th>Codigo</th>
							<th>Venda</th>
							<th>Data</th>
							<th>Credito</th>
							<th>Debito</th>
							<th>Juros</th>
							<th>Total</th>
							<th>Historico</th>
							<th>Vencimento</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</form>
			<div row="s 12" id='paginacaoContas'></div>
		</div>
		<div class="row">
			<div class="col s12 l3">
				<a href="javascript:pagarSelecionados()"
					class="waves-effect waves-light btn green" id="semid">Pagar Selecionados</a>
			</div>
			<div class="col s12 l3">
				<a href="#modalReceberAvulso"
					class="modal-trigger waves-effect waves-light btn green" id="semid2">Pagar Avulso</a>
			</div>
			<div class="col s12 l3">
				<a href="javascript:contasRecebidas()"
					class="modal-trigger waves-effect waves-light btn orange" id="semid2">Pagamentos</a>
			</div>
			<div class="col s12 l3">
				<a href="javascript:retornarPrincipal()"
					class="waves-effect waves-light btn bule" id="retornar">Voltar</a>
			</div>
		</div>
	</div>
	<div id="modalAddReceber" class="modal">
		<div class="container">
			<div class="row">
				<div class=" col s6 l6 input-field">
					<label class="active" for="receber_valor">Valor</label><input class="input-field" type="text" id="receber_valor" placeholder="" >
				</div>
				<label class="active" for="receber_vencimento">Vencimento</label><input type="date" class="col s6 l6 datepicker activ" id="receber_vencimento" placeholder="" />
			</div> 
			<div class="row">
				<label class="active" for="discriminacao">Discriminacao</label><input type="text" class=" col s12 l12 input-field" id="discriminacao" placeholder="" />
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarReceber(receber_valor.value,receber_vencimento.value,discriminacao.value);"
					class="modal-close waves-effect waves-green btn-flat">Adicionar</a>
			</div>
		</div>
	</div>
	<div id="modalReceberAvulso" class="modal">
		<div class="container">
			<div class="row">
				<div class=" col s12 l12 input-field">
					<label class="active" for="receber_valor_avulso">Valor</label><input class="input-field" type="text" id="receber_valor_avulso" placeholder="" >
				</div>
			<div class="modal-footer">
				<a href="javascript:pagarAvulso(receber_valor_avulso.value);"
					class="modal-close waves-effect waves-green btn-flat">Efetuar pagamento</a>
			</div>
			<br><br><br>
		</div>
	</div>
</body>
</html>
