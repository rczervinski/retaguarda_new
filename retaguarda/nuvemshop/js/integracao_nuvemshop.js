// Função para carregar as configurações da NuvemShop
function carregarConfiguracoes() {
    $.ajax({
        url: 'integracao_ajax.php',
        type: 'post',
        data: { request: 'fetchNuvemshop' },
        dataType: 'json',
        success: function(response) {
            createRows(response);
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar configurações: ' + error);
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
            var descricao = response[i].descricao || 'NUVEMSHOP';
            var client_id = response[i].client_id || '';
            var status = response[i].ativo == 1 ? 'Ativo' : 'Inativo';
            var statusClass = response[i].ativo == 1 ? 'green-text' : 'red-text';
            var statusCardClass = response[i].ativo == 1 ? 'active' : 'inactive';

            // Criar linha da tabela (desktop)
            var tr_str = "<tr>" +
                "<td>" + codigo + "</td>" +
                "<td>" + descricao + "</td>" +
                "<td>" + client_id + "</td>" +
                "<td class='" + statusClass + "'>" + status + "</td>" +
                "<td>" +
                "<button class='btn-small waves-effect orange' onClick='testarConexaoNuvemshop()' title='Testar Conexão'>" +
                "<i class='material-icons'>wifi</i></button> " +
                "<button class='btn-small waves-effect " + (response[i].ativo == 1 ? "red" : "green") + "' " +
                "onClick='alterarStatus(" + codigo + ", " + (response[i].ativo == 1 ? "0" : "1") + ")' " +
                "title='" + (response[i].ativo == 1 ? "Desativar" : "Ativar") + "'>" +
                "<i class='material-icons'>" + (response[i].ativo == 1 ? "pause" : "play_arrow") + "</i></button> " +
                "<button class='btn-small waves-effect red darken-4' onClick='excluirConfig(" + codigo + ")' title='Excluir'>" +
                "<i class='material-icons'>delete</i></button>" +
                "</td>" +
                "</tr>";

            $("#configTable tbody").append(tr_str);

            // Criar card (mobile)
            var card_str = "<div class='config-card'>" +
                "<div class='config-card-header'>" +
                "<div class='config-card-title'>Configuração #" + codigo + "</div>" +
                "<div class='config-card-status " + statusCardClass + "'>" + status + "</div>" +
                "</div>" +
                "<div class='config-card-info'>" +
                "<strong>Descrição:</strong> " + descricao + "<br>" +
                "<strong style='margin-left: 13px;'>ID:</strong> " + client_id +
                "</div>" +
                "<div class='config-card-actions'>" +
                "<button class='btn-small waves-effect orange' onClick='testarConexaoNuvemshop()' title='Testar Conexão'>" +
                "<i class='material-icons left'>wifi</i>Testar</button>" +
                "<button class='btn-small waves-effect " + (response[i].ativo == 1 ? "red" : "green") + "' " +
                "onClick='alterarStatus(" + codigo + ", " + (response[i].ativo == 1 ? "0" : "1") + ")'>" +
                "<i class='material-icons left'>" + (response[i].ativo == 1 ? "pause" : "play_arrow") + "</i>" +
                (response[i].ativo == 1 ? "Desativar" : "Ativar") + "</button>" +
                "<button class='btn-small waves-effect red darken-4' onClick='excluirConfig(" + codigo + ")'>" +
                "<i class='material-icons left'>delete</i>Excluir</button>" +
                "</div>" +
                "</div>";

            $("#configCards").append(card_str);
        }
    } else {
        // Mensagem quando não há configurações (tabela)
        var tr_str = "<tr>" +
            "<td colspan='5' class='center'>Nenhuma configuração encontrada. Clique em 'Autenticar na NuvemShop' para começar.</td>" +
            "</tr>";
        $("#configTable tbody").append(tr_str);

        // Mensagem quando não há configurações (cards)
        var card_str = "<div class='config-card' style='text-align: center; border-left-color: #ff9800;'>" +
            "<div class='config-card-info' style='margin: 0; color: #666;'>" +
            "<i class='material-icons' style='font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;'>cloud_off</i>" +
            "Nenhuma configuração encontrada.<br>" +
            "Clique em 'Autenticar na NuvemShop' para começar." +
            "</div>" +
            "</div>";
        $("#configCards").append(card_str);
    }
}

// Função para testar a conexão com a NuvemShop
function testarConexaoNuvemshop() {
    // Mostrar indicador de carregamento
    Materialize.toast('Testando conexão...', 2000);
    
    $.ajax({
        url: 'integracao_ajax.php',
        type: 'post',
        data: {
            request: 'testarConexaoNuvemshop'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Materialize.toast('Conexão estabelecida com sucesso!', 4000, 'green');
                $('#status_conexao').html('<span class="green-text"><i class="material-icons">check_circle</i> Conectado</span>');
                
                // Atualizar a interface para mostrar que está conectado
                $('.nuvemshop-status').removeClass('red').addClass('green');
                $('.nuvemshop-status-text').text('Conectado');
                
                // Habilitar botões que dependem da conexão
                $('.requires-connection').removeClass('disabled');
            } else {
                console.error('Erro detalhado:', response);
                Materialize.toast('Falha na conexão: ' + response.message, 4000, 'red');
                $('#status_conexao').html('<span class="red-text"><i class="material-icons">error</i> Desconectado</span>');
                
                // Atualizar a interface para mostrar que está desconectado
                $('.nuvemshop-status').removeClass('green').addClass('red');
                $('.nuvemshop-status-text').text('Desconectado');
                
                // Desabilitar botões que dependem da conexão
                $('.requires-connection').addClass('disabled');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', xhr.responseText);
            Materialize.toast('Erro ao testar conexão: ' + error, 4000, 'red');
            $('#status_conexao').html('<span class="red-text"><i class="material-icons">error</i> Erro</span>');
            
            // Atualizar a interface para mostrar que há um erro
            $('.nuvemshop-status').removeClass('green').addClass('red');
            $('.nuvemshop-status-text').text('Erro');
        }
    });
}

// Garantir que a função esteja disponível globalmente
window.testarConexaoNuvemshop = testarConexaoNuvemshop;

// Função para alterar o status da configuração
function alterarStatus(codigo, status) {
    $.ajax({
        url: 'integracao_ajax.php',
        type: 'post',
        data: {
            request: 'atualizarStatusNuvemshop',
            codigo: codigo,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Materialize.toast('Status atualizado com sucesso!', 4000, 'green');
                carregarConfiguracoes();
            } else {
                Materialize.toast('Erro ao atualizar status: ' + response.message, 4000, 'red');
            }
        },
        error: function(xhr, status, error) {
            Materialize.toast('Erro ao atualizar status: ' + error, 4000, 'red');
        }
    });
}

// Função para excluir uma configuração
function excluirConfig(codigo) {
    if (confirm('Tem certeza que deseja excluir esta configuração?')) {
        $.ajax({
            url: 'integracao_ajax.php',
            type: 'post',
            data: {
                request: 'excluirConfigNuvemshop',
                codigo: codigo
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Materialize.toast('Configuração excluída com sucesso!', 4000, 'green');
                    carregarConfiguracoes();
                } else {
                    Materialize.toast('Erro ao excluir configuração: ' + response.message, 4000, 'red');
                }
            },
            error: function(xhr, status, error) {
                Materialize.toast('Erro ao excluir configuração: ' + error, 4000, 'red');
            }
        });
    }
}

// Função para mostrar a tela de autenticação
function mostrarAutenticacao() {
    $("#nuvemshop_principal").hide();
    $("#nuvemshop_auth").show();
}

// Função para voltar à tela principal
function voltarPrincipal() {
    $("#nuvemshop_principal").show();
    $("#nuvemshop_auth").hide();
}

// Função para gerar o link de autorização
function gerarLinkAutorizacao() {
    var storeUrl = $('#store_url').val().trim();
    
    if (!storeUrl) {
        Materialize.toast('Por favor, insira a URL do painel da sua loja NuvemShop.', 4000, 'red');
        return;
    }
    
    // Remover trailing slashes
    storeUrl = storeUrl.replace(/\/+$/, '');
    
    // Extrair o domínio base da loja
    var domainRegex = /^(https?:\/\/[^\/]+)/i;
    var match = storeUrl.match(domainRegex);
    
    if (!match) {
        Materialize.toast('URL inválida. Por favor, insira uma URL completa começando com http:// ou https://', 4000, 'red');
        return;
    }
    
    var baseDomain = match[1];
    var appId = '17589'; // ID do seu aplicativo
    
    // Construir o link de autorização
    var authLink = baseDomain + '/admin/v2/apps/' + appId + '/authorize';
    
    // Exibir o link
    $('#auth_link').val(authLink);
    $('#link_container').show();
    
    Materialize.toast('Link de autorização gerado com sucesso!', 4000, 'green');
}

// Função para salvar as credenciais
function salvarCredenciais() {
    var accessToken = $('#access_token').val().trim();
    var storeId = $('#store_id').val().trim();
    
    if (!accessToken) {
        Materialize.toast('Por favor, insira o Token de Acesso.', 4000, 'red');
        return;
    }
    
    if (!storeId) {
        Materialize.toast('Por favor, insira o ID da Loja.', 4000, 'red');
        return;
    }
    
    // Extrair o domínio da loja a partir da URL (se disponível)
    var storeUrl = $('#store_url').val().trim();
    var storeDomain = '';
    
    if (storeUrl) {
        var domainRegex = /^https?:\/\/([^\/]+)/i;
        var match = storeUrl.match(domainRegex);
        if (match && match[1]) {
            storeDomain = match[1];
        }
    }
    
    // Mostrar indicador de carregamento
    Materialize.toast('Salvando configurações...', 2000);
    
    // Salvar as credenciais no banco de dados
    $.ajax({
        url: 'integracao_ajax.php',
        type: 'post',
        data: {
            request: 'salvarTokenNuvemshop',
            access_token: accessToken,
            store_id: storeId,
            url_checkout: storeDomain ? 'https://' + storeDomain : ''
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Materialize.toast('Credenciais salvas com sucesso!', 4000, 'green');
                voltarPrincipal();
                carregarConfiguracoes();
            } else {
                console.error('Erro detalhado:', response);
                Materialize.toast('Erro ao salvar credenciais: ' + response.message, 4000, 'red');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', xhr.responseText);
            Materialize.toast('Erro ao salvar credenciais: ' + error, 4000, 'red');
        }
    });
}

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    // Inicializar componentes Materialize
    $('.modal').modal();
    $('.collapsible').collapsible();
    
    // Mostrar a tela principal e esconder a tela de autenticação
    $("#nuvemshop_principal").show();
    $("#nuvemshop_auth").hide();
    
    // Carregar as configurações
    carregarConfiguracoes();
    
    // Adicionar evento ao botão de abrir link
    $(document).on('click', '#open_auth_link', function() {
        var authLink = $('#auth_link').val();
        if (authLink) {
            window.open(authLink, '_blank');
        }
    });
    
    // Adicionar o evento de clique ao botão de teste
    $('#btn_testar_conexao').click(function(e) {
        e.preventDefault();
        testarConexaoNuvemshop();
    });
    
    // Botão de teste na página principal
    $('.btn-test-nuvemshop').click(function(e) {
        e.preventDefault();
        testarConexaoNuvemshop();
    });
});
