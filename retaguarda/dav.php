<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>DAV</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<script type="text/javascript" src="js/dav.js"></script>
<body>
	<div class="container" id="dav_principal">
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
			<div class="input-field col s4 l4">
				<label for='desc_pesquisa'>Pesquisa</label> <input type='text'
					id='desc_pesquisa' />
			</div>
			<div class="col s2 l2">
				<a class="btn-floating btn-small waves-effect yellow"
					id='but_fetchall'><i class="material-icons">search</i></a> <a
					class="btn-floating btn-small waves-effect green"
					onClick='cadastro_dav(0)' id='but_add'><i class="material-icons">add</i></a>
			</div>
		</div>
		<form action="#">
			<table class="responsive-table striped" id='userTable'>
				<thead>
					<tr>
						<th>Codigo</th>
						<th>Data</th>
						<th>Hora</th>
						<th>Cliente</th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</form>
		<div row="s 12" id='paginacao'></div>
		<br>
	</div>
	<div class="container" id="dav_cadastro">
		<br>
		<div class="row">
			<div class="input-field col s4 l4">
				<label for="dav_codigo" class="active">Codigo</label><input
					type="text" class="input-field" placeholder="" id="dav_codigo" disabled />
			</div>
			<div class="input-field col s4 l4">
				<label for="dataEmissao" class="active">Data Emissão</label><input
					type="text" class="input-field" placeholder="" id="dataEmissao"
					disabled />
			</div>
			<div class="input-field col s4 l4">
				<label for="horaEmissao" class="active">Hora Emissão</label><input
					type="text" class="input-field" placeholder="" id="horaEmissao"
					disabled />
			</div>
		</div>
		<div class="row">
			<div class="col s12 l12">
					<label class="active">Vendedor</label> <select id="dav_vendedor" class="browser-default">
							</select>
			</div>
		</div>
		<form action="#">
			<ul class="collapsible">
				<li>
					<div class="collapsible-header  #7986cb indigo lighten-3 white-text">Cliente</div>
					<div class="collapsible-body">
						<div id="dav_cliente_pesquisa">
							<br>
							<div class="row">
								<div class="input-field col s6">
									<label for='desc_pesquisa_cliente'>Pesquisa</label> <input
										type='text' id='desc_pesquisa_cliente' />
								</div>
								<div class="col s6">
									<a class="btn-floating btn-small waves-effect yellow"
										id='but_fetch_cliente'><i class="material-icons">search</i></a>
								</div>
							</div>
							<form id="consultaCliente" action="#">
								<table class="responsive-table striped" id='userTableCliente'>
									<thead>
										<tr>
											<th>Codigo</th>
											<th>Nome</th>
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
							<div row="s 12" id='paginacaoCliente'></div>
						</div>
						<div id="dav_cliente">
							<div class="row">
								<div class="input-field col s6 l1">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_codigo" /><label class="active" for="cli_codigo">Codigo</label>
								</div>
								<div class="input-field col s12 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_razao_social" /><label class="active"
										for="cli_razao_social">Razao Social</label>
								</div>
								<div class="input-field col s12 l5">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_cpf_cnpj" /><label class="active" for="cli_cpf_cnpj">CPF/CNPJ</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_logradouro" /><label class="active"
										for="cli_logradouro">Logradouro</label>
								</div>
								<div class="input-field col s6 l2">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_numero" /><label class="active" for="cli_numero">Numero</label>
								</div>
								<div class="input-field col s6 l4">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_complemento" /><label class="active"
										for="cli_complemento">Complemento</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 l4">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_bairro" /><label class="active" for="cli_bairro">Bairro</label>
								</div>
								<div class="input-field col s12 l4">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_cep" /><label class="active" for="cli_cep">CEP</label>
								</div>
								<div class="input-field col s12 l4">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_inscricao_rg" /><label class="active"
										for="cli_inscricao_rg">I.E</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_municipio" /><label class="active" for="cli_municipio">Cod
										Mun.</label>
								</div>
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_municipio_desc" /><label class="active"
										for="cli_municipio_desc">Municipio</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled id="cli_uf"></input>
									<label class="active" for="cli_uf">Cod UF</label>
								</div> 
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled id="cli_uf_desc"></input>
									<label class="active" for="cli_uf_desc">UF</label>
								</div> 
							</div> 
							<div class="row">
								<div class="input-field col s12 l12">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_fone" /><label class="active" for="cli_fone">Fone</label>
								</div>
							</div>
							<div class="row">
								<div class="col s12 l12">
									<a href="javascript:retornarClienteDav()"
										class="waves-effect waves-light btn bule" id="retornarCli">Selecionar
										Outro</a>
								</div>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-4 white-text"">
						Produtos</div>
					<div class="collapsible-body">
						<div id="dav_produto_pesquisa">
							<br>
							<div class="row">
								<div class="input-field col s6">
									<label for='desc_pesquisa_produto'>Pesquisa</label> <input
										type='text' id='desc_pesquisa_produto' />
								</div>
								<div class="col s6">
									<a class="btn-floating btn-small waves-effect yellow"
										id='but_fetch_dav_produto'><i class="material-icons">search</i></a>
								</div>
							</div>
							<form id="consultaProduto" action="#">
								<table class="responsive-table striped" id='userTableDavProduto'>
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
							<div row="s 12" id='paginacaoDavProduto'></div>
							<div class="row">
								<div class="col s12 l12">
									<a href="javascript:retornarDavProduto()"
										class="waves-effect waves-light btn bule" id="retornarDavProd">Voltar</a>
								</div>
							</div>
						</div>
						<div id="dav_produto">
							<div class="row">
								<div class="input-field col s3 l3">
									<label class="active" for='dav_prod_codigo'>Codigo</label> <input
										type='text' id='dav_prod_codigo' />
								</div>
								<div class="input-field col s7 l7">
									<label class="active" for='dav_prod_descricao'>Descricao</label>
									<input type='text' id='dav_prod_descricao' />
								</div>
								<div class="col s2 l2">
									<a href="javascript:pesquisarProdutoDav()"
										class="btn-floating btn-small waves-effect yellow"><i
										class="material-icons">search</i></a>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s4 l4">
									<label class="active" for='dav_prod_complemento'>Complemento</label>
									<input type='text' id='dav_prod_complemento' />
								</div>
								<div class="input-field col s3 l3">
									<label class="active" for='dav_prod_qtde'>Qtde</label> <input
										type='text' id='dav_prod_qtde' />
								</div>
								<div class="input-field col s3 l3">
									<label class="active" for='dav_prod_valunit'>Val Unit.</label>
									<input type='text' id='dav_prod_valunit' />
								</div>
								<div class="col s2 l2">
									<a href="javascript:adicionarProdutoOrcamento()"
										class="btn-floating btn-small waves-effect green"><i
										class="material-icons">add</i></a>
								</div>
							</div>
							<div class="row">
								<form id="" action="#">
									<table class="table-wrapper responsive-table scroll striped"
										id='davProdutoItens'>
										<thead>
											<tr>
												<th>Codigo</th>
												<th>Descricao</th>
												<th>Qtde</th>
												<th>Val.Unit.</th>
												<th>Total</th>
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
											</tr>
										</tbody>
									</table>
								</form>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-2 white-text"">
						Informacoes Adicionais</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s12">
							<textarea id="dav_observacao" class="materialize-textarea"></textarea>
          					<label for="dav_observacao">Observacao</label>
							</div>
						</div>
				</li>
			</ul>
			<div class="row">
				<div class="col s4 l4">
					<label class="active" for='dav_subtotal'>SubTotal</label>
					<input type='text' id='dav_subtotal' />
				</div>
				<div class="col s4 l4">
					<label class="active" for='dav_desconto'>Desconto</label>
					<input type='text' id='dav_desconto' />
				</div>
				<div class="col s4 l4">
					<label class="active" for='dav_total'>Total</label>
					<input type='text' id='dav_total' />
				</div>
			</div>
			<div class="row">
				<div class="col s3 l3">
					<a class="waves-effect waves-light btn red"
						href="javascript:limparDav()">Limpar</a>
				</div>
				<div class="col s3 l3">
					<a href="javascript:gravarDav()"
						class="waves-effect waves-light btn green" id="gravarDav">Gravar</a>
				</div>
				<div class="col s3 l3">
					<a href="javascript:imprimir()"
						class="waves-effect waves-light btn bule" id="imprimir">Imprimir</a>
				</div>
				<div class="col s3 l3">
					<a href="javascript:retornarPrincipal()"
						class="waves-effect waves-light btn bule" id="retornar">Voltar</a>
				</div>

			</div>
		</form>
	</div>
</body>