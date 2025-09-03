import { NextRequest, NextResponse } from 'next/server';
import { Pool } from 'pg';

// Configuração da conexão com PostgreSQL
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false
});

// GET /api/produtos/[codigo]/grade - Buscar grade completa do produto
export async function GET(
  request: NextRequest,
  { params }: { params: { codigo: string } }
) {
  try {
    console.log('🔍 API GET /api/produtos/[codigo]/grade chamada');
    console.log('📝 Código do produto:', params.codigo);
    
    const codigoInterno = parseInt(params.codigo);
    
    if (isNaN(codigoInterno)) {
      return NextResponse.json(
        { success: false, error: 'Código do produto inválido' },
        { status: 400 }
      );
    }
    
    // Buscar todas as variantes da grade com informações completas
    const query = `
      SELECT 
        gd.codigo,
        gd.codigo_gtin,
        gd.nome as descricao,
        gd.variacao,
        gd.caracteristica,
        gd.codigo_interno,
        COALESCE(ib.preco_venda, '0') as preco_venda,
        COALESCE(ou.qtde, '0') as estoque,
        COALESCE(ou.comprimento, '0') as comprimento,
        COALESCE(ou.largura, '0') as largura,
        COALESCE(ou.altura, '0') as altura,
        COALESCE(ou.peso, '0') as peso
      FROM produtos_gd gd
      LEFT JOIN produtos p ON gd.codigo_gtin = p.codigo_gtin
      LEFT JOIN produtos_ib ib ON p.codigo_interno = ib.codigo_interno  
      LEFT JOIN produtos_ou ou ON p.codigo_interno = ou.codigo_interno
      WHERE gd.codigo_interno = $1
      ORDER BY gd.codigo
    `;
    
    console.log('🔍 Executando consulta da grade...');
    const result = await pool.query(query, [codigoInterno]);
    
    console.log('📊 Variantes encontradas:', result.rows.length);
    
    // Formatar dados das variantes
    const variantes = result.rows.map(row => ({
      codigo: row.codigo.toString(),
      codigo_gtin: row.codigo_gtin,
      descricao: row.descricao,
      variacao: row.variacao,
      caracteristica: row.caracteristica,
      preco_venda: parseFloat(row.preco_venda || '0'),
      estoque: parseInt(row.estoque || '0'),
      dimensoes: {
        comprimento: parseFloat(row.comprimento || '0'),
        largura: parseFloat(row.largura || '0'),
        altura: parseFloat(row.altura || '0'),
        peso: parseFloat(row.peso || '0')
      }
    }));
    
    return NextResponse.json({
      success: true,
      grade: variantes
    });
    
  } catch (error) {
    console.error('❌ Erro ao buscar grade:', error);
    return NextResponse.json(
      { success: false, error: 'Erro interno do servidor' },
      { status: 500 }
    );
  }
}

// POST /api/produtos/[codigo]/grade - Salvar/atualizar grade do produto
export async function POST(
  request: NextRequest,
  { params }: { params: { codigo: string } }
) {
  try {
    console.log('💾 API POST /api/produtos/[codigo]/grade chamada');
    console.log('📝 Código do produto:', params.codigo);
    
    const codigoInterno = parseInt(params.codigo);
    
    if (isNaN(codigoInterno)) {
      return NextResponse.json(
        { success: false, error: 'Código do produto inválido' },
        { status: 400 }
      );
    }
    
    const body = await request.json();
    console.log('📦 Body recebido:', JSON.stringify(body, null, 2));
    
    const { variantes } = body;
    
    if (!Array.isArray(variantes)) {
      console.log('❌ Variantes não é um array:', typeof variantes);
      return NextResponse.json(
        { success: false, error: 'Variantes devem ser um array' },
        { status: 400 }
      );
    }
    
    console.log('📦 Variantes a processar:', variantes.length);
    
    // Iniciar transação
    const client = await pool.connect();
    
    try {
      await client.query('BEGIN');
      
      // 1. Deletar grade existente
      console.log('🗑️ Removendo grade existente...');
      await client.query(
        'DELETE FROM produtos_gd WHERE codigo_interno = $1',
        [codigoInterno]
      );
      
      // 2. Inserir novas variantes na grade
      for (const variante of variantes) {
        console.log('➕ Inserindo variante:', variante.codigo_gtin);
        
        await client.query(`
          INSERT INTO produtos_gd (
            codigo_gtin, 
            nome, 
            variacao, 
            caracteristica, 
            codigo_interno
          ) VALUES ($1, $2, $3, $4, $5)
        `, [
          variante.codigo_gtin,
          variante.descricao || '',
          variante.variacao || '',
          variante.caracteristica || '',
          codigoInterno
        ]);
        
        // 3. Atualizar preço se fornecido
        if (variante.preco_venda && variante.preco_venda > 0) {
          const produtoResult = await client.query(
            'SELECT codigo_interno FROM produtos WHERE codigo_gtin = $1',
            [variante.codigo_gtin]
          );
          
          if (produtoResult.rows.length > 0) {
            const codigoInternoVariante = produtoResult.rows[0].codigo_interno;
            
            await client.query(`
              UPDATE produtos_ib 
              SET preco_venda = $1 
              WHERE codigo_interno = $2
            `, [variante.preco_venda.toString(), codigoInternoVariante]);
          }
        }
        
        // 4. Atualizar estoque e dimensões se fornecidos
        if (variante.estoque !== undefined || variante.dimensoes) {
          const produtoResult = await client.query(
            'SELECT codigo_interno FROM produtos WHERE codigo_gtin = $1',
            [variante.codigo_gtin]
          );
          
          if (produtoResult.rows.length > 0) {
            const codigoInternoVariante = produtoResult.rows[0].codigo_interno;
            
            const updates = [];
            const values = [];
            let paramCount = 1;
            
            if (variante.estoque !== undefined) {
              updates.push(`qtde = $${paramCount++}`);
              values.push(variante.estoque.toString());
            }
            
            if (variante.dimensoes) {
              if (variante.dimensoes.comprimento !== undefined) {
                updates.push(`comprimento = $${paramCount++}`);
                values.push(variante.dimensoes.comprimento.toString());
              }
              if (variante.dimensoes.largura !== undefined) {
                updates.push(`largura = $${paramCount++}`);
                values.push(variante.dimensoes.largura.toString());
              }
              if (variante.dimensoes.altura !== undefined) {
                updates.push(`altura = $${paramCount++}`);
                values.push(variante.dimensoes.altura.toString());
              }
              if (variante.dimensoes.peso !== undefined) {
                updates.push(`peso = $${paramCount++}`);
                values.push(variante.dimensoes.peso.toString());
              }
            }
            
            if (updates.length > 0) {
              values.push(codigoInternoVariante);
              await client.query(`
                UPDATE produtos_ou 
                SET ${updates.join(', ')} 
                WHERE codigo_interno = $${paramCount}
              `, values);
            }
          }
        }
      }
      
      await client.query('COMMIT');
      console.log('✅ Grade salva com sucesso');
      
      return NextResponse.json({
        success: true,
        message: 'Grade salva com sucesso'
      });
      
    } catch (error) {
      await client.query('ROLLBACK');
      throw error;
    } finally {
      client.release();
    }
    
  } catch (error) {
    console.error('❌ Erro ao salvar grade:', error);
    console.error('❌ Stack trace:', error instanceof Error ? error.stack : 'No stack trace');
    
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

// DELETE /api/produtos/[codigo]/grade - Remover variante específica da grade
export async function DELETE(
  request: NextRequest,
  { params }: { params: { codigo: string } }
) {
  try {
    console.log('🗑️ API DELETE /api/produtos/[codigo]/grade chamada');
    
    const { searchParams } = new URL(request.url);
    const codigoGrade = searchParams.get('codigo');
    
    if (!codigoGrade) {
      return NextResponse.json(
        { success: false, error: 'Código da variante é obrigatório' },
        { status: 400 }
      );
    }
    
    console.log('🗑️ Removendo variante código:', codigoGrade);
    
    const result = await pool.query(
      'DELETE FROM produtos_gd WHERE codigo = $1',
      [parseInt(codigoGrade)]
    );
    
    if (result.rowCount === 0) {
      return NextResponse.json(
        { success: false, error: 'Variante não encontrada' },
        { status: 404 }
      );
    }
    
    console.log('✅ Variante removida com sucesso');
    
    return NextResponse.json({
      success: true,
      message: 'Variante removida com sucesso'
    });
    
  } catch (error) {
    console.error('❌ Erro ao remover variante:', error);
    return NextResponse.json(
      { success: false, error: 'Erro interno do servidor' },
      { status: 500 }
    );
  }
}
