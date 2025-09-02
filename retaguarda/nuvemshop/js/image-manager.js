/**
 * Gerenciador de Imagens para Nuvemshop
 * Responsável por verificar e preparar imagens de produtos para envio à API
 */
class ImageManager {
    constructor(options = {}) {
        // ✅ CORRIGIDO: URL dinâmica baseada no host atual
        const protocol = window.location.protocol;
        const host = window.location.host;
        this.baseUrl = `${protocol}//${host}/upload/`;  // ✅ Caminho correto para sua estrutura

        this.supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        this.maxImages = 4;
        this.debug = options.debug || false;
        
        this.log('ImageManager inicializado', {
            baseUrl: this.baseUrl,
            supportedExtensions: this.supportedExtensions,
            maxImages: this.maxImages,
            estrutura: 'Estrutura esperada: /upload/ (mesmo nível que /retaguarda/)'
        });
    }

    /**
     * Log de debug
     * @param {string} message Mensagem
     * @param {*} data Dados adicionais
     * @param {string} level Nível do log
     */
    log(message, data = null, level = 'info') {
        if (!this.debug) return;
        
        const timestamp = new Date().toISOString();
        const prefix = `[ImageManager ${timestamp}]`;
        
        if (level === 'error') {
            console.error(`${prefix} ${message}`, data);
        } else if (level === 'warn') {
            console.warn(`${prefix} ${message}`, data);
        } else {
            console.log(`${prefix} ${message}`, data);
        }
    }

    /**
     * Verifica se uma URL de imagem existe
     * @param {string} url URL da imagem
     * @returns {Promise<boolean>} True se a imagem existe
     */
    async checkImageExists(url) {
        return new Promise((resolve) => {
            const img = new Image();
            
            img.onload = () => {
                this.log(`Imagem encontrada: ${url}`);
                resolve(true);
            };
            
            img.onerror = () => {
                this.log(`Imagem não encontrada: ${url}`, null, 'warn');
                resolve(false);
            };
            
            // Timeout de 5 segundos
            setTimeout(() => {
                this.log(`Timeout ao verificar imagem: ${url}`, null, 'warn');
                resolve(false);
            }, 3000);
            img.src = url;
        });
    }

    /**
     * Gera todas as possíveis URLs de imagem para um código
     * @param {string} codigo Código do produto (GTIN)
     * @returns {Array} Array de objetos com url e position
     */
    generateImageUrls(codigo) {
        const urls = [];
        
        // Imagem principal (position 1)
        for (const ext of this.supportedExtensions) {
            urls.push({
                url: `${this.baseUrl}${codigo}.${ext}`,
                position: 1,
                pattern: `${codigo}.${ext}`
            });
        }
        
        // Imagens secundárias (positions 2, 3, 4)
        for (let i = 2; i <= this.maxImages; i++) {
            for (const ext of this.supportedExtensions) {
                urls.push({
                    url: `${this.baseUrl}${codigo}_${i}.${ext}`,
                    position: i,
                    pattern: `${codigo}_${i}.${ext}`
                });
            }
        }
        
        this.log(`URLs geradas para código ${codigo}:`, urls.length);
        return urls;
    }

    /**
     * Verifica quais imagens existem para um produto
     * @param {string} codigo Código do produto (GTIN)
     * @returns {Promise<Array>} Array de imagens encontradas
     */
    async checkProductImages(codigo) {
        this.log(`🔍 Iniciando verificação de imagens para código: ${codigo}`);
        this.log(`📂 Base URL configurada: ${this.baseUrl}`);

        if (!codigo) {
            this.log('Código não fornecido', null, 'error');
            return [];
        }

        const possibleUrls = this.generateImageUrls(codigo);
        const foundImages = [];

        this.log(`🌐 URLs possíveis geradas: ${possibleUrls.length}`);
        
        // Verificar cada posição sequencialmente
        for (let position = 1; position <= this.maxImages; position++) {
            const positionUrls = possibleUrls.filter(item => item.position === position);
            let imageFound = false;
            
            this.log(`Verificando posição ${position} (${positionUrls.length} extensões)`);
            
            // Verificar todas as extensões para esta posição
            for (const urlData of positionUrls) {
                this.log(`   🌐 Testando URL: ${urlData.url}`);
                const exists = await this.checkImageExists(urlData.url);

                if (exists) {
                    foundImages.push({
                        src: urlData.url,
                        position: position
                    });

                    this.log(`   ✅ Imagem encontrada na posição ${position}: ${urlData.pattern}`);
                    imageFound = true;
                    break; // Parar na primeira extensão encontrada para esta posição
                } else {
                    this.log(`   ❌ Não encontrada: ${urlData.pattern}`);
                }
            }
            
            if (!imageFound) {
                this.log(`❌ Nenhuma imagem encontrada na posição ${position}`);
            }
        }
        
        // Ordenar por posição
        foundImages.sort((a, b) => a.position - b.position);
        
        this.log(`Verificação concluída. Total de imagens encontradas: ${foundImages.length}`, foundImages);
        
        return foundImages;
    }

    /**
     * Prepara o array de imagens para envio à API da Nuvemshop
     * @param {string} codigo Código do produto (GTIN)
     * @returns {Promise<Array>} Array formatado para a API
     */
    async prepareImagesForApi(codigo) {
        this.log(`Preparando imagens para API - código: ${codigo}`);
        
        const images = await this.checkProductImages(codigo);
        
        if (images.length === 0) {
            this.log('Nenhuma imagem encontrada para incluir na API');
            return [];
        }
        
        // Formato esperado pela API da Nuvemshop
        const apiImages = images.map(img => ({
            src: img.src,
            position: img.position
        }));
        
        this.log(`Imagens preparadas para API:`, apiImages);
        
        return apiImages;
    }
}

// Exportar para uso global
window.ImageManager = ImageManager;
