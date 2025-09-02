/**
 * Grade Manager - Interface avançada para gerenciar variações de produtos
 * Permite editar preço, estoque, dimensões e imagens por variante
 */

class GradeManager {
    constructor() {
        this.currentProductId = null;
        this.variants = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupImageUpload();
    }

    bindEvents() {
        // Botão para abrir modal de grade avançada
        $(document).on('click', '#btn-grade-avancada', (e) => {
            e.preventDefault();
            this.openAdvancedGradeModal();
        });

        // Salvar alterações da grade
        $(document).on('click', '#btn-salvar-grade', (e) => {
            e.preventDefault();
            this.saveGradeChanges();
        });

        // Preview de imagem
        $(document).on('click', '.variant-image-preview', (e) => {
            e.preventDefault();
            const gtin = $(e.target).closest('.variant-card').data('gtin');
            this.showImagePreview(gtin);
        });
    }

    setupImageUpload() {
        // Configurar drag & drop para upload de imagens
        const dropZone = document.getElementById('image-drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('drag-over');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('drag-over');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.handleImageFiles(files);
                }
            });
        }
    }

    openAdvancedGradeModal() {
        const codigoInterno = document.getElementById('codigo_interno').value;
        if (!codigoInterno || codigoInterno === '0') {
            Materialize.toast('<i class="material-icons">error</i> Salve o produto primeiro', 3000, 'red');
            return;
        }

        this.currentProductId = codigoInterno;
        this.loadGradeData();
        
        // Abrir modal usando Materialize CSS 0.100.1
        $('#modal-grade-avancada').modal('open');
    }

    loadGradeData() {
        if (!this.currentProductId) return;

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: {
                request: 'carregar_grade_completa',
                codigo_interno: this.currentProductId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.variants = response.variants;
                    this.renderGradeInterface();
                } else {
                    Materialize.toast('<i class="material-icons">error</i> ' + (response.error || 'Erro ao carregar grade'), 3000, 'red');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erro ao carregar grade:', error);
                Materialize.toast('<i class="material-icons">error</i> Erro ao carregar grade', 3000, 'red');
            }
        });
    }

    renderGradeInterface() {
        const container = $('#grade-variants-container');
        container.empty();

        if (this.variants.length === 0) {
            container.html(`
                <div class="center-align" style="padding: 40px;">
                    <i class="material-icons large grey-text">apps</i>
                    <p class="grey-text">Nenhuma variação encontrada</p>
                    <p class="grey-text">Adicione variações na aba "Grade" primeiro</p>
                </div>
            `);
            return;
        }

        this.variants.forEach((variant, index) => {
            const card = this.createVariantCard(variant, index);
            container.append(card);
        });

        // Inicializar componentes Materialize (verificar se existe)
        if (typeof M !== 'undefined') {
            M.updateTextFields();
            $('.tooltipped').tooltip();
        }
        
        // Verificar quais imagens existem e mostrar/esconder botões de remover
        this.checkExistingImages();
    }

    checkExistingImages() {
        this.variants.forEach((variant) => {
            const gtin = variant.codigo_gtin;
            const img = $(`.variant-card[data-gtin="${gtin}"] img`);
            
            if (img.length > 0) {
                // Adicionar timestamp para evitar cache
                const currentSrc = img.attr('src');
                if (currentSrc && !currentSrc.includes('?')) {
                    img.attr('src', currentSrc + '?t=' + Date.now());
                }
                
                // Verificar se a imagem carregou com sucesso
                img.on('load', function() {
                    // Imagem existe, mostrar botão de remover
                    $(`.variant-card[data-gtin="${gtin}"] .remove-image-btn`).show();
                }).on('error', function() {
                    // Imagem não existe, esconder botão de remover
                    $(`.variant-card[data-gtin="${gtin}"] .remove-image-btn`).hide();
                });
            }
        });
    }

    createVariantCard(variant, index) {
        const gtin = variant.codigo_gtin;
        const cardId = `variant-card-${index}`;
        
        return `
            <div class="col s12 m6 l4" id="${cardId}" data-gtin="${gtin}">
                <div class="card variant-card" data-gtin="${gtin}">
                    <div class="card-content">
                        <span class="card-title activator grey-text text-darken-4">
                            ${variant.descricao || 'Variação'}
                            <i class="material-icons right">more_vert</i>
                        </span>
                        
                                                 <!-- Imagem da variante -->
                         <div class="variant-image-container center-align" style="margin: 15px 0;">
                             <div class="variant-image-preview" data-gtin="${gtin}" style="cursor: pointer;">
                                 <div class="image-container" style="width: 100px; height: 100px; position: relative;">
                                                                           <img src="../upload/${gtin}.webp" 
                                           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                           onload="this.nextElementSibling.style.display='none';"
                                           style="max-width: 100%; max-height: 100%; border-radius: 4px; object-fit: cover;" />
                                     <div class="no-image-placeholder" style="display: flex; width: 100%; height: 100%; border: 2px dashed #ddd; border-radius: 4px; align-items: center; justify-content: center; position: absolute; top: 0; left: 0; background: #f5f5f5;">
                                         <i class="material-icons grey-text">image</i>
                                     </div>
                                 </div>
                             </div>
                                                           <div style="margin-top: 10px;">
                                  <input type="file" 
                                         id="image-upload-${gtin}" 
                                         accept="image/*" 
                                         style="display: none;" 
                                         onchange="gradeManager.handleImageUpload(event, '${gtin}')" />
                                  <button class="btn-small waves-effect waves-light blue" 
                                          onclick="document.getElementById('image-upload-${gtin}').click()">
                                      <i class="material-icons">upload</i> Imagem
                                  </button>
                                  <button class="btn-small waves-effect waves-light red remove-image-btn" 
                                          onclick="gradeManager.removeImage('${gtin}')"
                                          style="margin-left: 5px; display: none;"
                                          data-gtin="${gtin}">
                                      <i class="material-icons">delete</i>
                                  </button>
                              </div>
                         </div>

                        <!-- Informações básicas -->
                        <div class="row">
                            <div class="col s12">
                                <div class="input-field">
                                    <input type="text" 
                                           id="variant-gtin-${index}" 
                                           value="${gtin}" 
                                           readonly />
                                    <label class="active">GTIN</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s6">
                                <div class="input-field">
                                    <input type="text" 
                                           id="variant-caracteristica-${index}" 
                                           value="${variant.caracteristica || ''}" 
                                           class="variant-input" 
                                           data-field="caracteristica" 
                                           data-index="${index}" />
                                    <label class="active">Característica</label>
                                </div>
                            </div>
                            <div class="col s6">
                                <div class="input-field">
                                    <input type="text" 
                                           id="variant-variacao-${index}" 
                                           value="${variant.variacao || ''}" 
                                           class="variant-input" 
                                           data-field="variacao" 
                                           data-index="${index}" />
                                    <label class="active">Variação</label>
                                </div>
                            </div>
                        </div>

                                                 <!-- Preço e estoque -->
                         <div class="row">
                             <div class="col s6">
                                 <div class="input-field">
                                     <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            id="variant-preco-${index}" 
                                            value="${parseFloat(variant.preco) || 0}"
                                            class="variant-input" 
                                            data-field="preco" 
                                            data-index="${index}" />
                                     <label class="active">Preço</label>
                                 </div>
                             </div>
                             <div class="col s6">
                                 <div class="input-field">
                                     <input type="number" 
                                            min="0" 
                                            id="variant-estoque-${index}" 
                                            value="${parseInt(variant.estoque) || 0}"
                                            class="variant-input" 
                                            data-field="estoque" 
                                            data-index="${index}" />
                                     <label class="active">Estoque</label>
                                 </div>
                             </div>
                         </div>

                         <!-- Dimensões -->
                         <div class="row">
                             <div class="col s3">
                                 <div class="input-field">
                                     <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            id="variant-peso-${index}" 
                                            value="${parseFloat(variant.peso) || 0}"
                                            class="variant-input" 
                                            data-field="peso" 
                                            data-index="${index}" />
                                     <label class="active">Peso (kg)</label>
                                 </div>
                             </div>
                             <div class="col s3">
                                 <div class="input-field">
                                     <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            id="variant-altura-${index}" 
                                            value="${parseFloat(variant.altura) || 0}"
                                            class="variant-input" 
                                            data-field="altura" 
                                            data-index="${index}" />
                                     <label class="active">Altura (cm)</label>
                                 </div>
                             </div>
                             <div class="col s3">
                                 <div class="input-field">
                                     <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            id="variant-largura-${index}" 
                                            value="${parseFloat(variant.largura) || 0}"
                                            class="variant-input" 
                                            data-field="largura" 
                                            data-index="${index}" />
                                     <label class="active">Largura (cm)</label>
                                 </div>
                             </div>
                             <div class="col s3">
                                 <div class="input-field">
                                     <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            id="variant-comprimento-${index}" 
                                            value="${parseFloat(variant.comprimento) || 0}"
                                            class="variant-input" 
                                            data-field="comprimento" 
                                            data-index="${index}" />
                                     <label class="active">Comp. (cm)</label>
                                 </div>
                             </div>
                         </div>
                    </div>

                    <!-- Card reveal -->
                    <div class="card-reveal">
                        <span class="card-title grey-text text-darken-4">
                            Detalhes da Variação
                            <i class="material-icons right">close</i>
                        </span>
                        <div class="variant-details">
                            <p><strong>GTIN:</strong> ${gtin}</p>
                            <p><strong>Característica:</strong> ${variant.caracteristica || 'N/A'}</p>
                            <p><strong>Variação:</strong> ${variant.variacao || 'N/A'}</p>
                            <p><strong>Código:</strong> ${variant.codigo || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    handleImageUpload(event, gtin) {
        const file = event.target.files[0];
        if (!file) return;

        // Verificar tipo de arquivo
        if (!file.type.startsWith('image/')) {
            Materialize.toast('<i class="material-icons">error</i> Arquivo deve ser uma imagem', 3000, 'red');
            return;
        }

        // Upload da imagem
        this.uploadImage(file, gtin);
    }

    uploadImage(file, gtin) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('gtin', gtin);
        formData.append('request', 'upload_variant_image');

        console.log('Fazendo upload de imagem:', file.name, 'Tipo:', file.type, 'Tamanho:', file.size);

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                console.log('Resposta do upload:', response);
                if (response.success) {
                    Materialize.toast('<i class="material-icons">check</i> Imagem enviada com sucesso', 2000, 'green');
                    
                    // Atualizar preview
                    const variantCard = $(`.variant-card[data-gtin="${gtin}"]`);
                    const previewImg = variantCard.find('.variant-image-preview img');
                    previewImg.attr('src', `../upload/${response.filename}?t=${Date.now()}`);
                    previewImg.show();
                    variantCard.find('.no-image-placeholder').hide();
                    
                    // Mostrar botão de remover
                    variantCard.find('.remove-image-btn').show();
                } else {
                    Materialize.toast('<i class="material-icons">error</i> ' + (response.error || 'Erro ao enviar imagem'), 3000, 'red');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erro no upload:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                let errorMsg = 'Erro ao enviar imagem';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg = response.error;
                    }
                } catch (e) {
                    // Se não conseguir parsear JSON, usar mensagem padrão
                }
                
                Materialize.toast('<i class="material-icons">error</i> ' + errorMsg, 3000, 'red');
            }
        });
    }

    removeImage(gtin) {
        // Confirmar remoção
        if (!confirm('Tem certeza que deseja remover a imagem desta variante?')) {
            return;
        }

        console.log('Removendo imagem para GTIN:', gtin);

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: {
                request: 'remover_imagem_variante',
                gtin: gtin
            },
            dataType: 'json',
            success: (response) => {
                console.log('Resposta do servidor:', response);
                if (response.success) {
                    Materialize.toast('<i class="material-icons">check</i> Imagem removida com sucesso', 2000, 'green');
                    
                    // Atualizar preview - esconder imagem e mostrar placeholder
                    const variantCard = $(`.variant-card[data-gtin="${gtin}"]`);
                    const previewImg = variantCard.find('.variant-image-preview img');
                    const placeholder = variantCard.find('.no-image-placeholder');
                    
                    // Esconder imagem e mostrar placeholder
                    previewImg.hide();
                    placeholder.show();
                    
                    // Esconder botão de remover
                    variantCard.find('.remove-image-btn').hide();
                    
                    // Limpar input de arquivo
                    $(`#image-upload-${gtin}`).val('');
                    
                    // Forçar recarregamento da imagem (para cache) - método mais robusto
                    previewImg.attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
                    
                    // Aguardar um pouco e então limpar completamente
                    setTimeout(() => {
                        previewImg.attr('src', '');
                    }, 100);
                    
                    console.log('Imagem removida com sucesso para GTIN:', gtin);
                } else {
                    Materialize.toast('<i class="material-icons">error</i> ' + (response.error || 'Erro ao remover imagem'), 3000, 'red');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erro ao remover imagem:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                Materialize.toast('<i class="material-icons">error</i> Erro ao remover imagem', 3000, 'red');
            }
        });
    }

    saveGradeChanges() {
        if (!this.currentProductId) return;

        const changes = [];
        let hasChanges = false;

        // Coletar alterações de cada variante
        this.variants.forEach((variant, index) => {
            const gtin = variant.codigo_gtin;
            const variantChanges = {};

            // Verificar campos de texto
            ['caracteristica', 'variacao'].forEach(field => {
                const input = $(`#variant-${field}-${index}`);
                const newValue = input.val().trim();
                if (newValue !== (variant[field] || '')) {
                    variantChanges[field] = newValue;
                    hasChanges = true;
                }
            });

            // Verificar campos numéricos
            ['preco', 'estoque', 'peso', 'altura', 'largura', 'comprimento'].forEach(field => {
                const input = $(`#variant-${field}-${index}`);
                const newValue = parseFloat(input.val()) || 0;
                const oldValue = parseFloat(variant[field]) || 0;
                if (newValue !== oldValue) {
                    variantChanges[field] = newValue;
                    hasChanges = true;
                }
            });

            if (Object.keys(variantChanges).length > 0) {
                variantChanges.gtin = gtin;
                changes.push(variantChanges);
            }
        });

        if (!hasChanges) {
            Materialize.toast('<i class="material-icons">info</i> Nenhuma alteração detectada', 2000, 'blue');
            return;
        }

        // Enviar alterações
        this.sendGradeChanges(changes);
    }

    sendGradeChanges(changes) {
        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: {
                request: 'atualizar_grade_avancada',
                codigo_interno: this.currentProductId,
                changes: JSON.stringify(changes)
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    Materialize.toast('<i class="material-icons">check</i> Grade atualizada com sucesso', 2000, 'green');
                    
                    // Recarregar dados
                    this.loadGradeData();
                    
                    // Fechar modal usando Materialize CSS 0.100.1
                    $('#modal-grade-avancada').modal('close');
                } else {
                    Materialize.toast('<i class="material-icons">error</i> ' + (response.error || 'Erro ao atualizar grade'), 3000, 'red');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erro ao salvar grade:', error);
                Materialize.toast('<i class="material-icons">error</i> Erro ao salvar alterações', 3000, 'red');
            }
        });
    }

    showImagePreview(gtin) {
        // Abrir modal com preview da imagem usando Materialize CSS 0.100.1
        const modal = $('#modal-preview-imagem');
        const img = modal.find('#preview-image');
        
        img.attr('src', `../upload/${gtin}.webp`);
        modal.modal('open');
    }

    adicionar_item_grade() {
        const gtin = $('#prod_gd_codigo_gtin').val();
        const descricao = $('#prod_gd_nome').val();
        const variacao = $('#prod_gd_variacao').val();
        const caracteristica = $('#prod_gd_caracteristica').val();

        if (!gtin || !descricao || !variacao || !caracteristica) {
            Materialize.toast('<i class="material-icons">error</i> Preencha todos os campos da grade', 3000, 'red');
            return;
        }

        $.ajax({
            url: 'produtos_ajax.php',
            type: 'POST',
            data: {
                request: 'adicionar_item_grade',
                codigo_interno: this.currentProductId,
                codigo_gtin: gtin,
                descricao: descricao,
                variacao: variacao,
                caracteristica: caracteristica
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    Materialize.toast('<i class="material-icons">check</i> Item adicionado à grade com sucesso', 2000, 'green');
                    
                    // Adicionar nova variante à lista
                    this.variants.push(response.variant);
                    
                    // Renderizar a nova variante
                    const newIndex = this.variants.length - 1;
                    const newCard = this.createVariantCard(response.variant, newIndex);
                    $('#grade-variants-container').append(newCard);
                    
                    // Limpar campos
                    $('#prod_gd_codigo_gtin').val('');
                    $('#prod_gd_nome').val('');
                    $('#prod_gd_variacao').val('');
                    $('#prod_gd_caracteristica').val('');
                } else {
                    Materialize.toast('<i class="material-icons">error</i> ' + (response.error || 'Erro ao adicionar item'), 3000, 'red');
                }
            },
            error: (xhr, status, error) => {
                console.error('Erro ao adicionar item na grade:', error);
                Materialize.toast('<i class="material-icons">error</i> Erro ao adicionar item', 3000, 'red');
            }
        });
    }
}

// Inicializar Grade Manager
let gradeManager;
$(document).ready(function() {
    gradeManager = new GradeManager();
});
