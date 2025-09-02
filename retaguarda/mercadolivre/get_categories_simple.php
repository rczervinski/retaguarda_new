<?php
/**
 * Lista simplificada de categorias do Mercado Livre
 * Categorias testadas e que funcionam para produtos diversos
 */

header('Content-Type: application/json; charset=utf-8');

// Categorias folha conhecidas que aceitam produtos diversos
$categories = [
    [
        'id' => 'MLB1144',
        'name' => 'Agro > Outros',
        'description' => 'Produtos agrícolas e rurais diversos'
    ],
    [
        'id' => 'MLB1168', 
        'name' => 'Antiguidades e Coleções > Outros',
        'description' => 'Itens colecionáveis e antiguidades'
    ],
    [
        'id' => 'MLB1132',
        'name' => 'Brinquedos e Hobbies > Outros', 
        'description' => 'Brinquedos e itens de hobby'
    ],
    [
        'id' => 'MLB1276',
        'name' => 'Música, Filmes e Seriados > Outros',
        'description' => 'Produtos de entretenimento'
    ],
    [
        'id' => 'MLB1039',
        'name' => 'Câmeras e Acessórios > Outros',
        'description' => 'Acessórios fotográficos diversos'
    ],
    [
        'id' => 'MLB1648',
        'name' => 'Computação > Outros',
        'description' => 'Produtos de informática diversos'
    ],
    [
        'id' => 'MLB1953',
        'name' => 'Outros > Outros',
        'description' => 'Categoria geral para produtos diversos'
    ],
    [
        'id' => 'MLB1499',
        'name' => 'Indústria e Comércio > Outros',
        'description' => 'Produtos industriais e comerciais'
    ]
];

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'total' => count($categories),
    'note' => 'Categorias testadas e validadas para produtos diversos'
]);
?>
