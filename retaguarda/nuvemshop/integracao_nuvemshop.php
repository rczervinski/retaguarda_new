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

<!-- CSS Responsivo Customizado -->
<style>
/* === RESPONSIVIDADE GERAL === */
body {
    background-color: #ffffff !important;
    color: #2c3e50;
}

.container {
    width: 95% !important;
    max-width: 1200px;
}

/* === TÍTULOS RESPONSIVOS === */
h4 {
    font-size: 2rem;
    margin: 1rem 0;
}

h5 {
    font-size: 1.5rem;
    margin: 0.8rem 0;
}

/* === CARDS RESPONSIVOS === */
.card {
    margin: 0.5rem 0 1rem 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    background-color: #ffffff !important;
    border: 1px solid #e8f5e8;
}

.card-content {
    padding: 20px;
    background-color: #ffffff;
}

.card-title {
    font-size: 1.3rem !important;
    font-weight: 500;
    margin-bottom: 15px;
    color: #2e7d32;
}

/* === BOTÕES RESPONSIVOS === */
.btn {
    margin: 5px;
    border-radius: 4px;
    text-transform: none;
    font-weight: 500;
}

.btn i.left {
    margin-right: 8px;
}

/* === TABELA RESPONSIVA === */
.responsive-table {
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    background-color: #ffffff;
}

.responsive-table th {
    background-color: #f8fffe;
    font-weight: 600;
    color: #2e7d32;
    padding: 12px 8px;
    border-bottom: 2px solid #e8f5e8;
}

.responsive-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #f0f8f0;
    background-color: #ffffff;
    color: #2c3e50;
}

/* === INPUTS RESPONSIVOS === */
.input-field {
    margin-top: 1rem;
}

.input-field input[type=text] {
    border-radius: 4px;
    padding: 0 12px;
}

.helper-text {
    font-size: 0.8rem;
    color: #4a90e2;
}

/* === MOBILE FIRST - Telas pequenas (até 600px) === */
@media only screen and (max-width: 600px) {
    .container {
        width: 98% !important;
        padding: 0 10px;
    }

    h4 {
        font-size: 1.5rem;
        text-align: center;
        margin: 0.5rem 0;
    }

    h5 {
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }

    .card-content {
        padding: 15px;
    }

    .card-title {
        font-size: 1.1rem !important;
        text-align: center;
    }

    /* Botões em coluna no mobile */
    .btn {
        width: 100%;
        margin: 8px 0;
        padding: 0 16px;
        height: 48px;
        line-height: 48px;
    }

    .btn i.left {
        margin-right: 12px;
    }

    /* Tabela responsiva no mobile */
    .responsive-table {
        font-size: 0.9rem;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 8px 4px;
        text-align: center;
    }

    /* Esconder colunas menos importantes no mobile */
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        display: none;
    }

    /* Inputs no mobile */
    .input-field input[type=text] {
        font-size: 16px; /* Evita zoom no iOS */
        padding: 12px;
        background-color: #ffffff;
    }

    .helper-text {
        font-size: 0.75rem;
        line-height: 1.2;
        color: #4a90e2;
    }

    /* Espaçamento reduzido no mobile */
    .row {
        margin-bottom: 10px;
    }

    br {
        display: none;
    }
}

/* === TABLET - Telas médias (601px a 992px) === */


/* === DESKTOP - Telas grandes (993px+) === */
@media only screen and (min-width: 700px) {
    .container {
        width: 85% !important;
    }

    .btn {
        margin: 8px;
        min-width: 160px;
    }

    /* Layout em linha para botões no desktop */
    .button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-start;
    }

    .button-group .btn {
        flex: 0 0 auto;
        margin: 0;
    }
}

/* === MELHORIAS VISUAIS === */
.toast {
    border-radius: 4px;
    background-color: #ffffff !important;
    color: #2c3e50 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Animações suaves */
.card, .btn {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 6px 16px rgba(46, 125, 50, 0.15);
    transform: translateY(-2px);
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
}

/* === CARDS MOBILE PARA CONFIGURAÇÕES === */
.config-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 16px;
    margin: 10px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #2e7d32;
    border: 1px solid #e8f5e8;
}

.config-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.config-card-title {
    font-weight: 600;
    font-size: 1.1rem;
    color: #2e7d32;
}

.config-card-status {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 500;
}

.config-card-status.active {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.config-card-status.inactive {
    background-color: #ffebee;
    color: #c62828;
}

.config-card-info {
    margin: 8px 0;
    color: #2c3e50;
    font-size: 0.9rem;
}

.config-card-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.config-card-actions .btn-small {
    margin: 0;
    padding: 0 12px;
    height: 32px;
    line-height: 32px;
    font-size: 0.8rem;
}

/* === ACESSIBILIDADE === */
@media (prefers-reduced-motion: reduce) {
    .card, .btn {
        transition: none;
    }

    .card:hover, .btn:hover {
        transform: none;
    }
}

/* === MELHORIAS ADICIONAIS === */
.helper-text {
    word-break: break-word;
}

.input-field input[type=text] {
    background-color: #ffffff;
    border-radius: 4px;
    border: 1px solid #e8f5e8;
}

.input-field input[type=text]:focus {
    border-bottom: 2px solid #2e7d32 !important;
    box-shadow: 0 1px 0 0 #2e7d32 !important;
    background-color: #ffffff;
}

.input-field label.active {
    color: #2e7d32 !important;
}

/* === LOADING E ESTADOS === */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.95);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    border: 4px solid #ffffff;
    border-top: 4px solid #2e7d32;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* === TEMA CLARO FORÇADO === */
body, .container, .card, .config-card {
    background-color: #ffffff !important;
    color: #2c3e50 !important;
}

.responsive-table, .responsive-table td {
    background-color: #ffffff !important;
}

/* === MELHORIAS VISUAIS CLARAS === */
.green-text {
    color: #27ae60 !important;
}

.red-text {
    color: #e74c3c !important;
}

.orange-text {
    color: #f39c12 !important;
}

.blue-text {
    color: #3498db !important;
}

/* === PRINT STYLES === */
@media print {
    .btn, .button-group {
        display: none !important;
    }

    .card {
        box-shadow: none;
        border: 1px solid #ccc;
    }

    .config-card-actions {
        display: none;
    }
}
</style>
</head>
<body>
    <?php include ("../conexao.php");?>

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

                        <!-- Tabela para desktop -->
                        <div class="hide-on-small-only">
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
                        </div>

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

                                    <button class="waves-effect waves-light btn orange" onClick="testarConexaoNuvemShop()">
                                        <i class="material-icons left">wifi</i>Testar Conexão
                                    </button>

                                    <button class="waves-effect waves-light btn blue" onClick="abrirPaginaNuvemshop('registrar_webhooks.php')">
                                        <i class="material-icons left">settings_remote</i>Configurar Webhooks
                                    </button>
                                </div>
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
                        <span class="card-title">Sincronização Automática</span>
                        <p>Para manter seu sistema sempre atualizado com as vendas da Nuvemshop, você pode configurar uma sincronização automática.</p>

                        <div class="row">
                            <div class="col s12">
                                <h5>Webhooks (Sincronização de Vendas)</h5>
                                <p>Os webhooks permitem que a Nuvemshop notifique seu sistema automaticamente quando ocorrer uma venda.</p>
                                <ol>
                                    <li>Clique no botão "Configurar Webhooks" acima.</li>
                                    <li>Se você estiver em um ambiente local (localhost), siga as instruções para configurar um túnel HTTPS.</li>
                                    <li>Após configurar os webhooks, seu sistema será notificado automaticamente sobre novas vendas.</li>
                                </ol>

                                </ol>
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
                                <div class="button-group">
                                    <button class="waves-effect waves-light btn blue" onClick="gerarLinkAutorizacao()">
                                        <i class="material-icons left">link</i>Gerar Link de Autorização
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="link_container" style="display:none;">
                            <div class="col s12">
                                <div class="input-field">
                                    <input id="auth_link" type="text" class="validate" readonly>
                                    <label for="auth_link" class="active">Link de Autorização</label>
                                </div>
                                <p>Clique no botão abaixo para abrir o link de autorização em uma nova janela:</p>
                                <div class="button-group">
                                    <button class="waves-effect waves-light btn green" id="open_auth_link">
                                        <i class="material-icons left">open_in_new</i>Abrir Link de Autorização
                                    </button>
                                </div>
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
                                <div class="button-group">
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
    </div>

    <script>
        $(document).ready(function() {
            $('.modal').modal();
            $('.collapsible').collapsible();

            $("#nuvemshop_principal").show();
            $("#nuvemshop_auth").hide();

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

            // Debug: teste de conexão iniciado
            console.log('Teste de conexão com a NuvemShop iniciado pelo usuário em:', new Date().toISOString());
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

        // Função para sincronizar estoque
        function sincronizarEstoque(tipo) {
            var mensagem = '';
            if (tipo === 'local_para_nuvemshop') {
                mensagem = 'Enviando estoque para Nuvemshop...';
            } else {
                mensagem = 'Importando estoque da Nuvemshop...';
            }

            Materialize.toast(mensagem, 2000);

            // Verificar se a variável global de caminho está definida
            var basePath = window.nuvemshopBasePath || '';

            // Se não estiver definida, usar o caminho relativo
            if (!basePath) {
                console.warn('Variável nuvemshopBasePath não definida, usando caminho relativo');
                basePath = '';
            }

            $.ajax({
                url: basePath + 'sincronizar_estoque.php?tipo=' + tipo,
                type: 'get',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var detalhes = 'Produtos atualizados: ' + response.atualizados + ', Erros: ' + response.erros;
                        Materialize.toast('Sincronização de estoque concluída! ' + detalhes, 4000, 'green');

                        // Exibir log detalhado no console
                        console.log('Log de sincronização de estoque:', response.log);
                    } else {
                        Materialize.toast('Erro na sincronização de estoque: ' + response.message, 4000, 'red');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', xhr.responseText);
                    Materialize.toast('Erro na sincronização de estoque: ' + error, 4000, 'red');
                }
            });
        }

        // Função para abrir páginas da Nuvemshop com o caminho correto
        function abrirPaginaNuvemshop(pagina) {
            // Verificar se a variável global de caminho está definida
            var basePath = window.nuvemshopBasePath || '';

            // Se não estiver definida, usar o caminho relativo
            if (!basePath) {
                console.warn('Variável nuvemshopBasePath não definida, usando caminho relativo');
                basePath = '../nuvemshop/';
            }

            // Abrir a página com o caminho correto
            window.open(basePath + pagina, '_blank');
        }
    </script>
</body>
</html>
