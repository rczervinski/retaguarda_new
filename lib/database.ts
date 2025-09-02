import { Pool } from 'pg'

// Configuração da pool de conexões
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: process.env.NODE_ENV === 'production' ? { rejectUnauthorized: false } : false,
  max: 20, // Máximo de conexões na pool
  idleTimeoutMillis: 30000, // Tempo para fechar conexão inativa
  connectionTimeoutMillis: 2000, // Tempo limite para conectar
})

// Função para executar queries
export async function query(text: string, params?: any[]) {
  const start = Date.now()
  const client = await pool.connect()
  
  try {
    const result = await client.query(text, params)
    const duration = Date.now() - start
    
    console.log(`🔍 [DATABASE] Query executada em ${duration}ms`)
    return result
  } catch (error) {
    console.error('❌ [DATABASE] Erro na query:', error)
    throw error
  } finally {
    client.release()
  }
}

// Função para executar transações
export async function transaction(callback: (client: any) => Promise<any>) {
  const client = await pool.connect()
  
  try {
    await client.query('BEGIN')
    const result = await callback(client)
    await client.query('COMMIT')
    return result
  } catch (error) {
    await client.query('ROLLBACK')
    console.error('❌ [DATABASE] Erro na transação:', error)
    throw error
  } finally {
    client.release()
  }
}

// Teste de conexão
export async function testConnection() {
  try {
    const result = await query('SELECT NOW() as timestamp')
    console.log('✅ Conexão com PostgreSQL estabelecida:', result.rows[0].timestamp)
    return true
  } catch (error) {
    console.error('❌ Erro ao conectar ao PostgreSQL:', error)
    return false
  }
}

// Inicializar teste de conexão
testConnection()

export { pool }