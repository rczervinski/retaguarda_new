/**
 * Utilitários para manipulação de dados e requisições AJAX
 * Este arquivo contém funções para lidar com problemas comuns como referências circulares
 *
 * IMPORTANTE: Este arquivo deve ser carregado ANTES de qualquer outro JavaScript
 * que faça requisições AJAX ou manipule objetos JSON complexos.
 */

// Configuração global - DESABILITAR LOGS para melhor performance
window.DISABLE_AJAX_LOGS = true;

// Configuração para desabilitar utils.js em páginas específicas
window.DISABLE_UTILS_FOR_PRODUTOS = true;

/**
 * Cria uma cópia segura de um objeto, removendo referências circulares
 * Implementação completamente nova que evita estouro de pilha
 * @param {Object} obj - O objeto a ser copiado
 * @param {number} [maxDepth=20] - Profundidade máxima permitida
 * @returns {Object} Uma cópia segura do objeto sem referências circulares
 */
function createSafeCopy(obj, maxDepth = 20) {
    if (!window.DISABLE_AJAX_LOGS) console.log("Iniciando createSafeCopy com objeto:", typeof obj);

    // Caso base: se não for um objeto ou for null, retorna o valor diretamente
    if (typeof obj !== 'object' || obj === null) {
        return obj;
    }

    // Lista de propriedades problemáticas conhecidas que podem causar referências circulares
    const problematicProps = [
        'ownerDocument', 'parentNode', 'parent', 'product', 'window', 'document',
        'self', 'top', 'frame', 'frames', 'variants', 'images', 'categories',
        'attributes', 'values', 'children', 'childNodes', 'firstChild', 'lastChild',
        'nextSibling', 'previousSibling', 'owner', 'prototype', '__proto__', 'constructor',
        'jQuery', '$', 'XMLHttpRequest', 'ActiveXObject'
    ];

    // Usar uma abordagem iterativa em vez de recursiva
    // Cada entrada na pilha contém: [objeto original, cópia, profundidade]
    const stack = [];
    const visited = new WeakMap();
    let result;

    // Inicializar o resultado baseado no tipo do objeto
    if (Array.isArray(obj)) {
        result = [];
    } else {
        result = {};
    }

    // Adicionar o objeto raiz à pilha
    stack.push([obj, result, 0]);
    visited.set(obj, result);

    // Processar a pilha até que esteja vazia
    while (stack.length > 0) {
        const [currentObj, currentCopy, depth] = stack.pop();

        // Verificar se atingimos a profundidade máxima
        if (depth >= maxDepth) {
            if (Array.isArray(currentCopy)) {
                // Para arrays, adicionar um marcador no final
                currentCopy.push("[Profundidade máxima atingida]");
            } else {
                // Para objetos, adicionar uma propriedade especial
                currentCopy["__maxDepthReached"] = true;
            }
            continue;
        }

        // Processar o objeto atual
        if (Array.isArray(currentObj)) {
            // Processar array
            for (let i = 0; i < currentObj.length; i++) {
                const value = currentObj[i];

                if (typeof value !== 'object' || value === null) {
                    // Valores primitivos são copiados diretamente
                    currentCopy[i] = value;
                } else if (visited.has(value)) {
                    // Referências circulares são marcadas
                    currentCopy[i] = "[Referência Circular]";
                } else {
                    // Criar uma nova cópia para objetos não visitados
                    const newCopy = Array.isArray(value) ? [] : {};
                    currentCopy[i] = newCopy;
                    visited.set(value, newCopy);
                    stack.push([value, newCopy, depth + 1]);
                }
            }
        } else {
            // Processar objeto
            // Limitar o número de propriedades
            const keys = Object.keys(currentObj);
            const maxProps = 500; // Reduzido para ser mais conservador

            if (keys.length > maxProps) {
                console.warn(`Objeto com muitas propriedades (${keys.length}), limitando a ${maxProps}`);
                currentCopy["__truncated"] = `${keys.length - maxProps} propriedades omitidas`;
            }

            // Processar apenas um número limitado de propriedades
            const keysToProcess = keys.slice(0, maxProps);

            for (const key of keysToProcess) {
                // Pular propriedades problemáticas
                if (problematicProps.includes(key)) {
                    currentCopy[key] = "[Propriedade Ignorada]";
                    continue;
                }

                try {
                    const value = currentObj[key];

                    if (typeof value === 'function') {
                        // Funções são representadas como strings
                        currentCopy[key] = "[Função]";
                    } else if (typeof value !== 'object' || value === null) {
                        // Valores primitivos são copiados diretamente
                        currentCopy[key] = value;
                    } else if (visited.has(value)) {
                        // Referências circulares são marcadas
                        currentCopy[key] = "[Referência Circular]";
                    } else {
                        // Criar uma nova cópia para objetos não visitados
                        const newCopy = Array.isArray(value) ? [] : {};
                        currentCopy[key] = newCopy;
                        visited.set(value, newCopy);
                        stack.push([value, newCopy, depth + 1]);
                    }
                } catch (e) {
                    console.error(`Erro ao processar propriedade ${key}:`, e);
                    currentCopy[key] = `[Erro: ${e.message}]`;
                }
            }
        }
    }

    if (!window.DISABLE_AJAX_LOGS) console.log("createSafeCopy concluído com sucesso");
    return result;
}

/**
 * Verifica se um objeto é serializável
 * @param {Object} obj - O objeto a ser verificado
 * @returns {boolean} true se o objeto pode ser serializado, false caso contrário
 */
function isSerializable(obj) {
    try {
        JSON.stringify(obj);
        return true;
    } catch (e) {
        console.error("Objeto não serializável:", e);
        return false;
    }
}

/**
 * Prepara dados para envio via AJAX, removendo referências circulares
 * @param {Object} data - Os dados a serem enviados
 * @param {number} [maxDepth=15] - Profundidade máxima de recursão
 * @returns {Object} Dados seguros para serialização
 */
function prepareAjaxData(data, maxDepth = 15) {
    // Verificar se utils.js está desabilitado para produtos
    if (window.DISABLE_UTILS_FOR_PRODUTOS &&
        (window.location.href.includes('produtos.php') ||
         document.querySelector('#produto_principal') ||
         document.querySelector('#produto_cadastro'))) {
        return data; // Retornar dados sem processamento
    }

    // Se não for um objeto, retornar como está
    if (typeof data !== 'object' || data === null) return data;

    try {
        if (!window.DISABLE_AJAX_LOGS) console.log("Preparando dados para AJAX:", typeof data, Array.isArray(data) ? "array" : "objeto");

        // Criar uma cópia segura dos dados com profundidade limitada
        // Usando a nova implementação iterativa que evita estouro de pilha
        const safeCopy = createSafeCopy(data, maxDepth);

        // Tentar serializar para verificar se é serializável
        try {
            // Apenas testar a serialização, não usamos o resultado
            JSON.stringify(safeCopy);
            if (!window.DISABLE_AJAX_LOGS) console.log("Dados preparados com sucesso e são serializáveis");
            return safeCopy;
        } catch (serializeError) {
            console.error("Dados ainda não são serializáveis após processamento:", serializeError);

            // Abordagem de emergência: criar um objeto plano
            console.log("Tentando abordagem de emergência: objeto plano");
            return createFlatObject(data);
        }
    } catch (e) {
        console.error("Erro ao preparar dados AJAX:", e);

        // Abordagem de emergência: criar um objeto plano
        try {
            console.log("Tentando abordagem de emergência após erro: objeto plano");
            return createFlatObject(data);
        } catch (flatError) {
            console.error("Falha total na preparação de dados:", flatError);
            return {}; // Último recurso: objeto vazio
        }
    }
}

/**
 * Cria um objeto plano a partir de um objeto complexo
 * Usado como último recurso quando outras abordagens falham
 * @param {Object} data - O objeto a ser simplificado
 * @returns {Object} Um objeto plano serializável
 */
function createFlatObject(data) {
    // Criar um objeto simples
    const flatObject = {};

    // Se não for um objeto, retornar um objeto vazio
    if (typeof data !== 'object' || data === null) {
        return flatObject;
    }

    try {
        // Processar apenas as propriedades de primeiro nível
        for (const key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                const value = data[key];

                // Tratar diferentes tipos de dados
                if (typeof value === 'string' ||
                    typeof value === 'number' ||
                    typeof value === 'boolean' ||
                    value === null) {
                    // Valores primitivos são copiados diretamente
                    flatObject[key] = value;
                } else if (Array.isArray(value)) {
                    // Arrays são simplificados para arrays vazios
                    flatObject[key] = [];
                } else if (typeof value === 'object') {
                    // Objetos são simplificados para objetos vazios
                    flatObject[key] = {};
                } else {
                    // Outros tipos são convertidos para string
                    flatObject[key] = String(value);
                }
            }
        }

        if (!window.DISABLE_AJAX_LOGS) console.log("Objeto plano criado com sucesso");
        return flatObject;
    } catch (e) {
        console.error("Erro ao criar objeto plano:", e);
        return {}; // Último recurso: objeto vazio
    }
}

/**
 * Serializa objetos de forma segura, tratando referências circulares
 * @param {Object} obj - O objeto a ser serializado
 * @param {number} [maxDepth=15] - Profundidade máxima para processamento
 * @returns {string} JSON string segura
 */
window.safeStringify = function(obj, maxDepth = 15) {
    // Verificar se utils.js está desabilitado para produtos
    if (window.DISABLE_UTILS_FOR_PRODUTOS &&
        (window.location.href.includes('produtos.php') ||
         document.querySelector('#produto_principal') ||
         document.querySelector('#produto_cadastro'))) {
        try {
            return JSON.stringify(obj); // Usar JSON.stringify simples
        } catch (e) {
            return '{}'; // Retornar objeto vazio em caso de erro
        }
    }

    if (!window.DISABLE_AJAX_LOGS) console.log("safeStringify iniciado");

    // Se não for um objeto, serializar diretamente
    if (typeof obj !== 'object' || obj === null) {
        return JSON.stringify(obj);
    }

    try {
        // Usar a função prepareAjaxData com profundidade limitada
        const safeObj = prepareAjaxData(obj, maxDepth);

        // Tentar serializar o objeto seguro
        const result = JSON.stringify(safeObj);

        // Verificar se o resultado é válido
        if (result === undefined) {
            console.error("Resultado de serialização indefinido");
            return '{}';
        }

        if (!window.DISABLE_AJAX_LOGS) console.log("safeStringify concluído com sucesso");
        return result;
    } catch (e) {
        console.error("Erro ao serializar objeto em safeStringify:", e);

        // Abordagem de emergência: objeto totalmente plano
        try {
            console.log("Tentando serialização de emergência");
            return JSON.stringify(createFlatObject(obj));
        } catch (emergencyError) {
            console.error("Falha na serialização de emergência:", emergencyError);
            return '{}';
        }
    }
};

// Função para deserializar JSON de forma segura
window.safeParse = function(jsonString) {
    try {
        return JSON.parse(jsonString);
    } catch (e) {
        console.error("Erro ao deserializar JSON:", e);
        return {};
    }
};

/**
 * Sobrescreve o método $.ajax do jQuery para tratar referências circulares
 * Implementação completamente nova e mais robusta
 */
(function($) {
    // Verificar se o jQuery está disponível
    if (typeof $ !== 'function' || typeof $.ajax !== 'function') {
        console.error("jQuery não está disponível. A sobrescrita de $.ajax não será aplicada.");
        return;
    }

    // console.log("Aplicando sobrescrita segura ao método $.ajax do jQuery");

    // Armazenar a implementação original de $.ajax
    var originalAjax = $.ajax;

    // Sobrescrever $.ajax com nossa versão que trata referências circulares
    $.ajax = function(url, options) {
        // Normalizar parâmetros (jQuery permite chamar $.ajax(options) ou $.ajax(url, options))
        if (typeof url === 'object') {
            options = url;
            url = undefined;
        }

        options = options || {};

        // Criar uma cópia das opções para evitar modificar o objeto original
        var safeOptions = {};
        for (var key in options) {
            if (options.hasOwnProperty(key)) {
                safeOptions[key] = options[key];
            }
        }

        // Registrar informações sobre a requisição para depuração
        const requestInfo = {
            url: safeOptions.url || url,
            type: safeOptions.type || 'GET',
            dataType: safeOptions.dataType || 'json'
        };

        // console.log("Iniciando requisição AJAX:", requestInfo);

        // Se houver dados e não for uma string ou FormData, tratar referências circulares
        if (safeOptions.data && typeof safeOptions.data === 'object' &&
            !(safeOptions.data instanceof FormData) &&
            !(safeOptions.data instanceof String) &&
            !(typeof safeOptions.data.toString === 'function' &&
              safeOptions.data.toString() === '[object FormData]')) {

            try {
                if (!window.DISABLE_AJAX_LOGS) console.log("Preparando dados para envio AJAX");

                // Verificar se é contentType JSON
                var isJsonContent = safeOptions.contentType &&
                                   (safeOptions.contentType.indexOf('application/json') !== -1);

                if (isJsonContent) {
                    // Se for JSON, usar safeStringify diretamente
                    try {
                        console.log("Detectado contentType JSON, usando safeStringify");
                        safeOptions.data = window.safeStringify(safeOptions.data, 15);
                    } catch (jsonError) {
                        console.error("Erro ao serializar dados JSON:", jsonError);
                        safeOptions.data = "{}"; // Usar objeto vazio como fallback
                    }
                } else {
                    // Para outros tipos, usar prepareAjaxData
                    safeOptions.data = prepareAjaxData(safeOptions.data, 15);

                    // Verificar se os dados foram preparados corretamente
                    if (safeOptions.data === null || safeOptions.data === undefined) {
                        console.error("Dados preparados são nulos ou indefinidos, usando objeto vazio");
                        safeOptions.data = {};
                    }
                }

                if (!window.DISABLE_AJAX_LOGS) console.log("Dados preparados com sucesso");
            } catch (e) {
                console.error("Erro ao preparar dados para AJAX:", e);
                safeOptions.data = isJsonContent ? "{}" : {}; // Usar objeto vazio como fallback
            }
        }

        // Guardar as funções de callback originais
        var originalBeforeSend = safeOptions.beforeSend;
        var originalSuccess = safeOptions.success;
        var originalError = safeOptions.error;
        var originalComplete = safeOptions.complete;

        // Substituir a função beforeSend para adicionar logs
        if (typeof originalBeforeSend === 'function') {
            safeOptions.beforeSend = function(xhr, settings) {
                try {
                    console.log("Executando beforeSend para requisição AJAX:", requestInfo);
                    return originalBeforeSend.call(this, xhr, settings);
                } catch (e) {
                    console.error("Erro em beforeSend:", e);
                    return true; // Continuar a requisição
                }
            };
        }

        // Substituir a função de sucesso para tratar a resposta
        if (typeof originalSuccess === 'function') {
            safeOptions.success = function(response, textStatus, jqXHR) {
                try {
                    if (!window.DISABLE_AJAX_LOGS) console.log("Processando resposta AJAX bem-sucedida");

                    // Verificar se é uma requisição para a Nuvemshop
                    var isNuvemshopRequest = (safeOptions.url && safeOptions.url.indexOf('nuvemshop') !== -1) ||
                                            (url && url.toString().indexOf('nuvemshop') !== -1);

                    // Para requisições da Nuvemshop, não processar a resposta para evitar problemas
                    if (isNuvemshopRequest) {
                        if (!window.DISABLE_AJAX_LOGS) console.log("Resposta da Nuvemshop detectada, pulando processamento para evitar recursão");
                        originalSuccess.call(this, response, textStatus, jqXHR);
                        return;
                    }

                    // Se a resposta for um objeto, verificar se tem propriedades problemáticas
                    if (response && typeof response === 'object') {
                        // Verificar se a resposta contém propriedades que podem causar problemas
                        var hasProblematicProps = false;
                        var problematicProps = ['variants', 'images', 'categories', 'product', 'attributes', 'values'];

                        for (var i = 0; i < problematicProps.length; i++) {
                            if (response[problematicProps[i]]) {
                                hasProblematicProps = true;
                                break;
                            }
                        }

                        // Se tiver propriedades problemáticas, não processar
                        if (hasProblematicProps) {
                            if (!window.DISABLE_AJAX_LOGS) console.log("Resposta contém propriedades potencialmente problemáticas, pulando processamento");
                            originalSuccess.call(this, response, textStatus, jqXHR);
                            return;
                        }

                        try {
                            // Usar uma abordagem simplificada para evitar problemas
                            var safeResponse = {};

                            // Copiar apenas propriedades de primeiro nível
                            for (var key in response) {
                                if (response.hasOwnProperty(key)) {
                                    var value = response[key];

                                    // Copiar apenas valores primitivos e arrays simples
                                    if (typeof value === 'string' ||
                                        typeof value === 'number' ||
                                        typeof value === 'boolean' ||
                                        value === null) {
                                        safeResponse[key] = value;
                                    } else if (Array.isArray(value) && value.length < 10) {
                                        // Para arrays pequenos, verificar se contém apenas primitivos
                                        var isSimpleArray = true;
                                        for (var j = 0; j < value.length; j++) {
                                            if (typeof value[j] === 'object' && value[j] !== null) {
                                                isSimpleArray = false;
                                                break;
                                            }
                                        }

                                        if (isSimpleArray) {
                                            safeResponse[key] = value;
                                        } else {
                                            safeResponse[key] = "[Array Complexo]";
                                        }
                                    } else if (typeof value === 'object') {
                                        // Para objetos, usar uma representação simplificada
                                        safeResponse[key] = "[Objeto]";
                                    }
                                }
                            }

                            originalSuccess.call(this, safeResponse, textStatus, jqXHR);
                        } catch (processError) {
                            console.error("Erro ao processar resposta:", processError);
                            originalSuccess.call(this, response, textStatus, jqXHR);
                        }
                    } else {
                        // Se não for um objeto, passar a resposta original
                        originalSuccess.call(this, response, textStatus, jqXHR);
                    }
                } catch (e) {
                    console.error("Erro ao processar resposta AJAX:", e);

                    // Tentar chamar o callback original com a resposta original
                    try {
                        originalSuccess.call(this, response, textStatus, jqXHR);
                    } catch (innerError) {
                        console.error("Erro ao chamar callback original:", innerError);

                        // Último recurso: chamar o callback com um objeto vazio
                        try {
                            originalSuccess.call(this, {}, textStatus, jqXHR);
                        } catch (finalError) {
                            console.error("Falha completa no processamento da resposta:", finalError);
                        }
                    }
                }
            };
        }

        // Substituir a função de erro para melhor tratamento de erros
        if (typeof originalError === 'function') {
            safeOptions.error = function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na requisição AJAX:", {
                    url: requestInfo.url,
                    type: requestInfo.type,
                    status: jqXHR ? jqXHR.status : 'N/A',
                    statusText: jqXHR ? jqXHR.statusText : 'N/A',
                    error: errorThrown
                });

                // Tentar processar a resposta de erro se for JSON
                try {
                    if (jqXHR && jqXHR.responseText) {
                        var contentType = jqXHR.getResponseHeader('content-type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            var errorResponse = JSON.parse(jqXHR.responseText);
                            console.error("Detalhes do erro JSON:", errorResponse);
                        }
                    }
                } catch (parseError) {
                    console.error("Erro ao analisar resposta de erro:", parseError);
                }

                // Chamar o callback de erro original
                try {
                    originalError.call(this, jqXHR, textStatus, errorThrown);
                } catch (callbackError) {
                    console.error("Erro ao chamar callback de erro original:", callbackError);
                }
            };
        }

        // Substituir a função complete para adicionar logs
        if (typeof originalComplete === 'function') {
            safeOptions.complete = function(jqXHR, textStatus) {
                console.log("Requisição AJAX completa:", textStatus);

                try {
                    originalComplete.call(this, jqXHR, textStatus);
                } catch (e) {
                    console.error("Erro em complete:", e);
                }
            };
        }

        // Adicionar timeout se não estiver definido
        if (!safeOptions.timeout) {
            safeOptions.timeout = 60000; // 60 segundos
        }

        // Chamar a implementação original de $.ajax com os dados tratados
        try {
            return originalAjax.call(this, url, safeOptions);
        } catch (ajaxError) {
            console.error("Erro ao chamar $.ajax original:", ajaxError);

            // Tentar novamente com dados mínimos
            if (safeOptions.data) {
                console.log("Tentando novamente sem dados complexos");
                safeOptions.data = safeOptions.contentType &&
                                  (safeOptions.contentType.indexOf('application/json') !== -1) ?
                                  "{}" : {};
                try {
                    return originalAjax.call(this, url, safeOptions);
                } catch (retryError) {
                    console.error("Falha na segunda tentativa:", retryError);
                    throw retryError; // Propagar o erro
                }
            } else {
                throw ajaxError; // Propagar o erro original
            }
        }
    };

    // console.log("Sobrescrita de $.ajax aplicada com sucesso");
})(jQuery);

/**
 * Sobrescreve o método $.param do jQuery para tratar referências circulares
 * Implementação completamente nova e mais robusta
 */
(function($) {
    // Verificar se o jQuery está disponível
    if (typeof $ !== 'function' || typeof $.param !== 'function') {
        console.error("jQuery não está disponível. A sobrescrita de $.param não será aplicada.");
        return;
    }

    // console.log("Aplicando sobrescrita segura ao método $.param do jQuery");

    // Armazenar a implementação original de $.param
    var originalParam = $.param;

    // Sobrescrever $.param com nossa versão que trata referências circulares
    $.param = function(obj, traditional) {
        // Verificar se utils.js está desabilitado para produtos
        if (window.DISABLE_UTILS_FOR_PRODUTOS &&
            (window.location.href.includes('produtos.php') ||
             document.querySelector('#produto_principal') ||
             document.querySelector('#produto_cadastro'))) {
            return originalParam.call(this, obj, traditional); // Usar implementação original
        }

        // Se não for um objeto, usar a implementação original
        if (typeof obj !== 'object' || obj === null) {
            return originalParam.call(this, obj, traditional);
        }

        try {
            console.log("Preparando objeto para $.param");

            // Criar uma cópia segura do objeto
            var safeObj;

            try {
                // Usar uma profundidade menor para serialização de parâmetros
                safeObj = createSafeCopy(obj, 10);

                // Verificar se o objeto foi preparado corretamente
                if (safeObj === null || safeObj === undefined) {
                    console.error("Objeto preparado é nulo ou indefinido, usando objeto vazio");
                    safeObj = {};
                }
            } catch (copyError) {
                console.error("Erro ao criar cópia segura para $.param:", copyError);

                // Criar um objeto plano como fallback
                safeObj = createFlatObject(obj);
            }

            console.log("Objeto preparado com sucesso para $.param");

            // Chamar a implementação original de $.param com os dados tratados
            try {
                return originalParam.call(this, safeObj, traditional);
            } catch (paramError) {
                console.error("Erro ao chamar $.param original:", paramError);

                // Tentar novamente com um objeto ainda mais simplificado
                try {
                    // Criar um objeto ultra-simplificado
                    const simpleObject = {};

                    // Incluir apenas strings e números
                    for (const key in safeObj) {
                        if (Object.prototype.hasOwnProperty.call(safeObj, key)) {
                            const value = safeObj[key];

                            if (typeof value === 'string' || typeof value === 'number') {
                                simpleObject[key] = value;
                            } else if (value === null) {
                                simpleObject[key] = "";
                            } else if (typeof value === 'boolean') {
                                simpleObject[key] = value ? "1" : "0";
                            } else {
                                simpleObject[key] = "";
                            }
                        }
                    }

                    console.log("Tentando $.param com objeto ultra-simplificado");
                    return originalParam.call(this, simpleObject, traditional);
                } catch (retryError) {
                    console.error("Falha na segunda tentativa de $.param:", retryError);
                    return ""; // Retornar string vazia como último recurso
                }
            }
        } catch (e) {
            console.error("Erro geral em $.param:", e);
            return ""; // Retornar string vazia como último recurso
        }
    };

    // console.log("Sobrescrita de $.param aplicada com sucesso");
})(jQuery);

/**
 * Interceptor global para todas as respostas AJAX
 * Implementação simplificada para evitar problemas de recursão
 */
$(document).ajaxComplete(function(event, xhr, settings) {
    // Verificar se o jQuery está disponível
    if (typeof $ !== 'function') {
        console.error("jQuery não está disponível. O interceptor AJAX não funcionará corretamente.");
        return;
    }

    try {
        // Obter informações da requisição
        var requestUrl = settings.url || 'desconhecido';

        // Verificar se é uma requisição para a Nuvemshop
        var isNuvemshopRequest = requestUrl.indexOf('nuvemshop') !== -1;

        // Para requisições da Nuvemshop, não processar a resposta para evitar problemas
        if (isNuvemshopRequest) {
            console.log("Resposta da Nuvemshop detectada, pulando processamento para evitar recursão");
            return;
        }

        // Verificar se a resposta é JSON
        var contentType = xhr.getResponseHeader('content-type');
        if (!contentType || contentType.indexOf('application/json') === -1) {
            // Não é JSON, ignorar
            return;
        }

        // Tentar analisar a resposta JSON
        var responseText = xhr.responseText;
        if (!responseText) {
            // Resposta vazia, ignorar
            return;
        }

        // Verificar se a resposta é muito grande
        if (responseText.length > 100000) { // 100KB
            console.warn("Resposta JSON muito grande (" + Math.round(responseText.length / 1024) + "KB), pulando processamento");
            return;
        }

        try {
            // Analisar a resposta JSON
            var jsonResponse = JSON.parse(responseText);

            // Verificar se a resposta é um objeto
            if (typeof jsonResponse !== 'object' || jsonResponse === null) {
                // Não é um objeto, ignorar
                return;
            }

            // Verificar se a resposta contém propriedades que podem causar problemas
            var hasProblematicProps = false;
            var problematicProps = ['variants', 'images', 'categories', 'product', 'attributes', 'values'];

            for (var i = 0; i < problematicProps.length; i++) {
                if (jsonResponse[problematicProps[i]]) {
                    hasProblematicProps = true;
                    break;
                }
            }

            // Se tiver propriedades problemáticas, não processar
            if (hasProblematicProps) {
                console.log("Resposta contém propriedades potencialmente problemáticas, pulando processamento");
                return;
            }

            // Para respostas simples, criar uma cópia segura
            try {
                // Usar uma abordagem simplificada para evitar problemas
                var safeResponse = {};

                // Copiar apenas propriedades de primeiro nível
                for (var key in jsonResponse) {
                    if (jsonResponse.hasOwnProperty(key)) {
                        var value = jsonResponse[key];

                        // Copiar apenas valores primitivos
                        if (typeof value === 'string' ||
                            typeof value === 'number' ||
                            typeof value === 'boolean' ||
                            value === null) {
                            safeResponse[key] = value;
                        } else if (Array.isArray(value) && value.length < 10) {
                            // Para arrays pequenos, copiar diretamente
                            safeResponse[key] = value;
                        } else if (typeof value === 'object') {
                            // Para objetos, usar uma representação simplificada
                            safeResponse[key] = "[Objeto]";
                        }
                    }
                }

                // Verificar se a resposta processada é serializável
                try {
                    var safeResponseString = JSON.stringify(safeResponse);

                    // Não substituir a resposta original, apenas logar
                    console.log("Resposta AJAX processada com sucesso (sem substituição)");
                } catch (serializeError) {
                    console.error("Erro ao serializar resposta processada:", serializeError);
                }
            } catch (processError) {
                console.error("Erro ao processar resposta JSON:", processError);
            }
        } catch (parseError) {
            console.error("Erro ao analisar resposta JSON:", parseError);
        }
    } catch (e) {
        console.error("Erro no interceptor AJAX:", e);
    }
});

/**
 * Interceptor global para erros AJAX
 * Implementação completamente nova e mais robusta
 */
$(document).ajaxError(function(event, xhr, settings, error) {
    // Verificar se o jQuery está disponível
    if (typeof $ !== 'function') {
        console.error("jQuery não está disponível. O interceptor de erro AJAX não funcionará corretamente.");
        return;
    }

    try {
        // Obter informações da requisição
        var requestUrl = settings.url || 'desconhecido';
        var requestType = settings.type || 'GET';

        console.error("Erro AJAX:", {
            url: requestUrl,
            type: requestType,
            error: error,
            status: xhr ? xhr.status : 'N/A',
            statusText: xhr ? xhr.statusText : 'N/A'
        });

        // Tentar analisar a resposta de erro se for JSON
        if (!xhr) {
            return;
        }

        try {
            var contentType = xhr.getResponseHeader('content-type');
            if (contentType && contentType.indexOf('application/json') !== -1) {
                var responseText = xhr.responseText;
                if (responseText) {
                    try {
                        var errorResponse = JSON.parse(responseText);
                        console.error("Detalhes do erro JSON:", errorResponse);

                        // Verificar se há mensagens de erro específicas
                        if (errorResponse.error) {
                            console.error("Mensagem de erro:", errorResponse.error);
                        }
                        if (errorResponse.message) {
                            console.error("Mensagem:", errorResponse.message);
                        }
                        if (errorResponse.errors) {
                            console.error("Erros:", errorResponse.errors);
                        }
                    } catch (parseError) {
                        console.error("Erro ao analisar resposta JSON de erro:", parseError);
                    }
                }
            }
        } catch (headerError) {
            console.error("Erro ao obter cabeçalho de resposta:", headerError);
        }
    } catch (e) {
        console.error("Erro no interceptor de erro AJAX:", e);
    }
});

/**
 * Verificações finais e exportação de funções
 */
(function() {
    // Verificar se o jQuery está disponível
    if (typeof jQuery === 'undefined') {
        console.error("ERRO CRÍTICO: jQuery não está disponível. As funções de tratamento de AJAX não funcionarão corretamente.");
    } else {
        // console.log("Utilitários de AJAX carregados - Tratamento avançado de referências circulares ativado (v2.0)");
    }

    // Exportar funções para o escopo global
    window.createSafeCopy = createSafeCopy;
    window.prepareAjaxData = prepareAjaxData;
    window.isSerializable = isSerializable;
    window.createFlatObject = createFlatObject;

    // Verificar se as funções foram exportadas corretamente
    if (typeof window.safeStringify !== 'function') {
        console.error("ERRO: A função safeStringify não foi exportada corretamente.");
    }

    // console.log("Inicialização de utils.js concluída com sucesso");
})();
