<?php
session_start();
$regime_tributario = $_SESSION['regime_tributario'];
$uf = $_SESSION['uf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="iso-8859-1">
<title>NFe</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
	rel="stylesheet">
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.1/css/materialize.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

</head> 
<script type="text/javascript"	src="https://code.jquery.com/jquery-3.2.1.js"></script> 
<script type="text/javascript" src="js/arrumar.js"></script>

<body>
	<a href="javascript:arrumarDuplicidade()">ARRUMAR DUPLICIDADE</a>
	
</body>
</html>