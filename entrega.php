<?php

include "connect.php";

$select = mysql_query("SELECT numero_nf FROM log_itrack WHERE status != 'entregue';");

while($row = mysql_fetch_assoc($select)){
  $chave = $row['numero_nf'];
  $concat .= "'" . $chave . "',"; 
}

$ini = "(";
$meio = substr($concat, 0, -1);
$fim = ")";
$query = $ini . $meio . $fim;
echo $query . "<br><br>";

$sel = "SELECT x1.status, x1.id, x1.numerocontrole, x1.recebidopor, x1.dataretorno, x1.dataconfirmacao, x1.dataentrega, x1.destinatario, x1.identregador, x2.numero_nf FROM pedidos x1 join tb_aux_fiat x2 on x1.id=x2.id_pedido where x2.numero_nf in $query and (x1.status = 'entregue' || x1.status = 'emtransitoentregue')";
echo $sel . "<br>";
$select = mysql_query($sel);

while($row = mysql_fetch_assoc($select)){

  $id_pedido = $row['id'];
  echo "Id do Pedido: " . $id_pedido . "<br>";
  $numerocontrole = $row['numerocontrole'];
  echo "Numero da NF: " . $numerocontrole . "<br>";
  $destinatario = $row['destinatario'];
  echo "Destinatário" . $destinatario . "<br>";
  $recebidopor = $row['recebidopor'];
  if($recebidopor=='' || $recebidopor==NULL){
    $recebidopor = $destinatario;
  }
  echo "Recebido por: " . $recebidopor . "<br>";
  $dataretorno = str_replace(' ', 'T', $row['dataretorno'] . "-03");
  $dataconfirmacao = str_replace(' ', 'T', $row['dataconfirmacao'] . "-03");
  $dataentrega = str_replace(' ', 'T', $row['dataentrega'] . "-03");
  if($dataretorno == '0000-00-00T00:00:00-03'){
    $dataFinaliza = $dataconfirmacao;
  } else {
    $dataFinaliza = $dataretorno;
  }
  echo "Data Retorno: " . $dataretorno . "<br>";
  echo "Data Confirmação: " . $dataconfirmacao . "<br>";
  echo "Data Entrega: " . $dataentrega . "<br>";
  $identregador = $row['identregador'];
  echo "Id do Entregador: " . $identregador . "<br>";
  $numero_nf = $row['numero_nf'];
  echo "Numero da NF (tb_aux_fiat): " . $numero_nf . "<br>";

  $idpk = "SELECT x2.idcargapk FROM tb_aux_fiat x1 JOIN log_itrack x2 ON x1.numero_nf=x2.numero_nf WHERE x2.numero_nf = '$numero_nf'";
  echo "Query idcargapk: " . $idpk . "<br>";
  $sel = mysql_query($idpk);
  $r = mysql_fetch_assoc($sel);
  $idcargapk = $r['idcargapk'];
  echo "Id Carga iTrack: " . $idcargapk . "<br>";

  $mot = mysql_query("SELECT x2.nome FROM pedidos x1 JOIN funcionarios x2 ON x1.identregador = x2.id WHERE x1.numerocontrole = '$numerocontrole';");
  $m = mysql_fetch_assoc($mot);
  $nome = strtoupper($m['nome']);
  if($nome=='' || $nome==NULL){
    $nome = 'Quality Entregas';
  }
  echo "Nome Entregador: " . $nome . "<br>";
  $placa = $m['placa'];
  if($placa=='' || $placa==NULL){
    $placa = 'ABC1234';
  }
  echo "Placa Entregador: " . $placa . "<br>";

  $cargaEntrega = '{  
    "content":{
    "fotosComprovantes":[
      "http://sistema.qualityentregas.com.br/canhotos/processadas/'.$id_pedido.'.png"
    ],
    "rgRecebedor":"0",
    "nomeRecebedor":"'.$recebidopor.'",
    "idCargaPk":"'.$idcargapk.'",
    "dataFinalizacao":"'.$dataFinaliza.'",
    "motorista":{ 
        "tipoVeiculo":"VEICULO AUTOMOTOR",
        "nome":"'.$nome.'",
        "placaVeiculo":"'.$placa.'"
    }
  },
    "token":"'.$token.'"
  }';

  echo "Json: " . $cargaEntrega . "<br>";
  
  // BACKUP AQUI <<

  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://www.itrackbrasil.com.br/ws/User/Carga/Entrega",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "$cargaEntrega",
    CURLOPT_HTTPHEADER => array(
      "content-type: application/json"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    echo "Status: " . $response . "<br><br>";
  }

  $return = json_decode($response, true);
  $rEnv = $return["success"];

  if($rEnv==true){
    mysql_query("UPDATE log_itrack SET status = 'entregue', json_entrega_envio = '$cargaEntrega', json_entrega_retorno = '$response' WHERE idcargapk = '$idcargapk'");
  } else {
    mysql_query("UPDATE log_itrack SET status = 'ERROR', json_entrega_envio = '$cargaEntrega', json_entrega_retorno = '$response' WHERE idcargapk = '$idcargapk'");
  }
}

?>