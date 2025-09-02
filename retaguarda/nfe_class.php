<?php

class nf_base
{
    public $codigo_interno;
    public $documento;
    public $serie;
    public $tipo;
    public $natureza;
    public $cliente;
    public $emissao;
    public $saida;
    public $hora;
    public $status;
    public $pedido;
    public $chave;
    public $protocolo;
    public $data_protocolo;
    public $hora_protocolo;
    public $recibo;
    public $dados_adicionais;
    public $motivo;
    public $iest;
    public $dados_adicionais2;
    public $xml;
}

class nf_docref
{
    public $codigo;
    public $numero;
    public $serie;
    public $chave;
    public $uf;
    public $anomes;
    public $cnpj;
    public $mod;
    public $serie_ref;
    public $numero_ref;
}

class nf_fat
{
    public $codigo_interno;
    public $numero_nf;
    public $serie_nf;
    public $numero;
    public $vencimento;
    public $valor;
}

class nf_imposto
{
    public $codigo;
    public $numero;
    public $documento;
    public $serie;
    public $bcicms;
    public $vlicmsst;
    public $vlprods;
    public $vlfrete;
    public $vlseguro;
    public $vldesconto;
    public $vldespesas;
    public $vlipi;
    public $vltotal;
    public $imposto_federal;
    public $imposto_estadual;
}

class nf_tra
{
    public $nf_numero;
    public $nf_serie;
    public $cod_transportadora;
    public $por_conta;
    public $cod_antt;
    public $placa;
    public $placa_uf;
    public $quantidade;
    public $especie;
    public $marca;
    public $numeracao;
    public $peso_liquido;
    public $peso_bruto;
}

class nf_prod
{
    public $nf_numero;
    public $nf_serie;
    public $codigo_gtin;
    public $descricao;
    public $codigo_ncm;
    public $cst_cson;
    public $cfop;
    public $unidade;
    public $quantidade;
    public $preco_unitario;
    public $total;
    public $bc_icms;
    public $val_icms;
    public $val_ipi;
    public $aliquota_icms;
    public $aliquota_ipi;
    public $desconto;
    public $numero_serie;
    public $codigo;
    public $frete;
    public $seguro;
    public $outros;
    public $aliquota_icms_st;
    public $bc_icms_st;
    public $val_icms_st;
    public $aliquota_fcp;
    public $aliquota_fcp_st;
    public $val_fcp;
    public $val_fcp_st;
    public $perc_dif;
    public $val_icmsop;
    public $val_icmsdif;
    public $numero_pedido_compra;
    public $item_pedido_compra;
}
 
?>