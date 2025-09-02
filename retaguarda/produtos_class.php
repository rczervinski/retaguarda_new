<?php
class produtos
{
    public $codigo_interno;
    public $codigo_gtin;
    public $descricao;
    public $status;
}
class produtos_ib
{
    public $codigo_interno;
    public $descricao_detalhada;
    public $grupo;
    public $subgrupo;
    public $categoria;
    public $unidade;
    public $peso_bruto;
    public $peso_liquido;
    public $preco_venda;
    public $preco_compra;
    public $perc_lucro;
    public $codigo_ncm;
    public $produto_balanca;
    public $validade;
    public $unidade_entrada;
    public $fator_conversao;
    public $ex_tipi;
    public $genero;
    public $cfop;
    public $cest;
}
class produtos_cp
{
    public $codigo_composicao;
    public $codigo_interno;
    public $qtde;
}
class produtos_ou
{
    public $codigo_interno;
    public $percDescA;
    public $percDescB;
    public $percDescC;
    public $percDescD;
    public $percDescE;
    public $valDescA;
    public $valDescB;
    public $valDescC;
    public $valDescD;
    public $valDescE;
    public $qtde;
    public $qtde_min;
    public $inativo;
    public $valorPromo;
    public $fornecedor;
    public $dt1Promo;
    public $dt2Promo;
    public $vencimento;
    public $descricao_personalizada;
    public $dt_cadastro;
    public $dt_ultima_alteracao;
    public $preco_gelado;
    public $desc_etiqueta;
    public $produto_producao;
    public $comprimento;
    public $largura;
    public $altura;
    public $peso;
}
class produtos_tb{
    public $codigo_interno;
    public $ipi_reducao_bc;
    public $aliquota_ipi;
    public $ipi_reducao_bc_st;
    public $aliquota_ipi_st ;
    public $pis_reducao_bc;
    public $aliquota_pis;
    public $pis_reducao_bc_st;
    public $aliquota_pis_st;
    public $cofins_reducao_bc;
    public $aliquota_cofins;
    public $cofins_reducao_bc_st;
    public $aliquota_cofins_st;
    public $situacao_tributaria;
    public $origem;
    public $aliquota_calculo_credito;
    public $modalidade_deter_bc_icms;
    public $aliquota_icms;
    public $icms_reducao_bc;
    public $modalidade_deter_bc_icms_st;
    public $icms_reducao_bc_st;
    public $perc_mva_icms_st;
    public $aliquota_icms_st;
    public $ipi_cst;
    public $calculo_ipi;
    public $cst_pis;
    public $calculo_pis;
    public $cst_cofins;
    public $calculo_cofins;
    public $aliquota_fcp;
    public $aliquota_fcp_st;
    public $perc_dif;
}
?>