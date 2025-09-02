/**
 * CRUD de Imagens para Produtos
 * Gerencia imagens na pasta /upload
 */
// Verificar se a classe já foi declarada
if (typeof ProductImageCRUD === 'undefined') {
    class ProductImageCRUD {
    constructor(options = {}) {
        this.backendUrl = options.backendUrl || 'image-crud-backend.php';
        this.debug = options.debug || false;
        this.currentCodigoGtin = null;
        this.images = [];
        this.selectedPosition = 1;
        
        this.log('ProductImageCRUD inicializado');
    }

    /**
     * Log de debug
     */
    log(message, data = null, level = 'info') {
        if (!this.debug) return;
        
        const timestamp = new Date().toISOString();
        const prefix = `[ProductImageCRUD ${timestamp}]`;
        
        if (level === 'error') {
            console.error(`${prefix} ${message}`, data);
        } else if (level === 'warn') {
            console.warn(`${prefix} ${message}`, data);
        } else {
            console.log(`${prefix} ${message}`, data);
        }
    }

    /**
     * Carrega imagens de um produto
     */
    async loadProductImages(codigoGtin) {
        this.log(`Carregando imagens para código: ${codigoGtin}`);
        this.currentCodigoGtin = codigoGtin;
        
        try {
            const response = await fetch(`${this.backendUrl}?operation=list&codigo_gtin=${codigoGtin}`);
            const data = await response.json();
            
            if (data.success) {
                this.images = data.images;
                this.log(`${data.total} imagens carregadas`, this.images);
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.log('Erro ao carregar imagens', error, 'error');
            throw error;
        }
    }

    /**
     * Deleta uma imagem
     */
    async deleteImage(filename) {
        this.log(`Deletando imagem: ${filename}`);
        
        try {
            const formData = new FormData();
            formData.append('operation', 'delete');
            formData.append('filename', filename);
            
            const response = await fetch(this.backendUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.log('Imagem deletada com sucesso');
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.log('Erro ao deletar imagem', error, 'error');
            throw error;
        }
    }

    /**
     * Faz upload de uma imagem
     */
    async uploadImage(file, position) {
        this.log(`Fazendo upload para posição ${position}`, { filename: file.name, size: file.size });
        
        if (!this.currentCodigoGtin) {
            throw new Error('Código GTIN não definido');
        }
        
        try {
            const formData = new FormData();
            formData.append('operation', 'upload');
            formData.append('codigo_gtin', this.currentCodigoGtin);
            formData.append('position', position);
            formData.append('image', file);
            
            const response = await fetch(this.backendUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.log('Upload realizado com sucesso', data);
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.log('Erro no upload', error, 'error');
            throw error;
        }
    }

    /**
     * Obtém imagem por posição
     */
    getImageByPosition(position) {
        return this.images.find(img => img.position === position);
    }

    /**
     * Obtém todas as posições disponíveis
     */
    getAvailablePositions() {
        return [
            { position: 1, label: 'Imagem 1 (Principal)' },
            { position: 2, label: 'Imagem 2' },
            { position: 3, label: 'Imagem 3' },
            { position: 4, label: 'Imagem 4' },
            { position: 5, label: 'Imagem da Categoria' }
        ];
    }

    /**
     * Verifica se uma posição tem imagem
     */
    hasImageAtPosition(position) {
        return this.images.some(img => img.position === position);
    }

    /**
     * Obtém estatísticas das imagens
     */
    getImageStats() {
        const total = this.images.length;
        const positions = this.images.map(img => img.position);
        const missing = [1, 2, 3, 4, 5].filter(pos => !positions.includes(pos));
        
        return {
            total,
            positions,
            missing,
            hasMain: positions.includes(1),
            hasCategory: positions.includes(5)
        };
    }
}

// Tornar a classe disponível globalmente apenas se não existir
window.ProductImageCRUD = ProductImageCRUD;
}
