<?php
	require_once "vendor/autoload.php";
	require_once "config.php";
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\Common\Soap\SoapCurl;
@$cnpj=$_GET['cnpj'];
$configJson = json_encode($arr);
$content = file_get_contents('certificado.pfx');
$tools = new Tools($configJson, Certificate::readPfx($content, '1234'));
$tools->model('55');

$uf = 'PR';
//$cnpj = '03919470000141';
$iest = '';
$cpf = '';
$response = $tools->sefazCadastro($uf, $cnpj, $iest, $cpf);

header('Content-type: text/xml; charset=UTF-8');
echo $response;
?>
