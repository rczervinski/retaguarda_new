<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>Vendedores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"	rel="stylesheet">
<link rel="stylesheet"	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

</head> 
<?php include ("conexao.php");?>
<?php include ("vendedores_class.php");?> 
<script type="text/javascript" src="js/vendedores.js"></script>
<body>
	<div class="container" id="vendedor_principal">
		<br>
		<div class="row">
			<div class="input-field col s6">
				<label class="active"  for='desc_pesquisa'>Pesquisa</label> <input type='text'
					id='desc_pesquisa' />
			</div>
			<div class="col s6">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchall'><i class="material-icons">search</i></a> <a
					class="btn-floating btn-small waves-effect green"
					onClick='cadastro_vendedor(0)' id='but_add'><i
					class="material-icons">add</i></a>
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
	<div class="container" id="vendedor_cadastro">
		<form action="#">
			<ul class="collapsible">
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-2 white-text">
						<i class="material-icons">Abc</i>Dados Básicos
					</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s12 l4">
								<i class="material-icons prefix">key</i> <input type="text"
									class="input-field" placeholder="" disabled
									onfocusout="verificarCodigo();" id="codigo" /><label class="active" 
									for="codigo">Código</label>
							</div>
							<div class="input-field col s12 l8">
								<input type="text" class="input-field" placeholder=""
									id="nome" /><label class="active"  for="nome">Nome</label>
							</div>
							<div class="input-field col s12 l12">
								<input type="text" class="input-field" placeholder=""
									id="comissao"  value="0"/><label class="active"  for="comissao">Comissao</label>
							</div>
							<div class="input-field col s12 l12">
								<input type="password" class="input-field" placeholder=""
									id="senha"  value="0"/><label class="active"  for="senha">Senha</label>
							</div>
						</div>


						
						
						
						<div class="row">
							<div class="input-field col s12 l4">
								<input type="text" class="input-field" placeholder="" id="desc_max" value="0" /><label class="active" 
									for="desc_max">Desconto maximo permitido</label>
							</div>
							<div class="col s6 l3">
								<input type="checkbox" valign="botton" id="inativo" /><label class="active" 
									for="inativo">Vendedor inativo</label>
							</div>
														
						</div>
				
				</li>
			</ul>
			<div class="row"> 
				<div class="col s12 l4">
					<a class="waves-effect waves-light btn red"
						href="javascript:limparVendedor()">Limpar</a>
				</div>
				<div class="col s12 l4">
					<a href="javascript:gravarVendedores()"
						class="waves-effect waves-light btn green" id="gravarVendedores">Gravar</a>
				</div>
				<div class="col s12 l4">
					<a href="javascript:retornarPrincipal()"
						class="waves-effect waves-light btn bule" id="retornar">Voltar</a>
				</div>
			</div>
		</form>
	</div>
</body>
</html>
