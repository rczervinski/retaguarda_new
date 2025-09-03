import { NextRequest, NextResponse } from 'next/server';
import { Pool } from 'pg';

// Configuração da conexão com PostgreSQL
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

// GET /api/produtos/buscar-gtin?gtin=12345 - Buscar produto por código GTIN
export async function GET(request: NextRequest) {
  console.log('🔍 API /api/produtos/buscar-gtin GET chamada');
  console.log('⏰ Timestamp:', new Date().toISOString());
  
  try {
    const { searchParams } = new URL(request.url);
    const gtin = searchParams.get('gtin');
    
    if (!gtin) {
      console.log('❌ GTIN não fornecido');
      return NextResponse.json(
        { success: false, error: 'Código GTIN é obrigatório' },
        { status: 400 }
      );
    }
    
    console.log('🔍 Buscando produto com GTIN:', gtin);
    
    // Query para buscar produto por GTIN com informações completas
    const produtoQuery = `
      SELECT 
        p.codigo_interno,
        p.codigo_gtin, 
        p.descricao, 
        p.status,
        -- Dados de preço
        COALESCE(pib.preco_venda, '0') as preco_venda,
        COALESCE(pib.preco_compra, '0') as preco_compra,
        pib.unidade,
        -- Dados de estoque e dimensões
        COALESCE(pou.qtde, '0') as estoque,
        COALESCE(pou.comprimento, '0') as comprimento,
        COALESCE(pou.largura, '0') as largura,
        COALESCE(pou.altura, '0') as altura,
        COALESCE(pou.peso, '0') as peso
      FROM produtos p
      LEFT JOIN produtos_ib pib ON p.codigo_interno = pib.codigo_interno
      LEFT JOIN produtos_ou pou ON p.codigo_interno = pou.codigo_interno
      WHERE p.codigo_gtin = $1
      LIMIT 1
    `;
    
    console.log('🔍 Executando query para GTIN:', gtin);
    
    const result = await pool.query(produtoQuery, [gtin]);
    
    if (result.rows.length === 0) {
      console.log('❌ Produto não encontrado com GTIN:', gtin);
      return NextResponse.json(
        { success: false, error: 'Produto não encontrado com este GTIN' },
        { status: 404 }
      );
    }
    
    const produto = result.rows[0];
    console.log('✅ Produto encontrado:', produto.descricao);
    
    const response = {
      success: true,
      data: {
        codigo_interno: produto.codigo_interno.toString(),
        codigo_gtin: produto.codigo_gtin,
        descricao: produto.descricao,
        status: produto.status,
        preco_venda: parseFloat(produto.preco_venda) || 0,
        preco_compra: parseFloat(produto.preco_compra) || 0,
        unidade: produto.unidade,
        estoque: parseFloat(produto.estoque) || 0,
        dimensoes: {
          comprimento: parseFloat(produto.comprimento) || 0,
          largura: parseFloat(produto.largura) || 0,
          altura: parseFloat(produto.altura) || 0,
          peso: parseFloat(produto.peso) || 0
        }
      }
    };
    
    console.log('✅ Resposta enviada:', response);
    return NextResponse.json(response);
    
  } catch (error) {
    console.error('❌ Erro na API buscar-gtin:', error);
    return NextResponse.json(
      { success: false, error: 'Erro interno do servidor' },
      { status: 500 }
    );
  }
}
