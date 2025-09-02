-- Adicionar campo categoria_ml na tabela produtos_ib
-- Este campo armazenará o ID da categoria do Mercado Livre para cada produto

DO $$
BEGIN
    -- Verificar se o campo já existe
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'produtos_ib' AND column_name = 'categoria_ml') THEN
        
        -- Adicionar campo categoria_ml
        ALTER TABLE produtos_ib ADD COLUMN categoria_ml VARCHAR(20);

        -- Adicionar campo para dados completos da predição
        ALTER TABLE produtos_ib ADD COLUMN categoria_ml_data TEXT;

        -- Adicionar campos para cache de imagens
        ALTER TABLE produtos_ib ADD COLUMN imagens_cache TEXT;
        ALTER TABLE produtos_ib ADD COLUMN imagens_cache_updated TIMESTAMP;

        -- Adicionar comentários explicativos
        COMMENT ON COLUMN produtos_ib.categoria_ml IS 'ID da categoria do Mercado Livre (ex: MLB1574, MLB1144)';
        COMMENT ON COLUMN produtos_ib.categoria_ml_data IS 'JSON com dados completos da predição de categoria';

        -- Criar índices para performance
        CREATE INDEX IF NOT EXISTS idx_produtos_ib_categoria_ml ON produtos_ib(categoria_ml);
        
        RAISE NOTICE 'Campo categoria_ml adicionado com sucesso na tabela produtos_ib';
    ELSE
        RAISE NOTICE 'Campo categoria_ml já existe na tabela produtos_ib';
    END IF;
END
$$;

-- Mostrar estatísticas
SELECT 
    'produtos_ib' as tabela,
    COUNT(*) as total_produtos,
    COUNT(categoria_ml) as com_categoria_ml,
    COUNT(*) - COUNT(categoria_ml) as sem_categoria_ml
FROM produtos_ib;
