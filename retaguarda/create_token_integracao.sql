-- Verifica se a tabela já existe
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_tables WHERE tablename = 'token_integracao') THEN
        -- Cria a tabela token_integracao
        CREATE TABLE token_integracao (
            codigo SERIAL PRIMARY KEY,
            descricao VARCHAR(50) NOT NULL,
            client_id VARCHAR(100),
            client_secret VARCHAR(100),
            access_token VARCHAR(255),
            code VARCHAR(100),
            url_checkout VARCHAR(255),
            ativo INTEGER DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        -- Insere um registro padrão para a NuvemShop
        INSERT INTO token_integracao (codigo, descricao, ativo) 
        VALUES (1, 'NUVEMSHOP', 0);
    END IF;
END
$$;