<?php

function getLote(){
//pega ultimo lote
$fQueryLote = "SELECT numLote FROM tb_emicte ORDER BY numLote DESC LIMIT 1";
$fResultLote = mysql_query($fQueryLote);
$fRowLote = mysql_fetch_assoc($fResultLote);

$value = $fRowLote['numLote'];
$value++;
return $value;
}

set_time_limit(60);

include "../../../include/conn.php";

function get_string_between($string, $start, $end){ //função usada para debug
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

/*

Validação de Processos

*/

if (controlaExecucao('datactetransmitelotes', 'N', 2) == false) {

	exit();

}

include '../Classes/CTeTool.class.php';
include '../Funcoes/xml2array.php';

$Cte = new CTeTool();

/*
PROCESSAMENTO DO DIRETORIO "CTeRecepcao" PARA ENVIO DE CTEs.
*/

// retirados 17,20,21
$aEmissoras = array(2,4,5,6,7,9,12,18,17,21);
// Emissores de CTes API - Andre 2020-01-16 Silva
$QueryDados = mysql_query("SELECT id FROM empresas WHERE emitir_cte = 'S' ORDER BY fantasia");
while($RowDados = mysql_fetch_assoc($QueryDados)){
	$EmissoresGo[] .= $RowDados['id'];
}
// $aEmissoras = array(17);

foreach ($aEmissoras as $Emissora) {

	$xmlLote = '';
	$inLote = '';

	/*
		PAR.
	*/

	// $Query = "SELECT x1.xml, x1.chavecte, x1.tpamb 
	//           FROM tb_emicte x1 
	//           JOIN pedidos x2 ON x2.id = x1.id_pedido 
	//           WHERE x2.fretevalor != 0 
	//           AND (x2.dataentrega >= '" . date('Y-m-d', strtotime("NOW - 15 DAYS")) . "' OR x2.dataentrega = '0000-00-00') 
	//           AND x1.idemissor = '".$Emissora."'
	//           AND x1.numCte % 2 = 0 AND x1.status = 'assinado' AND x1.data_emis > '".date("Y-m-d H:i:s", strtotime("- 15 Days"))."' 
	//           AND x1.tipocte IN (0,3) AND x1.xml IS NOT NULL 
	//           GROUP BY x2.id 
	//           ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 15;";

	$Query = "SELECT x1.xml, x1.chavecte, x1.tpamb,x1.id_pedido
	          FROM tb_emicte x1
	          USE INDEX (status,data_emis)
	          WHERE x1.idemissor = '".$Emissora."'
	          AND x1.numCte % 2 = 0 AND x1.status = 'assinado' AND x1.data_emis > date_sub(now(), interval 25 day)
	          AND x1.xml IS NOT NULL
	          AND x1.id_pedido<>''
	          ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 35;";
	$Result = mysql_query($Query);

	// $Query = "SELECT x1.xml, x1.chavecte, x1.tpamb FROM tb_emicte x1 JOIN pedidos x2 ON x2.id = x1.id_pedido WHERE x2.fretevalor != 0 AND (x2.dataentrega >= '" . date('Y-m-d', strtotime("NOW - 15 DAYS")) . "' OR x2.dataentrega = '0000-00-00') AND x1.idemissor = '".$Emissora."' AND x1.numCte % 2 = 0 AND x1.status = 'assinado' AND x1.data_emis > '".date("Y-m-d H:i:s", strtotime("- 15 Days"))."' AND x1.tipocte IN (0,3) AND x1.xml IS NOT NULL GROUP BY x2.id ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 50;";
	// $Result = mysql_query($Query);

	$aRows=array();

	while ($Row = mysql_fetch_assoc($Result)) {
		$hResult=mysql_query("SELECT id FROM pedidos WHERE id='".$Row["id_pedido"]."' AND fretevalor!=0");
		
		if (mysql_num_rows($hResult)>0) {
			$aRows[]=$Row;
		}
	}

	if (sizeOf($aRows)>0){
		for ($i=0;$i<sizeOf($aRows);$i++) {

			$xmlLote .= $aRows[$i]['xml'];
			$inLote  .= "'" . $aRows[$i]['chavecte'] . "',";
			$Ambiente = $aRows[$i]['tpamb'];

		}

$totalLote = substr($totalLote, 0, -1);
$totalLote = explode(',', $inLote);

foreach($totalLote as $chavecte){

		$getLote = getLote();
		mysql_query("UPDATE tb_emicte SET numLote = '".$getLote."' WHERE chavecte IN(".$chavecte.")");

		$Result = $Cte->CTeSend('CTeRecepcao', $xmlLote, $getLote, $Emissora, $Ambiente);

		if(!in_array($Emissora, $EmissoresGo)){
			$XmlResult = $Result;
			$Result = xml2array($Result,0);
		}

		print_r($Result);
		if($Result['retEnviCte']['cStat']=='103'){

			$Recibo = $Result['retEnviCte']['infRec']['nRec'];

			$QueryUpdate = "UPDATE tb_emicte SET status = 'enviado', recibo = '".$Recibo."', data_envio = '".date("Y-m-d H:i:s")."' WHERE status = 'assinado' AND chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		} else {

			$QueryUpdate = "UPDATE tb_emicte SET status = 'falhaenvio', observacao = 'Erro ao processar o lote! (".$Result['retEnviCte']['cStat'].")', recibo = '".$Recibo."', 
			                data_envio = '".date("Y-m-d H:i:s")."' 
			                WHERE chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		}

} //encerra foreach

	}

	$xmlLote = '';
	$inLote = '';

	/*
		IMPAR.
	*/

	$Query = "SELECT x1.xml, x1.chavecte, x1.tpamb FROM tb_emicte x1 JOIN pedidos x2 ON x2.id = x1.id_pedido WHERE x2.fretevalor != 0 AND x1.idemissor = '".$Emissora."' AND x1.numCte % 2 = 1 AND x1.status = 'assinado' AND x1.data_emis > date_sub(now(), interval 25 day) AND x1.xml IS NOT NULL GROUP BY x2.id ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 35";
	$Result = mysql_query($Query);

	if (mysql_num_rows($Result)>0){
		while ($Row = mysql_fetch_assoc($Result)) {
			
			$xmlLote .= $Row['xml'];
			$inLote  .= "'" . $Row['chavecte'] . "',";
			$Ambiente = $Row['tpamb'];

		}
$totalLote = substr($totalLote, 0, -1);
$totalLote = explode(',', $inLote);

foreach($totalLote as $chavecte){
		$getLote = getLote();
		mysql_query("UPDATE tb_emicte SET numLote = '".$getLote."' WHERE chavecte IN(".$chavecte.")");

		$Result = $Cte->CTeSend('CTeRecepcao', $xmlLote, $getLote, $Emissora, $Ambiente);

		if(!in_array($Emissora, $EmissoresGo)){
			$XmlResult = $Result;
			$Result = xml2array($Result,0);
		}

		if($Result['retEnviCte']['cStat']=='103'){

			$Recibo = $Result['retEnviCte']['infRec']['nRec'];

			$QueryUpdate = "UPDATE tb_emicte SET status = 'enviado', recibo = '".$Recibo."', data_envio = '".date("Y-m-d H:i:s")."' WHERE status = 'assinado' AND chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		} else {

			$QueryUpdate = "UPDATE tb_emicte SET status = 'falhaenvio', observacao = 'Erro ao processar o lote! (".$Result['retEnviCte']['cStat'].")', recibo = '".$Recibo."', 
			                data_envio = '".date("Y-m-d H:i:s")."'
			                WHERE chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		}

}//encerra foreach

	}

	$xmlLote = '';
	$inLote = '';

	/*
		GRUPO PAR.
	*/

	$Query = "SELECT x1.xml, x1.chavecte, x1.tpamb FROM tb_emicte x1 JOIN tb_emicte_grupos x2 ON x1.idgrupo = x2.id JOIN pedidos x3 ON x3.id = x2.idpedido WHERE x3.fretevalor != 0 AND x1.idemissor = '".$Emissora."' AND x1.numCte % 2 = 0 AND x1.status = 'assinado' AND x1.data_emis > date_sub(now(), interval 25 day) AND x1.xml IS NOT NULL GROUP BY x2.id ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 25;";
	$Result = mysql_query($Query);

	if (mysql_num_rows($Result)>0){
		while ($Row = mysql_fetch_assoc($Result)) {

			$xmlLote .= $Row['xml'];
			$inLote  .= "'" . $Row['chavecte'] . "',";
			$Ambiente = $Row['tpamb'];

		}

$totalLote = substr($totalLote, 0, -1);
$totalLote = explode(',', $inLote);

foreach($totalLote as $chavecte){
		$getLote = getLote();
		mysql_query("UPDATE tb_emicte SET numLote = '".$getLote."' WHERE chavecte IN(".$chavecte.")");

		$Result = $Cte->CTeSend('CTeRecepcao', $xmlLote, $getLote, $Emissora, $Ambiente);

		if(!in_array($Emissora, $EmissoresGo)){
			$XmlResult = $Result;
			$Result = xml2array($Result,0);
		}

		print_r($Result);
		if($Result['retEnviCte']['cStat']=='103'){

			$Recibo = $Result['retEnviCte']['infRec']['nRec'];

			$QueryUpdate = "UPDATE tb_emicte SET status = 'enviado', recibo = '".$Recibo."', data_envio = '".date("Y-m-d H:i:s")."' WHERE status = 'assinado' AND chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		} else {

			$QueryUpdate = "UPDATE tb_emicte SET status = 'falhaenvio', observacao = 'Erro ao processar o lote! (".$Result['retEnviCte']['cStat'].")', recibo = '".$Recibo."', 
			               data_envio = '".date("Y-m-d H:i:s")."' 
			               WHERE chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		}
} //encerra foreach

	}

	$xmlLote = '';
	$inLote = '';

	/*
		GRUPO IMPAR.
	*/

	$Query = "SELECT x1.xml, x1.chavecte, x1.tpamb FROM tb_emicte x1 JOIN tb_emicte_grupos x2 ON x1.idgrupo = x2.id JOIN pedidos x3 ON x3.id = x2.idpedido WHERE x3.fretevalor != 0 AND x1.idemissor = '".$Emissora."' AND x1.numCte % 2 = 1 AND x1.status = 'assinado' AND x1.data_emis > date_sub(now(), interval 25 day) AND x1.xml IS NOT NULL GROUP BY x2.id ORDER BY x1.prioridade DESC, x1.data_emis ASC LIMIT 25;";
	$Result = mysql_query($Query);

	if (mysql_num_rows($Result)>0){
		while ($Row = mysql_fetch_assoc($Result)) {
			
			$xmlLote .= $Row['xml'];
			$inLote  .= "'" . $Row['chavecte'] . "',";
			$Ambiente = $Row['tpamb'];

		}

$totalLote = substr($totalLote, 0, -1);
$totalLote = explode(',', $inLote);

foreach($totalLote as $chavecte){
		$getLote = getLote();
		mysql_query("UPDATE tb_emicte SET numLote = '".$getLote."' WHERE chavecte IN(".$chavecte.")");

		$Result = $Cte->CTeSend('CTeRecepcao', $xmlLote, $getLote, $Emissora, $Ambiente);

		if(!in_array($Emissora, $EmissoresGo)){
			$XmlResult = $Result;
			$Result = xml2array($Result,0);
		}

		print_r($Result);
		if($Result['retEnviCte']['cStat']=='103'){

			$Recibo = $Result['retEnviCte']['infRec']['nRec'];

			$QueryUpdate = "UPDATE tb_emicte SET status = 'enviado', recibo = '".$Recibo."', data_envio = '".date("Y-m-d H:i:s")."' WHERE status = 'assinado' AND chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		} else {

			$QueryUpdate = "UPDATE tb_emicte SET status = 'falhaenvio', observacao = 'Erro ao processar o lote! (".$Result['retEnviCte']['cStat'].")', recibo = '".$Recibo."', data_envio = '".date("Y-m-d H:i:s")."' WHERE chavecte IN (".substr($inLote, 0, -1).")";
			$ResultUpdate = mysql_query($QueryUpdate);

		}
}//encerra foreach
	}

}

/*

Fim Validação de Processos

*/

controlaExecucao('datactetransmitelotes', 'S');

?>
