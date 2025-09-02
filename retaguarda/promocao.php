<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>Promocoes</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<script type="text/javascript"
	src="https://code.jquery.com/jquery-3.2.1.js"></script>
<script
	src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/js/materialize.min.js"></script>
</head> 
<?php include ("conexao.php");?>
<script type="text/javascript" src="js/promocao.js"></script>
<body>
	<div class="container" id="promocao_principal">
		<br>
		<div class="row">
			<div class="input-field col s6">
				<label for='desc_pesquisa'>Pesquisa</label> <input type='text'
					id='desc_pesquisa' />
			</div>
			<div class="col s6">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchall'><i class="material-icons">search</i></a> <a
					class="btn-floating btn-small waves-effect green"
					onClick='cadastro_promocao(0)' id='but_add'><i
					class="material-icons">add</i></a>
			</div>
		</div>
		<form action="#">
			<table class="responsive-table striped" id='userTable'>
				<thead>
					<tr>
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
	<div class="container" id="promocao_cadastro">

		<br>
		<div class="row">
			<div class="input-field col s12 l12">
				<input type="text" class="input-field" placeholder="" id="nome" /><label
					for="nome">Nome</label>
			</div>
		</div>
		<div class="row">
			<div class="input-field col s6 l6">
				<input type="text" class="input-field" placeholder="" id="qtde" /><label
					for="qtde">Qtde</label>
			</div>
			<div class="input-field col s6 l6">
				<input type="text" class="input-field" placeholder="" id="preco" /><label
					for="preco">Valor Promocao</label>
			</div>
		</div>

		<br><br><hr>
		<div id="promocao_produto">
			<div class="row">
				<div class="input-field col s3 l3">
					<label class="active" for='promocao_prod_codigo'>Codigo</label> <input
						class="input-field" type='text' id='promocao_prod_codigo' onfocusout="buscarDescCodPromo();"/>
				</div>
				<div class="input-field col s5 l5">
					<label class="active" for='promocao_prod_descricao'>Descricao</label>
					<input type='text' id='promocao_prod_descricao' />
				</div>
				<div class="col s2 l2">
					<a href="javascript:gravar_promocao()"
						class="btn-floating btn-small waves-effect green"><i
						class="material-icons">add</i></a>
				</div>
			</div>
		</div>
		<div class="row">
			<form id="" action="#">
				<table class="table-wrapper responsive-table scroll striped"
					id='promocaoProdutoItens'>
					<thead>
						<tr>
							<th>Codigo</th>
							<th>Descricao</th>
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
		</div>
		<div class="row">
			<div class="col s12 l4">
				<a class="waves-effect waves-light btn red"
					href="javascript:limparPromocao()">Limpar</a>
			</div>
			<div class="col s12 l4">
				<a href="javascript:gravarPromocao()"
					class="waves-effect waves-light btn green" id="gravarVendedores">Gravar</a>
			</div>
			<div class="col s12 l4">
				<a href="javascript:retornarPrincipal()"
					class="waves-effect waves-light btn bule" id="retornar">Voltar</a>
			</div>
		</div>
	</div>




</body>
</html>
