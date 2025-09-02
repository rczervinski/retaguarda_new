<?php
session_start();
$regime_tributario = $_SESSION['regime_tributario'];
$uf = $_SESSION['uf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>NFe</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head> 
<?php include ("conexao.php");?>
<?php include ("nfe_class.php");?> 
<script type="text/javascript" src="js/nfe.js"></script>
<body>
	<div class="container" id="nfe_principal">
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
					onClick='cadastro_nfe(0)' id='but_add'><i class="material-icons">add</i></a>
			</div>
		</div>
		<form action="#">
			<table class="responsive-table striped" id='userTable'>
				<thead>
					<tr>
						<th>Documento</th>
						<th>Serie</th>
						<th>Data</th>
						<th>Hora</th>
						<th>Destinatario</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</form>
		<div row="s 12" id='paginacao'></div>
		<br>
		<div class="row">
				<div class="col s12 l3">
					<a href="#modalGerarXML" class="modal-trigger waves-effect waves-light btn yellow" id="gerarxml">Gerar XML</a>
				</div>
		</div>
	</div>
	<div class="container" id="nfe_cadastro">
		<form action="#">
			<ul class="collapsible">
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-2 white-text">
						Emitente</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s12 l12">
								<input type="text" id="cfop" value="5102" /><label for="cfop"
									class="active">CFOP</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l4">
								<input type="date" class="datepicker activ" id="emissao"
									placeholder="" /> <label class="active" for="emissao">Emissao</label>
							</div>
							<div class="input-field col s6 l4">
								<input type="date" class="datepicker activ" id="saida"
									placeholder="" /> <label class="active" for="saida">Saida</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l4">
								<input type="text" id="documento" value="" disabled /><label
									for="documento" class="active">Documento</label>
							</div>
							<div class="input-field col s6 l4">
								<input type="text" id="serie" value="" disabled /><label
									for="serie" class="active">Serie</label>
							</div>
						</div>
						<div class="row">
							<div class="col s6 l6">
								<label class="active">Finalidade</label> <select
									class="browser-default" id="finalidade">
									<option value="1" selected>1-Normal</option>
									<option value="2">2-Complementar</option>
									<option value="3">3-Ajuste</option>
									<option value="4">4-Devolucao</option>
								</select>
							</div>
							<div class="col s6 l6">
								<label class="active">Tipo</label> <select
									class="browser-default" id="tipo">
									<option value="0">0-Entrada</option>
									<option value="1" selected>1-Saida</option>
								</select>
							</div>
							<div class="row">
								<div class="input-field col s12 l4">
									<input type="text" disabled id="razao_social" value="" /><label
										for="razao_social" class="active">Estabelecimento</label>
								</div>
								<div class="input-field col s12 l4">
									<input type="text" disabled id="cnpj" value="" /><label
										for="cnpj" class="active">CNPJ</label>
								</div>
								<div class="input-field col s12 l4">
									<input type="text" disabled id="ie" value="" /><label for="ie"
										class="active">I.E.</label>
								</div>
							</div>
						</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-3 white-text"">Destinatario</div>
					<div class="collapsible-body">
						<div id="nfe_cliente_pesquisa">
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
						<div id="nfe_cliente">
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
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_uf" /><label class="active" for="cli_uf">Cod UF</label>
								</div>
								<div class="input-field col s6 l6">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_uf_desc" /><label class="active" for="cli_uf_desc">UF</label>
								</div>
								<div class="input-field col s12 l12">
									<input type="text" class="input-field" placeholder="" disabled
										id="cli_fone" /><label class="active" for="cli_fone">Fone</label>
								</div>
							</div>
							<div class="row">
								<div class="col s12 l12">
									<a href="javascript:retornarClienteNfe()"
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
						<div id="nfe_produto_pesquisa">
							<br>
							<div class="row">
								<div class="input-field col s6">
									<label for='desc_pesquisa_produto'>Pesquisa</label> <input
										type='text' id='desc_pesquisa_produto' />
								</div>
								<div class="col s6">
									<a class="btn-floating btn-small waves-effect yellow"
										id='but_fetch_nfe_produto'><i class="material-icons">search</i></a>
								</div>
							</div>
							<form id="consultaProduto" action="#">
								<table class="responsive-table striped" id='userTableNfeProduto'>
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
							<div row="s 12" id='paginacaoNfeProduto'></div>
							<div class="row">
								<div class="col s12 l12">
									<a href="javascript:retornarNfeProduto()"
										class="waves-effect waves-light btn bule" id="retornarNfeProd">Voltar</a>
								</div>
							</div>
						</div>
						<div id="nfe_produto">
							<div class="row">
								<div class="input-field col s4 l4">
									<label class="active" for='nfe_prod_codigo'>Codigo</label> <input
										type='text' id='nfe_prod_codigo' />
								</div>
								<div class="input-field col s8 l7">
									<label class="active" for='nfe_prod_descricao'>Descricao</label>
									<input type='text' id='nfe_prod_descricao' />
								</div>
								<div class="col s1 l1">
									<a href="javascript:pesquisarProdutoNfe()"
										class="btn-floating btn-small waves-effect yellow"><i
										class="material-icons">search</i></a>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s6 l3">
									<label class="active" for='nfe_prod_complemento'>Complemento</label>
									<input type='text' id='nfe_prod_complemento' />
								</div>
								<div class="input-field col s6 l3">
									<label class="active" for='nfe_prod_st'>ST</label> <input
										type='text' id='nfe_prod_st' />
								</div>
								<div class="input-field col s6 l3">
									<label class="active" for='nfe_prod_cfop'>CFOP</label> <input
										type='text' id='nfe_prod_cfop' />
								</div>
								<div class="input-field col s6 l3">
									<input type='hidden' id='nfe_prod_icms' />
								</div>
								<div class="input-field col s6 l3">
									<label class="active" for='nfe_prod_qtde'>Qtde</label> <input
										type='text' id='nfe_prod_qtde' />
								</div>
							</div>
							<div class="row">
								<div class="input-field col s4 l4">
									<label class="active" for='nfe_prod_desconto'>Desconto</label><input
										type='text' id='nfe_prod_desconto' />
								</div>
								<div class="input-field col s4 l4">
									<label class="active" for='nfe_prod_valunit'>Val Unit.</label>
									<input type='text' id='nfe_prod_valunit' />
								</div>
								<div class="col s1 l1">
									<a href="javascript:perguntarICMS()"
										class="btn-floating btn-small waves-effect green"><i
										class="material-icons">add</i></a>
								</div>
							</div>
							<div class="row">
								<form id="" action="#">
									<table class="table-wrapper responsive-table scroll striped"
										id='nfeProdutoItens'>
										<thead>
											<tr>
												<th>Codigo</th>
												<th>Descricao</th>
												<th>NCM/SH</th>
												<th>CST</th>
												<th>CFOP</th>
												<th>Un</th>
												<th>Qtde</th>
												<th>Val.Unit.</th>
												<th>Total</th>
												<!-- 												<th>BC ICMS</th> -->
												<!-- 												<th>Val ICMS</th> -->
												<!-- 												<th>Val IPI</th> -->
												<!-- 												<th>Aliq ICSM</th> -->
												<!-- 												<th>Aliq IPI</th> -->
												<!-- 												<th>Aliq ICMS ST</th> -->
												<!-- 												<th>BC ICMS ST</th> -->
												<!-- 												<th>Val ICMS ST</th> -->
												<!-- 												<th>Desc R$</th> -->
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
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
												<!-- 												<td></td> -->
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
						Impostos</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_bc_icms" value="0" /><label
									for="imposto_bc_icms" class="active">BC ICMS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_icms" value="0" /><label
									for="imposto_val_icms" class="active">Valor ICMS</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_bc_icms_st" value="0" /><label
									for="imposto_bc_icms_st" class="active">BC ICMS ST</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_icms_st" value="0" /><label
									for="imposto_val_icms_st" class="active">Valor ICMS ST</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_produtos" value="0" /><label
									for="imposto_val_produtos" class="active">Valor Produtos</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_frete" value="0" /><label
									for="imposto_val_frete" class="active">Frete</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_seguro" value="0" /><label
									for="imposto_val_seguro" class="active">Seguro</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_descontos" value="0" /><label
									for="imposto_val_descontos" class="active">Desconto</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_despesas" value="0" /><label
									for="imposto_val_despesas" class="active">Despesas</label>
							</div>
							<div class="input-field col s6 l3">
								<input type="text" id="imposto_val_ipi" value="0" /><label
									for="imposto_val_ipi" class="active">Val IPI</label>
							</div>
							<div class="input-field col s6 l6">
								<input type="text" id="imposto_val_total" value="0" /><label
									for="imposto_val_total" class="active">TOTAL</label>
							</div>
						</div>
					</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-3 white-text"">
						Faturas</div>
					<div class="collapsible-body">
						<div class="row">
							<div class="input-field col s3 l3">
								<label class="active" for='nfe_fat_numero'>Numero</label> <input
									type='text' id='nfe_fat_numero' />
							</div>
							<div class="input-field col s4 l4">
								<input type="date" class="datepicker activ"
									id="nfe_fat_vencimento" placeholder="" /> <label class="active"
									for="nfe_fat_vencimento">Vencimento</label>
							</div>
							<div class="input-field col s4 l4">
								<label class="active" for='nfe_fat_valor'>Valor</label> <input
									type='text' id='nfe_fat_valor' />
							</div>
							<div class="col s1 l1">
								<a href="javascript:adicionarFatura()"
									class="btn-floating btn-small waves-effect green"><i
									class="material-icons">add</i></a>
							</div>
							<div class="row">
								<form id="" action="#">
									<table class="table-wrapper responsive-table scroll striped"
										id='nfeFatItens'>
										<thead>
											<tr>
												<th>Numero</th>
												<th>Vencimento</th>
												<th>Valor</th>
												<th></th>
											</tr>
										</thead>
										<tbody>
											<tr>
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
						class="collapsible-header  #7986cb indigo lighten-4 white-text"">
						Referencia</div>
					<div class="collapsible-body">
						<div class="row">
								<div class="input-field col s6">
									<label for='nfe_referencia_chave'>Chave</label> <input
										type='text' id='nfe_referencia_chave' />
								</div>
								<div class="col s12 l12">
									<a href="javascript:limparDocRefNfe()"
										class="waves-effect waves-light btn bule" id="limparDocRef">Limpar</a>
								</div>
								<div class="col s12 l12">
									<a href="javascript:gravarDocRefNfe()"
										class="waves-effect waves-light btn bule" id="gravarDocRef">Gravar</a>
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
									<label for='nfe_inf_adicional'>Informacoes Adicionais Fisco</label> <input
										type='text' id='nfe_inf_adicional' />
								</div>
						</div>								
						<div class="row">
								<div class="input-field col s12">
									<label for='nfe_inf_adicional2'>Informacoes Adicionais</label> <input
										type='text' id='nfe_inf_adicional2' />
								</div>
								<div class="col s12 l12">
									<a href="javascript:gravarInfAdicional()"
										class="waves-effect waves-light btn bule" id="gravarInfAdicional">Gravar</a>
								</div>
						</div>
				</li>
				<li>
					<div
						class="collapsible-header  #7986cb indigo lighten-3 white-text"">
						Transportadora</div>
					<div class="collapsible-body">
						<div id="nfe_transportadora_pesquisa">
							<br>
							<div class="row">
								<div class="input-field col s6">
									<label for='desc_pesquisa_transportadora'>Pesquisa</label> <input
										type='text' id='desc_pesquisa_transportadora' />
								</div>
								<div class="col s6">
									<a class="btn-floating btn-small waves-effect yellow"
										id='but_fetch_transportadora'><i class="material-icons">search</i></a>
								</div>
							</div>
							<form id="consultaTransportadora" action="#">
								<table class="responsive-table striped"
									id='userTableTransportadora'>
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
							<div row="s 12" id='paginacaoTransportadora'></div>
						</div>
						<div id="nfe_transportadora">
							<div class="row">
								<div class="input-field col s4 l4">
									<label class="active" for='nfe_tra_codigo'>Codigo</label> <input
										type='text' id='nfe_tra_codigo' />
								</div>
								<div class="input-field col s8 l8">
									<label class="active" for='nfe_tra_razao_social'>Razao Social</label>
									<input type='text' id='nfe_tra_razao_social' />
								</div>
								<div class="col s3 l3">
									<label for="nfe_tran_frete" class="active">Frete</label> <select
										class="browser-default" value="9" id="nfe_tran_frete">
										<option value="0">0-Emitente</option>
										<option value="1">1-Destinatario</option>
										<option value="2">2-Terceiro</option>
										<option value="9" selected>9-Sem Frete</option>
									</select>
								</div>
								<div class="input-field col s3 l3">
									<label class="active" for='nfe_tra_cod_antt'>Cod ANTT</label> <input
										type='text' id='nfe_tra_cod_antt' />
								</div>
								<div class="input-field col s3 l3">
									<label class="active" for='nfe_tra_placa'>Placa</label> <input
										type='text' id='nfe_tra_placa' />
								</div>
								<div class="input-field col s3 l3">
									<label class="active" for='nfe_tra_placa_uf'>UF</label> <input
										type='text' id='nfe_tra_placa_uf' />
								</div>
								<div class="input-field col s6 l6">
									<label class="active" for='nfe_tra_cnpj'>CNPJ</label> <input
										type='text' id='nfe_tra_cnpj' />
								</div>
								<div class="input-field col s6 l6">
									<label class="active" for='nfe_tra_inscricao_rg'>I.E.</label> <input
										type='text' id='nfe_tra_inscricao_rg' />
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 l6">
									<label class="active" for='nfe_tra_logradouro'>Endereco</label>
									<input type='text' id='nfe_tra_logradouro' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_uf_desc'>UF</label> <input
										type='text' id='nfe_tra_uf_desc' />
								</div>
								<div class="input-field col s12 l4">
									<label class="active" for='nfe_tra_municipio_desc'>Municipio</label>
									<input type='text' id='nfe_tra_municipio_desc' />
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_qtde'>Qtde</label>
									<input type='text' id='nfe_tra_qtde' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_especie'>Especie</label> <input
										type='text' id='nfe_tra_especie' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_marca'>Marca</label>
									<input type='text' id='nfe_tra_marca' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_numeracao'>Numeracao</label>
									<input type='text' id='nfe_tra_numeracao' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_pesobruto'>Peso Bruto</label>
									<input type='text' id='nfe_tra_pesobruto' />
								</div>
								<div class="input-field col s12 l2">
									<label class="active" for='nfe_tra_pesoliquido'>Peso Liquido</label>
									<input type='text' id='nfe_tra_pesoliquido' />
								</div>
							</div>
							<div class="row">
								<div class="col s12 l12">
									<a href="javascript:retornarTransportadoraNfe()"
										class="waves-effect waves-light btn bule" id="retornarTra">Selecionar
										Outro</a>
								</div>
								<div class="col s12 l12">
									<a href="javascript:gravarTransportadoraNfe()"
										class="waves-effect waves-light btn bule" id="gravarTra">Gravar</a>
								</div>
								<div class="col s12 l12">
									<a href="javascript:limparTransportadoraNfe()"
										class="waves-effect waves-light btn bule" id="limparTra">Limpar</a>
								</div>
							</div>
						</div>
					</div>
				</li>
			</ul>
			<div class="row">
				<div class="col s12 l3">
					<a class="waves-effect waves-light btn red"
						href="javascript:limparNfe()">Limpar</a>
				</div>
				<div class="col s12 l3">
					<a href="javascript:gravarNfe()"
						class="waves-effect waves-light btn green" id="gravarNfe">Enviar</a>
				</div>
				<div class="col s12 l3">
					<a href="javascript:retornarPrincipal()"
						class="waves-effect waves-light btn bule" id="retornnar">Voltar</a>
				</div>
			</div>
		</form>
	</div>
	<div id="modalicms" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="percdev"><label
					class="active" for="percdev">Perc ICMS Devol</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarPercIcmsDevol(percdev.value);"
					class="modal-close waves-effect waves-green btn-flat">OK</a>
			</div>
		</div>
	</div>
	<div id="modalbcicms" class="modal">
		<div class="container">
			<div class="modal-content input-field">
				<input class="input-field" type="hidden" id="percdev2"><label
					class="active" for="percdev2">Perc ICMS Devol</label>
			</div>
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="bcicms_devol"><label
					class="active" for="bcicms_devol">Base Calculo ICMS Devol</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:adicionarBCIcmsDevol(bcicms_devol.value,percdev2.value);"
					class="modal-close waves-effect waves-green btn-flat">OK</a>
			</div>
		</div>
	</div>
	<div id="modalCorrecao" class="modal">
		<div class="container">
		<div class="modal-content input-field">
				<input class="input-field" type="hidden" id="codigo_interno_corr"><label
					class="active" for="codigo_interno_corr"></label>
			</div>
			<div class="modal-content input-field">
				<input class="input-field" type="text" id="correcao"><label
					class="active" for="correcao">Correcao</label>
			</div>
			<div class="modal-footer">
				<a href="javascript:efetuarCorrecao(correcao.value,codigo_interno_corr.value);"
					class="modal-close waves-effect waves-green btn-flat">OK</a>
			</div>
		</div>
	</div>
	<div id="modalGerarXML" class="modal">
		<div class="container">
			<div class="row">
				<label class="active" for="data1">Data Inicial</label>
				 <input type="date" class="col s12 l12 datepicker activ" id="data1" placeholder="" />
			 </div>		
			<div class="row">		
				<label class="active" for="data2">Data Final</label>
				<input type="date" class="col s12 l12 datepicker activ" id="data2" placeholder="" />
			</div> 
			<div class="modal-footer">
				<a href="javascript:gerarXML(data1.value,data2.value);"
					class="modal-close waves-effect waves-green btn-flat">Gerar</a>
			</div>
		</div>
	</div>
</body>
</html>
