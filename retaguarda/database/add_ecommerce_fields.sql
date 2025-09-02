-- Adicionar campos específicos por plataforma de e-commerce
-- Executar este script para criar os novos campos

-- Verificar se os campos já existem antes de adicionar
DO $$
BEGIN
    -- Campo para Nuvemshop
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'produtos' AND column_name = 'ns') THEN
        ALTER TABLE produtos ADD COLUMN ns VARCHAR(10);
        RAISE NOTICE 'Campo NS adicionado';
    ELSE
        RAISE NOTICE 'Campo NS já existe';
    END IF;
    
    -- Campo para Mercado Livre
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'produtos' AND column_name = 'ml') THEN
        ALTER TABLE produtos ADD COLUMN ml VARCHAR(10);
        RAISE NOTICE 'Campo ML adicionado';
    ELSE
        RAISE NOTICE 'Campo ML já existe';
    END IF;
    
    -- Campo para Shopee
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'produtos' AND column_name = 'shopee') THEN
        ALTER TABLE produtos ADD COLUMN shopee VARCHAR(10);
        RAISE NOTICE 'Campo SHOPEE adicionado';
    ELSE
        RAISE NOTICE 'Campo SHOPEE já existe';
    END IF;
END
$$;

-- Migrar dados existentes do campo STATUS para os novos campos
-- Produtos da Nuvemshop
UPDATE produtos SET ns = status 
WHERE status IN ('ENS', 'ENSVI', 'ENSV', 'E') AND ns IS NULL;

-- Comentários para documentar os campos
COMMENT ON COLUMN produtos.ns IS 'Status Nuvemshop: ENS, ENSVI, ENSV, E';
COMMENT ON COLUMN produtos.ml IS 'Status Mercado Livre: ML, MLVI, MLV';
COMMENT ON COLUMN produtos.shopee IS 'Status Shopee: SH, SHVI, SHV';
COMMENT ON COLUMN produtos.status IS 'Status geral do sistema (E = e-commerce local, outros valores diversos)';

-- Criar índices para performance
CREATE INDEX IF NOT EXISTS idx_produtos_ns ON produtos(ns);
CREATE INDEX IF NOT EXISTS idx_produtos_ml ON produtos(ml);
CREATE INDEX IF NOT EXISTS idx_produtos_shopee ON produtos(shopee);

-- Mostrar estatísticas após migração
SELECT 
    'Nuvemshop' as plataforma,
    COUNT(*) as total_produtos,
    COUNT(CASE WHEN ns IS NOT NULL THEN 1 END) as com_tag
FROM produtos
UNION ALL
SELECT 
    'Mercado Livre' as plataforma,
    COUNT(*) as total_produtos,
    COUNT(CASE WHEN ml IS NOT NULL THEN 1 END) as com_tag
FROM produtos
UNION ALL
SELECT 
    'Shopee' as plataforma,
    COUNT(*) as total_produtos,
    COUNT(CASE WHEN shopee IS NOT NULL THEN 1 END) as com_tag
FROM produtos;
