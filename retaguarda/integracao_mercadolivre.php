<?php
session_start();
include "conexao.php";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integração Mercado Livre</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .button-group .btn {
            margin: 5px 0;
        }
        .auth-steps {
            margin-top: 20px;
        }
        .step-card {
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 12px;
        }
        .status-ativo { background-color: #4caf50; }
        .status-inativo { background-color: #f44336; }
        .ml-logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        .integration-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <br>
        
        <!-- Tela principal -->
        <div id="mercadolivre_principal">
            <div class="integration-header">
                <img src="https://http2.mlstatic.com/frontend-assets/ui-navigation/5.21.22/mercadolibre/logo_large_25years@2x.png" 
                     alt="Mercado Livre" class="ml-logo">
                <h4>Integração Mercado Livre</h4>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Configuração Atual</span>
                            <table class="responsive-table striped" id="configTable">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th>Client ID</th>
                                        <th>Usuário ML</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>

                            <!-- Cards para mobile -->
                            <div class="hide-on-med-and-up" id="configCards">
                                <!-- Cards serão inseridos aqui via JavaScript -->
                            </div>

                            <div class="row" style="margin-top: 20px;">
                                <div class="col s12">
                                    <div class="button-group">
                                        <button class="waves-effect waves-light btn green" onClick="mostrarAutenticacao()">
                                            <i class="material-icons left">link</i>Autenticar
                                        </button>

                                        <button class="waves-effect waves-light btn orange" onClick="testarConexaoMercadoLivre()">
                                            <i class="material-icons left">wifi</i>Testar Conexão
                                        </button>

                                        <button class="waves-effect waves-light btn blue" onClick="abrirPaginaMercadoLivre('webhook/receiver.php')">
                                            <i class="material-icons left">settings_remote</i>Configurar Webhooks
                                        </button>

                                        <button class="waves-effect waves-light btn purple" onClick="abrirPaginaMercadoLivre('admin/dashboard.php')">
                                            <i class="material-icons left">dashboard</i>Dashboard
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tela de autenticação -->
        <div class="container" id="mercadolivre_auth" style="display:none;">
            <br>
            <div class="row">
                <div class="col s12">
                    <h4>Autenticação Mercado Livre</h4>
                    <p>Siga os passos abaixo para autenticar sua conta no Mercado Livre.</p>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="card step-card">
                        <div class="card-content">
                            <span class="card-title">Passo 1: Configurar Aplicação</span>
                            <p>Primeiro, configure os dados da sua aplicação Mercado Livre.</p>

                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <input id="client_id" type="text" class="validate">
                                    <label for="client_id">Client ID</label>
                                    <span class="helper-text">Obtido no painel de desenvolvedores do ML</span>
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="client_secret" type="password" class="validate">
                                    <label for="client_secret">Client Secret</label>
                                    <span class="helper-text">Chave secreta da aplicação</span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col s12">
                                    <button class="waves-effect waves-light btn blue" onClick="salvarConfiguracao()">
                                        <i class="material-icons left">save</i>Salvar Configuração
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="card step-card">
                        <div class="card-content">
                            <span class="card-title">Passo 2: Autorização OAuth2</span>
                            <p>Clique no botão abaixo para gerar o link de autorização do Mercado Livre.</p>

                            <div class="row">
                                <div class="col s12">
                                    <button class="waves-effect waves-light btn blue" onClick="gerarLinkAutorizacao()">
                                        <i class="material-icons left">link</i>Gerar Link de Autorização
                                    </button>
                                </div>
                            </div>

                            <div id="link_container" style="display:none; margin-top: 20px;">
                                <div class="row">
                                    <div class="input-field col s12">
                                        <textarea id="auth_link" class="materialize-textarea" readonly></textarea>
                                        <label for="auth_link">Link de Autorização</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s12">
                                        <button class="waves-effect waves-light btn green" id="open_auth_link">
                                            <i class="material-icons left">open_in_new</i>Abrir Link de Autorização
                                        </button>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s12">
                                        <div class="card yellow lighten-4">
                                            <div class="card-content">
                                                <span class="card-title">
                                                    <i class="material-icons left">info</i>Instruções
                                                </span>
                                                <ol>
                                                    <li>Clique no botão "Abrir Link de Autorização" acima</li>
                                                    <li>Faça login na sua conta do Mercado Livre</li>
                                                    <li>Autorize o aplicativo a acessar sua conta</li>
                                                    <li>Você será redirecionado automaticamente de volta</li>
                                                    <li>O token será salvo automaticamente no sistema</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <button class="waves-effect waves-light btn grey" onClick="voltarPrincipal()">
                        <i class="material-icons left">arrow_back</i>Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <script>
        // Definir URL base para AJAX
        window.mercadoLivreAjaxUrl = 'ml_ajax.php';
    </script>

    <script src="js/integracao_mercadolivre.js"></script>

    <script>
        $(document).ready(function() {
            $('.modal').modal();
            $('.collapsible').collapsible();

            $("#mercadolivre_principal").show();
            $("#mercadolivre_auth").hide();

            // Aguardar o carregamento do script externo antes de chamar a função
            setTimeout(function() {
                if (typeof carregarConfiguracoes === 'function') {
                    carregarConfiguracoes();
                } else {
                    console.error('Função carregarConfiguracoes não encontrada');
                }
            }, 100);

            // Adicionar evento ao botão de abrir link
            $(document).on('click', '#open_auth_link', function() {
                var authLink = $('#auth_link').val();
                if (authLink) {
                    window.open(authLink, '_blank');
                }
            });

            // Verificar se há parâmetros de sucesso/erro na URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'oauth_success') {
                M.toast({html: 'Autenticação realizada com sucesso!', classes: 'green'});
                carregarConfiguracoes();
            } else if (urlParams.get('error')) {
                const error = urlParams.get('error');
                const message = urlParams.get('message') || 'Erro na autenticação';
                M.toast({html: 'Erro: ' + message, classes: 'red'});
            }
        });

        function mostrarAutenticacao() {
            $("#mercadolivre_principal").hide();
            $("#mercadolivre_auth").show();
        }

        function voltarPrincipal() {
            $("#mercadolivre_principal").show();
            $("#mercadolivre_auth").hide();
        }

        function abrirPaginaMercadoLivre(pagina) {
            window.open('mercadolivre/' + pagina, '_blank');
        }
    </script>
</body>
</html>
