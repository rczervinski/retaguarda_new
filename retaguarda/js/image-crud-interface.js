/**
 * Interface do Sistema CRUD de Imagens para Produtos
 * Arquivo: image-crud-interface.js
 * Descrição: Interface visual para gerenciar imagens usando ProductImageCRUD
 */

// Variáveis globais para o sistema de imagens
let productImageCRUD = null;
let currentImages = [];
let selectedPosition = 1;

// Inicializar quando a página carregar
$(document).ready(function() {
    // Aguardar carregamento dos scripts
    setTimeout(function() {
        if (typeof ProductImageCRUD !== 'undefined') {
            productImageCRUD = new ProductImageCRUD({
                debug: true,
                backendUrl: 'image-crud-backend.php'
            });
            console.log('ProductImageCRUD inicializado');
        } else {
            console.error('ProductImageCRUD não carregado');
        }
    }, 1000);
});

/**
 * Função chamada quando a aba de imagens é aberta
 */
function initializeImageCRUD() {
    // Pegar o primeiro campo codigo_gtin (da seção principal, não da composição)
    const codigoGtin = $('input[id="codigo_gtin"]').first().val();

    console.log('🔍 Debug CRUD Imagens:');
    console.log('- Código GTIN encontrado:', codigoGtin);
    console.log('- Total de campos codigo_gtin:', $('input[id="codigo_gtin"]').length);

    // Debug: mostrar todos os valores dos campos codigo_gtin
    $('input[id="codigo_gtin"]').each(function(index) {
        console.log(`- Campo ${index + 1}:`, $(this).val(), '(parent:', $(this).closest('.collapsible-body').prev().text().trim(), ')');
    });

    if (!codigoGtin) {
        updateImageStats('Código GTIN não definido');
        console.warn('❌ Código GTIN está vazio');
        return;
    }

    if (!productImageCRUD) {
        updateImageStats('Sistema não inicializado');
        setTimeout(initializeImageCRUD, 500);
        return;
    }

    console.log('Carregando imagens para:', codigoGtin);
    loadProductImages(codigoGtin);
}

/**
 * Carregar imagens do produto
 */
async function loadProductImages(codigoGtin) {
    try {
        updateImageStats('Carregando imagens...');

        const result = await productImageCRUD.loadProductImages(codigoGtin);
        currentImages = result.images || [];

        console.log('Imagens carregadas:', currentImages);

        // Atualizar interface
        updateImagePositionsList();
        updateImageStats();

        // Selecionar primeira posição disponível
        if (currentImages.length > 0) {
            selectImagePosition(currentImages[0].position);
        } else {
            selectImagePosition(1);
        }

    } catch (error) {
        console.error('Erro ao carregar imagens:', error);
        updateImageStats('Erro ao carregar imagens: ' + error.message);
    }
}

/**
 * Atualizar lista de posições
 */
function updateImagePositionsList() {
    const container = $('#image-positions-list');
    const positions = productImageCRUD.getAvailablePositions();

    let html = '';
    positions.forEach(pos => {
        const hasImage = productImageCRUD.hasImageAtPosition(pos.position);
        const isSelected = selectedPosition === pos.position;

        html += `
            <div class="position-item ${isSelected ? 'selected' : ''}" onclick="selectImagePosition(${pos.position})" style="
                padding: 10px;
                margin: 5px 0;
                border: 2px solid ${isSelected ? '#2196f3' : '#ddd'};
                border-radius: 4px;
                cursor: pointer;
                background: ${isSelected ? '#e3f2fd' : hasImage ? '#e8f5e8' : '#fff'};
            ">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <strong>${pos.label}</strong>
                        <div style="font-size: 0.8em; color: #666;">
                            ${hasImage ? '✅ Imagem disponível' : '❌ Sem imagem'}
                        </div>
                    </div>
                    <div>
                        ${hasImage ? '<i class="material-icons green-text">check_circle</i>' : '<i class="material-icons grey-text">radio_button_unchecked</i>'}
                    </div>
                </div>
            </div>
        `;
    });

    container.html(html);
}

/**
 * Selecionar posição de imagem
 */
function selectImagePosition(position) {
    selectedPosition = position;
    updateImagePositionsList();
    updateImagePreview();
    updateActionButtons();
}

/**
 * Atualizar preview da imagem
 */
function updateImagePreview() {
    const image = productImageCRUD.getImageByPosition(selectedPosition);
    const previewContainer = $('#image-preview-container');
    const previewImage = $('#image-preview');
    const previewPlaceholder = $('#image-preview-placeholder');
    const imageInfo = $('#image-info');
    const positionName = selectedPosition === 5 ? 'da Categoria' : selectedPosition;

    if (image) {
        // Mostrar imagem
        previewImage.attr('src', image.url).show();
        previewPlaceholder.hide();
        previewContainer.css('border-color', '#4caf50');

        // Mostrar informações
        $('#image-filename').text(image.filename);
        $('#image-size').text(formatFileSize(image.size));
        $('#image-modified').text(formatDate(image.modified));
        imageInfo.show();

        // Atualizar placeholder para mostrar que está ocupada
        previewPlaceholder.html(`
            <i class="material-icons" style="font-size: 48px; color: #4caf50;">check_circle</i>
            <p style="margin: 10px 0; color: #4caf50; font-weight: bold;">Posição ${positionName} Ocupada</p>
            <p style="margin: 0; color: #666; font-size: 12px;">Exclua para adicionar nova imagem</p>
        `);

    } else {
        // Mostrar placeholder
        previewImage.hide();
        previewPlaceholder.show();
        previewContainer.css('border-color', '#ddd');
        imageInfo.hide();

        // Atualizar placeholder para mostrar que está vazia
        previewPlaceholder.html(`
            <i class="material-icons" style="font-size: 48px; color: #ccc;">add_photo_alternate</i>
            <p style="margin: 10px 0; color: #666;">Posição ${positionName} Vazia</p>
            <p style="margin: 0; color: #999; font-size: 12px;">Clique para adicionar imagem</p>
        `);
    }
}

/**
 * Atualizar botões de ação
 */
function updateActionButtons() {
    const hasImage = productImageCRUD.hasImageAtPosition(selectedPosition);
    const deleteBtn = $('#delete-btn');
    const uploadBtn = $('#upload-btn');
    const positionName = selectedPosition === 5 ? 'da Categoria' : selectedPosition;

    if (hasImage) {
        // Se já tem imagem, mostrar apenas botão de excluir
        deleteBtn.show();
        uploadBtn.hide();

        // Atualizar texto do botão de exclusão
        deleteBtn.html('<i class="material-icons left">delete</i>Excluir Imagem ' + positionName);
    } else {
        // Se não tem imagem, mostrar apenas botão de upload
        deleteBtn.hide();
        uploadBtn.show();

        // Atualizar texto do botão de upload
        uploadBtn.html('<i class="material-icons left">cloud_upload</i>Enviar Imagem ' + positionName);
    }
}

/**
 * Atualizar estatísticas
 */
function updateImageStats(customMessage = null) {
    const statsText = $('#stats-text');

    if (customMessage) {
        statsText.text(customMessage);
        return;
    }

    if (!productImageCRUD || !currentImages) {
        statsText.text('Carregando...');
        return;
    }

    const stats = productImageCRUD.getImageStats();
    const message = `${stats.total} imagens encontradas. ${stats.hasMain ? 'Principal ✅' : 'Principal ❌'} | ${stats.hasCategory ? 'Categoria ✅' : 'Categoria ❌'}`;
    statsText.text(message);
}

/**
 * Trigger upload de imagem
 */
function triggerImageUpload() {
    // Verificar se já existe imagem na posição selecionada
    const hasImage = productImageCRUD.hasImageAtPosition(selectedPosition);

    if (hasImage) {
        const positionName = selectedPosition === 5 ? 'da Categoria' : selectedPosition;
        showToast(`Já existe uma imagem na posição ${positionName}. Exclua primeiro para adicionar uma nova.`, 'orange');
        return;
    }

    $('#image-upload-input').click();
}

/**
 * Manipular upload de imagem
 */
$(document).ready(function() {
    $('#image-upload-input').change(async function() {
        const file = this.files[0];
        if (!file) return;

        // Verificação de segurança: não permitir sobrepor imagens
        const hasImage = productImageCRUD.hasImageAtPosition(selectedPosition);
        if (hasImage) {
            const positionName = selectedPosition === 5 ? 'da Categoria' : selectedPosition;
            showToast(`❌ Não é possível sobrepor imagens! Exclua a imagem ${positionName} primeiro.`, 'red');
            $(this).val(''); // Limpar input
            return;
        }

        try {
            showToast('Fazendo upload...', 'blue');

            await productImageCRUD.uploadImage(file, selectedPosition);

            // Recarregar imagens
            const codigoGtin = $('input[id="codigo_gtin"]').first().val();
            await loadProductImages(codigoGtin);

            showToast('Upload realizado com sucesso!', 'green');

        } catch (error) {
            console.error('Erro no upload:', error);
            showToast('Erro no upload: ' + error.message, 'red');
        } finally {
            // Limpar input
            $(this).val('');
        }
    });
});

/**
 * Deletar imagem selecionada
 */
async function deleteSelectedImage(event) {
    // Prevenir comportamento padrão que pode causar redirecionamento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log('🗑️ Iniciando exclusão de imagem...');

    const image = productImageCRUD.getImageByPosition(selectedPosition);
    if (!image) {
        console.warn('❌ Nenhuma imagem selecionada para deletar');
        return false;
    }

    const positionName = selectedPosition === 5 ? 'da Categoria' : selectedPosition;

    console.log('🔍 Imagem a ser deletada:', image);

    if (confirm(`Tem certeza que deseja deletar a Imagem ${positionName}?\n\nArquivo: ${image.filename}`)) {
        try {
            console.log('✅ Usuário confirmou exclusão');
            showToast('Deletando imagem...', 'orange');

            console.log('📡 Enviando requisição de exclusão...');
            const deleteResult = await productImageCRUD.deleteImage(image.filename);
            console.log('📡 Resultado da exclusão:', deleteResult);

            // Recarregar imagens
            console.log('🔄 Recarregando lista de imagens...');
            const codigoGtin = $('input[id="codigo_gtin"]').first().val();
            await loadProductImages(codigoGtin);

            console.log('✅ Exclusão concluída com sucesso');
            showToast('Imagem deletada com sucesso!', 'green');

        } catch (error) {
            console.error('❌ Erro ao deletar:', error);
            showToast('Erro ao deletar: ' + error.message, 'red');
        }
    } else {
        console.log('❌ Usuário cancelou exclusão');
    }

    return false; // Prevenir qualquer redirecionamento
}

/**
 * Utilitários
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(timestamp) {
    return new Date(timestamp * 1000).toLocaleString('pt-BR');
}

function showToast(message, color = 'blue') {
    if (typeof Materialize !== 'undefined') {
        Materialize.toast(message, 3000, color);
    } else {
        console.log(`Toast: ${message}`);
    }
}

/**
 * Interceptar abertura da aba de imagens
 */
$(document).on('click', '.collapsible-header', function() {
    const header = $(this);
    const isImageTab = header.find('i').text().trim() === 'image';

    if (isImageTab) {
        setTimeout(() => {
            initializeImageCRUD();
        }, 300);
    }
});

/**
 * Prevenir redirecionamentos indesejados em botões do CRUD
 */
$(document).on('click', '#delete-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    deleteSelectedImage(e);
    return false;
});

// Tornar funções globais
window.initializeImageCRUD = initializeImageCRUD;
window.selectImagePosition = selectImagePosition;
window.triggerImageUpload = triggerImageUpload;
window.deleteSelectedImage = deleteSelectedImage;
