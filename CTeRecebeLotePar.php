<?php

function limpaXml($Xml){

  preg_match_all('#<?xml version="(.*?)" encoding="(.*?)"?><cteProc versao="(.*?)" xmlns="http://www.portalfiscal.inf.br/cte">#', $Xml, $aCabecsEncontrados);
  $Xml = str_replace('<?'.$aCabecsEncontrados[0][0], '', $Xml);
  $Xml = str_replace($aProtsEncontrados[0][0], '', $Xml);

  if(count($aCabecsEncontrados[0])>1) {
    
    $Xml = '<?'.$aCabecsEncontrados[0][0] . $Xml . $aProtsEncontrados[0][0];
    $updateXml = "UPDATE tb_emicte SET xml = '".$Xml."' WHERE chavecte = '".$Row['chavecte']."';";
    $result = mysql_query($updateXml);
    
    if(!$result) {
    
      return $Xml;
    
    }else{
    
      return $Xml;
    
    }
  
  }
  
  return $Xml;

}

function retiraAssinatura($Xml){

  $Xml = preg_replace('#<Signature(.*?)>(.*?)</Signature>#','', $Xml);
  return $Xml;

}

set_time_limit(0);# 3 minutos

include "../../../include/conn.php";

/*

Validação de Processos

*/

if(controlaExecucao('datacterecebelotepar', 'N', 2) == false) {

	exit();

}

include '../Classes/CTeTool.class.php';
include '../Funcoes/xml2array.php';

$Cte = new CTeTool();

/*
PROCESSAMENTO DO "CTeRetRecepcao" PARA RETORNO DE CTEs.
*/

$Versao = '3.00';

$QueryProtocolo = "SELECT recibo, idemissor, tpamb FROM tb_emicte WHERE numLote % 2 = 0 AND status = 'enviado' AND data_envio > DATE_SUB(NOW(), INTERVAL 15 DAY) AND recibo != '' GROUP BY recibo ORDER BY data_emis ASC, prioridade ASC LIMIT 15";
$ResultProtocolo = mysql_query($QueryProtocolo);

while ($RowProtocolo = mysql_fetch_assoc($ResultProtocolo)){

	$Recibo = $RowProtocolo['recibo'];
	$Emissora = $RowProtocolo['idemissor'];
	$Ambiente = $RowProtocolo['tpamb'];

	$xmlFile = '<consReciCTe versao="' . $Versao . '" xmlns="http://www.portalfiscal.inf.br/cte"><tpAmb>' . $Ambiente . '</tpAmb><nRec>' . $Recibo . '</nRec></consReciCTe>';

	$Result = $Cte->CTeSend('CTeRetRecepcao', $xmlFile, 0,$Emissora,$Ambiente);

	$QueryDados = mysql_query("SELECT id FROM empresas WHERE emitir_cte = 'S' ORDER BY fantasia");
	while($RowDados = mysql_fetch_assoc($QueryDados)){
		$EmissoresGo[] .= $RowDados['id'];
	}

	if(!in_array($Emissora, $EmissoresGo)){
		$XmlResult = $Result;
		$Result = xml2array($Result,0);
	}


	if($Result['retConsReciCTe']['cStat'] == '104'){

		$QtdCtesRetornados = count($Result['retConsReciCTe']['protCTe']);
		for($i=0;$i<$QtdCtesRetornados;$i++){
	
			if(in_array($Emissora, $EmissoresGo)) {
				$qtdCTe = 0;
			} else {
				$qtdCTe = 1;
			}
			if($QtdCtesRetornados>$qtdCTe) {
				$DadosCte = $Result['retConsReciCTe']['protCTe'][$i];
			} else {
				$DadosCte = $Result['retConsReciCTe']['protCTe'];
			}

			$Chave = $DadosCte['infProt']['chCTe'];
			$DataRec = $DadosCte['infProt']['dhRecbto'];
			$Nprot = $DadosCte['infProt']['nProt'];
			$DigVal = $DadosCte['infProt']['digVal'];
			$Cstat = $DadosCte['infProt']['cStat'];
			$Xmotivo = $DadosCte['infProt']['xMotivo'];
			$verAplic = $DadosCte['infProt']['verAplic'];
			$numCte = substr($Chave, 25, 9);

			$QueryXml = "SELECT xml FROM tb_emicte WHERE chavecte = '" . $Chave . "' AND idemissor = '" . $Emissora . "';";
			$ResultXml = mysql_query($QueryXml);
			$RowXml = mysql_fetch_assoc($ResultXml);

			$XmlBody = $RowXml['xml'];

			if (($Nprot != '') && ($DigVal != '')) {

				$nProtDigVal = '<nProt>'.$Nprot.'</nProt><digVal>'.$DigVal.'</digVal>';

			} else {

				$nProtDigVal = '';

			}

			echo "Cstat: $Cstat\n<br>";
			if ($Cstat == '100'){

				$XML = '<?xml version="1.0" encoding="UTF-8"?><cteProc versao="'.$Versao.'" xmlns="http://www.portalfiscal.inf.br/cte">';
				$XML.= $XmlBody;
				$XML.= '<protCTe versao="'.$Versao.'"><infProt><tpAmb>'.$Ambiente.'</tpAmb><verAplic>'.$verAplic.'</verAplic><chCTe>'.$Chave.'</chCTe><dhRecbto>'.$DataRec.'</dhRecbto>'.$nProtDigVal.'<cStat>'.$Cstat.'</cStat><xMotivo>'.$Xmotivo.'</xMotivo></infProt></protCTe>';
				$XML.= '</cteProc>';

				$DataEmis = date("Y-m-d H:i:s");

				// Adicionado validacao para so salvar XML que corresponda a chave indicada.
				
				$aXMLBody = xml2array(retiraAssinatura(limpaXml($XmlBody)),0);

				$num_cte = $aXMLBody['CTe']['infCte']['ide']['nCT'];
				$emit_CNPJ = $aXMLBody['CTe']['infCte']['emit']['CNPJ'];

				$QueryEmpresas = "SELECT id FROM empresas WHERE cnpj = '" . $emit_CNPJ . "';";
				$ResultEmpresas = mysql_query($QueryEmpresas);
				$RowEmpresas = mysql_fetch_assoc($ResultEmpresas);

				$id_emissor = $RowEmpresas['id'];

				if($numCte == $num_cte && $Emissora == $id_emissor) {

					$UpdateStatus = "UPDATE tb_emicte SET status = 'autorizado', data_retorno = '".$DataEmis."', observacao = '".$Xmotivo."', num_status= '".$Cstat."', protocolo = '".$Nprot."', xml = '".$XML."' WHERE chavecte = '".$Chave."'";
					$ResultUpdateStatus = mysql_query($UpdateStatus);	

				} else {

					$UpdateStatus = "UPDATE tb_emicte SET status = 'rejeitado', data_retorno = '".$DataEmis."', observacao = 'Falha na validação dos dados do XML (" . $numCte . " / " . $num_cte . " - " . $Emissora .  " / " . $id_emissor . "))', num_status= '".$Cstat."', protocolo = '".$Nprot."', xml = '".$XML."' WHERE chavecte = '".$Chave."'";
					$ResultUpdateStatus = mysql_query($UpdateStatus);

				}

			} else if($Cstat == '204'){
				$DataEmis = date("Y-m-d H:i:s");

				$UpdateStatus = "UPDATE tb_emicte SET status = 'presoretorno', data_retorno = '".$DataEmis."', observacao = '".$Xmotivo."',num_status= '".$Cstat."' WHERE chavecte = '".$Chave."'";
				$ResultUpdateStatus = mysql_query($UpdateStatus);

			} else if($Cstat == '210' || $Cstat == '232' || $Cstat == '233' || $Cstat == '424' || $Cstat == '426' || $Cstat == '427' || $Cstat == '533') {
	
				$DataEmis = date("Y-m-d H:i:s");

				$aDados = array();

				$QueryPedidos = "SELECT id, dest_ie, bairro, cidade, uf FROM pedidos WHERE ChaveCte = '" . $Chave . "' AND dataentrega >= DATE_SUB(CURDATE(), INTERVAL 90 DAY);";
				$ResultPedidos = mysql_query($QueryPedidos);

				while($RowPedidos = mysql_fetch_assoc($ResultPedidos)) {

					$DadosAntigos = array('id' => $RowPedidos['id'], 'ie' => $RowPedidos['dest_ie'], 'bairro' => $RowPedidos['bairro'], 'cidade' => $RowPedidos['cidade'], 'uf' => $RowPedidos['uf']);
					array_push($aDados, $DadosAntigos);

				}

				// $UpdateStatus = "UPDATE tb_emicte SET status = 'inutilizado', id_pedido = '', idgrupo = '0', data_retorno = '".$DataEmis."', observacao = '".$Xmotivo."',num_status= '".$Cstat."', observacao_cte = '" . json_encode($aDados) . "' WHERE chavecte = '".$Chave."'";
				// $ResultUpdateStatus = mysql_query($UpdateStatus);

				$aXMLBody = xml2array(retiraAssinatura(limpaXml($XmlBody)),0);

				$dest_CNPJ = $aXMLBody['CTe']['infCte']['dest']['CNPJ'];

				// $url = 'https://www.sintegraws.com.br/api/v1/execute-api.php?token=7C401215-C814-42C2-B165-636C382D7A55&cnpj=' . $dest_CNPJ . '&plugin=ST';
				// $json = file_get_contents($url);
				// $json = json_decode($json, TRUE);

				// $UpdatePedido = "UPDATE pedidos SET dest_ie = '" . $json['inscricao_estadual'] . "', bairro = '" . $json['bairro'] . "', cidade = '" . $json['municipio'] . "', uf = '" . $json['uf'] . "' WHERE ChaveCte = '" . $Chave . "' AND dataentrega >= DATE_SUB(CURDATE(), INTERVAL 90 DAY);";
				// $ResultUpdatePedido = mysql_query($UpdatePedido);

				$DataEmis = date("Y-m-d H:i:s");

				$UpdateStatus = "UPDATE tb_emicte SET status = 'rejeitado', data_retorno = '" . $DataEmis . "', observacao = '" . $Xmotivo . "',num_status= '" . $Cstat . "' WHERE chavecte = '" . $Chave . "'";
				$ResultUpdateStatus = mysql_query($UpdateStatus);

			} else {

				$DataEmis = date("Y-m-d H:i:s");

				$UpdateStatus = "UPDATE tb_emicte SET status = 'rejeitado', data_retorno = '".$DataEmis."', observacao = '".$Xmotivo."',num_status= '".$Cstat."' WHERE chavecte = '".$Chave."'";
				$ResultUpdateStatus = mysql_query($UpdateStatus);

			}

		}

	} elseif ($Result['retConsReciCTe']['cStat']=='105' || $Result['retConsReciCTe']['cStat']=='107' || $Result['retConsReciCTe']['cStat']=='108' || $Result['retConsReciCTe']['cStat']=='109'){

		echo 'Aguarde!';
		continue;

	}elseif($Result['retConsReciCTe']['cStat']=='678' ){
		echo 'Rejeição por Uso indevido';

		$UpdateStatus = "UPDATE tb_emicte SET status = 'rejeitado', data_retorno = '" . $DataEmis . "', observacao = 'Rejeicao por Uso indevido', num_status = '" . $Result['retConsReciCTe']['cStat'] . "' WHERE recibo = '" . $Recibo . "' AND status = 'enviado';";
		$ResultUpdateStatus = mysql_query($UpdateStatus);

		controlaExecucao('datacterecebelotepar', 'S');
		exit();

	} else {

		$Cstat = $Result['retConsReciCTe']['cStat'];
		$Xmotivo = $Result['retConsReciCTe']['xMotivo'];
		$nRec = $Result['retConsReciCTe']['nRec'];
		$DataEmis = date("Y-m-d H:i:s");

		$UpdateRejeitados = "UPDATE tb_emicte SET status = 'rejeitado', num_status = '".$cStat."', observacao = '".$Xmotivo."', data_retorno = '".$DataEmis."' WHERE recibo = '".$nRec."' AND status = 'enviado'";
		$ResultUpdateRejeitados = mysql_query($UpdateRejeitados);
		continue;

	}

}

/*

Fim Validação de Processos

*/

controlaExecucao('datacterecebelotepar', 'S');

?>