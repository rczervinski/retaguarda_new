// Este arquivo adiciona funcionalidades responsivas à tabela de vendasonline
// Baseado no mesmo padrão implementado para produtos.php

// Função para adicionar atributos data-label a cada célula da tabela
function adicionarDataLabels(tabela) {
    // Obter todos os cabeçalhos da tabela
    var headers = Array.from(tabela.querySelectorAll('thead th')).map(function(th) {
        return th.textContent.trim();
    });

    console.log("Cabeçalhos encontrados:", headers);

    // Para cada linha da tabela
    var rows = tabela.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        // Para cada célula da linha
        var cells = row.querySelectorAll('td');
        cells.forEach(function(cell, index) {
            // Adicionar atributo data-label com o texto do cabeçalho correspondente
            if (index < headers.length) {
                // Sempre atualizar o data-label para garantir que esteja correto
                cell.setAttribute('data-label', headers[index]);
                console.log("Adicionado data-label:", headers[index], "à célula", index);
            }
        });
    });

    // Garantir que a tabela tenha a classe responsive-table
    if (!tabela.classList.contains('responsive-table')) {
        tabela.classList.add('responsive-table');
        console.log("Adicionada classe responsive-table à tabela");
    }
}

// Função para configurar rolagem horizontal com cards
function configurarRolagemHorizontal() {
    var tabela = document.getElementById('userTable');
    if (!tabela) return;

    console.log("Configurando rolagem horizontal com cards para a tabela de vendas online");

    // Adicionar data-label a cada célula da tabela
    adicionarDataLabels(tabela);

    // Aplicar também à tabela de detalhes do pedido
    var tabelaDetalhes = document.getElementById('tableDetalheVenda');
    if (tabelaDetalhes) {
        adicionarDataLabels(tabelaDetalhes);
    }

    // Forçar a aplicação do estilo responsivo
    document.querySelectorAll('table').forEach(function(table) {
        if (!table.classList.contains('responsive-table')) {
            table.classList.add('responsive-table');
            console.log("Adicionada classe responsive-table à tabela", table.id);
        }
    });

    // Verificar se o container da tabela tem overflow-x: auto
    var tableContainer = tabela.closest('.table-container');
    if (tableContainer) {
        // Garantir que o container tenha overflow-x: auto
        tableContainer.style.overflowX = 'auto';
        tableContainer.style.webkitOverflowScrolling = 'touch';
    }

    // Forçar a atualização do layout
    setTimeout(function() {
        window.dispatchEvent(new Event('resize'));
    }, 100);
}

// Função para atualizar a tabela após carregar novos dados via AJAX
function atualizarTabelaResponsiva() {
    // Aplicar data-labels à tabela principal para garantir que funcione em modo responsivo
    var tabela = document.getElementById('userTable');
    if (tabela) {
        console.log("Atualizando data-labels após AJAX");
        adicionarDataLabels(tabela);
    }

    // Verificar tamanho da tela e aplicar layout adequado
    var larguraTela = window.innerWidth;
    if (larguraTela <= 600) {
        configurarRolagemHorizontal();
    }
}

// Executar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log("Inicializando funcionalidades responsivas para vendasonline");

    // Aplicar data-labels à tabela principal para garantir que funcione em modo responsivo
    var tabela = document.getElementById('userTable');
    if (tabela) {
        console.log("Aplicando data-labels à tabela principal");
        adicionarDataLabels(tabela);
    }

    // Verificar se estamos em um dispositivo móvel e aplicar layout responsivo
    if (window.innerWidth <= 600) {
        configurarRolagemHorizontal();
    }

    // Adicionar evento para atualizar a tabela quando a janela for redimensionada
    window.addEventListener('resize', function() {
        var larguraTela = window.innerWidth;
        if (larguraTela <= 600) {
            configurarRolagemHorizontal();
        }
    });
});

// Modificar a função createRows original para adicionar data-labels
var originalCreateRows = window.createRows;
if (typeof originalCreateRows === 'function') {
    window.createRows = function(response) {
        // Chamar a função original
        originalCreateRows(response);

        // Adicionar data-labels após a criação das linhas
        setTimeout(function() {
            atualizarTabelaResponsiva();
        }, 100);
    };
}
