<?php

include "connect.php";

$busca = mysql_query("SELECT DISTINCT chave_composta FROM log_itrack;");
//apenas tratando as cargas com status quality 'emtransito'

//constroi o 'in' da query para ser mais rápido a consulta com o banco.
while($row = mysql_fetch_assoc($busca)){
    $chave = $row['chave_composta'];
    $concat .= "'" . $chave . "',"; 
}

$ini = "(";
$meio = substr($concat, 0, -1);
$fim = ")";
$query = $ini . $meio . $fim;

// echo "<br>" . $query . "<br>";

$localiza = mysql_query("SELECT x2.numero_nf, x2.chave_nf, x1.datacadastro, x2.idcargapk, x1.status FROM pedidos x1 JOIN tb_aux_fiat x2 ON x1.id = x2.id_pedido WHERE x1.numerocontrole IN $query AND x1.status != 'edicao';");

while($row = mysql_fetch_assoc($localiza)){
    $numero_nf = $row['numero_nf'];
    $chave_nf = $row['chave_nf'];
    $datacadastro = $row['datacadastro'];
    $idcargapk = $row['idcargapk'];
    $status = $row['status'];

    // echo "numero_nf: " . $numero_nf . "/ datacadastro: " . $datacadastro . "/ idcargapk: " . $idcargapk . "/ status: " . $status . "<br>";

    // Aqui será adicionado o switch case com
    // as validações para o tipo de descrição e idocorrenciapk,
    // conforme acordado com a itrack

    $json = '{
        "content": {
        "idOcorrenciaPk": "70",
        "idCargaFk": "'.$idcargapk.'",
        "descricao": "Em transito",
        "danfe": "'.$chave_nf.'",
        "nroNotaFiscal": "'.$numero_nf.'",
        "dataOcorrencia": "'.$datacadastro.'"
        },
        "token": "'.$token.'"
    }';

    echo $json . "<br><br>";

}

/*
while($row = mysql_fetch_assoc($busca)){
    $idcargapk = $row['idcargapk'];
    $chave_nf = $row['chave_nf'];
    $numero_nf = $row['numero_nf'];
    // $datacadastro = $row['datacadastro'];
    $d = str_replace(" ", "T",$row['datacadastro']);
    $datacadastro = $d."-03"; //UTC -3:00
    // echo $datacadastro."<br>";
    // idOcorrenciaPk = "70" -> "emtransito";

    $json = '{
        "content": {
        "idOcorrenciaPk": "70",
        "idCargaFk": "'.$idcargapk.'",
        "descricao": "Em transito",
        "danfe": "'.$chave_nf.'",
        "nroNotaFiscal": "'.$numero_nf.'",
        "dataOcorrencia": "'.$datacadastro.'"
        },
        "token": "'.$token.'"
    }';

    echo $json . "<br>";

    // $ch = curl_init('https://www.itrackbrasil.com.br/ws/User/Carga/Tracking/Ocorrencia');
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    // $exjson = curl_exec($ch);
    // if (curl_error($exjson)) {
    //     echo curl_error($exjson);
    // }else{
    //     if(!($json == '' || $json == NULL)){
    //         //Debug do Json
    //         $handle = fopen("jsontracking.txt", "a");
    //         fputs($handle, "Json> $json\n");
    //         fclose($handle);
    //     }
    //     curl_close($ch);
    // }
    // sleep(10);
   
    // $arrCarga = json_decode($exjson, true);
    // $msg = $arrCarga['message'];
    // echo $msg."<br>";
    // if($msg==true){
    //     $att = mysql_query("UPDATE log_itrack SET status = 'emtransito' WHERE chave_nf = '$chave_nf'");
    // }
}
*/
?>