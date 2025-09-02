-- Tabela para aprendizado de erros do Mercado Livre
-- Sistema híbrido: mapeamento manual + análise automática + aprendizado

DO $$
BEGIN
    -- Criar tabela se não existir
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'ml_error_learning') THEN
        
        CREATE TABLE ml_error_learning (
            id SERIAL PRIMARY KEY,
            error_code VARCHAR(100) NOT NULL,
            original_error TEXT NOT NULL,
            mapped_error TEXT NOT NULL,
            source VARCHAR(50) NOT NULL, -- 'manual_mapping', 'auto_analysis', 'fallback'
            created_at TIMESTAMP DEFAULT NOW(),
            last_seen TIMESTAMP DEFAULT NOW(),
            count INTEGER DEFAULT 1,
            admin_reviewed BOOLEAN DEFAULT FALSE,
            admin_mapping TEXT NULL, -- Mapeamento customizado pelo admin
            notes TEXT NULL
        );
        
        -- Índices para performance
        CREATE UNIQUE INDEX idx_ml_error_learning_code ON ml_error_learning(error_code);
        CREATE INDEX idx_ml_error_learning_source ON ml_error_learning(source);
        CREATE INDEX idx_ml_error_learning_count ON ml_error_learning(count DESC);
        
        -- Comentários
        COMMENT ON TABLE ml_error_learning IS 'Aprendizado de erros do Mercado Livre para melhorar mapeamentos';
        COMMENT ON COLUMN ml_error_learning.error_code IS 'Código do erro do ML (ex: item.listing_type_id.requiresPictures)';
        COMMENT ON COLUMN ml_error_learning.original_error IS 'JSON do erro original retornado pelo ML';
        COMMENT ON COLUMN ml_error_learning.mapped_error IS 'JSON do erro mapeado para português';
        COMMENT ON COLUMN ml_error_learning.source IS 'Origem do mapeamento: manual, automático ou fallback';
        COMMENT ON COLUMN ml_error_learning.count IS 'Quantas vezes este erro foi encontrado';
        COMMENT ON COLUMN ml_error_learning.admin_reviewed IS 'Se o admin já revisou este erro';
        COMMENT ON COLUMN ml_error_learning.admin_mapping IS 'Mapeamento customizado pelo admin';
        
        RAISE NOTICE 'Tabela ml_error_learning criada com sucesso';
    ELSE
        RAISE NOTICE 'Tabela ml_error_learning já existe';
    END IF;
END
$$;

-- Inserir alguns exemplos para teste
INSERT INTO ml_error_learning (error_code, original_error, mapped_error, source, count) VALUES
('item.listing_type_id.requiresPictures', 
 '{"code":"item.listing_type_id.requiresPictures","message":"Item pictures are mandatory for listing type gold_special"}',
 '{"type":"error","title":"Imagens obrigatórias","description":"Esta categoria requer pelo menos uma imagem","solution":"Adicione uma imagem do produto"}',
 'manual_mapping', 5),

('shipping.me2_adoption_mandatory',
 '{"code":"shipping.me2_adoption_mandatory","message":"ME2 adoption is mandatory for the user"}',
 '{"type":"warning","title":"Mercado Envios obrigatório","description":"Configure ME2 na sua conta","solution":"Acesse configurações de frete no ML"}',
 'manual_mapping', 3)

ON CONFLICT (error_code) DO NOTHING;

-- Mostrar estatísticas
SELECT 
    'ml_error_learning' as tabela,
    COUNT(*) as total_erros,
    COUNT(CASE WHEN source = 'manual_mapping' THEN 1 END) as manuais,
    COUNT(CASE WHEN source = 'auto_analysis' THEN 1 END) as automaticos,
    COUNT(CASE WHEN admin_reviewed = true THEN 1 END) as revisados
FROM ml_error_learning;
