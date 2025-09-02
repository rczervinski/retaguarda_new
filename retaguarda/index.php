<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include 'conexao.php';
$query = "select e.uf,c.regime_tributario from estabelecimentos e, configuracao c";
$result = pg_query($conexao, $query);
while ($row = pg_fetch_assoc($result)) {
    $_SESSION['uf'] = $row['uf'];
    $_SESSION['regime_tributario'] = $row['regime_tributario'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gutty Retaguarda</title>
<meta name="viewport"
	content="width=device-width, initial-scale=1 maximum-scale=1">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-fullscreen" content="yes">
<meta name="HandheldFriendly" content="True">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- CSS  -->
<link rel="stylesheet"
	href="lib/font-awesome/web-fonts-with-css/css/fontawesome-all.css">
<link rel="stylesheet" href="css/materialize.min.css">
<link rel="stylesheet" href="css/normalize.css">
<link rel="stylesheet" href="css/style.css">
<!-- materialize icon -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<!-- Owl carousel -->
<link rel="stylesheet"
	href="lib/owlcarousel/assets/owl.carousel.min.css">
<link rel="stylesheet"
	href="lib/owlcarousel/assets/owl.theme.default.min.css">
<!-- Slick CSS -->
<link rel="stylesheet" type="text/css" href="lib/slick/slick/slick.css">
<link rel="stylesheet" type="text/css"
	href="lib/slick/slick/slick-theme.css">
<!-- Magnific Popup core CSS file -->
<link rel="stylesheet"
	href="lib/Magnific-Popup-master/dist/magnific-popup.css">
</head>
<body id="homepage">
	<!-- BEGIN PRELOADING -->
	<div id="preloading" class="preloading" style="display: none;">
		<div class="wrap-preload">
			<div class="cssload-loader"></div>
		</div>
	</div>
	<!-- END PRELOADING -->
	<!-- HEADER -->
	<header id="header">
		<div class="nav-wrapper container">
			<div class="header-logo">
				<a href="index.php" class="nav-logo"><div id="titulo">G U T T Y</div></a>
				<div id="loading" class="valign-wrapper center-align">
					<img src="./img/loading.gif" width="50" height="50" />
				</div>
			</div>
			<div class="header-menu-button">
				<a href="#" data-activates="nav-mobile-category"
					class="button-collapse" id="button-collapse-category">
					<div class="cst-btn-menu">
						<i class="fas fa-align-right"></i>
					</div>
				</a>
			</div>

    		<div class="header-icon-menu">
    			<a href="configuracao.php"  data-activates="nav-mobile-account" class="button-collapse" id="button-collapse-account"><i class="fas fa-cog"></i></a>
  			</div>

		</div>
	</header>
	<nav>
		<ul id="nav-mobile-category" class="side-nav">
			<li class="sidenav-logo">Gutty</li>
			<li>
				<div class="search-wrapper ">
					<input id="search"><i class="material-icons">search</i>
					<div class="search-results"></div>
				</div>
			</li>
			<li>
				<ul class="collapsible collapsible-accordion">
					<li>
						<div class="collapsible-header">
							<i class="fas fa-plus"></i>Cadastros <span><i
								class="fas fa-caret-down"></i></span>
						</div>
						<div class="collapsible-body">
							<ul>
								<li><a class="waves-effect waves-blue" onClick="clientes();"><i
										class="fas fa-angle-right"></i>Clientes</a></li>
								<li><a class="waves-effect waves-blue" onClick="produtos();"><i
										class="fas fa-angle-right"></i>Produtos</a></li>
								<li><a class="waves-effect waves-blue" onClick="fornecedores();"><i
										class="fas fa-angle-right"></i>Fornecedores</a></li>
								<li><a class="waves-effect waves-blue"
									onClick="transportadoras();"><i class="fas fa-angle-right"></i>Transportadoras</a>
								</li>
								<li><a class="waves-effect waves-blue" onClick="vendedores();"><i
										class="fas fa-angle-right"></i>Vendedores</a></li>
								<li><a class="waves-effect waves-blue" onClick="usuarios();"><i
										class="fas fa-angle-right"></i>Usu&aacute;rios</a></li>
								<li><a class="waves-effect waves-blue" onClick="promocoes();"><i
										class="fas fa-angle-right"></i>Promocoes</a></li>
							</ul>
						</div>
					</li>
					<li>
						<div class="collapsible-header">
							<i class="fas fa-dollar-sign"></i>Financeiro <span><i
								class="fas fa-caret-down"></i></span>
						</div>
						<div class="collapsible-body">
							<ul>
								<li><a class="waves-effect waves-blue" onClick="receber();"><i
										class="fas fa-angle-right"></i>Contas Receber</a></li>
								<li><a class="waves-effect waves-blue" onClick="pagar();"><i
										class="fas fa-angle-right"></i>Contas Pagar</a></li>
							</ul>
						</div>
					</li>
					<li>
						<div class="collapsible-header">
							<i class="fas fa-plus"></i>Relatorios <span><i
								class="fas fa-caret-down"></i></span>
						</div>
						<div class="collapsible-body">
							<ul>
								<li><a class="waves-effect waves-blue" href="relatorios/produtos_listagem.php"><i
										class="fas fa-angle-right"></i>Listagem Produtos</a></li>
								<li><a class="waves-effect waves-blue" href="relatorios/produtos_listagem_ec.php"><i
										class="fas fa-angle-right"></i>Produtos no Ecomerce</a></li>
							</ul>
						</div>
					</li>
					<li><a class="waves-effect waves-blue" onClick="nfe();"><i
							class="fas fa-angle-right"></i>NFe</a></li>
					<li><a class="waves-effect waves-blue" onClick="dav();"><i
							class="fas fa-angle-right"></i>Orçamento</a></li>
					<li><a class="waves-effect waves-blue" onClick="vendasonline();"><i
							class="fas fa-angle-right"></i>Vendas On-Line</a></li>
				</ul>
			</li>
		</ul>
		<ul id="nav-mobile-account" class="side-nav">



  <li>
    <ul class="collapsible collapsible-accordion">
      <li>
        <div class="collapsible-header">
          <i class="fas fa-columns"></i>Configuração <span><i class="fas fa-caret-down"></i></span>
        </div>
        <div class="collapsible-body">
          <ul>
            <li>
              <ul class="collapsible collapsible-accordion">
                <li>
                  <div class="collapsible-header">
                    <i class="fas fa-exchange-alt"></i>Integração <span><i class="fas fa-caret-down"></i></span>
                  </div>
                  <div class="collapsible-body">
                    <ul>
                      <li>
                        <a class="waves-effect waves-blue" href="#" onClick="integracaoNuvemShop()"><i class="fas fa-cloud"></i>NuvemShop</a>
                      </li>
                      <li>
                        <a class="waves-effect waves-blue" href="#" onClick="integracaoMercadoLivre()"><i class="fas fa-shopping-bag"></i>MercadoLivre</a>
                      </li>
                    </ul>
                  </div>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </li>

</ul>
	</nav>
	<!-- END SIDENAV CATEGORY-->
	<!-- SIDENAV ACCOUNT-->
	<!--
<ul id="nav-mobile-account" class="side-nav">
  <li class="profile">
    <div class="li-profile-info">
      <img src="img/avatar-150x150.jpg" alt="profile">
      <h2>Jane Doe</h2>
      <div class="emailprofile">jano@gutty.com.br</div>
      <div class="Vendas">
         Balance : <span>R$1200</span>
      </div>
    </div>
    <div class="bg-profile-li" style="background-image: url(img/bg-profile.jpg);">
    </div>
  </li>
  <li>
    <a class="waves-effect waves-blue" href="index.html"><i class="fas fa-home"></i>Home</a>
  </li>
  <li>
    <a href="wish-list.html"><i class="fas fa-heart"></i>Wish list</a>
  </li>
  <li>
    <a href="gallery.html"><i class="fas fa-camera-retro"></i>Gallery</a>
  </li>
  <li>
    <a href="setting.html"><i class="fas fa-cog"></i>Setting</a>
  </li>
  <li>
    <ul class="collapsible collapsible-accordion">
      <li>
        <div class="collapsible-header">
          <i class="fas fa-columns"></i>Pages <span><i class="fas fa-caret-down"></i></span>
        </div>
        <div class="collapsible-body">
          <ul>
            <li>
              <a class="waves-effect waves-blue" href="index.html"><i class="fas fa-angle-right"></i>Home</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="setting.html"><i class="fas fa-angle-right"></i>Setting</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="404.html"><i class="fas fa-angle-right"></i>404</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="login.html"><i class="fas fa-angle-right"></i>Sign In</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="signup.html"><i class="fas fa-angle-right"></i>Sign Up</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="single-page.html"><i class="fas fa-angle-right"></i>Single page</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="gallery.html"><i class="fas fa-angle-right"></i>Gallery</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="product-list.html"><i class="fas fa-angle-right"></i>Product List</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="wish-list.html"><i class="fas fa-angle-right"></i>Wish List</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="product-page.html"><i class="fas fa-angle-right"></i>Product Detail</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="shopping-cart.html"><i class="fas fa-angle-right"></i>Shopping Cart</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="checkout.html"><i class="fas fa-angle-right"></i>Checkout</a>
            </li>
            <li>
              <a class="waves-effect waves-blue" href="contact.html"><i class="fas fa-angle-right"></i>Contact Us</a>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <a href="contact.html"><i class="fas fa-envelope"></i>Contact Us</a>
  </li>
  <li>
    <a href="login.html"><i class="fas fa-sign-in-alt"></i>Sign in</a>
  </li>
  <li>
    <a href="404.html"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
  </li>
</ul>-->
	<!-- END SIDENAV ACCOUNT-->
	<!-- END SIDENAV-->
	<!-- MAIN SLIDER -->
	<!-- <div class="main-slider" data-indicators="true"> -->
	<!--   <div class="carousel carousel-slider " data-indicators="true"> -->
	<!--     <a class="carousel-item"><img src="img/slide.jpg" alt="slider"></a> -->
	<!--     <a class="carousel-item"><img src="img/slide2.jpg" alt="slider"></a> -->
	<!--     <a class="carousel-item"><img src="img/slide3.jpg" alt="slider"></a> -->
	<!--   </div> -->
	<!-- </div> -->
	<!-- END MAIN SLIDER -->
	<div id="principal">
		<div class="main-slider" data-indicators="true">
			<div class="carousel carousel-slider " data-indicators="true">
				<a class="carousel-item"><img src="img/slide.jpg" alt="slider"></a>
				<a class="carousel-item"><img src="img/slide2.jpg" alt="slider"></a>
				<a class="carousel-item"><img src="img/slide3.jpg" alt="slider"></a>
			</div>
		</div>
	</div>
	<!-- POPULER SEARCH -->
	<div class="section populer-search">
		<div class="container">
			<div class="row row-title">
				<div class="col s12">
					<div class="section-title">
						<span class="theme-secondary-color">MAIS</span> UTILIZADOS
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col s12">
					<div class="list-tag-word">
						<a class="tag-word">Produtos</a> <a class="tag-word">Clientes</a>
						<a class="tag-word">NFe</a> <a class="tag-word">Relat&oacute;rios</a>
						<a class="tag-word">Envio de Contingencias</a> <a class="tag-word">Abrir
							Chamado</a> <a class="tag-word">Solicitar Boleto</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- END POPULER SEARCH -->
	<!-- FOOTER  -->
	<footer id="footer">
		<div class="footer-info">
			<div class="container">
				<div class="col s12 center">
					<i class="fas fa-map-marker-alt"></i> Gutty Automa&ccedil;&atilde;o
					Comercial<br> <i class="fas fa-phone-square"></i>(41)35340701<br> <i
						class="fas fa-envelope"></i> suporte@gutty.com.br<br>
					<div id="vencimento"></div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row row-footer-icon">
				<div class="col s12">
					<div class="footer-sosmed-icon ">
						<div class="wrap-circle-sosmed ">
							<a href="#">
								<div class="circle-sosmed">
									<i class="fab fa-instagram"></i>
								</div>
							</a>
						</div>
						<div class="wrap-circle-sosmed ">
							<a href="#">
								<div class="circle-sosmed">
									<i class="fab fa-linkedin-in"></i>
								</div>
							</a>
						</div>
						<div class="wrap-circle-sosmed ">
							<a href="#">
								<div class="circle-sosmed">
									<i class="fab fa-twitter"></i>
								</div>
							</a>
						</div>
						<div class="wrap-circle-sosmed ">
							<a href="#">
								<div class="circle-sosmed">
									<i class="fab fa-facebook-f"></i>
								</div>
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="row copyright">
				2023 <span>Gutty</span>, All rights reserved.
			</div>
		</div>
	</footer>
	<!-- END FOOTER -->
	<!-- Script -->
	<script type="text/javascript"
		src="https://code.jquery.com/jquery-3.2.1.js"></script>
	<!-- Utilitários para tratar referências circulares em AJAX -->
	<script type="text/javascript" src="js/utils.js"></script>
	<script
		src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/js/materialize.js"></script>
</head>
<!-- Owl carousel -->
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<!-- Magnific Popup core JS file -->
<script src="lib/Magnific-Popup-master/dist/jquery.magnific-popup.js"></script>
<!-- Slick JS -->
<script src="lib/slick/slick/slick.min.js"></script>
<!-- Custom script -->
<script src="js/custom.js"></script>
<script>
	$("#loading").hide();
	function integracao(){
		$("#principal").load("configuracao.php");
		$("#titulo").html("G U T T Y - Integração");
	}
	function integracaoNuvemShop(){
		$("#loading").show(); // Mostrar indicador de carregamento

		// Carregar a página de integração NuvemShop
		$("#principal").load("nuvemshop/integracao_nuvemshop.php", function() {
			$("#titulo").html("G U T T Y - Integração NuvemShop");
			$("#loading").hide();

			// Inicializar componentes Materialize
			$('.collapsible').collapsible();
			$('select').material_select();

			// Definir a variável global para o caminho base da Nuvemshop
			window.nuvemshopBasePath = "nuvemshop/";

			// Debug: página carregada
			console.log('Integração NuvemShop carregada com sucesso');
		});

		return false; // Prevenir comportamento padrão do link
	}

	function integracaoMercadoLivre(){
		$("#loading").show(); // Mostrar indicador de carregamento

		// Carregar a página de integração Mercado Livre
		$("#principal").load("integracao_mercadolivre.php", function() {
			$("#titulo").html("G U T T Y - Integração Mercado Livre");
			$("#loading").hide();

			// Inicializar componentes Materialize
			$('.collapsible').collapsible();
			$('select').material_select();

			// Debug: página carregada
			console.log('Integração Mercado Livre carregada com sucesso');
		});

		return false; // Prevenir comportamento padrão do link
	}

	function clientes(){
		$("#principal").load("clientes.php");
		$("#titulo").html("G U T T Y - Clientes");
	}
	function vendedores(){
		$("#principal").load("vendedores.php");
		$("#titulo").html("G U T T Y - Vendedores");
	}
	function produtos(){
		$("#principal").load("produtos.php", function() {
			// Carregar o script de sincronização (sem executar automaticamente)
			$.getScript("nuvemshop/js/sincronizacao_nuvemshop.js", function() {
				// Script carregado, mas sincronização será apenas manual via botão
				console.log("Script de sincronização carregado - use o botão para sincronizar");
			});
		});
		$("#titulo").html("G U T T Y - Produtos");
	}
	function fornecedores(){
		$("#principal").load("fornecedores.php");
		$("#titulo").html("G U T T Y - Fornecedores");
	}
	function transportadoras(){
		$("#principal").load("transportadoras.php");
		$("#titulo").html("G U T T Y - Transportadoras");
	}
	function usuarios(){
		$("#principal").load("usuarios.php");
		$("#titulo").html("G U T T Y - Usuarios");
	}
	function promocoes(){
		$("#principal").load("promocao.php");
		$("#titulo").html("G U T T Y - Promocoes");
	}
	function receber(){
		$("#principal").load("receber.php");
		$("#titulo").html("G U T T Y - Contas Receber");
	}
	function pagar(){
		$("#principal").load("pagar.php");
		$("#titulo").html("G U T T Y - Contas Pagar");
	}
	function nfe(){
		$("#principal").load("nfe.php");
		$.ajax({
			url: 'nfexml.php',
			type: 'post',
			data: { request: 'validade' },
			dataType: 'json',
			success: function(response) {
				$("#titulo").html("G U T T Y - NFe");
				$("#vencimento").html("Certificado val.: "+response);
			}
		});
	}
	function dav(){
		$("#principal").load("dav.php");
		$("#titulo").html("G U T T Y - D.A.V.");
	}
	function vendasonline(){
		$("#loading").show(); // Mostrar indicador de carregamento

		// Carregar a página de vendas online
		$("#principal").load("vendasonline.php", function() {
			$("#titulo").html("G U T T Y - Vendas On-line");
			$("#loading").hide();

			// Inicializar componentes Materialize
			$('.modal').modal();

			// Registrar para depuração
			$.post('debug_ajax.php', {
				action: 'load_complete',
				page: 'vendasonline'
			});
		});
	}
</script>
</body>
</html>
