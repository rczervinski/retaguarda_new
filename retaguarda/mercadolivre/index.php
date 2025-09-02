<?php 
    include("conexaoml.php");
    include("tokenml.php");
    if($code==""){
        include("permissaoml.php");
    }else{
        echo "Tudo certo<br>";
        echo $id_loja."<br>";
        echo $first_name."<br>";
    }

?>