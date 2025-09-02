<?php
// Desabilitar exibição de erros para evitar interferir no JSON
error_reporting(0);
ini_set('display_errors', 0);

//at
include "conexao.php";
include "produtos_class.php";
include "produtos_ajax_sincronizacao.php"; // Incluir arquivo de sincronização

// Verificar se a conexão foi estabelecida
if (!$conexao) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

/**
 * Função para limpar caracteres UTF-8 inválidos recursivamente
 */
function limparCaracteresUTF8($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = limparCaracteresUTF8($value);
        }
        return $data;
    } elseif (is_string($data)) {
        // Remover caracteres UTF-8 inválidos
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        // Remover caracteres de controle exceto quebras de linha
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        return $data;
    } else {
        return $data;
    }
}
$request = "";
$pagina = 0;
if (isset($_POST['request'])) {
    $request = $_POST['request'];
    $pagina = $_POST['pagina'];
    $razao_social = $_POST['razao_social'];
    $desc_pesquisa = $_POST['desc_pesquisa'];
}
if ($request == 'carregar_grupo') {
    // Definir cabeçalho JSON
    header('Content-Type: application/json; charset=utf-8');

    // Verificar se há filtro por categoria
    $categoria_filtro = isset($_POST['categoria_filtro']) ? $_POST['categoria_filtro'] : '';

    if (!empty($categoria_filtro)) {
        $query = "select distinct grupo from produtos_ib where categoria = '" . pg_escape_string($conexao, $categoria_filtro) . "' order by grupo";
    } else {
        $query = "select distinct grupo from produtos_ib order by grupo";
    }

    $result = pg_query($conexao, $query);

    // Verificar se a query foi executada com sucesso
    if (!$result) {
        $error = pg_last_error($conexao);
        error_log("Erro na query carregar_grupo: " . $error);
        echo json_encode(['error' => 'Erro ao carregar grupos: ' . $error]);
        die();
    }

    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $grupo = $row['grupo'];
        $response[] = array(
            "grupo" => $grupo
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregar_subgrupo') {
    // Definir cabeçalho JSON
    header('Content-Type: application/json; charset=utf-8');

    $query = "select distinct subgrupo from produtos_ib order by subgrupo";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $subgrupo = $row['subgrupo'];
        $response[] = array(
            "subgrupo" => $subgrupo
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'buscar_categorias_produto') {
    $codigo_interno = $_POST['codigo_interno'];
    $query = "SELECT categoria, grupo FROM produtos_ib WHERE codigo_interno = " . intval($codigo_interno);
    $result = pg_query($conexao, $query);

    $response = array(
        'categoria' => '',
        'grupo' => ''
    );

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $response = array(
            'categoria' => $row['categoria'] ?? '',
            'grupo' => $row['grupo'] ?? ''
        );
    }

    echo json_encode($response);
    die();
}
if ($request == 'carregar_categoria') {
    // Definir cabeçalho JSON
    header('Content-Type: application/json; charset=utf-8');

    $query = "select distinct categoria from produtos_ib order by categoria";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $categoria = $row['categoria'];
        $response[] = array(
            "categoria" => $categoria
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'adicionarFornecedor') {
    $query = "insert into fornecedores (codigo,razao_social,fantasia,cep) values(nextval('fornecedores_codigo_seq'),upper('" . $razao_social . "'),upper('" . $razao_social . "'),'83005410')";
    $result = pg_query($conexao, $query);
    $response = "ok";
    echo json_encode($response);
    die();
}
if ($request == 'carregar_unidade') {
    // Definir cabeçalho JSON
    header('Content-Type: application/json; charset=utf-8');

    $query = "select distinct unidade from produtos_ib order by unidade";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $unidade = $row['unidade'];
        $response[] = array(
            "unidade" => $unidade
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregar_fornecedor') {
    $query = "select codigo,razao_social from fornecedores order by razao_social";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $razao_social = $row['razao_social'];
        $codigo = $row['codigo'];
        $response[] = array(
            "codigo" => $codigo,
            "razao_social" => $razao_social
        );
    }
    echo json_encode($response);
    die();
}
// Fetch all records
if ($request == 'fetchall') {
    // Definir cabeçalho JSON
    header('Content-Type: application/json; charset=utf-8');

    // Garantir que $pagina seja um número inteiro
    $pagina = intval($pagina);

    // Log para depuração
    error_log("Requisição: fetchall, Página: $pagina, Termo: $desc_pesquisa");

    // Receber filtros se enviados
    $filtros = isset($_POST['filtros']) ? json_decode($_POST['filtros'], true) : array();

    // Construir condições WHERE baseadas nos filtros
    $condicoes_where = array();
    $joins_adicionais = "";

    // Filtros de e-commerce baseados no status
    $status_filtros = array();

    // NOVO SISTEMA: Usar campo NS em vez de STATUS para Nuvemshop
    if (!empty($filtros['nuvemshop']) || !empty($filtros['nuvem_normal']) || !empty($filtros['nuvem_vitrine']) || !empty($filtros['nuvem_variante'])) {
        $ns_filtros = [];

        if (!empty($filtros['nuvem_normal'])) {
            $ns_filtros[] = "'ENS'";
        }
        if (!empty($filtros['nuvem_vitrine'])) {
            $ns_filtros[] = "'ENSVI'";
        }
        if (!empty($filtros['nuvem_variante'])) {
            $ns_filtros[] = "'ENSV'";
        }
        // Se apenas Nuvemshop geral foi selecionado, incluir todos os status da Nuvemshop
        if (!empty($filtros['nuvemshop']) && empty($filtros['nuvem_normal']) && empty($filtros['nuvem_vitrine']) && empty($filtros['nuvem_variante'])) {
            $ns_filtros[] = "'ENS'";
            $ns_filtros[] = "'ENSVI'";
            $ns_filtros[] = "'ENSV'";
            $ns_filtros[] = "'E'"; // Status antigo migrado
        }

        if (!empty($ns_filtros)) {
            $where_conditions[] = "ns IN (" . implode(',', $ns_filtros) . ")";
        }
    }

    // Filtro para produtos apenas locais
    if (!empty($filtros['apenas_locais'])) {
        $condicoes_where[] = "(p.status IS NULL OR p.status = '' OR p.status NOT IN ('ENS', 'ENSVI', 'ENSV', 'E'))";
    }

    // Se há filtros de status específicos, adicionar à condição
    if (!empty($status_filtros)) {
        $condicoes_where[] = "p.status IN (" . implode(',', $status_filtros) . ")";
    }

    // Filtros de categoria e grupo
    if (!empty($filtros['categoria']) || !empty($filtros['grupo'])) {
        $joins_adicionais = " INNER JOIN produtos_ib pib ON p.codigo_interno = pib.codigo_interno ";

        if (!empty($filtros['categoria'])) {
            $condicoes_where[] = "pib.categoria = '" . pg_escape_string($conexao, $filtros['categoria']) . "'";
        }

        if (!empty($filtros['grupo'])) {
            $condicoes_where[] = "pib.grupo = '" . pg_escape_string($conexao, $filtros['grupo']) . "'";
        }
    }

    // Construir query base
    $base_query = "FROM produtos p" . $joins_adicionais;

    // Adicionar condições de pesquisa
    if (is_numeric($desc_pesquisa)) {
        $condicoes_where[] = "p.codigo_gtin='" . $desc_pesquisa . "'";
    } else if (!empty($desc_pesquisa)) {
        $condicoes_where[] = "p.descricao LIKE upper('%" . $desc_pesquisa . "%')";
    }

    // Montar WHERE final
    $where_clause = "";
    if (!empty($condicoes_where)) {
        $where_clause = " WHERE " . implode(' AND ', $condicoes_where);
    }

    // Queries finais
    $query = "SELECT p.codigo_gtin, p.descricao, p.codigo_interno, p.status " . $base_query . $where_clause . " ORDER BY p.descricao ASC LIMIT 50 OFFSET " . $pagina;
    $query_quantos = "SELECT count(*) " . $base_query . $where_clause;

    // Log para depuração
    error_log("Query: $query");

    $result = pg_query($conexao, $query);
    $result_quantos = pg_query($conexao, $query_quantos);

    // Verificar se as queries foram executadas com sucesso
    if (!$result || !$result_quantos) {
        $error = pg_last_error($conexao);
        error_log("Erro na query fetchall: " . $error);
        echo json_encode(['error' => 'Erro ao buscar produtos: ' . $error]);
        die();
    }

    $row_quantos = pg_fetch_row($result_quantos);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {
        $codigo_gtin = $row['codigo_gtin'];
        $descricao = $row['descricao'];
        $codigo_interno = $row['codigo_interno'];
        $status = $row['status'];
        $quantos = $row_quantos[0];

        $response[] = array(
            "codigo_gtin" => $codigo_gtin,
            "descricao" => $descricao,
            "codigo_interno" => $codigo_interno,
            "status" => $status,
            "quantos" => $quantos,
            "pagina" => $pagina // Adicionar o valor de pagina para a paginação
        );
    }
    echo json_encode($response);
    die();
}
//PARA CONSULTAR CODIGO DO PRODUTO EXISTENTE
if ($request == 'consultarCodigoProduto') {
    $response = "0";
    $codigo_gtin= $_POST['codigo_gtin'];
    $query="select codigo_interno from produtos where codigo_gtin='".$codigo_gtin."'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $codigo_interno = $row['codigo_interno'];
        $response[] = array(
            "codigo_interno" => $codigo_interno
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'consultarCodigoProdutoGrade') {
    $response = "0";
    $codigo_gtin= $_POST['codigo_gtin'];
    $query="select descricao from produtos where codigo_gtin='".$codigo_gtin."'";
    $result = pg_query($conexao, $query);
    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $descricao = $row['descricao'];
        $response[] = array(
            "descricao" => $descricao
        );
    }
    echo json_encode($response);
    die();
}
// PARA ALTERAR PRODUTOS
if ($request == 'atribuirProdutos') {
    $produtos = new produtos();
    $produtos->codigo_interno = $_POST['codigo_interno'];

    // Verificar se o produto existe
    $check_query = "SELECT codigo_interno FROM produtos WHERE codigo_interno = " . $produtos->codigo_interno;
    $check_result = pg_query($conexao, $check_query);

    if (!$check_result || pg_num_rows($check_result) == 0) {
        // Produto não encontrado
        $response = array(
            "success" => false,
            "error" => "Produto não encontrado com o código " . $produtos->codigo_interno
        );
        echo json_encode($response);
        die();
    }

    // Produto existe, buscar detalhes
    $query = "select p.codigo_interno, p.codigo_gtin, p.descricao, pb.descricao_detalhada,pb.grupo,pb.subgrupo,pb.categoria, pb.preco_venda, pb.preco_compra, pb.perc_lucro,pb.codigo_ncm,pb.cest,".
    "pb.cfop, pt.aliquota_icms,pb.produto_balanca,pb.validade, po.dt_cadastro, po.dt_ultima_alteracao,p.status,pb.unidade,po.codfor,pt.situacao_tributaria, ".
    "po.perc_desc_a, po.val_desc_a, po.perc_desc_b, po.val_desc_b,po.perc_desc_c,po.val_desc_c,po.perc_desc_d,po.val_desc_d, ".
    "po.perc_desc_e,po.val_desc_e,pt.aliquota_calculo_credito,pt.perc_dif,pt.modalidade_deter_bc_icms,pt.icms_reducao_bc, ".
    "pt.modalidade_deter_bc_icms_st, po.tamanho,po.vencimento, pt.aliquota_fcp_st, po.descricao_personalizada,po.preco_gelado, ".
    " po.desc_etiqueta, po.inativo,po.producao, pt.aliquota_fcp, po.qtde, po.qtde_min, pt.icms_reducao_bc_st, pt.perc_mva_icms_st,pt.aliquota_icms_st, ".
    " pt.ipi_reducao_bc, pt.aliquota_ipi, pt.ipi_reducao_bc_st,pt.aliquota_ipi_st, pt.cst_ipi, pt.calculo_ipi, ".
    " pt.pis_reducao_bc, pt.aliquita_pis, pt.pis_reducao_bc_st,pt.aliquota_pis_st, pt.cst_pis, pt.calculo_pis, ".
    " pt.cofins_reducao_bc, pt.aliquota_cofins, pt.cofins_reducao_bc_st,pt.aliquota_cofins_st, pt.cst_cofins, pt.calculo_cofins,po.comprimento,po.largura,po.altura,po.peso ".
        " from produtos p inner join produtos_ib pb on p.codigo_interno = pb.codigo_interno ".
        " inner join produtos_ou po on p.codigo_interno=po.codigo_interno ".
        " inner join produtos_tb pt on p.codigo_interno=pt.codigo_interno where p.codigo_interno=" . $produtos->codigo_interno;

    $result = pg_query($conexao, $query);

    if (!$result) {
        // Erro na consulta
        $error = pg_last_error($conexao);
        $response = array(
            "success" => false,
            "error" => "Erro ao buscar detalhes do produto: " . $error
        );
        echo json_encode($response);
        die();
    }

    $response = array();

    if (pg_num_rows($result) == 0) {
        // Produto existe mas não tem detalhes completos
        $response = array(
            "success" => false,
            "error" => "Produto encontrado, mas não tem detalhes completos. Pode ser necessário recriar o produto."
        );
        echo json_encode($response);
        die();
    }

    // Produto encontrado com sucesso
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo_interno" => $row['codigo_interno'],
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao'],
            "descricao_detalhada" => $row['descricao_detalhada'],
            "grupo" => $row['grupo'],
            "subgrupo" => $row['subgrupo'],
            "categoria" => $row['categoria'],
            "preco_venda" => number_format($row['preco_venda'],2,',',''),
            "preco_compra" => number_format($row['preco_compra'],2,',',''),
            "perc_lucro" => number_format($row['perc_lucro'],2,',',''),
            "codigo_ncm" => $row['codigo_ncm'],
            "cest" => $row['cest'],
            "cfop" => $row['cfop'],
            "aliquota_icms" => number_format($row['aliquota_icms'],2,',',''),
            "produto_balanca" => $row['produto_balanca'],
            "producao" => $row['producao'],
            "validade" => $row['validade'],
            "dt_cadastro" => date("d/m/Y",strtotime($row['dt_cadastro'])),
            "dt_ultima_alteracao" => date("d/m/Y",strtotime($row['dt_ultima_alteracao'])),
            "unidade" => $row['unidade'],
            "status" => $row['status'],
            "codfor" => $row['codfor'],
            "situacao_tributaria" => $row['situacao_tributaria'],
            "perc_desc_a" => number_format($row['perc_desc_a'],2,',',''),
            "val_desc_a" => number_format($row['val_desc_a'],2,',',''),
            "perc_desc_b" => number_format($row['perc_desc_b'],2,',',''),
            "val_desc_b" => number_format($row['val_desc_b'],2,',',''),
            "perc_desc_c" => number_format($row['perc_desc_c'],2,',',''),
            "val_desc_c" => number_format($row['val_desc_c'],2,',',''),
            "perc_desc_d" => number_format($row['perc_desc_d'],2,',',''),
            "val_desc_d" => number_format($row['val_desc_d'],2,',',''),
            "perc_desc_e" => number_format($row['perc_desc_e'],2,',',''),
            "val_desc_e" => number_format($row['val_desc_e'],2,',',''),
            "aliquota_calculo_credito" => number_format($row['aliquota_calculo_credito'],2,',',''),
            "perc_dif" => number_format($row['perc_dif'],2,',',''),
            "mod_deter_bc_icms" => $row['modalidade_deter_bc_icms'],
            "perc_redu_icms" => number_format($row['icms_reducao_bc'],2,',',''),
            "mod_deter_bc_icms_st" => $row['modalidade_deter_bc_icms_st'],
            "tamanho" => $row['tamanho'],
            "vencimento" => $row['vencimento'],
            "aliq_fcp_st" => number_format($row['aliquota_fcp_st'],2,',',''),
            "descricao_personalizada" => $row['descricao_personalizada'],
            "valorGelado" => number_format($row['preco_gelado'],2,',',''),
            "prod_desc_etiqueta" => $row['desc_etiqueta'],
            "inativo" => $row['inativo'],
            "aliq_fcp" => number_format($row['aliquota_fcp'],2,',',''),
            "qtde" => number_format($row['qtde'],2,',',''),
            "qtde_min" => number_format($row['qtde_min'],2,',',''),
            "perc_redu_icms_st" => number_format($row['icms_reducao_bc_st'],2,',',''),
            "perc_mv_adic_icms_st" => number_format($row['perc_mva_icms_st'],2,',',''),
            "aliq_icms_st" => number_format($row['aliquota_icms_st'],2,',',''),
            "ipi_reducao_bc" => number_format($row['ipi_reducao_bc'],2,',',''),
            "aliquota_ipi" => number_format($row['aliquota_ipi'],2,',',''),
            "ipi_reducao_bc_st" => number_format($row['ipi_reducao_bc_st'],2,',',''),
            "aliquota_ipi_st" => number_format($row['aliquota_ipi_st'],2,',',''),
            "cst_ipi" => $row['cst_ipi'],
            "calculo_ipi" => $row['calculo_ipi'],
            "pis_reducao_bc" => number_format($row['pis_reducao_bc'],2,',',''),
            "aliquota_pis" => number_format($row['aliquita_pis'],2,',',''),
            "pis_reducao_bc_st" => number_format($row['pis_reducao_bc_st'],2,',',''),
            "aliquota_pis_st" => number_format($row['aliquota_pis_st'],2,',',''),
            "cst_pis" => $row['cst_pis'],
            "calculo_pis" => $row['calculo_pis'],
            "cofins_reducao_bc" => number_format($row['cofins_reducao_bc'],2,',',''),
            "aliquota_cofins" => number_format($row['aliquota_cofins'],2,',',''),
            "cofins_reducao_bc_st" => number_format($row['cofins_reducao_bc_st'],2,',',''),
            "aliquota_cofins_st" => number_format($row['aliquota_cofins_st'],2,',',''),
            "cst_cofins" => $row['cst_cofins'],
            "calculo_cofins" => $row['calculo_cofins'],
            "comprimento" => $row['comprimento'],
            "largura" => $row['largura'],
            "altura" => $row['altura'],
            "peso" => $row['peso']
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'arquivoExiste') {
    $arquivo1=$_POST['arquivo1'];
    $arquivo2=$_POST['arquivo2'];
    $arquivo3=$_POST['arquivo3'];
    $arquivo4=$_POST['arquivo4'];
    $arquivo5=$_POST['arquivo5'];
    $result="";
    if(file_exists($arquivo1)){
        $result="1";
    }else{
        $result="0";
    }
    if(file_exists($arquivo2)){
        $result=$result."1";
    }else{
        $result=$result."0";
    }
    if(file_exists($arquivo3)){
        $result=$result."1";
    }else{
        $result=$result."0";
    }
    if(file_exists($arquivo4)){
        $result=$result."1";
    }else{
        $result=$result."0";
    }
    if(file_exists($arquivo5)){
        $result=$result."1";
    }else{
        $result=$result."0";
    }
    echo json_encode($result);
    die();
}
// PARA INSERIR / ATUALIZAR PRODUTOS
if ($request == 'inserirAtualizarProdutos') {
    $response = "0";
    $produtos = new produtos();
    $produtos->codigo_interno = $_POST['codigo_interno'];
    $produtos->codigo_gtin = $_POST['codigo_gtin'];
    $produtos->descricao = $_POST['descricao'];
    if($produtos->codigo_gtin==0 or $produtos->codigo_gtin==''){
        die();
        return;
    }
    // Não atualizar o status do produto com base no checkbox "Vender no E-commerce"
    // O status só deve ser atualizado quando o produto é efetivamente exportado para a Nuvemshop
    // Manter o status atual do produto
    $query = "SELECT status FROM produtos WHERE codigo_interno = " . $produtos->codigo_interno;
    $result = pg_query($conexao, $query);
    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $produtos->status = $row['status'];
    } else {
        $produtos->status = "";
    }
    if($produtos->codigo_interno==0)
    {
        // Verificar se o código GTIN já existe
        $check_query = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '" . $produtos->codigo_gtin . "'";
        $check_result = pg_query($conexao, $check_query);

        if (pg_num_rows($check_result) > 0) {
            // O código GTIN já existe, retornar erro
            $response = array(
                "success" => false,
                "error" => "Código GTIN já cadastrado. Por favor, use outro código."
            );
            echo json_encode($response);
            die();
        }

        // Se chegou aqui, o código GTIN não existe, podemos inserir
        $query = "insert into produtos (codigo_interno,descricao,codigo_gtin,status) values(nextval('produtos_seq'),upper('" . $produtos->descricao . "'),'" . $produtos->codigo_gtin . "','" . $produtos->status . "') returning codigo_interno";
        $result = pg_query($conexao, $query);

        // Verificar se a consulta foi bem-sucedida
        if (!$result) {
            $error = pg_last_error($conexao);
            $response = array(
                "success" => false,
                "error" => "Erro ao inserir produto: " . $error
            );
            echo json_encode($response);
            die();
        }

        $row = pg_fetch_assoc($result);
        if ($row && $row['codigo_interno'] > 0) {
            // ✅ CORREÇÃO: Atualizar o codigo_interno do produto com o valor gerado
            $produtos->codigo_interno = $row['codigo_interno'];

            $produtos_ib = new produtos_ib();
            $produtos_ib->codigo_interno = $row['codigo_interno'];
            $produtos_ib->descricao_detalhada = $_POST['descricao_detalhada'];
            $produtos_ib->grupo = $_POST['grupo'];
            $produtos_ib->subgrupo = $_POST['subgrupo'];
            $produtos_ib->categoria = $_POST['categoria'];
            $produtos_ib->unidade = $_POST['unidade'];
            $produtos_ib->preco_venda =str_replace(',','.',$_POST['preco_venda']);
            $produtos_ib->preco_compra =str_replace(',','.',$_POST['preco_compra']);
            $produtos_ib->perc_lucro =str_replace(',','.',$_POST['perc_lucro']);
            $produtos_ib->codigo_ncm = $_POST['ncm'];
            if ($_POST['produto_balanca'] == 'true') {
                $produtos_ib->produto_balanca = "1";
            } else {
                $produtos_ib->produto_balanca = "0";
            }
            $produtos_ib->validade = $_POST['validade'];
            $produtos_ib->cfop = $_POST['cfop'];
            $produtos_ib->cest = $_POST['cest'];
            $query = "insert into produtos_ib (codigo_interno,descricao_detalhada,grupo,subgrupo,categoria,unidade,preco_venda,preco_compra,perc_lucro,codigo_ncm,produto_balanca,validade,cfop,cest) " . " values('" . $produtos_ib->codigo_interno . "',upper('" . $produtos_ib->descricao_detalhada . "'),'" . $produtos_ib->grupo . "','" . $produtos_ib->subgrupo . "','" . $produtos_ib->categoria . "',upper('" . $produtos_ib->unidade . "'),'" . $produtos_ib->preco_venda . "','" . $produtos_ib->preco_compra . "','" . $produtos_ib->perc_lucro . "','" . $produtos_ib->codigo_ncm . "','" . $produtos_ib->produto_balanca . "','" . $produtos_ib->validade . "','" . $produtos_ib->cfop . "','" . $produtos_ib->cest . "')";
            $result = pg_query($conexao, $query);
            $teste=$query;
            $produtos_ou = new produtos_ou();
            $produtos_ou->codigo_interno = $row['codigo_interno'];
            $produtos_ou->percDescA = str_replace(',','.',$_POST['perc_desc_a']);
            $produtos_ou->percDescB = str_replace(',','.',$_POST['perc_desc_b']);
            $produtos_ou->percDescC = str_replace(',','.',$_POST['perc_desc_c']);
            $produtos_ou->percDescD = str_replace(',','.',$_POST['perc_desc_d']);
            $produtos_ou->percDescE = str_replace(',','.',$_POST['perc_desc_e']);
            $produtos_ou->valDescA = str_replace(',','.',$_POST['val_desc_a']);
            $produtos_ou->valDescB = str_replace(',','.',$_POST['val_desc_b']);
            $produtos_ou->valDescC = str_replace(',','.',$_POST['val_desc_c']);
            $produtos_ou->valDescD = str_replace(',','.',$_POST['val_desc_d']);
            $produtos_ou->valDescE = str_replace(',','.',$_POST['val_desc_e']);
            $produtos_ou->qtde = str_replace(',','.',$_POST['qtde']);
            $produtos_ou->qtde_min = str_replace(',','.',$_POST['qtde_min']);
            $produtos_ou->inativo = $_POST['inativo'];
            $produtos_ou->valor_Promo = $_POST['valor_Promo'];
            $produtos_ou->fornecedor = empty($_POST['codigo_fornecedor']) ? 'NULL' : $_POST['codigo_fornecedor'];
            $produtos_ou->tamanho = $_POST['tamanho'];
            $produtos_ou->comprimento = empty($_POST['comprimento']) ? 'NULL' : str_replace(',', '.', $_POST['comprimento']);
            $produtos_ou->largura = empty($_POST['largura']) ? 'NULL' : str_replace(',', '.', $_POST['largura']);
            $produtos_ou->altura = empty($_POST['altura']) ? 'NULL' : str_replace(',', '.', $_POST['altura']);
            $produtos_ou->peso = empty($_POST['peso']) ? 'NULL' : str_replace(',', '.', $_POST['peso']);
            $produtos_ou->vencimento = $_POST['vencimento'];
            if ($_POST['descricao_personalizada'] == 'true') {
                $produtos_ou->descricao_personalizada = "1";
            } else {
                $produtos_ou->descricao_personalizada = "0";
            }
            if ($_POST['produto_producao'] == 'true') {
                $produtos_ou->produto_producao= "1";
            } else {
                $produtos_ou->produto_producao= "0";
            }
            if ($_POST['inativo'] == 'true') {
                $produtos_ou->inativo = "1";
            } else {
                $produtos_ou->inativo = "0";
            }
            //$produtos_ou->dt_cadastro = '2022-08-08'; // $_POST['dt_cadastro'];
            //$produtos_ou->dt_ultima_alteracao = '2022-08-08'; // $_POST['dt_ultima_alteracao'];
            $produtos_ou->preco_gelado = str_replace(',','.',$_POST['valorGelado']);
            $produtos_ou->desc_etiqueta = $_POST['desc_etiqueta'];
            if (strlen($produtos_ou->vencimento) < 2) {
                $produtos_ou->vencimento = "2099-01-01";
            }
            $query = "insert into produtos_ou (codigo_interno,perc_desc_a,perc_desc_b,perc_desc_c,perc_desc_d,perc_desc_e,val_desc_a,val_desc_b,val_desc_c,val_desc_d,val_desc_e, " .
            "qtde,qtde_min,inativo,codfor,tamanho,vencimento,descricao_personalizada,dt_cadastro,preco_gelado,desc_etiqueta,producao,comprimento,largura,altura,peso) " .
            " values('" . $produtos_ou->codigo_interno . "','" . $produtos_ou->percDescA . "','" . $produtos_ou->percDescB . "','" . $produtos_ou->percDescC . "','" . $produtos_ou->percDescD . "','" . $produtos_ou->percDescE . "','" . $produtos_ou->valDescA . "','" . $produtos_ou->valDescB . "','" . $produtos_ou->valDescC . "','" . $produtos_ou->valDescD . "','" . $produtos_ou->valDescE . "','" . $produtos_ou->qtde . "','" . $produtos_ou->qtde_min . "','" . $produtos_ou->inativo . "'," . ($produtos_ou->fornecedor === 'NULL' ? 'NULL' : "'".$produtos_ou->fornecedor."'") . ",'" . $produtos_ou->tamanho . "','" . $produtos_ou->vencimento . "','" . $produtos_ou->descricao_personalizada . "',current_date ,'" . $produtos_ou->preco_gelado . "','" . $produtos_ou->desc_etiqueta . "','".$produtos_ou->produto_producao."'," . ($produtos_ou->comprimento === 'NULL' ? 'NULL' : "'".$produtos_ou->comprimento."'") . "," . ($produtos_ou->largura === 'NULL' ? 'NULL' : "'".$produtos_ou->largura."'") . "," . ($produtos_ou->altura === 'NULL' ? 'NULL' : "'".$produtos_ou->altura."'") . "," . ($produtos_ou->peso === 'NULL' ? 'NULL' : "'".$produtos_ou->peso."'") . ")";
            gravar($query);
            $result = pg_query($conexao, $query);

            // Verificar se a inserção em produtos_ou foi bem-sucedida
            if (!$result) {
                $error = pg_last_error($conexao);
                error_log("ERRO INSERT produtos_ou: " . $error);
                error_log("QUERY produtos_ou: " . $query);
                $response = array(
                    "success" => false,
                    "error" => "Erro ao inserir em produtos_ou: " . $error,
                    "query" => $query
                );
                echo json_encode($response);
                die();
            }
            $produtos_tb = new produtos_tb();
            $produtos_tb->codigo_interno = $row['codigo_interno'];
            $produtos_tb->ipi_reducao_bc = str_replace(',','.',$_POST['ipi_reducao_bc']);
            $produtos_tb->aliquota_ipi = str_replace(',','.',$_POST['aliquota_ipi']);
            $produtos_tb->ipi_reducao_bc_st = str_replace(',','.',$_POST['ipi_reducao_bc_st']);
            $produtos_tb->aliquota_ipi_st = str_replace(',','.',$_POST['aliquota_ipi_st']);
            $produtos_tb->pis_reducao_bc = str_replace(',','.',$_POST['pis_reducao_bc']);
            $produtos_tb->aliquota_pis = str_replace(',','.',$_POST['aliquota_pis']);
            $produtos_tb->pis_reducao_bc_st = str_replace(',','.',$_POST['pis_reducao_bc_st']);
            $produtos_tb->aliquota_pis_st = str_replace(',','.',$_POST['aliquota_pis_st']);
            $produtos_tb->cofins_reducao_bc = str_replace(',','.',$_POST['cofins_reducao_bc']);
            $produtos_tb->aliquota_cofins = str_replace(',','.',$_POST['aliquota_cofins']);
            $produtos_tb->cofins_reducao_bc_st = str_replace(',','.',$_POST['cofins_reducao_bc_st']);
            $produtos_tb->aliquota_cofins_st = str_replace(',','.',$_POST['aliquota_cofins_st']);
            $produtos_tb->situacao_tributaria = $_POST['situacao_tributaria'];
            $produtos_tb->origem = '0';
            $produtos_tb->aliquota_calculo_credito = str_replace(',','.',$_POST['aliquota_calculo_credito']);
            $produtos_tb->modalidade_deter_bc_icms = $_POST['mod_deter_bc_icms'];
            $produtos_tb->aliquota_icms = str_replace(',','.',$_POST['perc_icms']);
            $produtos_tb->icms_reducao_bc=str_replace(',','.', $_POST['perc_redu_icms']);
            $produtos_tb->modalidade_deter_bc_icms_st = $_POST['mod_deter_bc_icms_st'];
            $produtos_tb->icms_reducao_bc_st = str_replace(',','.',$_POST['perc_redu_icms_st']);
            $produtos_tb->perc_mva_icms_st = str_replace(',','.',$_POST['perc_mv_adic_icms_st']);
            $produtos_tb->aliquota_icms_st =str_replace(',','.',$_POST['aliq_icms_st']);
            $produtos_tb->ipi_cst = $_POST['cst_ipi'];
            $produtos_tb->calculo_ipi = $_POST['calculo_ipi'];
            $produtos_tb->cst_pis = $_POST['cst_pis'];
            $produtos_tb->calculo_pis = $_POST['calculo_pis'];
            $produtos_tb->cst_cofins = $_POST['cst_cofins'];
            $produtos_tb->calculo_cofins = $_POST['calculo_cofins'];
            $produtos_tb->aliquota_fcp = str_replace(',','.',$_POST['aliq_fcp']);
            $produtos_tb->aliquota_fcp_st = str_replace(',','.',$_POST['aliq_fcp_st']);
            $produtos_tb->perc_dif = str_replace(',','.',$_POST['perc_dif']);
            $query = "insert into produtos_tb (codigo_interno,
                    ipi_reducao_bc,
                    aliquota_ipi,
                    ipi_reducao_bc_st,
                    aliquota_ipi_st,
                    pis_reducao_bc,
                    aliquita_pis,
                    pis_reducao_bc_st,
                    aliquota_pis_st,
                    cofins_reducao_bc,
                    aliquota_cofins,
                    cofins_reducao_bc_st,
                    aliquota_cofins_st,
                    situacao_tributaria,
                    origem,
                    aliquota_calculo_credito,
                    modalidade_deter_bc_icms,
                    aliquota_icms,
                    icms_reducao_bc,
                    modalidade_deter_bc_icms_st,
                    icms_reducao_bc_st,
                    perc_mva_icms_st,
                    aliquota_icms_st,
                    cst_ipi,
                    calculo_ipi,
                    cst_pis,
                    calculo_pis,
                    cst_cofins,
                    calculo_cofins,
                    aliquota_fcp,aliquota_fcp_st,perc_dif)
             values('" . $produtos_tb->codigo_interno . "','" . $produtos_tb->ipi_reducao_bc . "','" . $produtos_tb->aliquota_ipi . "','" . $produtos_tb->ipi_reducao_bc_st . "','" . $produtos_tb->aliquota_ipi_st . "','" . $produtos_tb->pis_reducao_bc . "','" . $produtos_tb->aliquota_pis . "','" . $produtos_tb->pis_reducao_bc_st . "','" . $produtos_tb->aliquota_pis_st . "','" . $produtos_tb->cofins_reducao_bc . "','" . $produtos_tb->aliquota_cofins . "','" . $produtos_tb->cofins_reducao_bc_st . "','" . $produtos_tb->aliquota_cofins_st . "','" . $produtos_tb->situacao_tributaria . "','" . $produtos_tb->origem . "','" . $produtos_tb->aliquota_calculo_credito . "','" . $produtos_tb->modalidade_deter_bc_icms . "','" . $produtos_tb->aliquota_icms . "','" . $produtos_tb->icms_reducao_bc . "','" . $produtos_tb->modalidade_deter_bc_icms_st . "','" . $produtos_tb->icms_reducao_bc_st . "','" . $produtos_tb->perc_mva_icms_st . "','" . $produtos_tb->aliquota_icms_st . "','" . $produtos_tb->ipi_cst . "','" . $produtos_tb->calculo_ipi . "','" . $produtos_tb->cst_pis . "','" . $produtos_tb->calculo_pis . "','" . $produtos_tb->cst_cofins . "','" . $produtos_tb->calculo_cofins . "','" . $produtos_tb->aliquota_fcp . "','" . $produtos_tb->aliquota_fcp_st . "','" . $produtos_tb->perc_dif . "')";
            $result = pg_query($conexao, $query);
            $teste=$query;
        }
    }else
    {//UPDATE
        //PRODUTOS
        $query = "update produtos set descricao=upper('".$produtos->descricao. "'), status='".$produtos->status."' where codigo_interno=".$produtos->codigo_interno;
        $result = pg_query($conexao, $query);
        //PRODUTOS IB
        $produtos_ib = new produtos_ib();
        $produtos_ib->descricao_detalhada = $_POST['descricao_detalhada'];
        $produtos_ib->grupo = $_POST['grupo'];
        $produtos_ib->subgrupo = $_POST['subgrupo'];
        $produtos_ib->categoria = $_POST['categoria'];
        $produtos_ib->unidade = $_POST['unidade'];
        $produtos_ib->preco_venda =str_replace(',','.',$_POST['preco_venda']);
        $produtos_ib->preco_compra =str_replace(',','.',$_POST['preco_compra']);
        $produtos_ib->perc_lucro =str_replace(',','.',$_POST['perc_lucro']);
        $produtos_ib->codigo_ncm = $_POST['ncm'];
        if ($_POST['produto_balanca'] == 'true') {
            $produtos_ib->produto_balanca = "1";
        } else {
            $produtos_ib->produto_balanca = "0";
        }
        $produtos_ib->validade = $_POST['validade'];
        $produtos_ib->cfop = $_POST['cfop'];
        $produtos_ib->cest = $_POST['cest'];
        $query = "update produtos_ib set ".
        " descricao_detalhada=upper('".$produtos_ib->descricao_detalhada."'),".
        " grupo='".$produtos_ib->grupo."',".
        " subgrupo='".$produtos_ib->subgrupo."',".
        " categoria='".$produtos_ib->categoria."',".
        " unidade='".$produtos_ib->unidade."',".
        " preco_venda='".$produtos_ib->preco_venda."',".
        " preco_compra='".$produtos_ib->preco_compra."',".
        " perc_lucro='".$produtos_ib->perc_lucro."',".
        " codigo_ncm='".$produtos_ib->codigo_ncm."',".
        " produto_balanca='".$produtos_ib->produto_balanca."',".
        " validade='".$produtos_ib->validade."',".
        " cfop='".$produtos_ib->cfop."',".
        " cest='".$produtos_ib->cest."'".
        " where codigo_interno=".$produtos->codigo_interno;
        $result = pg_query($conexao, $query);
        //PRODUTOS OU
        $produtos_ou = new produtos_ou();
        $produtos_ou->percDescA = str_replace(',','.',$_POST['perc_desc_a']);
        $produtos_ou->percDescB = str_replace(',','.',$_POST['perc_desc_b']);
        $produtos_ou->percDescC = str_replace(',','.',$_POST['perc_desc_c']);
        $produtos_ou->percDescD = str_replace(',','.',$_POST['perc_desc_d']);
        $produtos_ou->percDescE = str_replace(',','.',$_POST['perc_desc_e']);
        $produtos_ou->valDescA = str_replace(',','.',$_POST['val_desc_a']);
        $produtos_ou->valDescB = str_replace(',','.',$_POST['val_desc_b']);
        $produtos_ou->valDescC = str_replace(',','.',$_POST['val_desc_c']);
        $produtos_ou->valDescD = str_replace(',','.',$_POST['val_desc_d']);
        $produtos_ou->valDescE = str_replace(',','.',$_POST['val_desc_e']);
        $produtos_ou->qtde = str_replace(',','.',$_POST['qtde']);
        $produtos_ou->qtde_min = str_replace(',','.',$_POST['qtde_min']);
        $produtos_ou->inativo = $_POST['inativo'];
        $produtos_ou->valor_Promo = $_POST['valor_Promo'];
        $produtos_ou->fornecedor = empty($_POST['codigo_fornecedor']) ? 'NULL' : $_POST['codigo_fornecedor'];
        $produtos_ou->tamanho = $_POST['tamanho'];
        $produtos_ou->comprimento = empty($_POST['comprimento']) ? 'NULL' : str_replace(',', '.', $_POST['comprimento']);
        $produtos_ou->largura = empty($_POST['largura']) ? 'NULL' : str_replace(',', '.', $_POST['largura']);
        $produtos_ou->altura = empty($_POST['altura']) ? 'NULL' : str_replace(',', '.', $_POST['altura']);
        $produtos_ou->peso = empty($_POST['peso']) ? 'NULL' : str_replace(',', '.', $_POST['peso']);
        $produtos_ou->vencimento = $_POST['vencimento'];
        if ($_POST['descricao_personalizada'] == 'true') {
            $produtos_ou->descricao_personalizada = "1";
        } else {
            $produtos_ou->descricao_personalizada = "0";
        }
        if ($_POST['produto_producao'] == 'true') {
            $produtos_ou->produto_producao= "1";
        } else {
            $produtos_ou->produto_producao= "0";
        }
        if ($_POST['inativo'] == 'true') {
            $produtos_ou->inativo = "1";
        } else {
            $produtos_ou->inativo = "0";
        }
        $produtos_ou->preco_gelado = str_replace(',','.',$_POST['valorGelado']);
        $produtos_ou->desc_etiqueta = $_POST['desc_etiqueta'];
        if (strlen($produtos_ou->vencimento) < 2) {
            $produtos_ou->vencimento = "2099-01-01";
        }
        $query = "update produtos_ou  set ".
            " perc_desc_a='".$produtos_ou->percDescA."',".
            " perc_desc_b='".$produtos_ou->percDescB."',".
            " perc_desc_c='".$produtos_ou->percDescC."',".
            " perc_desc_d='".$produtos_ou->percDescD."',".
            " perc_desc_e='".$produtos_ou->percDescE."',".
            " val_desc_a='".$produtos_ou->valDescA."',".
            " val_desc_b='".$produtos_ou->valDescB."',".
            " val_desc_c='".$produtos_ou->valDescC."',".
            " val_desc_d='".$produtos_ou->valDescD."',".
            " val_desc_e='".$produtos_ou->valDescE."',".
            " qtde='".$produtos_ou->qtde."',".
            " qtde_min='".$produtos_ou->qtde_min."',".
            " inativo='".$produtos_ou->inativo."',".
            " codfor=".($produtos_ou->fornecedor === 'NULL' ? 'NULL' : "'".$produtos_ou->fornecedor."'").",".
            " tamanho='".$produtos_ou->tamanho."',".
            " vencimento='".$produtos_ou->vencimento."',".
            " descricao_personalizada='".$produtos_ou->descricao_personalizada."',".
            " dt_ultima_alteracao=current_date, ".
            " preco_gelado='".$produtos_ou->preco_gelado."',".
            " desc_etiqueta='".$produtos_ou->desc_etiqueta."',".
            " producao='".$produtos_ou->produto_producao."',".
            " comprimento=".($produtos_ou->comprimento === 'NULL' ? 'NULL' : "'".$produtos_ou->comprimento."'").",".
            " largura=".($produtos_ou->largura === 'NULL' ? 'NULL' : "'".$produtos_ou->largura."'").",".
            " altura=".($produtos_ou->altura === 'NULL' ? 'NULL' : "'".$produtos_ou->altura."'").",".
            " peso=".($produtos_ou->peso === 'NULL' ? 'NULL' : "'".$produtos_ou->peso."'").
            " where codigo_interno=".$produtos->codigo_interno;
        $result = pg_query($conexao, $query);

        // Verificar se a atualização em produtos_ou foi bem-sucedida
        if (!$result) {
            $error = pg_last_error($conexao);
            error_log("ERRO UPDATE produtos_ou: " . $error);
            error_log("QUERY produtos_ou: " . $query);
            $response = array(
                "success" => false,
                "error" => "Erro ao atualizar produtos_ou: " . $error,
                "query" => $query
            );
            echo json_encode($response);
            die();
        }

        // Verificar se algum registro foi afetado
        $affected_rows = pg_affected_rows($result);
        if ($affected_rows == 0) {
            error_log("AVISO: Nenhuma linha afetada no UPDATE produtos_ou para codigo_interno: " . $produtos->codigo_interno);
        }
        //PRODUTOS_TB //back
        $produtos_tb = new produtos_tb();
        $produtos_tb->ipi_reducao_bc = str_replace(',','.',$_POST['ipi_reducao_bc']);
        $produtos_tb->aliquota_ipi = str_replace(',','.',$_POST['aliquota_ipi']);
        $produtos_tb->ipi_reducao_bc_st = str_replace(',','.',$_POST['ipi_reducao_bc_st']);
        $produtos_tb->aliquota_ipi_st = str_replace(',','.',$_POST['aliquota_ipi_st']);
        $produtos_tb->pis_reducao_bc = str_replace(',','.',$_POST['pis_reducao_bc']);
        $produtos_tb->aliquota_pis = str_replace(',','.',$_POST['aliquota_pis']);
        $produtos_tb->pis_reducao_bc_st = str_replace(',','.',$_POST['pis_reducao_bc_st']);
        $produtos_tb->aliquota_pis_st = str_replace(',','.',$_POST['aliquota_pis_st']);
        $produtos_tb->cofins_reducao_bc = str_replace(',','.',$_POST['cofins_reducao_bc']);
        $produtos_tb->aliquota_cofins = str_replace(',','.',$_POST['aliquota_cofins']);
        $produtos_tb->cofins_reducao_bc_st = str_replace(',','.',$_POST['cofins_reducao_bc_st']);
        $produtos_tb->aliquota_cofins_st = str_replace(',','.',$_POST['aliquota_cofins_st']);
        $produtos_tb->situacao_tributaria = $_POST['situacao_tributaria'];
        $produtos_tb->origem = '0';
        $produtos_tb->aliquota_calculo_credito = str_replace(',','.',$_POST['aliquota_calculo_credito']);
        $produtos_tb->modalidade_deter_bc_icms = $_POST['mod_deter_bc_icms'];
        $produtos_tb->aliquota_icms =str_replace(',','.',$_POST['perc_icms']);
        $produtos_tb->icms_reducao_bc = str_replace(',','.',$_POST['perc_redu_icms']);
        $produtos_tb->modalidade_deter_bc_icms_st = $_POST['mod_deter_bc_icms_st'];
        $produtos_tb->icms_reducao_bc_st = str_replace(',','.',$_POST['perc_redu_icms_st']);
        $produtos_tb->perc_mva_icms_st = str_replace(',','.',$_POST['perc_mv_adic_icms_st']);
        $produtos_tb->aliquota_icms_st = str_replace(',','.',$_POST['aliq_icms_st']);
        $produtos_tb->ipi_cst = $_POST['cst_ipi'];
        $produtos_tb->calculo_ipi = $_POST['calculo_ipi'];
        $produtos_tb->cst_pis = $_POST['cst_pis'];
        $produtos_tb->calculo_pis = $_POST['calculo_pis'];
        $produtos_tb->cst_cofins = $_POST['cst_cofins'];
        $produtos_tb->calculo_cofins = $_POST['calculo_cofins'];
        $produtos_tb->aliquota_fcp = str_replace(',','.',$_POST['aliq_fcp']);
        $produtos_tb->aliquota_fcp_st = str_replace(',','.',$_POST['aliq_fcp_st']);
        $produtos_tb->perc_dif = str_replace(',','.',$_POST['perc_dif']);
        $query = "update produtos_tb set ipi_reducao_bc='".$produtos_tb->ipi_reducao_bc."',".
                    "aliquota_ipi='$produtos_tb->aliquota_ipi', ipi_reducao_bc_st='$produtos_tb->ipi_reducao_bc_st',".
                    "aliquota_ipi_st='$produtos_tb->aliquota_ipi_st',pis_reducao_bc='$produtos_tb->pis_reducao_bc',".
                    "aliquita_pis='$produtos_tb->aliquota_pis',pis_reducao_bc_st='$produtos_tb->pis_reducao_bc_st',".
                    "aliquota_pis_st='$produtos_tb->aliquota_pis_st',cofins_reducao_bc='$produtos_tb->cofins_reducao_bc',".
                    "aliquota_cofins='$produtos_tb->aliquota_cofins',cofins_reducao_bc_st='$produtos_tb->cofins_reducao_bc_st',".
                    "aliquota_cofins_st='$produtos_tb->aliquota_cofins_st',situacao_tributaria='$produtos_tb->situacao_tributaria',".
                    "origem='$produtos_tb->origem',aliquota_calculo_credito='$produtos_tb->aliquota_calculo_credito',".
                    "modalidade_deter_bc_icms='$produtos_tb->modalidade_deter_bc_icms',aliquota_icms='$produtos_tb->aliquota_icms',".
                    "icms_reducao_bc='$produtos_tb->icms_reducao_bc',modalidade_deter_bc_icms_st='$produtos_tb->modalidade_deter_bc_icms_st',".
                    "icms_reducao_bc_st='$produtos_tb->icms_reducao_bc_st',perc_mva_icms_st='$produtos_tb->perc_mva_icms_st',".
                    "aliquota_icms_st='$produtos_tb->aliquota_icms_st',cst_ipi='$produtos_tb->ipi_cst',".
                    "calculo_ipi='$produtos_tb->calculo_ipi',cst_pis='$produtos_tb->cst_pis',".
                    "calculo_pis='$produtos_tb->calculo_pis',cst_cofins='$produtos_tb->cst_cofins',".
                    "calculo_cofins='$produtos_tb->calculo_cofins',aliquota_fcp='$produtos_tb->aliquota_fcp',".
                    "aliquota_fcp_st='$produtos_tb->aliquota_fcp_st',perc_dif='$produtos_tb->perc_dif' where codigo_interno=$produtos->codigo_interno";
        $result = pg_query($conexao, $query);
    }
    // Retornar uma resposta de sucesso com o código interno
    $response = array(
        "success" => true,
        "codigo_interno" => $produtos->codigo_interno,
        "message" => "Produto salvo com sucesso!"
    );
    echo json_encode($response);
    die();
}
if ($request == 'adicionar_item_grade') {
    $codigo_interno = $_POST['codigo_interno'];
    $codigo_gtin = $_POST['codigo_gtin'];
    $descricao = $_POST['descricao'];
    $variacao = $_POST['variacao'];
    $caracteristica = $_POST['caracteristica'];

    // Log dos dados recebidos
    error_log("DEBUG adicionar_item_grade: codigo_interno=$codigo_interno, codigo_gtin=$codigo_gtin, descricao=$descricao, variacao=$variacao, caracteristica=$caracteristica");

    // Validação do código GTIN
    if($codigo_gtin==0 or $codigo_gtin==''){
        error_log("ERRO adicionar_item_grade: codigo_gtin vazio ou zero");
        echo json_encode([
            'success' => false,
            'error' => 'Código GTIN é obrigatório'
        ]);
        die();
    }

    // Verificar se já existe item com mesmo GTIN
    $check_query = "SELECT codigo FROM produtos_gd WHERE codigo_gtin = '$codigo_gtin'";
    $check_result = pg_query($conexao, $check_query);

    if (!$check_result) {
        error_log("ERRO adicionar_item_grade: Erro ao verificar GTIN existente: " . pg_last_error($conexao));
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao verificar GTIN: ' . pg_last_error($conexao)
        ]);
        die();
    }

    if (pg_num_rows($check_result) > 0) {
        error_log("ERRO adicionar_item_grade: GTIN $codigo_gtin já existe na grade");
        echo json_encode([
            'success' => false,
            'error' => 'GTIN já existe na grade'
        ]);
        die();
    }

    // Obter próximo código disponível
    $next_codigo_query = "SELECT COALESCE(MAX(codigo), 0) + 1 as next_codigo FROM produtos_gd";
    $next_codigo_result = pg_query($conexao, $next_codigo_query);

    if (!$next_codigo_result) {
        error_log("ERRO adicionar_item_grade: Erro ao obter próximo código: " . pg_last_error($conexao));
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao obter próximo código: ' . pg_last_error($conexao)
        ]);
        die();
    }

    $next_codigo_row = pg_fetch_assoc($next_codigo_result);
    $next_codigo = $next_codigo_row['next_codigo'];

    error_log("DEBUG adicionar_item_grade: Próximo código: $next_codigo");

    // Construir query de inserção
    $query = "INSERT INTO produtos_gd (codigo, codigo_gtin, nome, caracteristica, variacao, codigo_interno)
              VALUES ($next_codigo, '$codigo_gtin', '$descricao', '$caracteristica', '$variacao', $codigo_interno) RETURNING *";

    error_log("DEBUG adicionar_item_grade: Executando query: $query");

    // Executar inserção
    $result = pg_query($conexao, $query);

    if (!$result) {
        $error = pg_last_error($conexao);
        error_log("ERRO adicionar_item_grade: Falha na inserção: $error");
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao inserir na grade: ' . $error,
            'query' => $query
        ]);
        die();
    }

    // Verificar se alguma linha foi afetada
    $affected_rows = pg_affected_rows($result);
    error_log("DEBUG adicionar_item_grade: Linhas afetadas: $affected_rows");

    if ($affected_rows == 0) {
        error_log("AVISO adicionar_item_grade: Nenhuma linha foi inserida");
        echo json_encode([
            'success' => false,
            'error' => 'Nenhuma linha foi inserida na grade'
        ]);
        die();
    }

    // Sucesso
    $new_variant = pg_fetch_assoc($result);
    error_log("SUCESSO adicionar_item_grade: Item adicionado com sucesso - GTIN: $codigo_gtin");
    echo json_encode([
        'success' => true,
        'message' => 'Item adicionado à grade com sucesso',
        'variant' => $new_variant
    ]);
    die();
}

if ($request == 'selecionar_itens_grade') {
    $response = "0";
    $codigo_interno = $_POST['codigo_interno'];

    $query = "select * from produtos_gd where codigo_interno=" . $codigo_interno." order by codigo" ;
    $result = pg_query($conexao, $query);

    $response = array();
    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo" => $row['codigo'],
            "codigo_gtin" => $row['codigo_gtin'],
            "codigo_interno" => $row['codigo_interno'],
            "descricao" => $row['nome'],
            "variacao" => $row['variacao'],
            "caracteristica" => $row['caracteristica']
        );
    }
    echo json_encode($response);
    die();
}
if ($request == 'carregar_grade_completa') {
    $codigo_interno = $_POST['codigo_interno'];

    if (!$codigo_interno) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno não informado'
        ]);
        die();
    }

    try {
        $query = "SELECT 
                        g.codigo,
                        g.codigo_gtin,
                        g.nome as descricao,
                        g.caracteristica,
                        g.variacao,
                        pib.preco_venda as preco,
                        pou.qtde as estoque,
                        pou.peso,
                        pou.altura,
                        pou.largura,
                        pou.comprimento
                  FROM produtos_gd g
                  LEFT JOIN produtos p ON g.codigo_gtin = p.codigo_gtin
                  LEFT JOIN produtos_ib pib ON p.codigo_interno = pib.codigo_interno
                  LEFT JOIN produtos_ou pou ON p.codigo_interno = pou.codigo_interno
                  WHERE g.codigo_interno = $codigo_interno
                  ORDER BY g.codigo";

        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao carregar grade: " . pg_last_error($conexao));
        }

        $variants = [];
        while ($row = pg_fetch_assoc($result)) {
            $variants[] = $row;
        }

        echo json_encode([
            'success' => true,
            'variants' => $variants
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

if ($request == 'carregar_grade_completa') {
    header('Content-Type: application/json');
    $codigo_interno = $_POST['codigo_interno'];

    if (!$codigo_interno) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno não informado'
        ]);
        die();
    }

    try {
        $query = "SELECT 
                        g.codigo,
                        g.codigo_gtin,
                        g.nome as descricao,
                        g.caracteristica,
                        g.variacao,
                        pib.preco_venda as preco,
                        pou.qtde as estoque,
                        pou.peso,
                        pou.altura,
                        pou.largura,
                        pou.comprimento
                  FROM produtos_gd g
                  LEFT JOIN produtos p ON g.codigo_gtin = p.codigo_gtin
                  LEFT JOIN produtos_ib pib ON p.codigo_interno = pib.codigo_interno
                  LEFT JOIN produtos_ou pou ON p.codigo_interno = pou.codigo_interno
                  WHERE g.codigo_interno = $codigo_interno
                  ORDER BY g.codigo";

        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao carregar grade: " . pg_last_error($conexao));
        }

        $variants = [];
        while ($row = pg_fetch_assoc($result)) {
            $variants[] = $row;
        }

        echo json_encode([
            'success' => true,
            'variants' => $variants
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

if ($request == 'deleta_grade') {
    $response = "0";
    $codigo = $_POST['codigo'];
    $query = "delete from produtos_gd where codigo=" . $codigo;
    $result = pg_query($conexao, $query);
    echo json_encode("OK");
    die();
}
function gravar($texto){
    $arquivo = "logProdutos.txt";
    $fp = fopen($arquivo, "a+");
    fwrite($fp, $texto);
    fclose($fp);
}
if ($request == 'obterDadosProduto') {
    $codigo_interno = $_POST['codigo_interno'];

    $query = "SELECT p.codigo_interno, p.codigo_gtin, p.descricao, pb.descricao_detalhada,
              pb.preco_venda, po.peso, po.altura, po.largura, po.comprimento
              FROM produtos p
              LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
              LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
              WHERE p.codigo_interno = " . $codigo_interno;

    $result = pg_query($conexao, $query);
    $response = array();

    while ($row = pg_fetch_assoc($result)) {
        $response[] = array(
            "codigo_interno" => $row['codigo_interno'],
            "codigo_gtin" => $row['codigo_gtin'],
            "descricao" => $row['descricao'],
            "descricao_detalhada" => $row['descricao_detalhada'],
            "preco_venda" => $row['preco_venda'],
            "peso" => $row['peso'],
            "altura" => $row['altura'],
            "largura" => $row['largura'],
            "comprimento" => $row['comprimento']
        );
    }

    echo json_encode($response);
    die();
}

// Endpoint para sincronizar o status dos produtos com a Nuvemshop
if ($request == 'sincronizarStatusProdutos') {
    try {
        // Limpar qualquer saída anterior
        ob_clean();

        // Incluir arquivo de sincronização
        require_once 'produtos_ajax_sincronizacao.php';

        // Chamar a função de sincronização com novo fluxo
        $resultado = sincronizarStatusProdutos(array(), $conexao);

        // Definir header JSON
        header('Content-Type: application/json');

        // Retornar o resultado
        echo json_encode($resultado);
        exit;

    } catch (Exception $e) {
        // Definir header JSON
        header('Content-Type: application/json');

        // Retornar erro
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Endpoint de teste para debug da sincronização
if ($request == 'testeSincronizacao') {
    try {
        ob_clean();
        header('Content-Type: application/json');

        error_log("🧪 TEST: Iniciando teste de sincronização");

        // Teste básico + verificação de caracteres problemáticos
        $teste_resultado = [
            'success' => true,
            'message' => 'Teste de sincronização funcionando',
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'php_version' => PHP_VERSION,
            'conexao_status' => $conexao ? 'Conectado' : 'Desconectado'
        ];

        // Verificar se há produtos com caracteres problemáticos
        if ($conexao) {
            $query_problemas = "SELECT codigo_gtin, descricao FROM produtos WHERE status IN ('ENS', 'ENSVI', 'ENSV') LIMIT 5";
            $result_problemas = pg_query($conexao, $query_problemas);

            $produtos_exemplo = [];
            $problemas_encontrados = [];

            if ($result_problemas) {
                while ($row = pg_fetch_assoc($result_problemas)) {
                    $descricao = $row['descricao'];
                    $codigo = $row['codigo_gtin'];

                    // Verificar se há caracteres problemáticos
                    $descricao_limpa = mb_convert_encoding($descricao, 'UTF-8', 'UTF-8');
                    $tem_caracteres_controle = preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $descricao);

                    $produto_info = [
                        'codigo' => $codigo,
                        'descricao_original' => $descricao,
                        'descricao_limpa' => $descricao_limpa,
                        'tem_problemas' => $tem_caracteres_controle || ($descricao !== $descricao_limpa),
                        'tamanho_original' => strlen($descricao),
                        'tamanho_limpo' => strlen($descricao_limpa)
                    ];

                    $produtos_exemplo[] = $produto_info;

                    if ($produto_info['tem_problemas']) {
                        $problemas_encontrados[] = $codigo;
                    }
                }
            }

            $teste_resultado['produtos_exemplo'] = $produtos_exemplo;
            $teste_resultado['produtos_com_problemas'] = $problemas_encontrados;
            $teste_resultado['total_problemas'] = count($problemas_encontrados);
        }

        // Testar se o arquivo de sincronização pode ser incluído
        if (file_exists('produtos_ajax_sincronizacao.php')) {
            $teste_resultado['arquivo_sincronizacao'] = 'Encontrado';
            require_once 'produtos_ajax_sincronizacao.php';

            if (function_exists('sincronizarStatusEEstoque')) {
                $teste_resultado['funcao_sincronizacao'] = 'Encontrada';
            } else {
                $teste_resultado['funcao_sincronizacao'] = 'Não encontrada';
            }
        } else {
            $teste_resultado['arquivo_sincronizacao'] = 'Não encontrado';
        }

        echo json_encode($teste_resultado);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Erro no teste: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Endpoint para sincronizar status E estoque dos produtos com a Nuvemshop
if ($request == 'sincronizarStatusEEstoque') {
    try {
        // Configurar timeouts para operação de longa duração
        set_time_limit(300); // 5 minutos
        ini_set('memory_limit', '512M'); // Aumentar limite de memória

        // Limpar qualquer saída anterior e iniciar buffer
        ob_clean();
        ob_start();

        // Log do início da sincronização
        error_log("🚀 SYNC START: Iniciando sincronização completa de status e estoque");
        error_log("⏰ SYNC: Timeout configurado para 300 segundos");
        error_log("💾 SYNC: Limite de memória configurado para 512M");

        // Incluir arquivo de sincronização
        require_once 'produtos_ajax_sincronizacao.php';

        // Verificar se a função existe
        if (!function_exists('sincronizarStatusEEstoque')) {
            throw new Exception('Função sincronizarStatusEEstoque não encontrada');
        }

        // Teste simples primeiro
        error_log("🧪 SYNC TEST: Testando função de sincronização");

        // Teste básico da função antes de chamar
        if (!$conexao) {
            throw new Exception('Conexão com banco de dados não estabelecida');
        }

        // Chamar a função de sincronização completa
        error_log("🔄 SYNC: Chamando função sincronizarStatusEEstoque");
        $resultado = sincronizarStatusEEstoque($conexao);
        error_log("✅ SYNC: Função retornou resultado");

        // Verificar se o resultado é válido
        if (!is_array($resultado)) {
            throw new Exception('Resultado da sincronização inválido: ' . gettype($resultado));
        }

        // Verificar se tem as chaves necessárias
        $required_keys = ['success'];
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $resultado)) {
                throw new Exception("Chave obrigatória '$key' não encontrada no resultado");
            }
        }

        // Limpar qualquer output indevido
        ob_clean();

        // Log do resultado da sincronização
        error_log("✅ SYNC COMPLETE: Sincronização finalizada");
        error_log("📊 SYNC RESULT: " . json_encode([
            'success' => $resultado['success'],
            'status_atualizados' => $resultado['status_atualizados'] ?? 0,
            'estoque_atualizados' => $resultado['estoque_atualizados'] ?? 0,
            'erros' => $resultado['erros'] ?? 0
        ]));

        // Definir header JSON
        header('Content-Type: application/json');

        // Limpar caracteres UTF-8 inválidos antes de codificar JSON
        $resultado_limpo = limparCaracteresUTF8($resultado);

        // Garantir que o JSON seja válido
        $json_output = json_encode($resultado_limpo, JSON_UNESCAPED_UNICODE);
        if ($json_output === false) {
            // Se ainda falhar, tentar com dados básicos
            $resultado_basico = [
                'success' => $resultado['success'] ?? false,
                'status_atualizados' => $resultado['status_atualizados'] ?? 0,
                'estoque_atualizados' => $resultado['estoque_atualizados'] ?? 0,
                'erros' => $resultado['erros'] ?? 0,
                'message' => 'Sincronização concluída (logs removidos devido a problemas de encoding)',
                'encoding_error' => json_last_error_msg()
            ];
            $json_output = json_encode($resultado_basico);

            if ($json_output === false) {
                throw new Exception('Erro crítico ao codificar JSON: ' . json_last_error_msg());
            }
        }

        // Retornar o resultado
        echo $json_output;
        exit;

    } catch (Exception $e) {
        // Log do erro
        error_log("❌ SYNC ERROR: Exception capturada");
        error_log("📄 SYNC ERROR: " . $e->getMessage());
        error_log("📍 SYNC ERROR: Arquivo: " . $e->getFile() . ", Linha: " . $e->getLine());
        error_log("🔍 SYNC ERROR: Stack trace: " . $e->getTraceAsString());

        // Limpar qualquer saída anterior
        ob_clean();

        // Definir header JSON
        header('Content-Type: application/json');

        // Retornar erro
        $error_response = [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'type' => 'Exception',
            'debug_info' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'time_limit' => ini_get('max_execution_time')
            ]
        ];

        echo json_encode($error_response);
        exit;
    } catch (Error $e) {
        // Log do erro fatal
        error_log("💀 SYNC FATAL ERROR: Error capturado");
        error_log("📄 SYNC FATAL ERROR: " . $e->getMessage());
        error_log("📍 SYNC FATAL ERROR: Arquivo: " . $e->getFile() . ", Linha: " . $e->getLine());
        error_log("🔍 SYNC FATAL ERROR: Stack trace: " . $e->getTraceAsString());

        // Limpar qualquer saída anterior
        ob_clean();

        // Definir header JSON
        header('Content-Type: application/json');

        // Retornar erro fatal
        $error_response = [
            'success' => false,
            'error' => 'Erro fatal: ' . $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'type' => 'Fatal Error',
            'debug_info' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'time_limit' => ini_get('max_execution_time')
            ]
        ];

        echo json_encode($error_response);
        exit;
    }
}

// Endpoint para atualizar o status de e-commerce de um produto
if ($request == 'atualizarStatusEcommerce') {
    $codigo_interno = $_POST['codigo_interno'] ?? null;
    $codigo_gtin = $_POST['codigo_gtin'] ?? null;
    $status = $_POST['status'];

    // Log para debug
    error_log("DEBUG atualizarStatusEcommerce: codigo_interno=$codigo_interno, codigo_gtin=$codigo_gtin, status=$status");

    // Verificar se pelo menos um código foi informado
    if (!$codigo_interno && !$codigo_gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno ou GTIN deve ser informado'
        ]);
        die();
    }

    try {
        // Primeiro, verificar se o produto existe
        if ($codigo_gtin) {
            $check_query = "SELECT codigo_interno, codigo_gtin, descricao, status FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
        } else {
            $check_query = "SELECT codigo_interno, codigo_gtin, descricao, status FROM produtos WHERE codigo_interno = $codigo_interno";
        }

        $check_result = pg_query($conexao, $check_query);

        if (!$check_result) {
            throw new Exception("Erro ao verificar produto: " . pg_last_error($conexao));
        }

        if (pg_num_rows($check_result) == 0) {
            $identificador = $codigo_gtin ? "GTIN $codigo_gtin" : "código interno $codigo_interno";
            error_log("DEBUG: Produto não encontrado com $identificador");
            throw new Exception("Produto não encontrado com $identificador");
        }

        $produto_atual = pg_fetch_assoc($check_result);
        error_log("DEBUG: Produto encontrado - GTIN: {$produto_atual['codigo_gtin']}, Status atual: '{$produto_atual['status']}', Novo status: '$status'");

        // NOVO SISTEMA: Determinar qual campo atualizar baseado no status
        $campo_destino = 'status'; // padrão

        // Se for status de Nuvemshop, usar campo NS
        if (in_array($status, ['ENS', 'ENSVI', 'ENSV', 'E'])) {
            $campo_destino = 'ns';
        }
        // Se for status de Mercado Livre, usar campo ML
        elseif (in_array($status, ['ML', 'MLVI', 'MLV'])) {
            $campo_destino = 'ml';
        }
        // Se for status de Shopee, usar campo SHOPEE
        elseif (in_array($status, ['SH', 'SHVI', 'SHV'])) {
            $campo_destino = 'shopee';
        }

        // Construir query baseada no parâmetro disponível
        if ($codigo_gtin) {
            // Usar GTIN para buscar o produto
            $query = "UPDATE produtos SET $campo_destino = '$status' WHERE codigo_gtin = '$codigo_gtin'";
        } else {
            // Usar código interno (compatibilidade com código existente)
            $query = "UPDATE produtos SET $campo_destino = '$status' WHERE codigo_interno = $codigo_interno";
        }

        error_log("DEBUG: Executando query: $query");
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao atualizar status do produto: " . pg_last_error($conexao));
        }

        // Verificar se algum registro foi afetado
        $affected_rows = pg_affected_rows($result);
        error_log("DEBUG: Linhas afetadas: $affected_rows");

        if ($affected_rows == 0) {
            $identificador = $codigo_gtin ? "GTIN $codigo_gtin" : "código interno $codigo_interno";
            throw new Exception("Nenhum produto foi atualizado com $identificador");
        }

        // Verificar se a atualização realmente funcionou
        $verify_result = pg_query($conexao, $check_query);
        if ($verify_result) {
            $produto_verificado = pg_fetch_assoc($verify_result);
            error_log("DEBUG: Status após atualização: '{$produto_verificado['status']}'");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Status atualizado com sucesso',
            'affected_rows' => $affected_rows,
            'debug' => [
                'produto_antes' => $produto_atual,
                'novo_status' => $status,
                'query' => $query
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para buscar um produto pelo código GTIN
if ($request == 'buscarProdutoPorGtin') {
    $codigo_gtin = $_POST['codigo_gtin'];

    if (!$codigo_gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'Código GTIN não informado'
        ]);
        die();
    }

    try {
        // Buscar o produto pelo código GTIN
        $query = "SELECT codigo_interno, codigo_gtin, descricao, status FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar produto: " . pg_last_error($conexao));
        }

        $produtos = array();
        while ($row = pg_fetch_assoc($result)) {
            $produtos[] = $row;
        }

        echo json_encode($produtos);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para obter a quantidade de um produto pelo código GTIN
if ($request == 'obterQuantidadeProduto') {
    $codigo_gtin = $_POST['codigo_gtin'];

    if (!$codigo_gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'Código GTIN não informado'
        ]);
        die();
    }

    try {
        // Buscar quantidade, preço e dimensões do produto pelo código GTIN
        $query = "SELECT po.qtde, pb.preco_venda, po.peso, po.altura, po.largura, po.comprimento
                  FROM produtos p
                  INNER JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  WHERE p.codigo_gtin = '$codigo_gtin'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar dados do produto: " . pg_last_error($conexao));
        }

        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'qtde' => $row['qtde'],
                'preco_venda' => $row['preco_venda'],
                'peso' => $row['peso'],
                'altura' => $row['altura'],
                'largura' => $row['largura'],
                'comprimento' => $row['comprimento']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Produto não encontrado'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint específico para buscar dimensões de uma variante
if ($request == 'obterDimensoesVariante') {
    $codigo_gtin = $_POST['codigo_gtin'];

    if (!$codigo_gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'Código GTIN não informado'
        ]);
        die();
    }

    try {
        // Buscar dimensões específicas da variante pelo código GTIN
        $query = "SELECT po.peso, po.altura, po.largura, po.comprimento,
                         pb.preco_venda, po.qtde, p.descricao
                  FROM produtos p
                  INNER JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  WHERE p.codigo_gtin = '$codigo_gtin'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar dimensões da variante: " . pg_last_error($conexao));
        }

        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'codigo_gtin' => $codigo_gtin,
                'descricao' => $row['descricao'],
                'peso' => floatval($row['peso']) ?: 0,
                'altura' => floatval($row['altura']) ?: 0,
                'largura' => floatval($row['largura']) ?: 0,
                'comprimento' => floatval($row['comprimento']) ?: 0,
                'preco_venda' => $row['preco_venda'],
                'qtde' => intval($row['qtde']) ?: 0
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Variante não encontrada'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para buscar produto pai por GTIN (deve ser ENSVI)
if ($request == 'buscarProdutoPaiPorGtin') {
    $gtin = $_POST['gtin'];

    if (!$gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'GTIN não informado'
        ]);
        die();
    }

    try {
        // Buscar produto pai pelo GTIN com status ENSVI
        $query = "SELECT p.codigo_interno, p.codigo_gtin, p.descricao, p.status
                  FROM produtos p
                  WHERE p.codigo_gtin = '$gtin' AND p.status = 'ENSVI'";
        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar produto pai: " . pg_last_error($conexao));
        }

        if (pg_num_rows($result) > 0) {
            $produto = pg_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'produto' => [
                    'codigo_interno' => $produto['codigo_interno'],
                    'codigo_gtin' => $produto['codigo_gtin'],
                    'descricao' => $produto['descricao'],
                    'status' => $produto['status']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Produto pai não encontrado ou não possui status ENSVI'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para atualizar dimensões de uma variante no banco de dados
if ($request == 'atualizarDimensoesVariante') {
    $codigo_gtin = $_POST['codigo_gtin'];
    $peso = $_POST['peso'];
    $altura = $_POST['altura'];
    $largura = $_POST['largura'];
    $comprimento = $_POST['comprimento'];

    if (!$codigo_gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'Código GTIN não informado'
        ]);
        die();
    }

    try {
        // Primeiro, verificar se o produto existe
        $query_check = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$codigo_gtin'";
        $result_check = pg_query($conexao, $query_check);

        if (!$result_check || pg_num_rows($result_check) == 0) {
            throw new Exception("Produto não encontrado com GTIN: $codigo_gtin");
        }

        $row = pg_fetch_assoc($result_check);
        $codigo_interno = $row['codigo_interno'];

        // Atualizar dimensões na tabela produtos_ou
        $query_update = "UPDATE produtos_ou SET
                         peso = " . floatval($peso) . ",
                         altura = " . floatval($altura) . ",
                         largura = " . floatval($largura) . ",
                         comprimento = " . floatval($comprimento) . "
                         WHERE codigo_interno = $codigo_interno";

        $result_update = pg_query($conexao, $query_update);

        if (!$result_update) {
            throw new Exception("Erro ao atualizar dimensões: " . pg_last_error($conexao));
        }

        // Verificar se alguma linha foi afetada
        $rows_affected = pg_affected_rows($result_update);

        if ($rows_affected > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Dimensões atualizadas com sucesso',
                'rows_affected' => $rows_affected
            ]);
        } else {
            // Se nenhuma linha foi afetada, pode ser que não existe registro em produtos_ou
            // Tentar inserir um novo registro
            $query_insert = "INSERT INTO produtos_ou (codigo_interno, peso, altura, largura, comprimento, qtde, qtde_min)
                            VALUES ($codigo_interno, " . floatval($peso) . ", " . floatval($altura) . ", " . floatval($largura) . ", " . floatval($comprimento) . ", 0, 0)";

            $result_insert = pg_query($conexao, $query_insert);

            if ($result_insert) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Dimensões inseridas com sucesso (novo registro)',
                    'action' => 'inserted'
                ]);
            } else {
                throw new Exception("Erro ao inserir dimensões: " . pg_last_error($conexao));
            }
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para obter dados completos de um produto (incluindo dimensões atualizadas)
if ($request == 'obterDadosCompletoProduto') {
    $codigo_interno = $_POST['codigo_interno'];

    if (!$codigo_interno) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno não informado'
        ]);
        die();
    }

    try {
        // Buscar dados completos do produto com dimensões atualizadas
        $query = "SELECT p.codigo_interno, p.codigo_gtin, p.descricao,
                         pb.descricao_detalhada, pb.preco_venda,
                         po.peso, po.altura, po.largura, po.comprimento
                  FROM produtos p
                  INNER JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  INNER JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  WHERE p.codigo_interno = $codigo_interno";

        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar dados do produto: " . pg_last_error($conexao));
        }

        if (pg_num_rows($result) > 0) {
            $produto = pg_fetch_assoc($result);

            // Garantir que dimensões sejam numéricas
            $produto['peso'] = floatval($produto['peso']) ?: 0;
            $produto['altura'] = floatval($produto['altura']) ?: 0;
            $produto['largura'] = floatval($produto['largura']) ?: 0;
            $produto['comprimento'] = floatval($produto['comprimento']) ?: 0;

            echo json_encode([
                'success' => true,
                'produto' => $produto
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Produto não encontrado'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para buscar produto por código interno (para exportação ML)
if ($request == 'buscarProduto') {
    $codigo_interno = $_POST['codigo_interno'];

    if (!$codigo_interno) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno não informado'
        ]);
        die();
    }

    try {
        // Buscar dados do produto
        $query = "SELECT p.*, pb.preco_venda, pb.descricao_detalhada, po.peso, po.altura, po.largura, po.comprimento, po.qtde as estoque
                  FROM produtos p
                  LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  WHERE p.codigo_interno = $codigo_interno";

        $result = pg_query($conexao, $query);

        if (!$result) {
            throw new Exception("Erro ao buscar produto: " . pg_last_error($conexao));
        }

        if (pg_num_rows($result) > 0) {
            $produto = pg_fetch_assoc($result);
            echo json_encode($produto);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Produto não encontrado'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para upload de imagem de variante
if ($request == 'upload_variant_image') {
    header('Content-Type: application/json');
    $gtin = $_POST['gtin'] ?? '';
    
    if (!$gtin) {
        echo json_encode([
            'success' => false,
            'error' => 'GTIN não informado'
        ]);
        die();
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro no upload do arquivo: ' . ($_FILES['image']['error'] ?? 'Desconhecido')
        ]);
        die();
    }

    $file = $_FILES['image'];
    $uploadDir = '../upload/';
    
    // Log para debug
    error_log("Upload de imagem - GTIN: $gtin, Tipo: " . $file['type'] . ", Tamanho: " . $file['size']);
    
    // Verificar se a extensão GD está disponível
    if (!extension_loaded('gd')) {
        error_log("Extensão GD não está disponível");
        echo json_encode([
            'success' => false,
            'error' => 'Extensão GD não está disponível no servidor'
        ]);
        die();
    }
    
    // Verificar se o diretório existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Verificar tipo de arquivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Tipo de arquivo não permitido: " . $file['type']);
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de arquivo não permitido: ' . $file['type'] . '. Use JPG, PNG, GIF ou WebP.'
        ]);
        die();
    }

    // Verificar tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'error' => 'Arquivo muito grande. Máximo 5MB.'
        ]);
        die();
    }

    try {
        // Gerar caminho do arquivo final
        $filename = $gtin . '.webp';
        $filepath = $uploadDir . $filename;
        
        // Log para debug
        error_log("Processando upload - Arquivo: {$file['tmp_name']} -> $filepath");
        
        // Criar imagem a partir do arquivo
        $imageData = null;
        switch ($file['type']) {
            case 'image/jpeg':
            case 'image/jpg':
                $imageData = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imageData = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $imageData = imagecreatefromwebp($file['tmp_name']);
                break;
            case 'image/gif':
                $imageData = imagecreatefromgif($file['tmp_name']);
                break;
            default:
                throw new Exception('Tipo de imagem não suportado: ' . $file['type']);
        }
        
        if (!$imageData) {
            throw new Exception('Não foi possível processar a imagem');
        }
        
        // Redimensionar se necessário (máximo 800x600 para otimizar)
        $originalWidth = imagesx($imageData);
        $originalHeight = imagesy($imageData);
        $maxWidth = 800;
        $maxHeight = 600;
        
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        if ($ratio < 1) {
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparência para PNG
            if ($file['type'] == 'image/png' || $file['type'] == 'image/gif') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }
            
            imagecopyresampled($resizedImage, $imageData, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($imageData);
            $imageData = $resizedImage;
        }
        
        // Salvar como WebP
        if (!imagewebp($imageData, $filepath, 90)) {
            imagedestroy($imageData);
            throw new Exception('Erro ao salvar imagem como WebP');
        }
        
        imagedestroy($imageData);
        
        // Log de sucesso
        error_log("Imagem salva com sucesso: $filepath");
        
        echo json_encode([
            'success' => true,
            'message' => 'Imagem enviada com sucesso',
            'filename' => $filename,
            'filepath' => $filepath
        ]);
        
    } catch (Exception $e) {
        error_log("Erro no upload da imagem: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao processar imagem: ' . $e->getMessage()
        ]);
    }
    
    die();
}

// Endpoint para remover imagem de variante
if ($request == 'remover_imagem_variante') {
    header('Content-Type: application/json');
    
    try {
        $gtin = $_POST['gtin'] ?? '';
        
        if (!$gtin) {
            throw new Exception('GTIN não informado');
        }

        $uploadDir = '../upload/';
        $filename = $gtin . '.webp';
        $filepath = $uploadDir . $filename;

        // Log para debug
        error_log("Tentando remover imagem: $filepath");

        // Verificar se o arquivo existe
        if (!file_exists($filepath)) {
            error_log("Arquivo não encontrado: $filepath");
            echo json_encode([
                'success' => false,
                'error' => 'Imagem não encontrada'
            ]);
            die();
        }

        // Tentar remover o arquivo
        if (unlink($filepath)) {
            error_log("Arquivo removido com sucesso: $filepath");
            echo json_encode([
                'success' => true,
                'message' => 'Imagem removida com sucesso'
            ]);
        } else {
            error_log("Erro ao remover arquivo: $filepath");
            throw new Exception('Erro ao remover imagem do sistema de arquivos');
        }
        
    } catch (Exception $e) {
        error_log("Erro na remoção de imagem: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    die();
}

// Endpoint para atualizar grade avançada
if ($request == 'atualizar_grade_avancada') {
    $codigo_interno = $_POST['codigo_interno'] ?? '';
    $changes = json_decode($_POST['changes'] ?? '[]', true);
    
    if (!$codigo_interno || !is_array($changes)) {
        echo json_encode([
            'success' => false,
            'error' => 'Dados inválidos'
        ]);
        die();
    }

    try {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($changes as $change) {
            $gtin = $change['gtin'] ?? '';
            if (!$gtin) continue;

            // Buscar código interno da variante
            $query_variant = "SELECT codigo_interno FROM produtos WHERE codigo_gtin = '$gtin'";
            $result_variant = pg_query($conexao, $query_variant);
            
            if (!$result_variant || pg_num_rows($result_variant) == 0) {
                $errors[] = "Variante com GTIN $gtin não encontrada";
                $errorCount++;
                continue;
            }

            $variant_data = pg_fetch_assoc($result_variant);
            $variant_codigo_interno = $variant_data['codigo_interno'];

            // Atualizar produtos_gd (característica e variação)
            if (isset($change['caracteristica']) || isset($change['variacao'])) {
                $update_fields = [];
                
                if (isset($change['caracteristica'])) {
                    $caracteristica = pg_escape_string($conexao, $change['caracteristica']);
                    $update_fields[] = "caracteristica = '$caracteristica'";
                }
                
                if (isset($change['variacao'])) {
                    $variacao = pg_escape_string($conexao, $change['variacao']);
                    $update_fields[] = "variacao = '$variacao'";
                }
                
                if (!empty($update_fields)) {
                    $query_gd = "UPDATE produtos_gd SET " . implode(', ', $update_fields) . " WHERE codigo_gtin = '$gtin'";
                    $result_gd = pg_query($conexao, $query_gd);
                    
                    if (!$result_gd) {
                        $errors[] = "Erro ao atualizar produtos_gd para GTIN $gtin: " . pg_last_error($conexao);
                        $errorCount++;
                        continue;
                    }
                }
            }

            // Atualizar produtos_ib (preço)
            if (isset($change['preco'])) {
                $preco = floatval($change['preco']);
                $query_ib = "UPDATE produtos_ib SET preco_venda = $preco WHERE codigo_interno = $variant_codigo_interno";
                $result_ib = pg_query($conexao, $query_ib);
                
                if (!$result_ib) {
                    $errors[] = "Erro ao atualizar preço para GTIN $gtin: " . pg_last_error($conexao);
                    $errorCount++;
                    continue;
                }
            }

            // Atualizar produtos_ou (estoque e dimensões)
            if (isset($change['estoque']) || isset($change['peso']) || isset($change['altura']) || isset($change['largura']) || isset($change['comprimento'])) {
                $update_fields = [];
                
                if (isset($change['estoque'])) {
                    $estoque = intval($change['estoque']);
                    $update_fields[] = "qtde = $estoque";
                }
                if (isset($change['peso'])) {
                    $peso = floatval($change['peso']);
                    $update_fields[] = "peso = $peso";
                }
                if (isset($change['altura'])) {
                    $altura = floatval($change['altura']);
                    $update_fields[] = "altura = $altura";
                }
                if (isset($change['largura'])) {
                    $largura = floatval($change['largura']);
                    $update_fields[] = "largura = $largura";
                }
                if (isset($change['comprimento'])) {
                    $comprimento = floatval($change['comprimento']);
                    $update_fields[] = "comprimento = $comprimento";
                }
                
                if (!empty($update_fields)) {
                    $query_ou = "UPDATE produtos_ou SET " . implode(', ', $update_fields) . " WHERE codigo_interno = $variant_codigo_interno";
                    $result_ou = pg_query($conexao, $query_ou);
                    
                    if (!$result_ou) {
                        $errors[] = "Erro ao atualizar dimensões/estoque para GTIN $gtin: " . pg_last_error($conexao);
                        $errorCount++;
                        continue;
                    }
                }
            }

            $successCount++;
        }

        echo json_encode([
            'success' => $errorCount === 0,
            'message' => "Atualizadas $successCount variantes" . ($errorCount > 0 ? ", $errorCount erros" : ''),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno: ' . $e->getMessage()
        ]);
    }
    die();
}

// Endpoint para carregar dados completos da grade (incluindo preço, estoque, dimensões)
if ($request == 'carregar_grade_completa') {
    $codigo_interno = $_POST['codigo_interno'] ?? '';
    
    if (!$codigo_interno) {
        echo json_encode([
            'success' => false,
            'error' => 'Código interno não informado'
        ]);
        die();
    }

    try {
        $query = "SELECT 
                    pg.codigo_gtin,
                    pg.caracteristica,
                    pg.variacao,
                    pg.nome as descricao,
                    COALESCE(pb.preco_venda::numeric, 0) as preco,
                    COALESCE(po.qtde::numeric, 0) as estoque,
                    COALESCE(po.peso::numeric, 0) as peso,
                    COALESCE(po.altura::numeric, 0) as altura,
                    COALESCE(po.largura::numeric, 0) as largura,
                    COALESCE(po.comprimento::numeric, 0) as comprimento
                  FROM produtos_gd pg
                  LEFT JOIN produtos p ON pg.codigo_gtin = p.codigo_gtin
                  LEFT JOIN produtos_ib pb ON p.codigo_interno = pb.codigo_interno
                  LEFT JOIN produtos_ou po ON p.codigo_interno = po.codigo_interno
                  WHERE pg.codigo_interno = $codigo_interno
                  ORDER BY pg.codigo";
        
        error_log("DEBUG carregar_grade_completa: Query: $query");
        $result = pg_query($conexao, $query);
        
        if (!$result) {
            throw new Exception("Erro ao buscar grade: " . pg_last_error($conexao));
        }

        $variants = [];
        while ($row = pg_fetch_assoc($result)) {
            $variants[] = $row;
        }

        error_log("DEBUG carregar_grade_completa: Encontradas " . count($variants) . " variantes");

        echo json_encode([
            'success' => true,
            'variants' => $variants
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    die();
}
?>
