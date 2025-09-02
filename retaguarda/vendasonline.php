<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>Vendas OnLine</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<link rel="stylesheet" href="css/vendasonline_fix.css?v=<?php echo time(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
	.modal-fixed-footer {
		max-height: 90% !important;
		height: 90% !important;
		width: 90% !important;
		max-width: 1200px;
	}
	.modal-content {
		padding: 20px !important;
	}
	.card {
		margin-top: 10px;
		margin-bottom: 10px;
	}
	.card-title {
		font-size: 18px !important;
		font-weight: bold;
	}
	#tableDetalheVenda {
		margin-top: 10px;
	}
	#tableDetalheVenda th {
		font-weight: bold;
	}
	.row {
		margin-bottom: 0;
	}
	.col {
		padding: 5px;
	}
	#sincronizacao_status {
		display: none;
		margin-bottom: 20px;
	}
	.preloader-wrapper {
		margin-right: 10px;
	}
	.chip {
		margin-right: 5px;
	}
</style>
</head>
<?php include ("conexao.php");?>

<style>
    /* Estilos básicos movidos para vendasonline_fix.css */
    .action-buttons a {
        margin-right: 5px;
    }

    #loading {
        display: none;
        text-align: center;
        margin: 20px 0;
    }
</style>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.js"></script>
<!-- Incluir Materialize CSS -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/js/materialize.min.js"></script>
<!-- Utilitários para tratar referências circulares em AJAX -->
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/vendasonline.js"></script>
<script type="text/javascript">
    // Verificar se os utilitários foram carregados corretamente
    $(document).ready(function() {
        console.log("Página de vendas online carregada");
        if (typeof prepareAjaxData === 'function') {
            console.log("Função prepareAjaxData carregada corretamente");
        } else {
            console.error("Função prepareAjaxData não foi carregada!");
        }

        // Inicializar componentes do Materialize
        $('.modal').modal();
        console.log("Modais inicializados");

        // Definir função M.toast para compatibilidade com versões mais recentes do Materialize
        window.M = window.M || {};
        M.toast = function(options) {
            var message = options.html || '';
            var displayLength = options.displayLength || 4000;
            var className = options.classes || '';
            var completeCallback = options.completeCallback || null;

            return Materialize.toast(message, displayLength, className, completeCallback);
        };
    });
</script>
<body>
	<div class="container" id="vendas_online_principal">
		<br>
		<!-- Indicador de carregamento -->
		<div id="loading" style="display: none; text-align: center; margin-bottom: 20px;">
			<div class="preloader-wrapper small active">
				<div class="spinner-layer spinner-blue-only">
					<div class="circle-clipper left">
						<div class="circle"></div>
					</div>
					<div class="gap-patch">
						<div class="circle"></div>
					</div>
					<div class="circle-clipper right">
						<div class="circle"></div>
					</div>
				</div>
			</div>
			<span style="margin-left: 10px;">Carregando...</span>
		</div>

		<!-- Status da sincronização (oculto) -->
		<div id="sincronizacao_status" class="card-panel blue lighten-4" style="display: none;">
			<div class="valign-wrapper">
				<div class="preloader-wrapper small active">
					<div class="spinner-layer spinner-blue-only">
						<div class="circle-clipper left">
							<div class="circle"></div>
						</div>
						<div class="gap-patch">
							<div class="circle"></div>
						</div>
						<div class="circle-clipper right">
							<div class="circle"></div>
						</div>
					</div>
				</div>
				<span id="sincronizacao_texto">Sincronizando pedidos...</span>
			</div>
		</div>

		<!-- Resultados da sincronização (oculto) -->
		<div id="sincronizacao_resultados" style="display: none;" class="card-panel green lighten-4">
			<h5>Sincronização Concluída</h5>
			<div id="sincronizacao_detalhes"></div>
		</div>

		<!-- Paginação na parte superior -->
		<div class="row">
			<div class="col s12">
				<ul class="pagination center-align" id="paginacao_superior"></ul>
			</div>
		</div>

		<!-- Tabela com altura fixa e barra de rolagem -->
		<div class="card">
			<div class="card-content">
				<div class="table-container">
					<table class="responsive-table striped" id='userTable'>
						<thead>
							<tr>
								<th>Codigo</th>
								<th>Data</th>
								<th>Hora</th>
								<th>Cliente</th>
								<th>Total</th>
								<th>Status</th>
								<th>Origem</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Paginação na parte inferior -->
		<div class="row">
			<div class="col s12">
				<ul class="pagination center-align" id="paginacao_inferior"></ul>
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col s12">
				<a href="nuvemshop/verificar_dados_pedido.php" class="btn blue waves-effect waves-light">
					<i class="material-icons left">search</i>Verificar Dados de Pedido
				</a>
			</div>
		</div>
	</div>
	<div id="modalDetalheVenda" class="modal modal-fixed-footer">
		<div class="modal-content">
			<h4>Detalhes do Pedido</h4>
			<div class="row">
				<div class="col s12 m6">
					<div class="card">
						<div class="card-content">
							<span class="card-title">Informações do Cliente</span>
							<div class="row">
								<div class="col s12" id="nome"></div>
								<div class="col s12" id="cpf"></div>
								<div class="col s12" id="endereco"></div>
								<div class="col s6" id="cep"></div>
								<div class="col s6" id="bairro"></div>
								<div class="col s8" id="municipio"></div>
								<div class="col s4" id="uf"></div>
								<div class="col s6" id="fone"></div>
								<div class="col s6" id="email"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col s12 m6">
					<div class="card">
						<div class="card-content">
							<span class="card-title">Informações do Pedido</span>
							<div class="row">
								<div class="col s6" id="codigo_pedido"></div>
								<div class="col s6" id="codigo_externo"></div>
								<div class="col s6" id="data_pedido"></div>
								<div class="col s6" id="hora_pedido"></div>
								<div class="col s12" id="status_pedido"></div>
								<div class="col s12" id="payment_status"></div>
								<div class="col s12" id="origem_pedido"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-content">
					<span class="card-title">Itens do Pedido</span>
					<table class="responsive-table striped" id='tableDetalheVenda'>
						<thead>
							<tr>
								<th>Código</th>
								<th>Descrição</th>
								<th>Qtde</th>
								<th>Preço</th>
								<th>Total</th>
								<th>Observação</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-content">
					<span class="card-title">Resumo do Pagamento</span>
					<div class="row">
						<div class="col s6" id="total_produtos"></div>
						<div class="col s6" id="frete"></div>
						<div class="col s6" id="forma_pgto"></div>
						<div class="col s6" id="valor_pago"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer" id="rodapevendasonline">
			<a href="#!" class="modal-close waves-effect waves-green btn-flat">Fechar</a>
		</div>
	</div>



<script type="text/javascript" src="js/vendasonline_responsive.js?v=<?php echo time(); ?>"></script>
<script>
	// Carregar pedidos e sincronizar ao abrir a página
	$(document).ready(function() {
		// Inicializar a página com sincronização automática
		sincronizarPedidos(true);

		// Forçar a aplicação do estilo responsivo com rolagem horizontal
        setTimeout(function() {
            console.log("Forçando aplicação do estilo responsivo com rolagem horizontal");
            var tabela = document.getElementById('userTable');
            if (tabela) {
                tabela.classList.add('responsive-table');
                if (typeof configurarRolagemHorizontal === 'function' && window.innerWidth <= 600) {
                    configurarRolagemHorizontal();
                } else if (typeof atualizarTabelaResponsiva === 'function') {
                    atualizarTabelaResponsiva();
                }
            }
        }, 1000);
	});

	// Função para exibir o modal de sincronização automática
	function exibirModalSincronizacaoAutomatica(novos, atualizados) {
		var mensagem = "A sincronização automática foi concluída com sucesso!<br><br>";
		mensagem += "<b>" + novos + "</b> novos pedidos<br>";
		mensagem += "<b>" + atualizados + "</b> pedidos atualizados";

		$('#modalSincronizacaoMensagem').html(mensagem);
		$('#modalSincronizacaoAutomatica').modal('open');
	}
</script>
</body>