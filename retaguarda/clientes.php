<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>Clientes</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"	rel="stylesheet">
<link rel="stylesheet"	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<?php include ("clientes_class.php");?> 
<script type="text/javascript" src="js/clientes.js"></script>
<body>
	<div class="container" id="cliente_principal">
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
					onClick='cadastro_cliente(0)' id='but_add'><i
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
	<div class="container" id="cliente_cadastro">
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
									onfocusout="verificarCodigo();" id="codigo" /><label
									for="codigo" class="active">Código</label>
							</div>
							<div class="input-field col s12 l8">
								<input type="text" class="input-field" placeholder=""
									id="fantasia" /><label for="fantasia" class="active">Fantasia</label>
							</div>
							<div class="input-field col s12 l12">
								<input type="text" class="input-field" placeholder=""
									id="razao_social" /><label for="razao_social" class="active">Razão Social</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 l6">
								<input type="text" class="input-field" placeholder=""
									id="cpf_cnpj" /><label class="active" for="cpf_cnpj">CPF/CNPJ</label>
							</div>
							<div class="input-field col s12 l6">
								<input type="text" class="input-field" placeholder=""
									id="inscricao_rg" /><label class="active" for="inscricao_rg">I.E./RG</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 l6">
								<input type="text" class="input-field" placeholder=""
									id="contato" /><label class="active" for="contato">Contato(nome do telefone da
									empresa)</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder=""
									id="classificacao" /><label class="active" for="classificacao">Classificação(A/B/C/D/E)</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder="" id="cep" onfocusout="consultarCep();" /><label
									for="cep" class="active" >CEP</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder=""
									id="logradouro" /><label class="active" for="logradouro">Logradouro</label>
							</div>
							<div class="input-field col s4 l3">
								<input type="text" class="input-field" placeholder=""
									id="numero" value="0"/><label class="active" for="numero">Número</label>
							</div>
							<div class="input-field col s12 l3">
								<input type="text" class="input-field" placeholder=""
									id="complemento" /><label class="active" for="complemento">Complemento</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l9">
								<input type="text" class="input-field" placeholder=""
									id="bairro" /><label class="active" for="bairro">Bairro</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder=""
									id="vencimento" value="1"/><label class="active" for="vencimento">Dia Vencimento</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" disabled
									id="municipio_desc" /><label class="active" for="municipio_desc">Municipio</label>
							</div>
							<div class="input-field col s6 l2">
								<input type="text" class="input-field" placeholder="" disabled
									id="municipio" value="0" /><label class="active" for="municipio">Cod Mun. IBGE</label>
							</div>
							<div class="input-field col s6 l2">
								<input type="text" class="input-field" placeholder="" disabled
									id="uf_desc" /><label class="active" for="uf_desc">UF</label>
							</div>
							<div class="input-field col s6 l2">
								<input type="text" class="input-field" placeholder="" disabled
									id="uf" value="0" /><label class="active" for="uf">Cod UF IBGE</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l4">
								<input type="text" class="input-field" placeholder="" id="fone" /><label
									for="fone" class="active">Fone</label>
							</div>
							<div class="input-field col s6 l4">
								<input type="text" class="input-field" placeholder=""
									id="celular" /><label class="active" for="celular">Celular</label>
							</div>
							<div class="input-field col s12 l4">
								<input type="text" class="input-field" placeholder="" id="email" /><label
									for="email"  class="active">E-mail</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="senha" /><label
									for="senha"  class="active">Senha</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" class="input-field" placeholder="" id="limite" value="0" /><label
									for="limite" class="active">Limite</label>
							</div>
							<div class="col s6 l3">
								<input type="checkbox" valign="botton" id="inativo" /><label
									for="inativo">Cliente inativo</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 l12">
								<input type="text" class="input-field" placeholder=""
									id="observacao" /><label class="active" for="senha">Observacao</label>
							</div>
						</div>
				
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-4 white-text"">
						<i class="material-icons">square_foot</i>Outros
					</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s12 l4">
								<input type="text" class="input-field" placeholder=""
									id="conjuge" /><label class="active" for="conjuge">Conjuge</label>
							</div>
							<div class="input-field col s12 l4">
								<input type="text" class="input-field" placeholder="" id="pai" /><label
									for="pai" class="active">Pai</label>
							</div>
							<div class="input-field col s12 l4">
								<input type="text" class="input-field" placeholder="" id="mae" /><label
									for="mae" class="active">Mae</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="ref1" /><label
									for="ref1" class="active">Referencia 1</label>
							</div>
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="fone_ref1" /><label
									for="fone_ref1" class="active">Fone Ref1</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="ref2" /><label
									for="ref2" class="active">Referencia 2</label>
							</div>
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="fone_ref2" /><label
									for="fone_ref2" class="active">Fone Ref2</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="profissao" /><label
									for="profissao" class="active">Profissao</label>
							</div>
							<div class="input-field col s6 l6">
								<input type="date"  class="datepicker activ" placeholder="" id="nascimento" /><label
									for="nascimento" class="active">Nascimento</label>
							</div>
							<div class="input-field col s6 l6">
								<input type="text" class="input-field" placeholder="" id="numeracao" /><label
									for="numeracao" class="active">Numeracao</label>
							</div>
						</div>
					</div>
				</li>
			</ul>
			<div class="row"> 
				<div class="col s12 l4">
					<a class="waves-effect waves-light btn red"
						href="javascript:limparCliente()">Limpar</a>
				</div>
				<div class="col s12 l4">
					<a href="javascript:gravarClientes()"
						class="waves-effect waves-light btn green" id="gravarClientes">Gravar</a>
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
