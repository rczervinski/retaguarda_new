<?php include('conexaoml.php');?>
<?php
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.mercadolibre.com/oauth/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "client_id": '.$client_id.',
    "client_secret": '.$client_secret.',
    "refresh_token": '.$code.',
    "grant_type":"refresh_token"
    }',
  CURLOPT_HTTPHEADER => array(
    'accept: application/json',
    'content-type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);
$resultado = json_decode($response);

curl_close($curl);

$query = "UPDATE tokenmercado SET
            access_token    = '".$resultado->access_token."',
            code            = '".$resultado->refresh_token."'
         WHERE codigo='1'";
    $result = pg_query($conexao, $query);
?>