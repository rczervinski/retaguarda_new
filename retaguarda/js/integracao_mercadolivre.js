// Função para obter URL base para AJAX
function getAjaxUrl() {
    return window.mercadoLivreAjaxUrl || 'integracao_ajax.php';
}

// Função para carregar as configurações do Mercado Livre
function carregarConfiguracoes() {
    console.log('Carregando configurações ML...');
    console.log('URL AJAX:', getAjaxUrl());

    $.ajax({
        url: getAjaxUrl(),
        type: 'post',
        data: { request: 'fetchMercadoLivre' },
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        success: function(response) {
            console.log('Resposta recebida:', response);
            createRows(response);
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar configurações:');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);

            if (xhr.status === 403) {
                M.toast({html: 'Erro 403: Acesso negado. Verifique permissões.', classes: 'red'});
            } else {
                M.toast({html: 'Erro ao carregar configurações: ' + error, classes: 'red'});
            }
        }
    });
}

// Função para criar as linhas da tabela de configurações
function createRows(response) {
    var len = 0;
    $('#configTable tbody').empty();
    $('#configCards').empty();

    if (response != null) {
        len = response.length;
    }

    if (len > 0) {
        for (var i = 0; i < len; i++) {
            var codigo = response[i].codigo;
            var descricao = response[i].descricao;
            var client_id = response[i].client_id || 'Não configurado';
            var user_id = response[i].user_id || 'Não autenticado';
            var ativo = response[i].ativo;

            var statusText = ativo == 1 ? 'Ativo' : 'Inativo';
            var statusClass = ativo == 1 ? 'status-ativo' : 'status-inativo';

            // Linha da tabela (desktop)
            var tr_str = "<tr>" +
                "<td>" + codigo + "</td>" +
                "<td>" + descricao + "</td>" +
                "<td>" + client_id + "</td>" +
                "<td>" + user_id + "</td>" +
                "<td><span class='status-badge " + statusClass + "'>" + statusText + "</span></td>" +
                "<td>" +
                    "<button class='btn-small waves-effect waves-light " + (ativo == 1 ? 'red' : 'green') + "' " +
                    "onclick='alterarStatus(" + codigo + ", " + (ativo == 1 ? 0 : 1) + ")'>" +
                    "<i class='material-icons'>" + (ativo == 1 ? 'toggle_off' : 'toggle_on') + "</i>" +
                    "</button> " +
                    "<button class='btn-small waves-effect waves-light blue' onclick='editarConfiguracao(" + codigo + ")'>" +
                    "<i class='material-icons'>edit</i>" +
                    "</button>" +
                "</td>" +
                "</tr>";

            $("#configTable tbody").append(tr_str);

            // Card para mobile
            var card_str = "<div class='card'>" +
                "<div class='card-content'>" +
                    "<span class='card-title'>" + descricao + "</span>" +
                    "<p><strong>Código:</strong> " + codigo + "</p>" +
                    "<p><strong>Client ID:</strong> " + client_id + "</p>" +
                    "<p><strong>Usuário:</strong> " + user_id + "</p>" +
                    "<p><strong>Status:</strong> <span class='status-badge " + statusClass + "'>" + statusText + "</span></p>" +
                    "<div class='card-action'>" +
                        "<button class='btn-small waves-effect waves-light " + (ativo == 1 ? 'red' : 'green') + "' " +
                        "onclick='alterarStatus(" + codigo + ", " + (ativo == 1 ? 0 : 1) + ")'>" +
                        "<i class='material-icons left'>" + (ativo == 1 ? 'toggle_off' : 'toggle_on') + "</i>" +
                        (ativo == 1 ? 'Desativar' : 'Ativar') +
                        "</button> " +
                        "<button class='btn-small waves-effect waves-light blue' onclick='editarConfiguracao(" + codigo + ")'>" +
                        "<i class='material-icons left'>edit</i>Editar" +
                        "</button>" +
                    "</div>" +
                "</div>" +
            "</div>";

            $("#configCards").append(card_str);
        }
    } else {
        // Nenhuma configuração encontrada
        var tr_str = "<tr><td colspan='6' class='center-align'>Nenhuma configuração encontrada. Clique em 'Autenticar' para começar.</td></tr>";
        $("#configTable tbody").append(tr_str);

        var card_str = "<div class='card'>" +
            "<div class='card-content center-align'>" +
                "<span class='card-title'>Nenhuma configuração</span>" +
                "<p>Clique em 'Autenticar' para começar a configuração.</p>" +
            "</div>" +
        "</div>";
        $("#configCards").append(card_str);
    }
}

// Função para alterar status da configuração
function alterarStatus(codigo, novoStatus) {
    $.ajax({
        url: getAjaxUrl(),
        type: 'post',
        data: {
            request: 'atualizarStatusMercadoLivre',
            codigo: codigo,
            status: novoStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                M.toast({html: 'Status atualizado com sucesso!', classes: 'green'});
                carregarConfiguracoes();
            } else {
                M.toast({html: 'Erro ao atualizar status: ' + response.message, classes: 'red'});
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao atualizar status:', xhr.responseText);
            M.toast({html: 'Erro ao atualizar status: ' + error, classes: 'red'});
        }
    });
}

// Função para editar configuração
function editarConfiguracao(codigo) {
    // Buscar dados da configuração
    $.ajax({
        url: getAjaxUrl(),
        type: 'post',
        data: {
            request: 'buscarConfiguracaoMercadoLivre',
            codigo: codigo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                // Preencher campos com dados existentes
                $('#client_id').val(response.data.client_id || '');
                $('#client_secret').val(response.data.client_secret || '');

                // Mostrar tela de autenticação
                mostrarAutenticacao();

                M.toast({html: 'Dados carregados para edição', classes: 'blue'});
            } else {
                M.toast({html: 'Erro ao carregar dados: ' + response.message, classes: 'red'});
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados:', xhr.responseText);
            M.toast({html: 'Erro ao carregar dados: ' + error, classes: 'red'});
        }
    });
}

// Função para salvar configuração (Client ID e Secret)
function salvarConfiguracao() {
    var clientId = $('#client_id').val().trim();
    var clientSecret = $('#client_secret').val().trim();

    if (!clientId) {
        M.toast({html: 'Por favor, insira o Client ID.', classes: 'red'});
        return;
    }

    if (!clientSecret) {
        M.toast({html: 'Por favor, insira o Client Secret.', classes: 'red'});
        return;
    }

    // Mostrar indicador de carregamento
    M.toast({html: 'Salvando configuração...', classes: 'blue'});

    // Salvar configuração no banco de dados
    $.ajax({
        url: getAjaxUrl(),
        type: 'post',
        data: {
            request: 'salvarConfiguracaoMercadoLivre',
            client_id: clientId,
            client_secret: clientSecret
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                M.toast({html: 'Configuração salva com sucesso!', classes: 'green'});
                // Não voltar para principal ainda, usuário precisa fazer OAuth
            } else {
                M.toast({html: 'Erro ao salvar configuração: ' + response.message, classes: 'red'});
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', xhr.responseText);
            M.toast({html: 'Erro ao salvar configuração: ' + error, classes: 'red'});
        }
    });
}

// Função para gerar link de autorização OAuth2
function gerarLinkAutorizacao() {
    var clientId = $('#client_id').val().trim();

    if (!clientId) {
        M.toast({html: 'Por favor, salve a configuração primeiro (Client ID é obrigatório).', classes: 'red'});
        return;
    }

    // Obter configuração do ambiente
    $.ajax({
        url: 'mercadolivre/get_config.php',
        type: 'get',
        dataType: 'json',
        success: function(config) {
            // Usar URL de callback do ambiente
            var redirectUri = config.callback_url;

            // Gerar state aleatório para segurança
            var state = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

            // Construir URL de autorização do Mercado Livre
            var authUrl = 'https://auth.mercadolivre.com.br/authorization?' +
                'response_type=code&' +
                'client_id=' + encodeURIComponent(clientId) + '&' +
                'redirect_uri=' + encodeURIComponent(redirectUri) + '&' +
                'state=' + encodeURIComponent(state);

            // Debug: mostrar URLs
            console.log('=== DEBUG OAUTH ===');
            console.log('Redirect URI:', redirectUri);
            console.log('Auth URL:', authUrl);
            console.log('Config:', config);

            // Exibir o link
            $('#auth_link').val(authUrl);
            $('#link_container').show();

            // Mostrar informações do ambiente
            var envInfo = config.is_ngrok ? 'Ambiente: ngrok' : (config.is_local ? 'Ambiente: Localhost' : 'Ambiente: Produção');
            console.log(envInfo, config);

            M.toast({html: 'Link de autorização gerado! (' + envInfo + ')', classes: 'green'});
        },
        error: function(xhr, status, error) {
            console.error('Erro ao obter configuração:', error);
            M.toast({html: 'Erro ao obter configuração do ambiente', classes: 'red'});
        }
    });
}

// Função para testar conexão com Mercado Livre
function testarConexaoMercadoLivre() {
    M.toast({html: 'Testando conexão...', classes: 'blue'});

    $.ajax({
        url: getAjaxUrl(),
        type: 'post',
        data: { request: 'testarConexaoMercadoLivre' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                M.toast({html: 'Conexão OK! ' + response.message, classes: 'green'});
            } else {
                M.toast({html: 'Erro na conexão: ' + response.message, classes: 'red'});
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao testar conexão:', xhr.responseText);
            M.toast({html: 'Erro ao testar conexão: ' + error, classes: 'red'});
        }
    });
}

// Função para mostrar a tela de autenticação
function mostrarAutenticacao() {
    $("#mercadolivre_principal").hide();
    $("#mercadolivre_auth").show();
}

// Função para voltar à tela principal
function voltarPrincipal() {
    $("#mercadolivre_principal").show();
    $("#mercadolivre_auth").hide();
    carregarConfiguracoes(); // Recarregar configurações
}
