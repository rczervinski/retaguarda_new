// Este arquivo complementa o produtos.js original sem substituí-lo
// Adicione-o como um script adicional na sua página

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes Materialize se ainda não estiverem inicializados
    if (typeof M !== 'undefined') {
        // Inicializar collapsible
        var elems = document.querySelectorAll('.collapsible');
        if (elems.length > 0) {
            var instances = M.Collapsible.init(elems, {
                accordion: false
            });
        }

        // Inicializar tabs
        var tabs = document.querySelectorAll('.tabs');
        if (tabs.length > 0) {
            var tabsInstance = M.Tabs.init(tabs);
        }

        // Inicializar modais
        var modals = document.querySelectorAll('.modal');
        if (modals.length > 0) {
            var modalInstances = M.Modal.init(modals);
        }

        // Inicializar selects
        var selects = document.querySelectorAll('select');
        if (selects.length > 0) {
            var selectInstances = M.FormSelect.init(selects);
        }
    }

    // Melhorar a responsividade das tabs de estados
    ajustarTabsEstados();

    // Melhorar a visualização de imagens
    configurarPreviewImagens();

    // Ajustar layout em dispositivos móveis
    ajustarLayoutMobile();
});

// Função para ajustar as tabs de estados
function ajustarTabsEstados() {
    var tabsContainer = document.querySelector('.tabs');
    if (tabsContainer) {
        // Verificar se estamos em um dispositivo móvel
        if (window.innerWidth < 768) {
            // Adicionar classe para melhorar a visualização em dispositivos móveis
            tabsContainer.classList.add('tabs-fixed-width');

            // Adicionar botão para mostrar mais estados em dispositivos pequenos
            if (!document.getElementById('more-states-btn')) {
                var moreBtn = document.createElement('li');
                moreBtn.className = 'tab';
                moreBtn.id = 'more-states-btn';

                var moreLink = document.createElement('a');
                moreLink.href = '#';
                moreLink.textContent = '...';
                moreLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleHiddenTabs();
                });

                moreBtn.appendChild(moreLink);
                tabsContainer.appendChild(moreBtn);

                // Esconder alguns estados menos comuns em dispositivos pequenos
                var tabs = tabsContainer.querySelectorAll('.tab');
                var visibleCount = Math.min(10, tabs.length - 1);

                for (var i = visibleCount; i < tabs.length - 1; i++) {
                    tabs[i].classList.add('hidden-tab');
                }
            }
        }
    }
}

// Função para mostrar/esconder tabs adicionais
function toggleHiddenTabs() {
    var hiddenTabs = document.querySelectorAll('.hidden-tab');
    hiddenTabs.forEach(function(tab) {
        tab.classList.toggle('show-tab');
    });
}

// Função para configurar preview de imagens
function configurarPreviewImagens() {
    // Adicionar listeners para os inputs de arquivo
    for (var i = 1; i <= 5; i++) {
        var input = document.getElementById('input' + i);
        if (input) {
            input.addEventListener('change', function(e) {
                var id = e.target.id;
                var num = id.replace('input', '');
                previewImagem(e.target, 'foto' + num);
            });
        }
    }

    // Carregar imagens existentes
    carregarImagensExistentes();
}

// Função para preview de imagem
function previewImagem(input, previewId) {
    var preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// Função para carregar imagens existentes
function carregarImagensExistentes() {
    var codigo = document.getElementById('codigo_gtin');
    var categoria = document.getElementById('categoria');

    if (!codigo || !categoria) return;

    var codigoValue = codigo.value;
    var categoriaValue = categoria.value;

    if (codigoValue) {
        // Verificar se as imagens existem e exibi-las
        for (var i = 1; i <= 4; i++) {
            var suffix = i > 1 ? '_' + i : '';
            checkImageExists('../upload/' + codigoValue + suffix + '.jpg', 'foto' + i);
        }

        if (categoriaValue) {
            checkImageExists('../upload/' + categoriaValue + '.jpg', 'foto5');
        }
    }
}

// Função para verificar se uma imagem existe
function checkImageExists(url, previewId) {
    var img = new Image();
    img.onload = function() {
        var preview = document.getElementById(previewId);
        if (preview) {
            preview.innerHTML = '<img src="' + url + '?' + new Date().getTime() + '" alt="Imagem">';
        }
    };
    img.src = url;
}

// Função para ajustar layout em dispositivos móveis
function ajustarLayoutMobile() {
    var larguraTela = window.innerWidth;

    if (larguraTela < 768) {
        // Ajustar tamanho dos botões em dispositivos móveis
        var botoes = document.querySelectorAll('.btn, .btn-floating');
        botoes.forEach(function(botao) {
            if (!botao.classList.contains('btn-small')) {
                botao.classList.add('btn-small');
            }
        });

        // Melhorar visualização de tabelas em dispositivos móveis
        var tabelas = document.querySelectorAll('table');
        tabelas.forEach(function(tabela) {
            if (!tabela.classList.contains('responsive-table')) {
                tabela.classList.add('responsive-table');
            }
        });

        // Ajustar campos de formulário para melhor visualização
        var inputs = document.querySelectorAll('input[type="text"]');
        inputs.forEach(function(input) {
            input.style.fontSize = '14px';
        });

        // Ajustar labels para melhor visualização
        var labels = document.querySelectorAll('label');
        labels.forEach(function(label) {
            label.style.fontSize = '12px';
        });

        // Aplicar layout horizontal para tabelas em dispositivos muito pequenos
        if (larguraTela <= 460) {
            aplicarLayoutHorizontal();
        } else {
            removerLayoutHorizontal();
        }
    } else {
        removerLayoutHorizontal();
    }
}

// Função para aplicar layout horizontal em tabelas para dispositivos móveis
function aplicarLayoutHorizontal() {
    var tabela = document.getElementById('userTable');
    var container = tabela ? tabela.closest('.table-container') : null;

    if (!tabela || !container) return;

    // Verificar se já existe o container de rolagem horizontal
    var scrollContainer = container.querySelector('.mobile-scroll-container');

    // Se não existir, criar o container
    if (!scrollContainer) {
        // Remover a tabela do container atual
        var tabelaClone = tabela.cloneNode(true);
        container.removeChild(tabela);

        // Criar container de rolagem horizontal
        scrollContainer = document.createElement('div');
        scrollContainer.className = 'mobile-scroll-container';
        container.appendChild(scrollContainer);

        // Adicionar a tabela ao container de rolagem
        scrollContainer.appendChild(tabelaClone);

        // Adicionar data-label a cada célula da tabela
        adicionarDataLabels(tabelaClone);
    }
}

// Função para remover layout horizontal
function removerLayoutHorizontal() {
    var container = document.querySelector('.table-container');
    if (!container) return;

    var scrollContainer = container.querySelector('.mobile-scroll-container');
    if (scrollContainer) {
        // Obter a tabela do container de rolagem
        var tabela = scrollContainer.querySelector('table');

        // Remover o container de rolagem e adicionar a tabela diretamente ao container principal
        if (tabela) {
            container.removeChild(scrollContainer);
            container.appendChild(tabela);
        }
    }
}

// Função para adicionar atributos data-label a cada célula da tabela
function adicionarDataLabels(tabela) {
    // Obter todos os cabeçalhos da tabela
    var headers = Array.from(tabela.querySelectorAll('thead th')).map(function(th) {
        return th.textContent.trim();
    });

    // Para cada linha da tabela
    var rows = tabela.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        // Para cada célula da linha
        var cells = row.querySelectorAll('td');
        cells.forEach(function(cell, index) {
            // Adicionar atributo data-label com o texto do cabeçalho correspondente
            if (index < headers.length) {
                cell.setAttribute('data-label', headers[index]);
            }
        });
    });
}

// Adicionar evento de redimensionamento da janela
window.addEventListener('resize', function() {
    ajustarTabsEstados();
    ajustarLayoutMobile();

    // Atualizar layout da tabela quando a página for redimensionada
    var larguraTela = window.innerWidth;
    if (larguraTela <= 460) {
        aplicarLayoutHorizontal();
    } else {
        removerLayoutHorizontal();
    }
});

// Adicionar estilos CSS dinâmicos para tabs ocultas
var style = document.createElement('style');
style.textContent = `
    .hidden-tab {
        display: none !important;
    }
    .show-tab {
        display: block !important;
    }

    @media only screen and (max-width: 768px) {
        .tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
        }

        .tabs .tab {
            flex: 0 0 auto;
        }
    }
`;
document.head.appendChild(style);

// Melhorar a experiência com a área de grade
function melhorarAreaGrade() {
    var tabelaGrade = document.getElementById('userTableGrade');
    if (tabelaGrade) {
        // Adicionar classe para tornar a tabela responsiva
        tabelaGrade.classList.add('responsive-table');

        // Adicionar botão para adicionar nova linha na grade se não existir
        var btnAddGrade = document.getElementById('btnAddGrade');
        if (!btnAddGrade) {
            var divBotao = document.createElement('div');
            divBotao.className = 'right-align';
            divBotao.style.marginTop = '10px';

            var botao = document.createElement('a');
            botao.className = 'btn-floating btn-small waves-effect waves-light';
            botao.id = 'btnAddGrade';
            botao.innerHTML = '<i class="material-icons">add</i>';
            botao.onclick = function() {
                // Chamar a função original de adicionar grade, se existir
                if (typeof addGrade === 'function') {
                    addGrade();
                }
            };

            divBotao.appendChild(botao);
            tabelaGrade.parentNode.appendChild(divBotao);
        }
    }
}

// Melhorar a experiência com a área de composição
function melhorarAreaComposicao() {
    var tabelaComposicao = document.getElementById('userTableComposicao');
    if (tabelaComposicao) {
        // Adicionar classe para tornar a tabela responsiva
        tabelaComposicao.classList.add('responsive-table');

        // Adicionar botão para adicionar nova linha na composição se não existir
        var btnAddComposicao = document.getElementById('btnAddComposicao');
        if (!btnAddComposicao) {
            var divBotao = document.createElement('div');
            divBotao.className = 'right-align';
            divBotao.style.marginTop = '10px';

            var botao = document.createElement('a');
            botao.className = 'btn-floating btn-small waves-effect waves-light';
            botao.id = 'btnAddComposicao';
            botao.innerHTML = '<i class="material-icons">add</i>';
            botao.onclick = function() {
                // Chamar a função original de adicionar composição, se existir
                if (typeof addComposicao === 'function') {
                    addComposicao();
                }
            };

            divBotao.appendChild(botao);
            tabelaComposicao.parentNode.appendChild(divBotao);
        }
    }
}

// Melhorar a experiência com a área de IPI/PIS/COFINS
function melhorarAreaTributacao() {
    // Adicionar tooltips para campos de tributação
    var camposTributacao = document.querySelectorAll('#ipi_reducao_bc, #aliquota_ipi, #pis_reducao_bc, #aliquota_pis, #cofins_reducao_bc, #aliquota_cofins');
    camposTributacao.forEach(function(campo) {
        campo.classList.add('tooltipped');
        campo.setAttribute('data-position', 'top');

        if (campo.id.includes('ipi')) {
            campo.setAttribute('data-tooltip', 'Valor para cálculo de IPI');
        } else if (campo.id.includes('pis')) {
            campo.setAttribute('data-tooltip', 'Valor para cálculo de PIS');
        } else if (campo.id.includes('cofins')) {
            campo.setAttribute('data-tooltip', 'Valor para cálculo de COFINS');
        }
    });

    // Inicializar tooltips
    if (typeof M !== 'undefined' && M.Tooltip) {
        var tooltipElems = document.querySelectorAll('.tooltipped');
        var tooltipInstances = M.Tooltip.init(tooltipElems);
    }
}

// Executar funções adicionais após o carregamento do DOM
document.addEventListener('DOMContentLoaded', function() {
    melhorarAreaGrade();
    melhorarAreaComposicao();
    melhorarAreaTributacao();

    // Adicionar formatação para campos numéricos
    var camposNumericos = document.querySelectorAll('input[type="text"][id$="_bc"], input[type="text"][id^="aliquota_"]');
    camposNumericos.forEach(function(campo) {
        campo.addEventListener('blur', function() {
            formatarNumeroDecimal(this);
        });
    });

    // Verificar se estamos em um dispositivo móvel e aplicar layout horizontal
    if (window.innerWidth <= 460) {
        aplicarLayoutHorizontal();
    }
});

// Função para atualizar a tabela após carregar novos dados via AJAX
function atualizarTabelaResponsiva() {
    // Verificar tamanho da tela e aplicar layout adequado
    var larguraTela = window.innerWidth;
    if (larguraTela <= 460) {
        aplicarLayoutHorizontal();
    } else {
        removerLayoutHorizontal();
    }
}

// Função para formatar números decimais
function formatarNumeroDecimal(campo) {
    var valor = campo.value.replace(/[^\d.,]/g, '');
    valor = valor.replace(',', '.');

    if (!isNaN(parseFloat(valor))) {
        campo.value = parseFloat(valor).toFixed(2).replace('.', ',');
    } else {
        campo.value = '0,00';
    }
}
