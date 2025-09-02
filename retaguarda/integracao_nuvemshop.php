<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8">
<title>Integração NuvemShop</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/js/materialize.js"></script>
<script type="text/javascript" src="nuvemshop/js/integracao_nuvemshop.js"></script>
</head>
<body>
    <?php include ("conexao.php");?>

    <div class="container" id="nuvemshop_principal">
        <br>
        <div class="row">
            <div class="col s12">
                <h4>Integração com NuvemShop</h4>
                <p>Configure a integração com a plataforma NuvemShop.</p>
            </div>
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
                                    <th>ID da Loja</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <div class="row" style="margin-top: 20px;">
                            <div class="col s12">
                                <button class="waves-effect waves-light btn green" onClick="mostrarAutenticacao()">
                                    <i class="material-icons left">link</i>Autenticar na NuvemShop
                                </button>
                                <button class="waves-effect waves-light btn orange" onClick="testarConexaoNuvemShop()">
                                    <i class="material-icons left">wifi</i>Testar Conexão
                                </button>
                                <button class="waves-effect waves-light btn blue" onClick="sincronizarProdutos()">
                                    <i class="material-icons left">sync</i>Sincronizar Produtos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tela de autenticação -->
    <div class="container" id="nuvemshop_auth" style="display:none;">
        <br>
        <div class="row">
            <div class="col s12">
                <h4>Autenticação NuvemShop</h4>
                <p>Siga os passos abaixo para autenticar sua loja NuvemShop.</p>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Passo 1: Gerar Link de Autorização</span>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="store_url" type="text" class="validate">
                                <label for="store_url">URL do Painel da sua Loja NuvemShop</label>
                                <span class="helper-text">Exemplo: https://minhaloja.lojavirtualnuvem.com.br/admin/v2/dashboard/</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12">
                                <button class="waves-effect waves-light btn blue" onClick="gerarLinkAutorizacao()">
                                    <i class="material-icons left">link</i>Gerar Link de Autorização
                                </button>
                            </div>
                        </div>

                        <div class="row" id="link_container" style="display:none;">
                            <div class="col s12">
                                <div class="input-field">
                                    <input id="auth_link" type="text" class="validate" readonly>
                                    <label for="auth_link" class="active">Link de Autorização</label>
                                </div>
                                <p>Clique no botão abaixo para abrir o link de autorização em uma nova janela:</p>
                                <button class="waves-effect waves-light btn green" id="open_auth_link">
                                    <i class="material-icons left">open_in_new</i>Abrir Link de Autorização
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Passo 2: Inserir Dados de Acesso</span>
                        <p>Após autorizar o aplicativo, insira o Token de Acesso e o ID da Loja que você recebeu:</p>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="access_token" type="text" class="validate">
                                <label for="access_token">Token de Acesso</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="store_id" type="text" class="validate">
                                <label for="store_id">ID da Loja</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12">
                                <button class="waves-effect waves-light btn green" onClick="salvarCredenciais()">
                                    <i class="material-icons left">save</i>Salvar Credenciais
                                </button>
                                <button class="waves-effect waves-light btn red" onClick="voltarPrincipal()">
                                    <i class="material-icons left">arrow_back</i>Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.modal').modal();
            $('.collapsible').collapsible();

            $("#nuvemshop_principal").show();
            $("#nuvemshop_auth").hide();

            carregarConfiguracoes();

            // Adicionar evento ao botão de abrir link
            $(document).on('click', '#open_auth_link', function() {
                var authLink = $('#auth_link').val();
                if (authLink) {
                    window.open(authLink, '_blank');
                }
            });
        });

        function mostrarAutenticacao() {
            $("#nuvemshop_principal").hide();
            $("#nuvemshop_auth").show();
        }

        function voltarPrincipal() {
            $("#nuvemshop_principal").show();
            $("#nuvemshop_auth").hide();
        }

        function gerarLinkAutorizacao() {
            var storeUrl = $('#store_url').val().trim();

            if (!storeUrl) {
                alert('Por favor, insira a URL do painel da sua loja NuvemShop.');
                return;
            }

            // Remover trailing slashes
            storeUrl = storeUrl.replace(/\/+$/, '');

            // Extrair o domínio base da loja
            var domainRegex = /^(https?:\/\/[^\/]+)/i;
            var match = storeUrl.match(domainRegex);

            if (!match) {
                alert('URL inválida. Por favor, insira uma URL completa começando com http:// ou https://');
                return;
            }

            var baseDomain = match[1];
            var appId = '17589';

            var authLink = baseDomain + '/admin/apps/' + appId + '/authorize';

            $('#auth_link').val(authLink);
            $('#link_container').show();
        }

        function testarConexaoNuvemShop() {
            Materialize.toast('Testando conexão...', 2000);

            console.log('Iniciando teste de conexão com a NuvemShop...');

            const requestData = {
                url: 'integracao_ajax.php',
                method: 'POST',
                data: {
                    request: 'testarConexaoNuvemshop'
                },
                timestamp: new Date().toISOString()
            };

            console.log('Dados da requisição:', JSON.stringify(requestData, null, 2));

            $.ajax({
                url: requestData.url,
                type: requestData.method.toLowerCase(),
                data: requestData.data,
                dataType: 'json',
                beforeSend: function(xhr) {
                    console.log('Enviando requisição para o servidor...');
                },
                success: function(response) {
                    console.log('Resposta recebida:', JSON.stringify(response, null, 2));

                    if (response.success) {
                        console.log('Conexão estabelecida com sucesso!');
                        console.log('Detalhes da loja:', {
                            nome: response.store_name || 'Não informado',
                            url: response.store_url || 'Não informado'
                        });

                        Materialize.toast('Conexão estabelecida com sucesso!', 4000, 'green');
                        $('#status_conexao').html('<span class="green-text"><i class="material-icons">check_circle</i> Conectado</span>');

                        $('.nuvemshop-status').removeClass('red').addClass('green');
                        $('.nuvemshop-status-text').text('Conectado');

                        $('.requires-connection').removeClass('disabled');
                    } else {
                        console.error('Falha na conexão com a NuvemShop');
                        console.error('Detalhes do erro:', {
                            mensagem: response.message || 'Erro não especificado',
                            erro: response.error || 'Sem detalhes adicionais'
                        });

                        Materialize.toast('Falha na conexão: ' + response.message, 4000, 'red');
                        $('#status_conexao').html('<span class="red-text"><i class="material-icons">error</i> Desconectado</span>');

                        $('.nuvemshop-status').removeClass('green').addClass('red');
                        $('.nuvemshop-status-text').text('Desconectado');

                        $('.requires-connection').addClass('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:');
                    console.error('Status:', status);
                    console.error('Erro:', error);

                    try {
                        const responseText = xhr.responseText;
                        console.error('Resposta do servidor:', responseText);

                        try {
                            const jsonResponse = JSON.parse(responseText);
                            console.error('Resposta JSON:', jsonResponse);
                        } catch (e) {
                            console.error('A resposta não é um JSON válido');
                        }
                    } catch (e) {
                        console.error('Não foi possível acessar a resposta do servidor');
                    }

                    Materialize.toast('Erro ao testar conexão: ' + error, 4000, 'red');
                    $('#status_conexao').html('<span class="red-text"><i class="material-icons">error</i> Erro</span>');

                    $('.nuvemshop-status').removeClass('green').addClass('red');
                    $('.nuvemshop-status-text').text('Erro');
                },
                complete: function() {
                    console.log('Teste de conexão concluído em:', new Date().toISOString());
                }
            });

            $.post('debug_ajax.php', {
                action: 'log_connection_test',
                timestamp: new Date().toISOString(),
                message: 'Teste de conexão com a NuvemShop iniciado pelo usuário'
            });
        }

        // Função para sincronizar produtos
        function sincronizarProdutos() {
            Materialize.toast('Iniciando sincronização de produtos...', 2000);

            $.ajax({
                url: 'integracao_ajax.php',
                type: 'post',
                data: {
                    request: 'sincronizarProdutosNuvemshop'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Materialize.toast('Sincronização concluída com sucesso!', 4000, 'green');
                    } else {
                        Materialize.toast('Erro na sincronização: ' + response.message, 4000, 'red');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', xhr.responseText);
                    Materialize.toast('Erro na sincronização: ' + error, 4000, 'red');
                }
            });
        }
    </script>
</body>
</html>
