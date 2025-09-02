/**
 * Sistema de Filtros Avançados para Produtos
 * Arquivo: filtros-avancados.js
 * Descrição: Gerencia todos os filtros da página de produtos
 */

// Variável global para armazenar filtros ativos
let filtrosAtivos = {};

// Inicializar sistema de filtros
$(document).ready(function() {
    // Inicializar modais do Materialize
    $('.modal').modal();
    
    // Carregar opções de categorias e grupos
    carregarOpcoesFiltroCategorias();
    carregarOpcoesFiltroGrupos();
    
    // Configurar eventos
    $('#filtro_nuvemshop').change(function() {
        if ($(this).is(':checked')) {
            $('#nuvemshop_subsecao').slideDown();
        } else {
            $('#nuvemshop_subsecao').slideUp();
            // Desmarcar subopções
            $('#filtro_nuvem_normal, #filtro_nuvem_vitrine, #filtro_nuvem_variante').prop('checked', false);
        }
    });

    // Configurar mudança de categoria para filtrar grupos
    $('#filtro_categoria').change(function() {
        const categoriaSelecionada = $(this).val();
        carregarOpcoesFiltroGrupos(categoriaSelecionada);
    });
});

/**
 * Carregar opções de categorias para o filtro
 */
function carregarOpcoesFiltroCategorias() {
    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: { request: 'carregar_categoria' },
        dataType: 'json',
        success: function(response) {
            const select = $('#filtro_categoria');
            select.empty();
            select.append('<option value="">Todas as categorias</option>');
            
            if (response && response.length > 0) {
                response.forEach(function(item) {
                    if (item.categoria && item.categoria.trim() !== '') {
                        select.append(`<option value="${item.categoria}">${item.categoria}</option>`);
                    }
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar categorias para filtro');
        }
    });
}

/**
 * Carregar opções de grupos para o filtro
 * @param {string} categoria - Categoria para filtrar grupos (opcional)
 */
function carregarOpcoesFiltroGrupos(categoria = '') {
    let requestData = { request: 'carregar_grupo' };
    
    // Se categoria específica foi selecionada, filtrar grupos por categoria
    if (categoria) {
        requestData.categoria_filtro = categoria;
    }

    $.ajax({
        url: 'produtos_ajax.php',
        type: 'post',
        data: requestData,
        dataType: 'json',
        success: function(response) {
            const select = $('#filtro_grupo');
            select.empty();
            select.append('<option value="">Todos os grupos</option>');
            
            if (response && response.length > 0) {
                response.forEach(function(item) {
                    if (item.grupo && item.grupo.trim() !== '') {
                        select.append(`<option value="${item.grupo}">${item.grupo}</option>`);
                    }
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar grupos para filtro');
        }
    });
}

/**
 * Aplicar filtros selecionados
 */
function aplicarFiltros() {
    // Coletar todos os filtros ativos
    filtrosAtivos = {
        // E-commerce
        nuvemshop: $('#filtro_nuvemshop').is(':checked'),
        mercadolivre: $('#filtro_mercadolivre').is(':checked'),
        shopee: $('#filtro_shopee').is(':checked'),
        americanas: $('#filtro_americanas').is(':checked'),
        
        // Tipos Nuvemshop
        nuvem_normal: $('#filtro_nuvem_normal').is(':checked'),
        nuvem_vitrine: $('#filtro_nuvem_vitrine').is(':checked'),
        nuvem_variante: $('#filtro_nuvem_variante').is(':checked'),
        
        // Categorias
        categoria: $('#filtro_categoria').val(),
        grupo: $('#filtro_grupo').val(),
        
        // Produtos locais
        apenas_locais: $('#filtro_apenas_locais').is(':checked')
    };

    console.log('Aplicando filtros:', filtrosAtivos);
    
    // Atualizar indicador visual no botão de filtro
    atualizarIndicadorFiltro();
    
    // Recarregar produtos com filtros
    fetchall();
}

/**
 * Limpar todos os filtros
 */
function limparFiltros() {
    // Desmarcar todos os checkboxes
    $('#modalFiltros input[type="checkbox"]').prop('checked', false);
    
    // Resetar selects
    $('#filtro_categoria, #filtro_grupo').val('');
    
    // Esconder subseção Nuvemshop
    $('#nuvemshop_subsecao').slideUp();
    
    // Limpar filtros ativos
    filtrosAtivos = {};
    
    // Atualizar indicador visual
    atualizarIndicadorFiltro();
    
    // Recarregar produtos sem filtros
    fetchall();
}

/**
 * Atualizar indicador visual do botão de filtro
 */
function atualizarIndicadorFiltro() {
    const botaoFiltro = $('.filter-button');
    const temFiltros = Object.values(filtrosAtivos).some(valor => 
        valor === true || (typeof valor === 'string' && valor.trim() !== '')
    );

    if (temFiltros) {
        botaoFiltro.removeClass('blue darken-1').addClass('orange darken-2');
        botaoFiltro.attr('title', 'Filtros Ativos - Clique para editar');
    } else {
        botaoFiltro.removeClass('orange darken-2').addClass('blue darken-1');
        botaoFiltro.attr('title', 'Filtros Avançados');
    }
}

/**
 * Função para obter filtros ativos (será usada no fetchall)
 * @returns {Object} Objeto com filtros ativos
 */
function obterFiltrosAtivos() {
    return filtrosAtivos;
}

// Tornar funções globais para uso em outros arquivos
window.aplicarFiltros = aplicarFiltros;
window.limparFiltros = limparFiltros;
window.obterFiltrosAtivos = obterFiltrosAtivos;
window.atualizarIndicadorFiltro = atualizarIndicadorFiltro;
window.carregarOpcoesFiltroCategorias = carregarOpcoesFiltroCategorias;
window.carregarOpcoesFiltroGrupos = carregarOpcoesFiltroGrupos;
