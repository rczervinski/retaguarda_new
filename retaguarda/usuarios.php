<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>Usuarios</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"	rel="stylesheet">
<link rel="stylesheet"	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<?php include ("usuarios_class.php");?> 
<script type="text/javascript" src="js/usuarios.js"></script>
<body>
	<div class="container" id="usuario_principal">
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
					onClick='cadastro_usuario(0)' id='but_add'><i
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
	<div class="container" id="usuario_cadastro">
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
									id="usuario" /><label class="active"  for="usuario">Nome</label>
							</div>
							<div class="input-field col s12 l8">
								<input type="text" class="input-field" placeholder=""
									id="senha" /><label class="active"  for="senha">Senha</label>
							</div>
						</div>
							<div class="row">
							<div class="col s12 l4">
								<input type="radio" valign="botton" name="opcao" id="operador" /><label class="active" 
									for="operador">Operador</label>
							</div>
								<div class="col s12 l4">
								<input type="radio" valign="botton" name="opcao" id="gerente" /><label class="active" 
									for="gerente">Gerente</label>
							</div>
									<div class="col s12 l4">
								<input type="radio" valign="botton" name="opcao" id="supervisor" /><label class="active" 
									for="supervisor">Supervisor</label>
									</div>
								</div>
									<div class="row">
									<div class="col s12 l12">
									</div>
							</div>
								<div class="row">
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_produtos" /><label class="active" 
									for="per_produtos">Produtos</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_clientes" /><label class="active" 
									for="per_clientes">Clientes</label>
							</div>			
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_fornecedores" /><label class="active" 
									for="per_fornecedores">Fornecedores</label>
							</div>
								</div>	
								<div class="row">			
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_transportadoras" /><label class="active" 
									for="per_transportadoras">Transportadoras</label>
							</div>						
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_vendedores" /><label class="active" 
									for="per_vendedores">Vendedores</label>
							</div>		
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_usuarios" /><label class="active" 
									for="per_usuarios">Usuários</label>
							</div>		
								</div>
								<div class="row">
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_entregas" /><label class="active" 
									for="per_entregas">Entregas</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_compradores" /><label class="active" 
									for="per_compradores">Compradores</label>
							</div>	
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_nfe" /><label class="active" 
									for="per_nfe">NFe</label>
							</div>	
								</div>
								<div class="row">
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_orcamentos" /><label class="active" 
									for="per_orcamentos">Orçamentos</label>
							</div>	
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_pedidos" /><label class="active" 
									for="per_pedidos">Pedidos</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_compras" /><label class="active" 
									for="per_compras">Compras</label>
							</div>
								</div>
								<div class="row">
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_contas_pagar" /><label class="active" 
									for="per_contas_pagar">Contas a Pagar</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_contas_receber" /><label class="active" 
									for="per_contas_receber">Contas a receber</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_relatorios" /><label class="active" 
									for="per_relatorios">Relatórios</label>
							</div>
								</div>
								<div class="row">
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_configuracao" /><label class="active" 
									for="per_configuracao">Configuração</label>
							</div>
							<div class="col s12 l4">
								<input type="checkbox" valign="botton" id="per_produtos_con" /><label class="active" 
									for="per_produtos_con">Produtos Consulta</label>
							</div>
							<div class="col s6 l4">
								<input type="checkbox" valign="botton" id="inativo" /><label class="active" 
									for="inativo">Usuario inativo</label
							</div>
								</div>
					</div>
				</li>
			</ul>
			<div class="row"> 
				<div class="col s12 l4">
					<a class="waves-effect waves-light btn red"
						href="javascript:limparUsuario()">Limpar</a>
				</div>
				<div class="col s12 l4">
					<a href="javascript:gravarUsuarios()"
						class="waves-effect waves-light btn green" id="gravarUsuarios">Gravar</a>
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
