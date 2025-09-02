/**
 * CategoryManager - Gerencia categorias da Nuvemshop
 * Integrado com o sistema de produtos para criar categorias automaticamente
 */
class CategoryManager {
    constructor(proxyUrl = 'nuvemshop/nuvemshop_proxy.php') {
        this.proxyUrl = proxyUrl;
        this.debug = true;
        this.categoriesCache = new Map(); // Cache para evitar consultas desnecessárias
    }

    /**
     * Log de debug
     * @param {string} message Mensagem
     * @param {*} data Dados opcionais
     * @param {string} level Nível do log
     */
    log(message, data = null, level = 'info') {
        if (!this.debug) return;

        const timestamp = new Date().toLocaleTimeString();
        const prefix = `[${timestamp}] [CategoryManager]`;

        if (level === 'error') {
            console.error(prefix, message, data);
        } else if (level === 'warn') {
            console.warn(prefix, message, data);
        } else {
            console.log(prefix, message, data);
        }
    }

    /**
     * Normaliza string para comparação (remove acentos, converte para maiúsculo, etc.)
     * @param {string} str String para normalizar
     * @returns {string} String normalizada
     */
    normalizeString(str) {
        if (!str || typeof str !== 'string') return '';

        return str
            .toUpperCase()
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remove acentos
            .replace(/[^A-Z0-9\s]/g, '') // Remove caracteres especiais
            .replace(/\s+/g, ' '); // Normaliza espaços
    }

    /**
     * Busca categorias existentes na Nuvemshop
     * @returns {Promise<Array>} Lista de categorias
     */
    async fetchCategories() {
        this.log('Buscando categorias existentes na Nuvemshop');

        try {
            const response = await fetch(`${this.proxyUrl}?operation=list_categories`);

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            this.log('Categorias encontradas', data);
            return data || [];

        } catch (error) {
            this.log('Erro ao buscar categorias', error, 'error');
            throw error;
        }
    }

    /**
     * Busca categoria por nome normalizado
     * @param {string} categoryName Nome da categoria
     * @param {number|null} parentId ID do parent (null para categoria raiz)
     * @returns {Promise<Object|null>} Categoria encontrada ou null
     */
    async findCategoryByName(categoryName, parentId = null) {
        const normalizedName = this.normalizeString(categoryName);

        if (!normalizedName) {
            this.log('Nome de categoria vazio após normalização', categoryName, 'warn');
            return null;
        }

        // Verificar cache primeiro
        const cacheKey = `${normalizedName}_${parentId || 'root'}`;
        if (this.categoriesCache.has(cacheKey)) {
            this.log('Categoria encontrada no cache', cacheKey);
            return this.categoriesCache.get(cacheKey);
        }

        try {
            const categories = await this.fetchCategories();

            // Buscar categoria com nome normalizado e parent correto
            const found = categories.find(cat => {
                const catNormalized = this.normalizeString(cat.name?.pt || cat.name);
                const catParentId = cat.parent || null;

                return catNormalized === normalizedName && catParentId === parentId;
            });

            if (found) {
                this.log('Categoria encontrada', { name: categoryName, id: found.id, parent: parentId });
                this.categoriesCache.set(cacheKey, found);
            } else {
                this.log('Categoria não encontrada', { name: categoryName, parent: parentId });
            }

            return found || null;

        } catch (error) {
            this.log('Erro ao buscar categoria por nome', error, 'error');
            return null;
        }
    }

    /**
     * Cria uma nova categoria na Nuvemshop
     * @param {string} categoryName Nome da categoria
     * @param {number|null} parentId ID do parent (null para categoria raiz)
     * @returns {Promise<Object>} Categoria criada
     */
    async createCategory(categoryName, parentId = null) {
        const normalizedName = this.normalizeString(categoryName);

        if (!normalizedName) {
            throw new Error('Nome de categoria inválido');
        }

        this.log('Criando nova categoria', { name: categoryName, normalized: normalizedName, parent: parentId });

        const categoryData = {
            name: {
                pt: categoryName.toUpperCase() // Manter formato original em maiúsculo
            },
            description: {
                pt: `Categoria ${categoryName}`
            },
            handle: {
                pt: normalizedName.toLowerCase().replace(/\s+/g, '-')
            }
        };

        // Adicionar parent se especificado
        if (parentId) {
            categoryData.parent = parentId;
        }

        try {
            const response = await fetch(`${this.proxyUrl}?operation=create_category`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(categoryData)
            });

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const result = await response.json();

            if (result.error) {
                throw new Error(result.error);
            }

            this.log('Categoria criada com sucesso', result);

            // Adicionar ao cache
            const cacheKey = `${normalizedName}_${parentId || 'root'}`;
            this.categoriesCache.set(cacheKey, result);

            return result;

        } catch (error) {
            this.log('Erro ao criar categoria', error, 'error');
            throw error;
        }
    }

    /**
     * Busca ou cria uma categoria
     * @param {string} categoryName Nome da categoria
     * @param {number|null} parentId ID do parent (null para categoria raiz)
     * @returns {Promise<Object>} Categoria (existente ou criada)
     */
    async findOrCreateCategory(categoryName, parentId = null) {
        if (!categoryName || categoryName.trim() === '') {
            throw new Error('Nome de categoria é obrigatório');
        }

        // Verificar se é um valor especial que não deve ser criado
        if (categoryName === 'SEM_CATEGORIA' || categoryName === 'SEM_GRUPO') {
            this.log('Valor especial ignorado - não criar categoria', categoryName);
            return null;
        }

        try {
            // Primeiro, tentar encontrar categoria existente
            let category = await this.findCategoryByName(categoryName, parentId);

            if (category) {
                this.log('Categoria já existe', category);
                return category;
            }

            // Se não encontrou, criar nova categoria
            category = await this.createCategory(categoryName, parentId);
            this.log('Nova categoria criada', category);

            return category;

        } catch (error) {
            this.log('Erro ao buscar ou criar categoria', error, 'error');
            throw error;
        }
    }

    /**
     * Processa categorias de um produto (categoria pai + subcategoria)
     * @param {Object} productData Dados do produto com categoria e grupo
     * @returns {Promise<Array>} Array com IDs das categorias
     */
    async processProductCategories(productData) {
        const { categoria, grupo } = productData;

        this.log('Processando categorias do produto', { categoria, grupo });

        const categoryIds = [];

        try {
            // Verificar se categoria é "Sem categoria"
            if (!categoria || categoria.trim() === '' || categoria === 'SEM_CATEGORIA') {
                this.log('Produto sem categoria - não criar categorias na Nuvemshop');
                return [];
            }

            // Verificar se grupo é "Sem grupo" ou vazio
            const hasGroup = grupo && grupo.trim() !== '' && grupo !== 'SEM_GRUPO';

            // Cenário 1: Apenas categoria (sem grupo ou grupo igual à categoria)
            if (!hasGroup || this.normalizeString(grupo) === this.normalizeString(categoria)) {
                this.log('Cenário: Apenas categoria principal');

                // Criar/buscar categoria principal
                const category = await this.findOrCreateCategory(categoria, null);
                if (category && category.id) {
                    categoryIds.push(category.id);
                    this.log('Produto vai para categoria principal', { categoria, categoryId: category.id });
                }

                return categoryIds;
            }

            // Cenário 2: Categoria + Grupo (hierarquia)
            this.log('Cenário: Hierarquia categoria > grupo');

            // Primeiro, criar/buscar categoria pai
            const parentCategory = await this.findOrCreateCategory(categoria, null);
            if (!parentCategory || !parentCategory.id) {
                // Se categoria pai é especial (SEM_CATEGORIA), não criar nada
                if (categoria === 'SEM_CATEGORIA') {
                    this.log('Categoria pai é especial - não criar hierarquia');
                    return [];
                }
                throw new Error(`Falha ao processar categoria pai: ${categoria}`);
            }

            // Depois, criar/buscar subcategoria
            const subCategory = await this.findOrCreateCategory(grupo, parentCategory.id);
            if (!subCategory || !subCategory.id) {
                // Se grupo é especial (SEM_GRUPO), produto vai para categoria pai
                if (grupo === 'SEM_GRUPO') {
                    this.log('Grupo é especial - produto vai para categoria pai', { categoria, categoryId: parentCategory.id });
                    categoryIds.push(parentCategory.id);
                    return categoryIds;
                }
                throw new Error(`Falha ao processar subcategoria: ${grupo}`);
            }

            // Produto vai para a subcategoria mais específica
            categoryIds.push(subCategory.id);

            this.log('Hierarquia processada com sucesso', {
                parent: parentCategory,
                sub: subCategory,
                finalCategoryId: subCategory.id
            });

            return categoryIds;

        } catch (error) {
            this.log('Erro ao processar categorias do produto', error, 'error');
            throw error;
        }
    }

    /**
     * Limpa o cache de categorias
     */
    clearCache() {
        this.categoriesCache.clear();
        this.log('Cache de categorias limpo');
    }
}

// Exportar para uso global
window.CategoryManager = CategoryManager;
