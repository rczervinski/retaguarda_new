<?php
	require_once "vendor/autoload.php";
	require_once "config.php";
	use NFePHP\Common\Certificate;
	use NFePHP\Common\Exception\CertificateException;
	$senha="1234";
	$configJson = json_encode($arr);
	$content = file_get_contents('certificado.pfx');
	$cert = Certificate::readPfx($content, $senha);
	$validFrom = $cert->getValidTo();
	echo $validFrom->format('d/m/Y');
?>