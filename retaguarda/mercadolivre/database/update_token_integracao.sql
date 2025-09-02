-- Script para adicionar campos necessários para Mercado Livre na tabela token_integracao

-- Verificar se os campos já existem antes de adicionar
DO $$
BEGIN
    -- Adicionar campo user_id se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'token_integracao' AND column_name = 'user_id') THEN
        ALTER TABLE token_integracao ADD COLUMN user_id VARCHAR(100);
    END IF;
    
    -- Adicionar campo expires_in se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'token_integracao' AND column_name = 'expires_in') THEN
        ALTER TABLE token_integracao ADD COLUMN expires_in INTEGER DEFAULT 0;
    END IF;
    
    -- Adicionar campo token_created_at se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'token_integracao' AND column_name = 'token_created_at') THEN
        ALTER TABLE token_integracao ADD COLUMN token_created_at INTEGER DEFAULT 0;
    END IF;
    
    -- Adicionar campo refresh_token se não existir (para clareza, mesmo usando 'code')
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'token_integracao' AND column_name = 'refresh_token') THEN
        ALTER TABLE token_integracao ADD COLUMN refresh_token VARCHAR(255);
    END IF;
END
$$;

-- Comentários para documentar o uso dos campos no Mercado Livre
COMMENT ON COLUMN token_integracao.code IS 'Para Nuvemshop: store_id | Para Mercado Livre: refresh_token';
COMMENT ON COLUMN token_integracao.url_checkout IS 'Para Nuvemshop: URL da loja | Para Mercado Livre: user_id';
COMMENT ON COLUMN token_integracao.user_id IS 'ID do usuário no Mercado Livre';
COMMENT ON COLUMN token_integracao.expires_in IS 'Tempo de expiração do token em segundos (ML: 21600 = 6h)';
COMMENT ON COLUMN token_integracao.token_created_at IS 'Timestamp de quando o token foi criado';
COMMENT ON COLUMN token_integracao.refresh_token IS 'Token de renovação (alternativa ao campo code)';

-- Inserir registro padrão para Mercado Livre se não existir
INSERT INTO token_integracao (descricao, ativo) 
SELECT 'MERCADO_LIVRE', 0
WHERE NOT EXISTS (
    SELECT 1 FROM token_integracao WHERE descricao = 'MERCADO_LIVRE'
);
