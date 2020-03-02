<?php
include "../../../../include/conn.php";


/*
    ⌜                                                 ⌝
                    INTEGRAÇÃO FCA/ITRACK
        Esse script deve ser chamado em todos os scripts
        pertencentes a integração, pois necessita do re-
        torno (token) para fazer as demais chamadas.
    ⌞                                                  ⌟

*/

// Realiza a conexão com a itrack e obtem o retorno (token)
// Id FCA: '298bde74da3b8fe77967f61a7b1283cc','7c7ac30350fa0c4931a02d00c5eebc77','b5b13e9d68b1a525e33de5906a25c202'


$conn = '{
    "account":"06321409000196",
    "password":"469125f38b95a3234c1e75dc45340f4106d6d7f3",
    "type":2
}';

$ch = curl_init('https://www.itrackbrasil.com.br/ws/User/Login');
//$ch = curl_init('https://beta.martinlabs.com.br/iTrackServer-1.0-SNAPSHOT/ws/User/Login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $conn);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$runCh = curl_exec($ch);

if(curl_error($ch)){
    echo curl_error($ch);
}

$arr = json_decode($runCh, true);
$token = $arr["data"]["token"];
#echo "token.: ".$token;
?>