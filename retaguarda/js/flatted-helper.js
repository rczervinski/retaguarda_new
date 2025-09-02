/**
 * Flatted Helper - Utilitários para lidar com referências circulares em JSON
 * Este arquivo adiciona funções para processar objetos com referências circulares
 * usando a biblioteca Flatted.
 */

// Verificar se Flatted já está disponível
if (typeof Flatted === 'undefined') {
    console.error("Biblioteca Flatted não encontrada. Certifique-se de incluir o script da biblioteca antes deste arquivo.");
}

/**
 * Serializa um objeto de forma segura, lidando com referências circulares
 * @param {Object} obj - O objeto a ser serializado
 * @param {boolean} [useFlatted=true] - Se deve usar Flatted (true) ou tentar JSON.stringify (false)
 * @returns {string} String JSON segura
 */
window.safeSerialize = function(obj, useFlatted = true) {
    if (!obj) return '{}';
    
    try {
        if (useFlatted && typeof Flatted !== 'undefined') {
            return Flatted.stringify(obj);
        } else {
            return JSON.stringify(obj);
        }
    } catch (e) {
        console.error("Erro ao serializar objeto:", e);
        
        // Tentar uma abordagem mais simples
        try {
            // Extrair apenas propriedades de primeiro nível
            var simpleObj = {};
            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    var value = obj[key];
                    if (typeof value === 'string' || 
                        typeof value === 'number' || 
                        typeof value === 'boolean' || 
                        value === null) {
                        simpleObj[key] = value;
                    } else if (Array.isArray(value)) {
                        simpleObj[key] = "[Array]";
                    } else if (typeof value === 'object') {
                        simpleObj[key] = "[Object]";
                    }
                }
            }
            return JSON.stringify(simpleObj);
        } catch (innerError) {
            console.error("Falha na serialização simplificada:", innerError);
            return '{}';
        }
    }
};

/**
 * Deserializa uma string JSON de forma segura, lidando com referências circulares
 * @param {string} jsonString - A string JSON a ser deserializada
 * @param {boolean} [useFlatted=true] - Se deve usar Flatted (true) ou tentar JSON.parse (false)
 * @returns {Object} Objeto deserializado
 */
window.safeParse = function(jsonString, useFlatted = true) {
    if (!jsonString) return {};
    
    try {
        if (useFlatted && typeof Flatted !== 'undefined') {
            return Flatted.parse(jsonString);
        } else {
            return JSON.parse(jsonString);
        }
    } catch (e) {
        console.error("Erro ao deserializar string JSON:", e);
        return {};
    }
};

/**
 * Processa um objeto de forma segura para uso em AJAX
 * @param {Object} obj - O objeto a ser processado
 * @returns {Object} Objeto seguro para AJAX
 */
window.safeProcessObject = function(obj) {
    if (!obj) return {};
    
    try {
        // Usar Flatted para processar o objeto
        if (typeof Flatted !== 'undefined') {
            // Serializar e deserializar para remover referências circulares
            var serialized = Flatted.stringify(obj);
            return Flatted.parse(serialized);
        } else {
            // Fallback para JSON se Flatted não estiver disponível
            var serialized = JSON.stringify(obj);
            return JSON.parse(serialized);
        }
    } catch (e) {
        console.error("Erro ao processar objeto:", e);
        
        // Extrair apenas propriedades essenciais
        var safeObj = {};
        try {
            if (typeof obj === 'object' && obj !== null) {
                // Extrair propriedades comuns
                if (obj.id) safeObj.id = obj.id;
                if (obj.name) safeObj.name = obj.name;
                if (obj.code) safeObj.code = obj.code;
                if (obj.message) safeObj.message = obj.message;
                if (obj.error) safeObj.error = obj.error;
                if (obj.description) safeObj.description = obj.description;
            }
        } catch (innerError) {
            console.error("Erro ao extrair propriedades essenciais:", innerError);
        }
        
        return safeObj;
    }
};

/**
 * Processa uma resposta AJAX de forma segura
 * @param {Object} response - A resposta AJAX a ser processada
 * @param {boolean} [isNuvemshop=false] - Se a resposta é da API da Nuvemshop
 * @returns {Object} Resposta processada de forma segura
 */
window.safeProcessResponse = function(response, isNuvemshop = false) {
    // Se a resposta for uma string, tentar converter para objeto
    if (typeof response === 'string') {
        try {
            response = safeParse(response);
        } catch (e) {
            console.error("Erro ao converter resposta string para objeto:", e);
            return { error: "Erro ao processar resposta" };
        }
    }
    
    // Se não for um objeto ou for null, retornar como está
    if (typeof response !== 'object' || response === null) {
        return response;
    }
    
    // Para respostas da Nuvemshop, extrair apenas propriedades essenciais
    if (isNuvemshop) {
        var safeResponse = {};
        
        // Extrair propriedades essenciais
        if (response.id) safeResponse.id = response.id;
        if (response.name) safeResponse.name = response.name;
        if (response.code) safeResponse.code = response.code;
        if (response.message) safeResponse.message = response.message;
        if (response.error) safeResponse.error = response.error;
        if (response.description) safeResponse.description = response.description;
        
        // Adicionar flag de sucesso
        safeResponse.success = !response.error && !response.code;
        
        return safeResponse;
    }
    
    // Para outras respostas, usar Flatted para processar
    return safeProcessObject(response);
};

// Sobrescrever a função safeStringify existente para usar Flatted
if (window.safeStringify) {
    var originalSafeStringify = window.safeStringify;
    window.safeStringify = function(obj, maxDepth) {
        try {
            if (typeof Flatted !== 'undefined') {
                return Flatted.stringify(obj);
            } else {
                return originalSafeStringify(obj, maxDepth);
            }
        } catch (e) {
            console.error("Erro em safeStringify:", e);
            return originalSafeStringify(obj, maxDepth);
        }
    };
}

console.log("Flatted Helper carregado com sucesso!");
