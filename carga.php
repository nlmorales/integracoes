<?php
include "connect.php";
 
//Envia os pedidos da FIAT em edição.

$selectPedido = mysql_query("SELECT 
x3.id_pedido,
x3.chave_composta,
x3.chave_nf,
x3.numero_nf,
x1.status,
x1.destinatario,
x1.dest_cpfcnpj,
x1.uf,
x1.cidade,
CONCAT(x1.logradouro, ' - ', x1.entreganumero) AS logradouro,
x1.cep,
x1.dataentrega
FROM
pedidos x1
    JOIN
clientes x2 ON x1.idcliente = x2.id
    JOIN
tb_aux_fiat x3 ON x3.id_pedido = x1.id
WHERE
x1.status = 'edicao'
AND NOT EXISTS( SELECT 
*
FROM
log_itrack x4
WHERE
x3.numero_nf = x4.numero_nf)
");

while($row = mysql_fetch_array($selectPedido)){
             
    $numero_nf = $row['numero_nf'];
    $id_pedido = $row['id_pedido'];
    $chave_composta = $row['chave_composta'];
    $idcliente = $row['idcliente'];
    $chave_nf = $row['chave_nf'];
    $destinatario = $row['destinatario'];
    $dest_cpfcnpj = $row['dest_cpfcnpj'];
    $estadodest = $row['uf'];
    $cidadedest = $row['cidade'];
    $logradourodest = $row['logradouro'];
    $cepdest = $row['cep'];
    $dataentrega = $row['dataentrega'].'T18:00:00-03';

    $json .= '{
        "danfe": "'.$chave_nf.'",
        "nroNotaFiscal": "'.$numero_nf.'",
        "dataPrevisao": "'.$dataentrega.'",
        "embarcador": {
            "responsavel":{
					"cnpj":"16701716003848",
					"razaoSocial":"FCA FIAT CHRYSLER AUTOMOVEIS BRASIL LTDA",
					"nomeFantasia":"FCA FIAT",
					"endereco":{
						"logradouro":"EST CARLOS ROBERTO PRATAVIERA",
						"nro":"650",
						"bairro":"JARDIM NOVA EUROPA",
						"cep":"13184-889",
						"cidade":"HORTOLANDIA",
						"uf":"SP"
					}
				}
        },
        "destinatario": {
            "endereco": {
                "logradouro": "'.$logradourodest.'",
                "cep": "'.$cepdest.'",
                "cidade": "'.$cidadedest.'",
                "uf": "'.$estadodest.'"
            },  
            "nome": "'.$destinatario.'",
            "cpfCnpj": "'.$dest_cpfcnpj.'"
        },
        "transportador": {
            "responsavel": {
                "cnpj": "06321409000196",
                "razaoSocial": "QUALITY TRANSPORTES E ENTREGAS RAPIDAS LTDA",
                "nomeFantasia": "QUALITY MATRIZ",
                "endereco": {
                    "logradouro": "AV JABAQUARA",
                    "nro": "1909",
                    "bairro": "MIRANDOPOLIS",
                    "cep": "04045-003",
                    "cidade": "SAO PAULO",
                    "uf": "SP"
                    }
                }
            }
        },';
}

$initJson = '{"token": "'.$token.'","content":[';
$fimJson = ']}';
$envioJson = $initJson . substr($json, 0, -1) . $fimJson;

echo $envioJson . "<br>";

$ch = curl_init('https://www.itrackbrasil.com.br/ws/User/Cargas'); 
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $envioJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$run = curl_exec($ch);
if (curl_error($run)) {
    echo curl_error($run);
} else {
    echo $run . "<br><br>";
}

$decode = json_decode($run, true); //transforma json em array;
$data = $decode["data"];
foreach ($data as $valor){ //percorre todos os dados do array e realizar update se tiver retorno do idcargapk;
    if($valor["idCargaPk"] != 0){//realiza update, insere log de envio, capta chave_composta
        $idcargapk = $valor["idCargaPk"];
        $danfe = $valor["danfe"];
        $sucess = $valor['sucess'];
        if($sucess == 'true') {
            $sucess = 'true';
        } else {
            $sucess = 'false';
        }
        $linha = mysql_fetch_assoc(mysql_query("SELECT chave_composta, numero_nf FROM tb_aux_fiat where chave_nf = '$danfe'"));
        $chave_composta = $linha['chave_composta'];
        $numNF = $linha['numero_nf'];
        mysql_query("INSERT INTO log_itrack (idcargapk,chave_composta,numero_nf,chave_nf,data,status,carga_retorno) VALUES ('$idcargapk','$chave_composta','$numNF','$danfe',NOW(),'edicao','$sucess')");
        mysql_query("UPDATE tb_aux_fiat SET idcargapk = '$idcargapk' WHERE chave_composta = '$chave_composta'");
     } else if($valor['idCargaPk']==0){
         $idcargapk = $valor['idCargaPk'];
         // $danfe = $valor['danfe'];
         $message = $valor['message'];
         mysql_query("UPDATE log_itrack SET carga_envio = '$message' WHERE idcargapk = '$idcargapk';");
	    }
}

?>