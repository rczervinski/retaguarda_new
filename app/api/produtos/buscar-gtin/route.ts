import { NextRequest, NextResponse } from 'next/server';
import { PrismaClient } from '@prisma/client';
import { convertBigIntToString } from '@/lib/bigint-utils';

const prisma = new PrismaClient();

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
    
    // Query para buscar produto por GTIN
    const produtoQuery = `
      SELECT 
        p.codigo_interno::text as codigo_interno,
        p.codigo_gtin, 
        p.descricao, 
        p.status,
        -- Dados básicos de produtos_ib
        pib.unidade,
        pib.preco_venda::text as preco_venda,
        pib.preco_compra::text as preco_compra
      FROM produtos p
      LEFT JOIN produtos_ib pib ON p.codigo_interno = pib.codigo_interno
      WHERE p.codigo_gtin = $1
      LIMIT 1
    `;
    
    console.log('🔍 Executando query para GTIN:', gtin);
    
    const resultado = await prisma.$queryRawUnsafe(produtoQuery, gtin);
    
    if (!resultado || (resultado as any[]).length === 0) {
      console.log('❌ Produto não encontrado com GTIN:', gtin);
      return NextResponse.json(
        { success: false, error: 'Produto não encontrado com este GTIN' },
        { status: 404 }
      );
    }
    
    const produto = (resultado as any[])[0];
    console.log('✅ Produto encontrado:', produto.descricao);
    
    const response = {
      success: true,
      data: convertBigIntToString(produto)
    };
    
    console.log('✅ Resposta formatada com sucesso');
    
    return NextResponse.json(response);
    
  } catch (error) {
    console.error('❌ Erro na API buscar-gtin:', error);
    return NextResponse.json(
      { 
        success: false, 
        error: 'Erro interno do servidor',
        details: error instanceof Error ? error.message : 'Erro desconhecido'
      },
      { status: 500 }
    );
  }
}
