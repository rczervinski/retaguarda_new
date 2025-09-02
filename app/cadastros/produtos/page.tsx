'use client'

import { useState, useEffect, useCallback } from 'react'
import { Sidebar } from '@/components/layout/Sidebar'
import { Header } from '@/components/layout/Header'
import { DataTable } from '@/components/ui/DataTable'
import { DualPagination } from '@/components/ui/DualPagination'
import { ProductCard } from '@/components/ui/ProductCard'
import { TableInfo } from '@/components/ui/TableInfo'
import { PlusIcon, PencilIcon, EyeIcon, CloudIcon } from '@heroicons/react/24/outline'
import Link from 'next/link'
import { PlatformIcons } from '@/components/ui/PlatformIcons'

interface Produto {
  codigo_interno: number
  codigo_gtin: string
  descricao: string
  status: string
  selected?: boolean
}

interface PaginationInfo {
  page: number
  limit: number
  total: number
  totalPages: number
  hasNextPage: boolean
  hasPrevPage: boolean
}

interface ApiResponse {
  data: Produto[]
  pagination: PaginationInfo
  search: string
  orderBy: string
  orderDirection: string
}

const statusLabels: { [key: string]: { label: string; color: string } } = {
  'ENS': { label: 'Nuvemshop', color: 'bg-blue-100 text-blue-800' },
  'ENSVI': { label: 'Vitrine', color: 'bg-green-100 text-green-800' },
  'ENSV': { label: 'Variante', color: 'bg-purple-100 text-purple-800' },
  'E': { label: 'E-commerce', color: 'bg-orange-100 text-orange-800' },
  '': { label: 'Local', color: 'bg-gray-100 text-gray-800' },
}

export default function ProdutosPage() {
  const [produtos, setProdutos] = useState<Produto[]>([])
  const [loading, setLoading] = useState(true)
  const [selectedProducts, setSelectedProducts] = useState<Set<number>>(new Set())
  const [pagination, setPagination] = useState<PaginationInfo>({
    page: 1,
    limit: 50,
    total: 0,
    totalPages: 0,
    hasNextPage: false,
    hasPrevPage: false
  })
  const [searchTerm, setSearchTerm] = useState('')
  const [orderBy, setOrderBy] = useState('descricao')
  const [orderDirection, setOrderDirection] = useState('ASC')

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      fetchProdutos()
    }, searchTerm ? 500 : 0) // Debounce search by 500ms

    return () => clearTimeout(timeoutId)
  }, [pagination.page, searchTerm, orderBy, orderDirection])

  const fetchProdutos = async () => {
    try {
      console.log('🔍 [FRONTEND] Iniciando busca de produtos...')
      setLoading(true)
      
      const params = new URLSearchParams({
        page: pagination.page.toString(),
        limit: pagination.limit.toString(),
        search: searchTerm,
        orderBy,
        orderDirection
      })
      
      const response = await fetch(`/api/produtos?${params}`)
      console.log('📡 [FRONTEND] Response status:', response.status)
      console.log('📡 [FRONTEND] Response ok:', response.ok)
      
      if (response.ok) {
        const apiData: ApiResponse = await response.json()
        console.log('📦 [FRONTEND] Dados recebidos:', apiData)
        console.log('📊 [FRONTEND] Quantidade de produtos:', apiData.data.length)
        
        setProdutos(apiData.data)
        setPagination(apiData.pagination)
      } else {
        const errorData = await response.json()
        console.error('❌ [FRONTEND] Erro na API:', errorData)
      }
    } catch (error) {
      console.error('❌ [FRONTEND] Erro ao carregar produtos:', error)
    } finally {
      setLoading(false)
    }
  }

  const toggleProductSelection = (codigoInterno: number) => {
    setSelectedProducts(prev => {
      const newSet = new Set(prev)
      if (newSet.has(codigoInterno)) {
        newSet.delete(codigoInterno)
      } else {
        newSet.add(codigoInterno)
      }
      return newSet
    })
  }

  const toggleSelectAll = () => {
    if (selectedProducts.size === produtos.length) {
      setSelectedProducts(new Set())
    } else {
      setSelectedProducts(new Set(produtos.map(p => p.codigo_interno)))
    }
  }

  const handlePageChange = (page: number) => {
    setPagination(prev => ({ ...prev, page }))
    setSelectedProducts(new Set()) // Limpar seleção ao mudar página
  }

  const handleSearch = (search: string) => {
    setSearchTerm(search)
    setPagination(prev => ({ ...prev, page: 1 })) // Voltar para primeira página
    setSelectedProducts(new Set()) // Limpar seleção
  }

  const handleSort = (field: string) => {
    const newDirection = field === orderBy && orderDirection === 'ASC' ? 'DESC' : 'ASC'
    setOrderBy(field)
    setOrderDirection(newDirection)
    setPagination(prev => ({ ...prev, page: 1 })) // Voltar para primeira página
  }

  const columns = [
    {
      key: 'ecommerce',
      label: (
        <div className="flex items-center">
          <input
            type="checkbox"
            checked={selectedProducts.size === produtos.length && produtos.length > 0}
            onChange={toggleSelectAll}
            className="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 focus:ring-2 mr-2"
          />
          E-commerce
        </div>
      ),
      render: (_: any, row: Produto) => (
        <input
          type="checkbox"
          checked={selectedProducts.has(row.codigo_interno)}
          onChange={() => toggleProductSelection(row.codigo_interno)}
          className="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 focus:ring-2"
        />
      )
    },
    {
      key: 'codigo_gtin',
      label: 'Código',
      sortable: true,
    },
    {
      key: 'descricao',
      label: 'Descrição',
      sortable: true,
    },
    {
      key: 'plataformas',
      label: 'Plataformas',
      render: (_: any, row: Produto) => (
        <PlatformIcons status={row.status} />
      )
    },
    {
      key: 'actions',
      label: 'Editar',
      render: (_: any, row: Produto) => (
        <Link
          href={`/cadastros/produtos/${row.codigo_interno}/editar`}
          className="text-blue-600 hover:text-blue-800"
          title="Editar produto"
        >
          <PencilIcon className="w-4 h-4" />
        </Link>
      )
    }
  ]

  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar />
      
      <div className="flex-1 flex flex-col overflow-hidden lg:ml-0">
        <Header />
        
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
          <div className="max-w-7xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Produtos</h1>
                <p className="text-gray-600">Gerencie o catálogo de produtos</p>
                {selectedProducts.size > 0 && (
                  <p className="text-sm text-blue-600 mt-1">
                    {selectedProducts.size} produto{selectedProducts.size > 1 ? 's' : ''} selecionado{selectedProducts.size > 1 ? 's' : ''}
                  </p>
                )}
              </div>
              <div className="flex items-center gap-3">
                {selectedProducts.size > 0 && (
                  <button
                    onClick={() => console.log('Exportar produtos:', Array.from(selectedProducts))}
                    className="btn-secondary flex items-center"
                  >
                    <CloudIcon className="w-5 h-5 mr-2" />
                    Exportar Selecionados
                  </button>
                )}
                <Link
                  href="/cadastros/produtos/novo"
                  className="btn-primary flex items-center"
                >
                  <PlusIcon className="w-5 h-5 mr-2" />
                  Novo Produto
                </Link>
              </div>
            </div>

            {/* Top Pagination - Hidden on mobile */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
              <DualPagination
                currentPage={pagination.page}
                totalPages={pagination.totalPages}
                total={pagination.total}
                limit={pagination.limit}
                onPageChange={handlePageChange}
                compact={true}
                showInfo={false}
                mobileHidden={true}
              />
            </div>

            {/* Table */}
            <div>
              <DataTable
                columns={columns}
                data={produtos}
                loading={loading}
                searchPlaceholder="Pesquisar por código ou descrição..."
                emptyMessage="Nenhum produto encontrado"
                externalSearch={true}
                onSearch={handleSearch}
                onSort={handleSort}
                currentSort={{ field: orderBy, direction: orderDirection as 'ASC' | 'DESC' }}
                enableMobileCards={true}
                mobileCardComponent={(props) => (
                  <ProductCard
                    produto={props}
                    isSelected={selectedProducts.has(props.codigo_interno)}
                    onSelectionChange={toggleProductSelection}
                  />
                )}
              />
            </div>

            {/* Table Info */}
            <TableInfo
              currentPage={pagination.page}
              totalPages={pagination.totalPages}
              total={pagination.total}
              limit={pagination.limit}
              loading={loading}
              selectedCount={selectedProducts.size}
            />

            {/* Bottom Pagination */}
            <div className="bg-white rounded-xl border border-gray-200 shadow-sm">
              <DualPagination
                currentPage={pagination.page}
                totalPages={pagination.totalPages}
                total={pagination.total}
                limit={pagination.limit}
                onPageChange={handlePageChange}
                showInfo={false}
              />
            </div>
          </div>
        </main>
      </div>
    </div>
  )
}
