<?php
ignore_user_abort(true);

$Simpress = array('5a4775bce6db2e5f576d06d5ec23bf39', '3d865f1b44cab4d8e8e5e8d99435395f', 'cd244e9bf90338f2da40b86edb579700', '4502b0a1696393d6db1f852b564af4f0', 'a83265eb9fcf4d4c406cf55b3836c952', '81550144f52ecda6ac2f676582e5789b');

/* $arrayCorrecao = array('');

//quando precisar forçar layout, basta colocar os numero de pedidos aqui e respectivamente alterar o arquivo do include
if(in_array($_POST['ncs'],$arrayCorrecao)){
include("CTeGeracte_Debug.php");
exit();
}*/

//alterado no dia 07/10/2019, por Augusto
//inclusão do QRCode na emissão do CTe, segundo alterações da SEFAZ (vigor à partir do dia 07/10/2019)
//https://drive.google.com/file/d/13oNWs3pSFemyYTocDNXFWFBsKxeMXv8h/view
//informações referente ao QR Code estão disponíveis na página 132 do link acima

// exit();

//func calculaPeso fica em sistema.init.include
try {

	date_default_timezone_set("America/Sao_Paulo");
	set_time_limit(60);
	$bHome = true;

	include("/var/www/sistemas/quality/include/init.include");
	include($SsPathToInclude . "/sistema/sistema.init.include");
	include($SsPathToHtml . '/sistema/integracoes/funcoes/xml2array.php');

	$dataCorteCte = date("Y-m-d H:i:s", strtotime("NOW - 4 MONTH"));
	$dataEntregaCorte = date('Y-m-d', strtotime('NOW - 4 MONTH'));

	function validaCte($CteFile)
	{

		$Xml = file_get_contents($CteFile);
		$postvars = array(
			'txtCTe' => $Xml,
			'submit1' => 'Validar'
		);

		$header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
		$header[] = 'Accept-Encoding: gzip, deflate, br';
		$header[] = 'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
		$header[] = 'Cache-Control: max-age=0';
		$header[] = 'Connection: keep-alive';
		$header[] = 'Content-Type: application/x-www-form-urlencoded';
		$header[] = 'Cookie: AffinitySefaz=62fb43fc089483bd561923232969a05b84477430b1a2f4afa188f69ceaa23d72; ticketSessionProviderSS=fdc8490a46914be3b0d9e84d5dd33f30; ASPSESSIONIDSUTATRTT=DLPBFGODHHJIHABLBFOPBAFI; __utma=195556014.291496837.1489034717.1489034717.1511031524.2; __utmb=195556014.2.10.1511031524; __utmc=195556014; __utmz=195556014.1511031524.2.1.utmcsr=oobj.com.br|utmccn=(referral)|utmcmd=referral|utmcct=/bc/article/como-validar-xml-no-validador-de-mensagens-nf-e-ou-ct-e-da-sefaz-rs-18.html';
		$header[] = 'Host: www.sefaz.rs.gov.br';
		$header[] = 'Referer: https://www.sefaz.rs.gov.br/ASP/AAE_ROOT/CTE/SAT-WEB-CTE-VAL_1.asp';
		$header[] = 'Upgrade-Insecure-Requests: 1';
		$header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://www.sefaz.rs.gov.br/ASP/AAE_ROOT/CTE/SAT-WEB-CTE-VAL_1.asp");
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postvars));
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$Retorno = curl_exec($ch);

		$Retorno = str_replace(chr(13), "", $Retorno);
		$Retorno = str_replace(chr(10), "", $Retorno);

		preg_match("#<td nowrap class='tdValLabel' valign='top'>SCHEMA:</td><td class='tdVal'>(.*?)</td></tr>#", $Retorno, $falhaSchema);

		$novolay = explode('<BR>', $falhaSchema[1]);

		if (trim($novolay[0]) == "The element 'CTe' in namespace 'http://www.portalfiscal.inf.br/cte' has invalid child element 'infCTeSupl' in namespace 'http://www.portalfiscal.inf.br/cte'. List of possible elements expected: 'Signature' in namespace 'http://www.w3.org/2000/09/xmldsig#'.") {

			$falhaSchema[1] = 'OK';
		}

		if ($falhaSchema[1] == 'OK') {

			return '1;XML obedece as regras definidas no arquivo XSD!';
		} else {

			$linhas = explode('<BR>', $falhaSchema[1]);

			$msg = '';
			for ($i = 0; $i < sizeof($linhas); $i++) {

				$strlinha = strtolower($linhas[$i]);
				preg_match("#the 'http://www.portalfiscal.inf.br/cte:(.*?)' element is invalid#", $strlinha, $tag);
				preg_match("#- the value '(.*?)' is invalid#", $strlinha, $value);

				if (strlen($tag[1]) > 1) {

					switch ($tag[1]) {

						case 'cmunfim':
						case 'xmunfim':
						case 'cmunini':
						case 'xmunini':
						case 'cmun':
						case 'xmun':
							$tipoerro = 'Verifique o municipio da entrega.';
							break;
						case 'nro':
							$tipoerro = 'Verifique o numero do endereco da entrega.';
							break;
						case 'xbairro':
							$tipoerro = 'Verifique o bairro do endereco da entrega.';
							break;
						case 'cnpj':
							$tipoerro = 'Verifique o cnpj do remetente ou destinatario da entrega.';
							break;
						case 'cpf':
							$tipoerro = 'Verifique o cpf do remetente ou destinatario da entrega.';
							break;
						case 'ie':
							$tipoerro = 'Verifique a inscricao estadual (ie) do remetente ou destinatario da entrega.';
							break;
						case 'xnome':
						case 'xfant':
							$tipoerro = 'Verifique o nome do remetente ou destinatario da entrega.';
							break;
						case 'xlgr':
							$tipoerro = 'Verifique o logradouro do endereco da entrega.';
							break;
						case 'cep':
							$tipoerro = 'Verifique o cep do endereco da entrega.';
							break;
						case 'uf':
							$tipoerro = 'Verifique o uf do endereco da entrega.';
							break;
						case 'vtprest':
						case 'vrec':
						case 'vcomp':
							$tipoerro = 'Verifique o valor do frete da entrega.';
							break;
						case 'vcarga':
							$tipoerro = 'Verifique o valor da nota fiscal de entrega.';
							break;
						case 'chave':
						case 'ndoc':
							$tipoerro = 'Verifique a chave de acesso cadastrada no pedido.';
							break;
						default:
							$tipoerro = 'Erro desconhecido.';
							break;
					}

					$msg .= 'Tag invalida: ' . $tag[1] . ' - O valor informado [' . $value[1] . '] nao esta valido. ' . $tipoerro . '<br>';
				}
			}

			$msg = $falhaSchema[1];

			return '0;' . $msg;
		}
	}

	function limpaXml($Xml)
	{

		preg_match_all('#<?xml version="(.*?)" encoding="(.*?)"?><cteProc versao="(.*?)" xmlns="http://www.portalfiscal.inf.br/cte">#', $Xml, $aCabecsEncontrados);
		$Xml = str_replace('<?' . $aCabecsEncontrados[0][0], '', $Xml);
		$Xml = str_replace($aProtsEncontrados[0][0], '', $Xml);

		if (count($aCabecsEncontrados[0]) > 1) {

			$Xml = '<?' . $aCabecsEncontrados[0][0] . $Xml . $aProtsEncontrados[0][0];
			$updateXml = "UPDATE tb_emicte SET xml = '" . $Xml . "' WHERE chavecte = '" . $Row['chavecte'] . "';";
			$result = mysql_query($updateXml);

			if (!$result) {

				return $Xml;
			} else {

				return $Xml;
			}
		}

		return $Xml;
	}

	function retiraAssinatura($Xml)
	{

		$Xml = preg_replace('#<Signature(.*?)>(.*?)</Signature>#', '', $Xml);
		return $Xml;
	}

	function getUfIBGE($Uf)
	{
		$QueryIBGE = "SELECT codigo FROM municipios_ibge WHERE uf = '" . $Uf . "' LIMIT 1";
		$ResultIBGE = mysql_query($QueryIBGE);
		$RowIBGE = mysql_fetch_assoc($ResultIBGE);
		$RetUf = substr($RowIBGE['codigo'], 0, 2);
		return $RetUf;
	}

	function getMunIBGE($Municipio, $Uf)
	{
		global $Utils;

		$Municipio = str_replace('.', '%', $Municipio);
		$Municipio = str_replace(' ', '%', $Municipio);
		$RowIBGE = $Utils->query("SELECT codigo FROM municipios_ibge WHERE nome LIKE '" . $Municipio . "' AND uf = '" . $Uf . "' LIMIT 1",1);

		if (!$RowIBGE) {
			sitevGravarLog("municipio nao encontrado", "Municipio " . $Municipio . " - " . $Uf, "", $QueryIBGE);
			return false;
		}

		$RetMun = $RowIBGE['codigo'];
		return $RetMun;
	}

	// function getAliquota($cMunIni, $cMunFim, $IE, $CnpjCliente) {
	// 	if (substr($cMunIni, 0, 2) != substr($cMunFim, 0, 2)) {
	// 		if ($IE == 'ISENTO') {
	// 			$Aliquota = '12';
	// 		} else {
	// 			if ((substr($cMunFim, 0, 1) != '3') && (substr($cMunFim, 0, 1) != '4') && (substr($cMunFim, 0, 1) != '5')) {
	// 				$Aliquota = '7';
	// 			} else {
	// 				if (substr($cMunFim, 0, 2) == '32') {
	// 					$Aliquota = '7';
	// 				} else {
	// 					$Aliquota = '12';
	// 				}
	// 			}
	// 		}
	// 	} else {
	// 		$Aliquota = '12';
	// 	}
	// 	return $Aliquota;
	// }

	// function getAliquota($cMunIni, $cMunFim, $IE, $CnpjCliente) {
	// 	if (substr($cMunIni, 0, 2) != substr($cMunFim, 0, 2)) {
	// 		if ($IE == 'ISENTO') {
	// 			$Aliquota = '12';
	// 		} else {

	// 			if(substr($cMunIni, 0, 2) == '32' && substr($cMunFim, 0, 2) == '35' && $CnpjCliente == '81887838000736'){

	// 				$Aliquota = '0';
	// 			}
	// 			else if ((substr($cMunFim, 0, 1) != '3') && (substr($cMunFim, 0, 1) != '4') && (substr($cMunFim, 0, 1) != '5')) {
	// 				$Aliquota = '7';
	// 			} else {
	// 				if (substr($cMunFim, 0, 2) == '32') {
	// 					$Aliquota = '7';
	// 				} else {
	// 					$Aliquota = '12';
	// 				}
	// 			}
	// 		}
	// 	} else {
	// 		$Aliquota = '12';
	// 	}
	// 	return $Aliquota;
	// } desativado 18/01/2018

	// function getAliquota($cMunIni, $cMunFim, $IE, $CnpjCliente) {
	// 	if (substr($cMunIni, 0, 2) != substr($cMunFim, 0, 2)) {
	// 		if ($IE == 'ISENTO' && ($CnpjCliente != '07173013000292' && $CnpjCliente != '07173013000373')) {
	// 			$Aliquota = '12';
	// 		} else {

	// 			if(substr($cMunIni, 0, 2) == '32' && substr($cMunFim, 0, 2) == '35' && $CnpjCliente == '81887838000736'){

	// 				$Aliquota = '0';
	// 			}
	// 			else if (substr($cMunIni, 0, 2) == '35' && substr($cMunFim, 0, 2) == '52') {
	// 				$Aliquota = '7';
	// 			} 
	// 			else if (substr($cMunIni, 0, 2) == '35' && substr($cMunFim, 0, 2) == '53') {
	// 				$Aliquota = '7';
	// 			} 
	// 			else if ((substr($cMunFim, 0, 1) != '3') && (substr($cMunFim, 0, 1) != '4') && (substr($cMunFim, 0, 1) != '5')) {
	// 				$Aliquota = '7';
	// 			}
	// 			else {
	// 				if (substr($cMunFim, 0, 2) == '32') {
	// 					$Aliquota = '7';
	// 				} else {
	// 					$Aliquota = '12';
	// 				}
	// 			}
	// 		}
	// 	} else {
	// 		$Aliquota = '12';
	// 	}
	// 	return $Aliquota;
	// } desativado 06/07/2018

	// Solicitado por Suelen 05/07/2018
	function getAliquota($cMunIni, $cMunFim, $IE, $UFCliente, $Dest_cnpj)
	{

		$Aliquota = '0';
		$aSulSudesteExcetoES = array('31', '33', '35', '41', '42', '43');
		$aNorteNordesteCentroOesteES = array('11', '12', '13', '14', '15', '16', '17', '21', '22', '23', '24', '25', '26', '27', '28', '29', '50', '51', '52', '53', '32');

		// REGRAS PARA INICIO DE PRESTACAO EM SP
		if (substr($cMunIni, 0, 2) == '35') {

			// // SE DESTINATARIO NAO-CONTRIBUINTE
			// if(strlen($Dest_cnpj) != 11 && $IE == 'ISENTO') {

			// 	$Aliquota = '12';

			// // SE O DESTINO FOR SP
			// } else 

			if (substr($cMunFim, 0, 2) == '35') {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM GO
		} else if (substr($cMunIni, 0, 2) == '52') {

			// SE DESTINATARIO NAO-CONTRIBUINTE EM GO
			if ($IE == 'ISENTO' && substr($cMunFim, 0, 2) == '52') {

				$Aliquota = '17';

				// SE DESTINATARIO CONTRIBUINTE EM GO
			} else if ($IE != 'ISENTO' && substr($cMunFim, 0, 2) == '52') {

				$Aliquota = '0';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM DF
		} else if (substr($cMunIni, 0, 2) == '53') {

			// SE DESTINO FOR DF
			if (substr($cMunFim, 0, 2) == '53') {

				$Aliquota = '0';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM ES
		} else if (substr($cMunIni, 0, 2) == '32') {


			// SE DESTINO FOR ES
			if (substr($cMunFim, 0, 2) == '32') {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM MG
		} else if (substr($cMunIni, 0, 2) == '31') {

			// SE DESTINO FOR MG
			if (substr($cMunFim, 0, 2) == '31') {

				// SE O TOMADOR FOR DE MG
				if ($UFCliente == 'MG') {

					$Aliquota = '0';

					// CASO CONTRARIO
				} else {

					$Aliquota = '12';
				}

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM RJ
		} else if (substr($cMunIni, 0, 2) == '33') {

			// SE DESTINO FOR RJ
			if (substr($cMunFim, 0, 2) == '33') {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';
			}

			// REGRAS PARA INICIO DE PRESTACAO EM PR
		} else if (substr($cMunIni, 0, 2) == '41') {

			// SE DESTINO FOR PR
			if (substr($cMunFim, 0, 2) == '41') {

				// SE O TOMADOR FOR DE PR
				if ($UFCliente == 'PR') {

					$Aliquota = '0';

					// CASO CONTRARIO
				} else {

					$Aliquota = '12';
				}

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';
			}
		} else if (substr($cMunIni, 0, 2) == '42') {

			if (in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

				// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if (in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';
			}
		}

		return $Aliquota;
	}

	function limpaChars($string)
	{
		$newstring = preg_replace("/[^a-zA-Z0-9_.]/", " ", strtr($string, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC "));
		$newstring = str_replace('  ', '', $newstring);
		return trim(strtoupper($newstring));
	}

	function abrev($string)
	{
		$newstring = '';
		if (strlen($string) > 50) {
			$palavras = explode(' ', $string);
			foreach ($palavras as $key => $value) {
				if ($key > 0) {
					if (strlen($value) > 3) {
						$value = substr($value, 0, 4) . '.';
					} else {
						$value = $value . ' ';
					}
					$newstring .= $value;
				} else {
					$newstring .= $value . ' ';
				}
			}
			return $newstring;
		} else {
			return $string;
		}
	}



	// Início da página
	$aNcs = (isset($_POST['ncs'])) ? explode(',', $_POST['ncs']) : explode(',', $_GET['ncs']);
	$aNcs = array_unique($aNcs); // Remove possiveis valores duplicados do array
	$NumNcs = count($aNcs);
	$prioridade = (isset($_POST['prioridade']) && $_POST['prioridade'] == '1') ? '1' : '0';
	$id_usuario = (isset($_POST['idusuario'])) ? $_POST['idusuario'] : $_GET['idusuario'];
	$outro_tomador = (isset($_POST['outro_tomador'])) ? $_POST['outro_tomador'] : $_GET['outro_tomador'];

	$CtesEnviados = 0;
	$cont = 0;

	while ($cont < $NumNcs) {
		$Nc = $aNcs[$cont];

		if (strlen($Nc) < 1) {

			$cont++;
			continue;
		}

		$aRows=$Utils->query("SELECT chavecte FROM tb_emicte WHERE id_pedido = " . $pdo->quote($Nc) . " AND status NOT IN ('cancelado','nuvem','substituido') AND data_emis >= '" . $dataCorteCte . "'");

		if (sizeOf($aRows)>0 && (!isset($_POST['tpcte']) || $_POST['tpcte'] != '1')) {
			print_r($aRows);
			echo "CTE do pedido ".$Nc." já gerado anteriormente.<br />\n";
			$cont++;
			continue;
		}

		$RowEmissao = $Utils->query("SELECT tipo, idcliente, numerocontrole, fretevalor, ChaveCte, dataentrega, cidade FROM pedidos WHERE id = '" . $Nc . "' AND (dataentrega >= '" . $dataEntregaCorte . "' OR dataentrega = '0000-00-00') LIMIT 1",1);
		
		//Adicionado cliente oncotech (6c9d53f9f75a7b68cbeeac9cd211dffa) para emitir CTes a cima de 16k. - nicolas - 03/02/2020
		if ($Nc != 'd00d56f131634a09ae908c55589c64e0') {
			if ($RowEmissao["fretevalor"] > 16000 && ($RowEmissao['idcliente'] != 'ca608fedc38b94564f13572436a137ab' && $RowEmissao['idcliente'] != '6c9d53f9f75a7b68cbeeac9cd211dffa')) {
				$GsEmailAdministrador = array("ti.desenvolvimento@qualityentregas.com.br");
				glbvErro("Tentando gerar CTE acima de R$ 16.000,00");
				$naoEmitidos .= "CTE " . $Nc . " não emitido! Frete acima de 16.000,00!\n";
				$cont++;
				continue;
			}
		}

		if ($RowEmissao['tipo'] == "retirada") {

			$cont++;
			continue;
		}

		// if($_POST['tpcte'] == '1'){

		// 	$GetAutorizados = "SELECT x2.status FROM tb_emicte_grupos x1 JOIN tb_emicte x2 ON x1.id = x2.idgrupo WHERE x1.idpedido = '". $Nc ."';";
		// 	$ResAutorizados = mysql_query($GetAutorizados);
		// 	$RowRow = mysql_fetch_assoc($ResAutorizados);

		// 	$Autorizados = "SELECT status FROM tb_emicte WHERE id_pedido = '". $Nc ."';";
		// 	$ResultAutorizados = mysql_query($Autorizados);
		// 	$RowAutorizados = mysql_fetch_assoc($ResultAutorizados);

		// 	if($RowAutorizados['status'] == 'autorizado' || $RowRow['status'] == 'autorizado'){

		// 		if($RowAutorizados['status'] == 'autorizado'){

		// 			$retornoQuery = $Autorizados;
		// 		}else if($RowRow['status'] == 'autorizado'){

		// 			$retornoQuery = $GetAutorizados;
		// 		}

		// 		echo 'CTe já autorizado para o id';
		// 		// $From = 'sistema@qualityentregas.com.br';
		// 		// $RealName = 'Sistema - Quality Entregas';
		// 		// $To = 'ti.desenvolvimento@qualityentregas.com.br';
		// 		// $Copy = '-c silva@qualityentregas.com.br';
		// 		// $Subject = 'CTe Autorizado - ' . date('d/m/Y');

		// 		// $mensagem = '<em>Olá!</em><p><em>Segue o retorno da query de CTe Autorizado.</em></p><br>';
		// 		// $mensagem .= $retornoQuery;
		// 		// $mensagem .= '<p><em>Att.</p><p>Quality Entregas - T.I.</em></p>';
		// 		// file_put_contents($SsPathToHtml.'/sistema/ferramentas/contentEmailPorcentagem.html', $mensagem);

		// 		// $CmdMail = 'mutt -e "set content_type=text/html charset=iso-8859-1 from=' . $From . ' realname=\'' . $RealName . '\'" ' . $Copy . ' -s "' . $Subject . '" -- "' . $To . '" < /var/www/sistemas/quality/secure/sistema/ferramentas/contentEmailPorcentagem.html > /dev/null';
		// 		// // shell_exec($CmdMail);
		// 		continue;

		// 	}

		// }

		
		$IdCliente = $RowEmissao['idcliente'];
		print_r($RowEmissao);

		if ($RowEmissao['numerocontrole'] != '') {

			if ($RowEmissao['fretevalor'] > 0.00) {

				####### VERIFICA MODAL

				$modal = '01';

				$aRows = $Utils->query("SELECT id FROM pedidosmodal WHERE modal = 'aereo' AND idpedido = '" . $Nc . "'",1);
				if ($aRows) {
					$modal = '02';
					$QueryEmissor = "SELECT *, 'Medicamentos' AS prodPredominante, 'N' AS bloqsp, 'N' AS bloqspestado FROM empresas WHERE id = '17' LIMIT 1";
				} else {
					$QueryEmissor = "SELECT x2.*, x1.prodPredominante, x1.bloqsp, x1.bloqspestado FROM clientes x1 JOIN empresas x2 ON x1.EmissoraCte = x2.id WHERE x1.id = '" . $IdCliente . "' LIMIT 1";
				}

				####### PEGA DADOS DA EMPRESA EMISSORA

				$RowEmissor = $Utils->query($QueryEmissor,1);

				if ($RowEmissor['id'] == '' || $RowEmissor['id'] == '0') {
					echo 'Cliente sem empresa emissora!';
					exit();
				}

				######## GERA E RESERVA O NUMERO DO CT-E POR EMPRESA EMISSORA

				// Se já tiver um CTE emitido, é porque esse script foi chamado anteriormente e não é necessário gerar novamente.
				$aRow = $Utils->query("SELECT numCte FROM tb_emicte WHERE status = 'gerado' AND id_pedido = '" . $Nc . "' LIMIT 1",1);
				
				if ($aRow) {
					echo "CTE já gerado para este pedido.";
					exit();
				}

				// Garantindo que a query irá gerar uma exception em caso de erro
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    	$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		    	try {
					if (!$pdo->beginTransaction()) {
						echo "Não foi possível obter o número do CTE!";
						exit();
					}

					$aRow = $Utils->query("INSERT INTO tb_emicte (numCte, idemissor, observacao, id_pedido, idusuarioemitiu, data_emis) 
										   SELECT MAX(numCte)+1 AS nextCte, '" . $RowEmissor['id'] . "' AS idemissor, 'CTeGeracte.php' AS observacao, 
										   '" . $Nc . "' AS id_pedido, '" . $id_usuario . "' AS idusuarioemitiu, '" . date('Y-m-d H:i:s') . "' AS data_emis
										   FROM tb_emicte WHERE idemissor='" . $RowEmissor['id'] . "'");

					$id_cte = $pdo->lastInsertId();
					$pdo->commit();
		    	}
				catch (Exception $e) {
					$pdo->rollBack();
					echo "Não foi possível gerar o CTE. Tente novamente. Erro: ".$e->getMessage();
					glbvErro("Não foi possível gerar o CTE. Erro: ".$e->getMessage());
					exit();
				}
				

				$aRow = $Utils->query("SELECT numCte FROM tb_emicte WHERE id_cte='".$id_cte."' LIMIT 1",1);

				if (!$aRow) {
					echo "Houve um problema na inserção do CTE!";
					exit();
				}

				$nextId = $aRow["numCte"];

				####### GERA CHAVE CTE

				$cUF = limpaChars(substr($RowEmissor['ibge_municipio'], 0, 2)); # Cod. UF (IBGE)
				$cUFEmis = $cUF;
				$AAMM = date("ym"); # Ano e Mes
				$CNPJ = limpaChars($RowEmissor['cnpj']); #CNPJ do emitente
				$mod = '57'; # Modelo do Documento Fiscal
				$serie = '001'; # Serie do Documento Fiscal

				$Seq = $nextId;

				$nCT = trim(str_pad($Seq, 9, "0", STR_PAD_LEFT)); # Sequencial - Numero do Documento Fiscal
				$tpEmis = '1'; # Forma de emissao
				$cCT = trim(str_pad($Seq, 8, "0", STR_PAD_LEFT)); # Codigo Numerico que compoe a Chave de Acesso

				$chaveSemDigito = "$cUF" . "$AAMM" . "$CNPJ" . "$mod" . "$serie" . "$nCT" . "$tpEmis" . "$cCT";

				$Soma = 0;

				$j = 2;
				$i = 1;

				while ($i < 44) {

					if ($j < 10) {

						$Dig = substr($chaveSemDigito, -$i, 1);
						$Val = $Dig * $j;

						$j++;
					} else {

						$j = 2;

						$Dig = substr($chaveSemDigito, -$i, 1);
						$Val = $Dig * $j;

						$j++;
					}

					$Soma += $Val;

					$i++;
				}

				$Resto = $Soma % 11;

				if (($Resto == 0) or ($Resto == 1)) {

					$Digito = 0;
				} else {

					$Digito = 11 - $Resto;
				}

				$Chave = "$chaveSemDigito" . "$Digito";

				####### FIM - GERA CHAVE CTE
				## PEGA CNPJ DO CLIENTE

				$QueryCnpj = "SELECT cpfcnpj, nfsterceiros, rgie, cliente, dddtelefone, telefone, endereco, numero, complemento, bairro, cidade, cep, estado, email1 FROM clientes WHERE id = '" . $IdCliente . "'";
				$ResultCnpj = mysql_query($QueryCnpj);
				$RowCnpj = mysql_fetch_assoc($ResultCnpj);

				$CnpjCliente = $RowCnpj['cpfcnpj'];

				##
				$RowEmissao['tipo'] = ($RowEmissao['tipo'] == 'devolucaoparcial') ? 'devolucao' : $RowEmissao['tipo'];

				if ($RowEmissao['tipo'] == 'devolucao') {
					// Query Modificada para devolucao
					$Query = "SELECT
					x3.cont,
					x1.id,
					x3.idusuariocadastrou,
					x3.idusuarioconfirmou,
					x3.idusuariocancelou,
					x3.idusuarioalterou,
					x3.identregador,
					x3.idcoleta,
					x3.idcliente,
					x3.idmotivo,
					x3.idpagamento,
					x3.idmalote,
					x3.coleta,
					x3.tipo,
					x3.tipoextra,
					x3.periodo,
					x3.numerocontrole,
					x1.chaveacesso,
					x3.motivosemchave,
					x3.cep,
					x3.logradouro,
					x3.entreganumero,
					x3.entregacompl,
					x3.bairro,
					x3.cidade,
					x3.uf,
					x3.dest_cpfcnpj,
					x3.dest_ie,
					x1.valor,
					x3.seguro,
					x3.km,
					x3.embalagem,
					x3.cubagemcomprimento,
					x3.cubagemlargura,
					x3.cubagemaltura,
					x3.entreguepeloentregador,
					x3.peso,
					x3.soentregar,
					x3.horamarcada,
					x3.horamarcadahora,
					x3.horamarcadaminuto,
					x3.tde,
					x3.tdehorainicio,
					x3.tdeminutoinicio,
					x3.tdehorafim,
					x3.tdeminutofim,
					x1.fretevalor,
					x3.freteentregador,
					x3.pagarfreteentregador,
					x3.observacao,
					x3.status,
					x3.autorizadopor,
					x3.recebidopor,
					x3.dataretorno,
					x3.dataentrega,
					x3.dataconfirmacao,
					x3.dataalteracao,
					x3.datacadastro,
					x3.idfinfechamento,
					x3.cobrarfretecliente,
					x3.foradehoravalor,
					x3.datacancelamento,
					x3.destinatario,
					x3.homecare,
					x3.idusuarioauditou,
					x3.dataauditoria,
					x3.malote,
					x3.NumCte,
					x3.EmisCte,
					x3.ChaveCte,
					x3.idregional,
					x3.idregiao,
					x3.origem,
					x3.transferencia,
					x3.numtransferencia,
					x3.auditmei,
					x3.idusuarioauditmei,
					x3.meipagar,
					x3.usrauditpagentregador,
					x3.datapagentregador,
					x3.valpagentregador,
					x3.file_ref,
					x3.idpedidooriginal,
					x3.devolvecarga,
					x3.protcliente,
					x3.lat_entrega,
					x3.lon_entrega,
					x3.lat_baixa,
					x3.lon_baixa,
					x3.fretepeso,
					x3.advalorem,
					x3.gaveta,
					x3.cotado,
					x3.composicaofrete,
					x3.chegadahorario,
					x3.abonarpedido,
					x3.maquina,
					x3.pesocobrado,
					x3.idusuarioreagendou,
					x3.idusuarioconfirmourd,
					x3.tipovolume,
					x3.caminhocanhoto,
					x2.redespacho,
					x2.id AS cli_id,
					IF(LENGTH(x2.cobrancanome)>1, x2.cobrancanome, x2.cliente) AS cli_cliente,
					x2.endereco AS cli_endereco,
					x2.numero AS cli_numero,
					x2.complemento AS cli_complemento,
					x2.bairro AS cli_bairro,
					x2.cidade AS cli_cidade,
					x2.estado AS cli_estado,
					x2.cep AS cli_cep,
					x2.cpfcnpj AS cli_cnpj,
					x2.rgie AS cli_ie,
					x2.cobrancacpfcnpj,
					x2.cobrancaie,
					x2.cobrancanome,
					x2.email1 AS cli_email,
					x2.fator as fator_cli,
					x2.seguro as segcliente,
					IF(x1.chaveacesso != x3.chaveacesso,'S','N') as notadevdest,
					x2.tipo_cte
					FROM pedidos x1
					JOIN clientes x2 ON x1.idcliente = x2.id
					JOIN pedidos x3 ON x1.id != x3.id AND x1.idcliente = x3.idcliente
						AND ((x3.tipo NOT IN('devolucao', 'devolucaoparcial')
						AND x1.tipo IN('devolucao', 'devolucaoparcial')
						AND x1.numerocontrole = x3.numerocontrole
						AND x3.cont < x1.cont) OR (x1.idmalote = x3.idmalote AND x1.idmalote!=''))
					WHERE x1.id = '" . $Nc . "' AND
					x1.fretevalor != '0.00' AND
					((LENGTH(x1.chaveacesso) = 44 OR SUBSTRING(x1.chaveacesso, 1, 3) = 'OUT') AND x1.idcliente != 'bad334a477a92df8cf80627786639ddf')";

					if (!in_array($IdCliente, $Simpress)) { //se for simpress, não aplica essa validação
						$Query .= " AND	(x1.dataentrega >= DATE_SUB(CURDATE(),INTERVAL 15 MONTH) OR x1.dataentrega = '0000-00-00')
	    AND	(x3.dataentrega >= DATE_SUB(CURDATE(),INTERVAL 15 MONTH) OR x3.dataentrega = '0000-00-00')";
					}

					$Query .= " ORDER BY x3.datacadastro DESC LIMIT 1;";

					$Result = mysql_query($Query);
					$Row = mysql_fetch_assoc($Result);

					// if(mysql_num_rows($Result) < 1) {

					// 	$Query = "
					// 	SELECT
					// 	x1.*,
					//  x2.redespacho,
					// 	x2.id AS cli_id,
					// 	x2.cliente AS cli_cliente,
					// 	x2.endereco AS cli_endereco,
					// 	x2.numero AS cli_numero,
					// 	x2.complemento AS cli_complemento,
					// 	x2.bairro AS cli_bairro,
					// 	x2.cidade AS cli_cidade,
					// 	x2.estado AS cli_estado,
					// 	x2.cep AS cli_cep,
					// 	x2.cpfcnpj AS cli_cnpj,
					// 	x2.cobrancacpfcnpj,
					// 	x2.cobrancaie,
					//  x2.cobrancanome,
					// 	x2.rgie AS cli_ie,
					// 	x2.email1 AS cli_email,
					// 	x2.fator as fator_cli,
					// 	x2.seguro as segcliente
					// 	FROM pedidos x1
					// 	JOIN clientes x2 ON x1.idcliente = x2.id
					// 	WHERE x1.id = '" . $Nc . "' AND
					// 	x1.fretevalor != '0.00' AND
					// 	LENGTH(x1.chaveacesso) > 5 AND
					// 	x1.dataentrega >= '" . $dataEntregaCorte . "' ";
					// 	$Result = mysql_query($Query);
					// 	$Row = mysql_fetch_assoc($Result);

					// }

				} else {

					// if($Nc == 'b37e96ebf2604b0cd7ec57fb1b3403b4') {

					// 	$Query = "SELECT
					// 		x1.cont,
					// 		x1.id,
					// 		x1.idusuariocadastrou,
					// 		x1.idusuarioconfirmou,
					// 		x1.idusuariocancelou,
					// 		x1.idusuarioalterou,
					// 		x1.identregador,
					// 		x1.idcoleta,
					// 		x1.idcliente,
					// 		x1.idmotivo,
					// 		x1.idpagamento,
					// 		x1.idmalote,
					// 		x1.coleta,
					// 		x1.tipo,
					// 		x1.tipoextra,
					// 		x1.periodo,
					// 		x1.numerocontrole,
					// 		x1.chaveacesso,
					// 		x1.motivosemchave,
					// 		x1.cep AS cli_cep,
					// 		x1.logradouro AS cli_endereco,
					// 		x1.entreganumero AS cli_numero,
					// 		x1.entregacompl AS cli_complemento,
					// 		x1.bairro AS cli_bairro,
					// 		x1.cidade AS cli_cidade,
					// 		x1.uf AS cli_estado,
					// 		x1.dest_cpfcnpj AS cli_cnpj,
					// 		x2.cobrancacpfcnpj,
					// 		x2.cobrancaie,
					// 		x2.cobrancanome,
					// 		x1.dest_ie AS cli_ie,
					// 		x1.valor,
					// 		x1.seguro,
					// 		x1.km,
					// 		x1.embalagem,
					// 		x1.cubagemcomprimento,
					// 		x1.cubagemlargura,
					// 		x1.cubagemaltura,
					// 		x1.entreguepeloentregador,
					// 		x1.peso,
					// 		x1.soentregar,
					// 		x1.horamarcada,
					// 		x1.horamarcadahora,
					// 		x1.horamarcadaminuto,
					// 		x1.tde,
					// 		x1.tdehorainicio,
					// 		x1.tdeminutoinicio,
					// 		x1.tdehorafim,
					// 		x1.tdeminutofim,
					// 		x1.fretevalor,
					// 		x1.freteentregador,
					// 		x1.pagarfreteentregador,
					// 		x1.observacao,
					// 		x1.status,
					// 		x1.autorizadopor,
					// 		x1.recebidopor,
					// 		x1.dataretorno,
					// 		x1.dataentrega,
					// 		x1.dataconfirmacao,
					// 		x1.dataalteracao,
					// 		x1.datacadastro,
					// 		x1.idfinfechamento,
					// 		x1.cobrarfretecliente,
					// 		x1.foradehoravalor,
					// 		x1.datacancelamento,
					// 		x1.destinatario  AS cli_cliente,
					// 		x1.homecare,
					// 		x1.idusuarioauditou,
					// 		x1.dataauditoria,
					// 		x1.malote,
					// 		x1.NumCte,
					// 		x1.EmisCte,
					// 		x1.ChaveCte,
					// 		x1.idregional,
					// 		x1.idregiao,
					// 		x1.origem,
					// 		x1.transferencia,
					// 		x1.numtransferencia,
					// 		x1.auditmei,
					// 		x1.idusuarioauditmei,
					// 		x1.meipagar,
					// 		x1.usrauditpagentregador,
					// 		x1.datapagentregador,
					// 		x1.valpagentregador,
					// 		x1.file_ref,
					// 		x1.idpedidooriginal,
					// 		x1.devolvecarga,
					// 		x1.protcliente,
					// 		x1.lat_entrega,
					// 		x1.lon_entrega,
					// 		x1.lat_baixa,
					// 		x1.lon_baixa,
					// 		x1.fretepeso,
					// 		x1.advalorem,
					// 		x1.gaveta,
					// 		x1.cotado,
					// 		x1.composicaofrete,
					// 		x1.chegadahorario,
					// 		x1.abonarpedido,
					// 		x1.maquina,
					// 		x1.pesocobrado,
					// 		x1.idusuarioreagendou,
					// 		x1.idusuarioconfirmourd,
					// 		x1.tipovolume,
					// 		x1.caminhocanhoto,
					//		x2.redespacho,
					// 		x2.id AS cli_id,
					// 		x2.cliente AS destinatario,
					// 		x2.endereco AS logradouro,
					// 		x2.numero AS entreganumero,
					// 		x2.complemento AS entregacompl,
					// 		x2.bairro AS bairro,
					// 		x2.cidade AS cidade,
					// 		x2.estado AS uf,
					// 		x2.cep AS cep,
					// 		x2.cpfcnpj AS dest_cpfcnpj,
					// 		x2.rgie AS dest_ie,
					// 		x2.email1 AS cli_email,
					// 		x2.fator as fator_cli,
					// 		x2.seguro as segcliente,
					// 		'N' as notadevdest
					// 		FROM pedidos x1
					// 		JOIN clientes x2 ON x1.idcliente = x2.id
					// 		WHERE x1.id = '" . $Nc . "' AND
					// 		x1.fretevalor != '0.00' AND
					//		LENGTH(x1.chaveacesso) > 5 AND
					// 		(x1.dataentrega >= '" . $dataEntregaCorte . "' OR x1.dataentrega = '0000-00-00');";
					// 	$Result = mysql_query($Query);
					// 	$Row = mysql_fetch_assoc($Result);
					// } else {
					$Query = "SELECT
						x1.*,
						x2.redespacho,
						x2.id AS cli_id,
						IF(LENGTH(x2.cobrancanome)>1, x2.cobrancanome, x2.cliente) AS cli_cliente,
						x2.endereco AS cli_endereco,
						x2.numero AS cli_numero,
						x2.complemento AS cli_complemento,
						x2.bairro AS cli_bairro,
						x2.cidade AS cli_cidade,
						x2.estado AS cli_estado,
						x2.cep AS cli_cep,
						x2.cpfcnpj AS cli_cnpj,
						x2.cobrancacpfcnpj,
						x2.cobrancaie,
						x2.cobrancanome,
						x2.rgie AS cli_ie,
						x2.email1 AS cli_email,
						x2.fator as fator_cli,
						x2.seguro as segcliente,
						x2.tipo_cte
						FROM pedidos x1
						JOIN clientes x2 ON x1.idcliente = x2.id
						WHERE x1.id = '" . $Nc . "' AND
						x1.fretevalor != '0.00' AND
						(x1.idcliente = 'b5b13e9d68b1a525e33de5906a25c202' OR ((LENGTH(x1.chaveacesso) = 44 OR SUBSTRING(x1.chaveacesso, 1, 3) = 'OUT') AND x1.idcliente != 'bad334a477a92df8cf80627786639ddf')) AND
						(x1.dataentrega >= '" . $dataEntregaCorte . "' OR x1.dataentrega = '0000-00-00')";
					$Result = mysql_query($Query);
					$Row = mysql_fetch_assoc($Result);
					// }

				}

				######## ADICIONADO BLOQUEIO DE SP

				if ($RowEmissor['bloqsp'] == 'S' && (strtolower($Row['cidade']) == "sao paulo" || strtolower($Row['cidade']) == "s?o paulo" || strtolower($Row['cidade']) == "são paulo" || strtolower(($Row['cidade'])) == "são paulo" || strtolower(($Row['cidade'])) == "sao paulo" || strtolower(($Row['cidade'])) == "s?o paulo")) {

					$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
					$ResultInutilizado = mysql_query($QueryInutilizado);

					$cont++;
					continue;
				}

				######## ADICIONADO BLOQUEIO DO ESTADO DE SP

				if ($RowEmissor['bloqspestado'] == 'S' && strtolower($Row['uf']) == "sp") {

					$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
					$ResultInutilizado = mysql_query($QueryInutilizado);

					$cont++;
					continue;
				}

				######## ADICIONADO BLOQUEIO DE RJ PARA HERBALIFE

				if ($Row['cli_cnpj'] == '00292858001653' && (strtolower($Row['cidade']) == "rio de janeiro" || strtolower($Row['cidade']) == "rio janeiro" || strtolower($Row['cidade']) == "rj" || strtolower(($Row['cidade'])) == "rio de janeiro" || strtolower(($Row['cidade'])) == "rio janeiro" || strtolower(($Row['cidade'])) == "rj")) {

					$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
					$ResultInutilizado = mysql_query($QueryInutilizado);

					$cont++;
					continue;
				}

				$ChaveNF = trim($Row['chaveacesso']);

				####### GERA O CTE.

				$FatorCubagem = ($Row['fator_cli'] > 0) ? $Row['fator_cli'] : 3000;
				if (strlen($_POST['fretemanual']) > 0) {

					$Row['fretevalor'] = trim(str_replace(',', '.', mysql_real_escape_string($_POST['fretemanual'])));
				}
				# Variaveis para preenchimento do CTe.
				# tag infCte

				$versao = '3.00';
				$Id = 'CTe' . $Chave;

				$XmlBody = '<CTe xmlns="http://www.portalfiscal.inf.br/cte"><infCte Id="' . $Id . '" versao="' . $versao . '">';

				# tag ide

				$QueryTipoCliente = "SELECT tiposervico FROM clientes WHERE id = '" . $IdCliente . "'";
				$ResultTipoCliente = mysql_query($QueryTipoCliente);
				$RowTipoCliente = mysql_fetch_assoc($ResultTipoCliente);

				$TipoServico = $RowTipoCliente['tiposervico'];

				$cUF = limpaChars(substr($RowEmissor['ibge_municipio'], 0, 2)); # Cod. UF (IBGE)
				$cCT = substr($Id, -9, 8);

				$emisUf = strtoupper($RowEmissor['estado']);

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$cliUf = strtoupper($Row['uf']);
					$destUf = strtoupper($Row['cli_estado']);
				} else {
					$cliUf = strtoupper($Row['cli_estado']);
					$destUf = strtoupper($Row['uf']);
				}

				$forPag = '0';
				$mod = '57';
				$serie = '1';
				$nCT = ltrim($cCT, "0");
				$dhEmi = date("Y-m-d") . 'T' . date("H:i:s") . date("P");
				$tpImp = '2';
				$tpEmis = '1';
				$cDV = trim($Digito);

				if (explode('.', $_SERVER['SERVER_NAME'])[0] == 'sistema') {
					$tpAmb = '1';
				} else {
					$tpAmb = '2'; // Homologacao
				}

				if (isset($_POST['tpcte']) && $_POST['tpcte'] != '0') {
					$tpCTe = $_POST['tpcte'];
				} else {
					$tpCTe = '0';
				}

				$procEmi = '0';
				$verProc = '0.01';
				$tpServ = '0';
				$retira = '1';
				$refCTE = trim($Chave);



				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$xMunEnv = trim(strtoupper(limpaChars($RowEmissor['cidade'])));
					$UFEnv = trim(strtoupper(limpaChars($RowEmissor['estado'])));
					$cMunEnv = trim($RowEmissor['ibge_municipio']);
					$cMunIni = trim(getMunIBGE(limpaChars($Row['cidade']), limpaChars($Row['uf'])));
					$xMunIni = trim(strtoupper(limpaChars($Row['cidade'])));
					$UFIni = trim(strtoupper($Row['uf']));
					$cMunFim = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMunFim = limpaChars(trim(strtoupper($Row['cli_cidade'])));
					$UFFim = limpaChars(trim(strtoupper($Row['cli_estado'])));
				} else {
					$xMunEnv = trim(strtoupper(limpaChars($RowEmissor['cidade'])));
					$UFEnv = trim(strtoupper(limpaChars($RowEmissor['estado'])));
					$cMunEnv = trim($RowEmissor['ibge_municipio']);
					$cMunIni = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMunIni = limpaChars(trim(strtoupper($Row['cli_cidade'])));
					$UFIni = limpaChars(trim(strtoupper($Row['cli_estado'])));
					$cMunFim = trim(getMunIBGE(limpaChars($Row['cidade']), limpaChars($Row['uf'])));
					$xMunFim = trim(strtoupper(limpaChars($Row['cidade'])));
					$UFFim = trim(strtoupper($Row['uf']));
				}

				$indIEToma = '1';

				if (strtoupper($Row['cli_ie']) == 'ISENTO') {

					$indIEToma = '2';
				}

				if (strtoupper($Row['cli_ie']) == 'ISENTO' && $Row['idcliente'] == '9a0472cbbcb7603681b25e7c6100b8a7') {

					$indIEToma = '9';
				}

				// BELO HORIZONTE - 3106200 ** DIVINOPOLIS - 3122306 ** SAO PAULO - 3550308 ** JUATUBA - 3136652
				// CONTAGEM - 3118601 ** GOVERNADOR VALADARES - 3127701 ** PATROCINIO - 3148103
				if ($Row['idcliente'] == '0e315ef5f424167a734414e4f30a9005') {
					$UFEnv = "MG";
					$xMunEnv = "MONTES CLAROS";
					$cMunEnv = "3143302";
					$cMunIni = $cMunEnv;
					$xMunIni = $xMunEnv;
					$UFIni = $UFEnv;
					$cMunFim = "3550308";
					$xMunFim = "SAO PAULO";
					$UFFim = $UFEnv;
					$UFFim = "SP";
				}

				if ($outro_tomador == 'S') {

					$CNPJ = trim($Row['cli_cnpj']);
					$IE = limpaChars(trim($Row['cli_ie']));

					$xNome = trim(abrev(strtoupper(limpaChars($Row['cli_cliente']))));
					$xFant = abrev(strtoupper(trim($xNome)));

					$xLgr = strtoupper(trim(limpaChars($Row['cli_endereco'])));
					$nro = trim(limpaChars($Row['cli_numero']));

					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {

						$xCpl = 'x';
					} else {

						$xCpl = $xCpl;
					}

					$xBairro = strtoupper(trim($Row['cli_bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					$email = mb_strtoupper(trim($Row['cli_email']));
					$cPais = '1058';
					$xPais = 'BRASIL';

					$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

					$tomador = '<toma4><toma>4</toma><CNPJ>' . $CNPJ . '</CNPJ><IE>' . $IE . '</IE><xNome>' . $xNome . '</xNome><xFant>' . $xFant . '</xFant>';
					$tomador .= '<enderToma><xLgr>' . $xLgr . '</xLgr><nro>' . $nro . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . $xBairro . '</xBairro><cMun>' . $cMun . '</cMun><xMun>' . $xMun . '</xMun><CEP>' . $CEP . '</CEP><UF>' . $UF . '</UF><cPais>' . $cPais . '</cPais><xPais>' . $xPais . '</xPais></enderToma>';
					$tomador .= '<email>' . $email . '</email></toma4>';
				} else if ($Row['redespacho'] != 'N') {

					# #  toma4

					if ($Row['redespacho'] == 'EDI') {

						$CNPJ = trim($Row['cobrancacpfcnpj']);
						$IE = limpaChars(trim($Row['cobrancaie']));

						$xNome = trim(abrev(strtoupper(limpaChars($Row['cobrancanome']))));
						$xFant = abrev(strtoupper(trim($xNome)));

						$xLgr = strtoupper(trim(limpaChars($Row['cli_endereco'])));
						$nro = trim(limpaChars($Row['cli_numero']));

						$xCpl = limpaChars($Row['cli_complemento']);
						if ($xCpl == '') {

							$xCpl = 'x';
						} else {

							$xCpl = $xCpl;
						}

						$xBairro = strtoupper(trim($Row['cli_bairro']));
						$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
						$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
						$CEP = trim($Row['cli_cep']);
						$UF = strtoupper(trim($Row['cli_estado']));
						$email = mb_strtoupper(trim($Row['cli_email']));
						$cPais = '1058';
						$xPais = 'BRASIL';

						$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

						if($Row['cli_id'] == '19fc460ed4f1c9d764cdece52cee1328' || $Row['cli_id'] == 'a29eac1b104704902420a24fe42ffdb3'){
							echo "#### Validação SUBCONTRATADO ####<br><br>";

							$tomador = '<toma3><toma>1</toma></toma3>';

							$QueryEdi = mysql_query("SELECT chave_cte FROM tb_edi WHERE chave_nf = '" . $Row['chaveacesso'] . "' LIMIT 1");
							$RowEdi = mysql_fetch_assoc($QueryEdi);

							$ChaveCteEdi = $RowEdi['chave_cte'];

						} else {
							$tomador = '<toma4><toma>4</toma><CNPJ>' . $CNPJ . '</CNPJ><IE>' . $IE . '</IE><xNome>' . $xNome . '</xNome><xFant>' . $xFant . '</xFant>';
							$tomador .= '<enderToma><xLgr>' . $xLgr . '</xLgr><nro>' . $nro . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . $xBairro . '</xBairro><cMun>' . $cMun . '</cMun><xMun>' . $xMun . '</xMun><CEP>' . $CEP . '</CEP><UF>' . $UF . '</UF><cPais>' . $cPais . '</cPais><xPais>' . $xPais . '</xPais></enderToma>';
							$tomador .= '<email>' . $email . '</email></toma4>';
						}	

					} else if ($Row['redespacho'] == 'CTE') {

						$QueryXml = mysql_query("SELECT xml, chavecte FROM xml_cte WHERE chavenf = '" . $Row['chaveacesso'] . "'");
						$RowXml = mysql_fetch_assoc($QueryXml);

						$aXMLBody = xml2array(retiraAssinatura(limpaXml(($RowXml['xml']))), 0);

						$CNPJ = trim($aXMLBody['CTe']['infCte']['emit']['CNPJ']);
						$CNPJ_REDESPACHO = $CNPJ;
						$IE = limpaChars(trim($aXMLBody['CTe']['infCte']['emit']['IE']));
						$IE_REDESPACHO = $IE;

						$xNome = trim(abrev(strtoupper(limpaChars($aXMLBody['CTe']['infCte']['emit']['xNome']))));
						$xNomeFant = $xNome;
						$xFant = abrev(strtoupper(trim($xNome)));

						$NumeroCTE = $aXMLBody['CTe']['infCte']['ide']['nCT'];

						$xLgr = strtoupper(trim(limpaChars($Row['cli_endereco'])));
						$nro = trim(limpaChars($Row['cli_numero']));

						$xCpl = limpaChars($Row['cli_complemento']);
						if ($xCpl == '') {

							$xCpl = 'x';
						} else {

							$xCpl = $xCpl;
						}

						$xBairro = strtoupper(trim($aXMLBody['CTe']['infCte']['emit']['enderEmit']['xBairro']));
						$cMun = trim(limpaChars($aXMLBody['CTe']['infCte']['emit']['enderEmit']['cMun']));
						$xMun = strtoupper(trim(limpaChars($aXMLBody['CTe']['infCte']['emit']['enderEmit']['xMun'])));
						$CEP = trim($aXMLBody['CTe']['infCte']['emit']['enderEmit']['CEP']);
						$UF = strtoupper(trim($aXMLBody['CTe']['infCte']['emit']['enderEmit']['UF']));
						$UF_REDESPACHO = $UF;
						$email = mb_strtoupper(trim($Row['cli_email']));
						$cPais = '1058';
						$xPais = 'BRASIL';
						$email = mb_strtoupper(trim($Row['cli_email']));

						// $toma = '0';
						// $tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';

						$tomador = '<toma4><toma>4</toma><CNPJ>' . $CNPJ . '</CNPJ><IE>' . $IE . '</IE><xNome>' . $xNome . '</xNome><xFant>' . $xFant . '</xFant>';
						$tomador .= '<enderToma><xLgr>' . $xLgr . '</xLgr><nro>' . $nro . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . $xBairro . '</xBairro><cMun>' . $cMun . '</cMun><xMun>' . $xMun . '</xMun><CEP>' . $CEP . '</CEP><UF>' . $UF . '</UF><cPais>' . $cPais . '</cPais><xPais>' . $xPais . '</xPais></enderToma>';
						$tomador .= '<email>' . $email . '</email></toma4>';

						// $tpServ = '2';
						$tpServ = '1';

						if ($Row['idcliente'] == '0e315ef5f424167a734414e4f30a9005') {
							$tpServ = '2';
							$toma = '3';
							$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
						}
					} else {

						$CNPJ = trim($Row['cli_cnpj']);
						$IE = limpaChars(trim($Row['cli_ie']));
						$xNome = trim(abrev(strtoupper(limpaChars($Row['cli_cliente']))));
						$xFant = abrev(strtoupper(trim($xNome)));

						$xLgr = strtoupper(trim(limpaChars($Row['cli_endereco'])));
						$nro = trim(limpaChars($Row['cli_numero']));

						$xCpl = limpaChars($Row['cli_complemento']);
						if ($xCpl == '') {

							$xCpl = 'x';
						} else {

							$xCpl = $xCpl;
						}

						$xBairro = strtoupper(trim($Row['cli_bairro']));
						$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
						$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
						$CEP = trim($Row['cli_cep']);
						$UF = strtoupper(trim($Row['cli_estado']));
						$email = mb_strtoupper(trim($Row['cli_email']));
						$cPais = '1058';
						$xPais = 'BRASIL';

						$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

						$tomador = '<toma4><toma>4</toma><CNPJ>' . $CNPJ . '</CNPJ><IE>' . $IE . '</IE><xNome>' . $xNome . '</xNome><xFant>' . $xFant . '</xFant>';
						$tomador .= '<enderToma><xLgr>' . $xLgr . '</xLgr><nro>' . $nro . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . $xBairro . '</xBairro><cMun>' . $cMun . '</cMun><xMun>' . $xMun . '</xMun><CEP>' . $CEP . '</CEP><UF>' . $UF . '</UF><cPais>' . $cPais . '</cPais><xPais>' . $xPais . '</xPais></enderToma>';
						$tomador .= '<email>' . $email . '</email></toma4>';
					}
				} else if ($Row['tipo_cte'] == 'subcontratacao') {

					$QueryXml = mysql_query("SELECT xml, chavecte FROM xml_cte WHERE chavenf = '" . $Row['chaveacesso'] . "' LIMIT 1");

					$RowXml = mysql_fetch_assoc($QueryXml);

					//NORMALMENTE DEVE BUSCAR APENAS PELA CHAVE_CTE, MAS VOU MANTER OS 2 PARA CASO HAJA ALGUMA EXCEÇÃO
					//ENTÃO SE NÃO ACHAR POR CHAVENF, BUSCA POR CHAVECTE DE QLQER MANEIRA
					if (mysql_num_rows($QueryXml) <= 0) {
						$QueryXml = mysql_query("SELECT xml, chavecte FROM xml_cte WHERE chavecte = '" . $Row['chaveacesso'] . "' LIMIT 1");
						$RowXml = mysql_fetch_assoc($QueryXml);
					}

					// $aXMLBody = xml2array(retiraAssinatura(limpaXml(($RowXml['xml']))),0);

					$aXMLBody = xml2array(retiraAssinatura(($RowXml['xml'])), 0);

					$CNPJ = trim($aXMLBody['cteProc']['CTe']['infCte']['emit']['CNPJ']);
					$CNPJ_REDESPACHO = $CNPJ;
					$IE = limpaChars(trim($aXMLBody['cteProc']['CTe']['infCte']['emit']['IE']));
					$IE_REDESPACHO = $IE;
					$ChaveCteXml = $RowXml['chavecte'];

					$xNome = trim(abrev(strtoupper(limpaChars($aXMLBody['cteProc']['CTe']['infCte']['emit']['xNome']))));
					$xNomeFant = $xNome;
					$xFant = abrev(strtoupper(trim($xNome)));

					$NumeroCTE = $aXMLBody['cteProc']['CTe']['infCte']['ide']['nCT'];

					$xLgr = strtoupper(trim(limpaChars($Row['cli_endereco'])));
					$nro = trim(limpaChars($Row['cli_numero']));

					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {

						$xCpl = 'x';
					} else {

						$xCpl = $xCpl;
					}

					$xBairro = strtoupper(trim($aXMLBody['cteProc']['CTe']['infCte']['emit']['enderEmit']['xBairro']));
					$cMun = trim(limpaChars($aXMLBody['cteProc']['CTe']['infCte']['emit']['enderEmit']['cMun']));
					$xMun = strtoupper(trim(limpaChars($aXMLBody['cteProc']['CTe']['infCte']['emit']['enderEmit']['xMun'])));
					$CEP = trim($aXMLBody['cteProc']['CTe']['infCte']['emit']['enderEmit']['CEP']);
					$UF = strtoupper(trim($aXMLBody['cteProc']['CTe']['infCte']['emit']['enderEmit']['UF']));
					$UF_REDESPACHO = $UF;
					$email = mb_strtoupper(trim($Row['cli_email']));
					$cPais = '1058';
					$xPais = 'BRASIL';
					$email = mb_strtoupper(trim($Row['cli_email']));

					$toma = '0';
					$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';

					//PROFILE
					if ($IdCliente == 'b11dad215b67039b93a3f03b745fd9c9' || $IdCliente == 'e68f702d0c2ec72cd8731abcc2457b29' || $IdCliente == 'a1727513de95981a346c7b20ee875497') {

						$toma = '1';
						$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
					}

					// $tomador = '<toma4><toma>4</toma><CNPJ>' . $CNPJ . '</CNPJ><IE>' . $IE . '</IE><xNome>' . $xNome . '</xNome><xFant>' . $xFant . '</xFant>';
					// $tomador .= '<enderToma><xLgr>' . $xLgr . '</xLgr><nro>' . $nro . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . $xBairro . '</xBairro><cMun>' . $cMun . '</cMun><xMun>' . $xMun . '</xMun><CEP>' . $CEP . '</CEP><UF>' . $UF . '</UF><cPais>' . $cPais . '</cPais><xPais>' . $xPais . '</xPais></enderToma>';
					// $tomador .= '<email>' . $email . '</email></toma4>';

					// $tpServ = '2';
					$tpServ = '1';
				} else {

					# #  toma3
					if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
						// destinatario

						$toma = '3';
						$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
					} else if ($Row['notadevdest'] == 'S') {
						// remetente

						$toma = '0';
						$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
					} else {
						// remetente

						$toma = '0';
						$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
					}
				}

				# emit

				$CNPJ = limpaChars($RowEmissor['cnpj']);
				$IE = limpaChars(trim($RowEmissor['ie']));
				$xNome = abrev(strtoupper(limpaChars($RowEmissor['razaosocial'])));
				$xFant = abrev(strtoupper(limpaChars($RowEmissor['fantasia'])));

				# # enderEmit

				$Endereco = explode(',', $RowEmissor['endereco']);

				$xLgr = trim(strtoupper($Endereco[0]));
				$nro = trim(limpaChars($Endereco[1]));
				$xCpl = limpaChars($RowEmissor['complemento']);
				if ($xCpl == '') {
					$xCpl = 'x';
				} else {
					$xCpl = $xCpl;
				}

				$xBairro = trim(strtoupper($RowEmissor['bairro']));
				$cMun = trim($RowEmissor['ibge_municipio']);
				$xMun = trim(strtoupper(limpaChars($RowEmissor['cidade'])));
				$CEP = trim($RowEmissor['cep']);
				$UF = trim(strtoupper($RowEmissor['estado']));
				$fone = trim($RowEmissor['fone']);

				$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

				$XmlEmit='';

				$XmlEmit .= '<emit><CNPJ>' . trim($CNPJ) . '</CNPJ><IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><xFant>' . trim(limpaChars($xFant)) . '</xFant><enderEmit><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . trim($xCpl) . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><fone>' . trim($fone) . '</fone></enderEmit></emit>';

				# rem

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$CNPJ = trim($Row['dest_cpfcnpj']);
					$IE = limpaChars(trim($Row['dest_ie']));
					$xNome = abrev(strtoupper(limpaChars($Row['destinatario'])));
					$xFant = abrev(strtoupper(trim($xNome)));
				} else {
					$CNPJ = trim($Row['cli_cnpj']);
					$IE = limpaChars(trim($Row['cli_ie']));
					$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));
					$xFant = abrev(strtoupper(trim($xNome)));
				}

				if (strlen($CNPJ) > 11) {
					$tagRemCNPJ = '<CNPJ>' . trim($CNPJ) . '</CNPJ>';
				} else {
					$tagRemCNPJ = '<CPF>' . trim($CNPJ) . '</CPF>';
				}

				if ($tpAmb == '2') {

					//$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

				}

				# # enderReme

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$xLgr = strtoupper(trim($Row['endereco']));
					$nro = trim(limpaChars($Row['entreganumero']));

					$xCpl = limpaChars($Row['complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cidade']), $Row['uf']));
					$xMun = strtoupper(trim(limpaChars($Row['cidade'])));
					$CEP = trim($Row['cep']);
					$UF = strtoupper(trim($Row['uf']));
					$email = mb_strtoupper(trim($Row['cli_email']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				} else {
					$xLgr = strtoupper(trim($Row['cli_endereco']));
					$nro = trim(limpaChars($Row['cli_numero']));

					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['cli_bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					$email = mb_strtoupper(trim($Row['cli_email']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				}

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$xLgr = strtoupper(trim($Row['logradouro']));
					$nro = trim(limpaChars($Row['entreganumero']));

					$xCpl = limpaChars($Row['complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cidade']), $Row['uf']));
					$xMun = strtoupper(trim(limpaChars($Row['cidade'])));
					$CEP = trim($Row['cep']);
					$UF = strtoupper(trim($Row['uf']));
					$email = mb_strtoupper(trim($Row['cli_email']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				} else {
					$xLgr = strtoupper(trim($Row['cli_endereco']));
					$nro = trim(limpaChars($Row['cli_numero']));

					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['cli_bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					$email = mb_strtoupper(trim($Row['cli_email']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				}

				# # fim enderReme
				#infNFe

				# fim rem

				if ($Row['redespacho'] != 'N') {

					if ($Row['redespacho'] == 'XML') {

						$QueryRedespacho = "SELECT
													emit_CNPJ AS cnpj,
													emit_IE AS ie,
													emit_xNome AS nome,
													emit_enderEmit_xLgr AS lgr,
													emit_enderEmit_nro AS nro,
													emit_enderEmit_xBairro AS bairro,
													emit_enderEmit_xMun AS cidade,
													emit_enderEmit_CEP AS cep,
													emit_enderEmit_UF AS uf,
													'a@a.com.br' AS email
												FROM
													tb_nfe
												WHERE
													chave_nf = '" . $ChaveNF . "';";
						$ResultRedespacho = mysql_query($QueryRedespacho);
						if (mysql_num_rows($ResultRedespacho) > 0) {

							$RowRedespacho = mysql_fetch_assoc($ResultRedespacho);

							$CNPJ = trim($RowRedespacho['cnpj']);
							$IE = limpaChars(trim($RowRedespacho['ie']));
							$xNome = abrev(strtoupper(limpaChars($RowRedespacho['nome'])));
							$xFant = $xNome;
							$xLgr = strtoupper(trim($RowRedespacho['lgr']));
							$nro = trim(limpaChars($RowRedespacho['nro']));

							$xCpl = 'x';

							$xBairro = strtoupper(trim($RowRedespacho['bairro']));
							$cMun = trim(getMunIBGE(limpaChars($RowRedespacho['cidade']), $RowRedespacho['uf']));
							$xMun = strtoupper(trim(limpaChars($RowRedespacho['cidade'])));
							$CEP = trim($RowRedespacho['cep']);
							$UF = strtoupper(trim($RowRedespacho['uf']));

							$email = $RowRedespacho['email'];

							$cPais = '1058';
							$xPais = 'BRASIL';

							$tagRemCNPJ = '<CNPJ>' . $CNPJ . '</CNPJ>';
						}
					} else if ($Row['redespacho'] == 'EDI') {

						$QueryRedespacho = "SELECT
													CgcCpfConsig AS cnpj,
													IEConsig AS ie,
													rSocialConsig AS nome,
													EndConsig AS lgr,
													'x' AS nro,
													BairroConsig AS bairro,
													CidadeConsig AS cidade,
													CEPConsig AS cep,
													EstadoConsig AS uf,
													'a@a.com.br' AS email
												FROM
													tb_edi
												WHERE
													chave_nf = '" . $ChaveNF . "'
												LIMIT 1;";
						$ResultRedespacho = mysql_query($QueryRedespacho);
						if (mysql_num_rows($ResultRedespacho) > 0) {

							$RowRedespacho = mysql_fetch_assoc($ResultRedespacho);

							$CNPJ = trim($RowRedespacho['cnpj']);
							$IE = limpaChars(trim($RowRedespacho['ie']));
							$xNome = abrev(strtoupper(limpaChars($RowRedespacho['nome'])));
							$xFant = $xNome;
							$xLgr = strtoupper(trim($RowRedespacho['lgr']));
							$nro = trim(limpaChars($RowRedespacho['nro']));

							$xCpl = 'x';

							$xBairro = strtoupper(trim($RowRedespacho['bairro']));
							if (strlen($xBairro) < 1) {
								$xBairro = 'NAO DECLARADO';
							}

							$cMun = trim(getMunIBGE(limpaChars($RowRedespacho['cidade']), $RowRedespacho['uf']));
							$xMun = strtoupper(trim(limpaChars($RowRedespacho['cidade'])));
							$CEP = trim($RowRedespacho['cep']);
							$UF = strtoupper(trim($RowRedespacho['uf']));

							$email = $RowRedespacho['email'];

							$cPais = '1058';
							$xPais = 'BRASIL';

							$tagRemCNPJ = '<CNPJ>' . $CNPJ . '</CNPJ>';
						}
					}
				}

				$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

				$XmlRem = '<rem>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><xFant>' . trim(limpaChars($xFant)) . '</xFant><enderReme><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderReme><email>' . $email . '</email></rem>';

				//SIMPRESS (REF. AO PEDIDO PARA ADEQUAR A MUDANÇA PARA O REGISTRO 315)
				if (in_array($IdCliente, $Simpress)) {

					if ($RowEmissao['tipo'] == 'devolucao') {

						if ($IdCliente == '3d865f1b44cab4d8e8e5e8d99435395f') {
							$tagRemCNPJ = '<CNPJ>07432517000360</CNPJ>';
							$IE = '623085249114';
							$xNome = 'simpress comercio locacao e servicos ltda barueri';
							$xLgr = 'av prefeito joao vilallobo quero';
							$nro = '2253';
							$xCpl = '0';
							$xBairro = 'PEDRA RACHADA';
							$cMun = '3505708';
							$xMun = 'barueri';
							$CEP = '06422122';
							$UF = 'SP';
						} else if ($IdCliente == '4502b0a1696393d6db1f852b564af4f0') {
							$tagRemCNPJ = '<CNPJ>07432517001847</CNPJ>';
							$IE = '257477446';
							$xNome = 'Simpress comercio locacao e servicos ltda itajai';
							$xLgr = 'rua jose pereira liberato';
							$nro = '525';
							$xCpl = 'sala 32';
							$xBairro = 'sao joao';
							$cMun = '4208203';
							$xMun = 'itajai';
							$CEP = '88304401';
							$UF = 'SC';
						} else if ($IdCliente == '5a4775bce6db2e5f576d06d5ec23bf39') {
							$tagRemCNPJ = '<CNPJ>07432517001766</CNPJ>';
							$IE = '206339438113';
							$xNome = 'simpress comercio locacao e servicos barueri iii';
							$xLgr = 'avenida prefeito joao villalobo quero';
							$nro = '2253';
							$xCpl = 'galpao 2 mod a sala 1a';
							$xBairro = 'jardim belval';
							$cMun = '3505708';
							$xMun = 'barueri';
							$CEP = '06422122';
							$UF = 'SP';
						} else if ($IdCliente == '81550144f52ecda6ac2f676582e5789b') {
							$tagRemCNPJ = '<CNPJ>07432517000794</CNPJ>';
							$IE = '206275253119';
							$xNome = 'SIMPRESS COMERCIO, LOCACAO E SERVICOS ltda barueri ii';
							$xLgr = 'AVENIDA PREFEITO JOAO VILLALOBO';
							$nro = '2253';
							$xCpl = '-';
							$xBairro = 'sitio pedra rachada';
							$cMun = '3505708';
							$xMun = 'barueri';
							$CEP = '06422122';
							$UF = 'SP';
						} else if ($IdCliente == 'a83265eb9fcf4d4c406cf55b3836c952') {
							$tagRemCNPJ = '<CNPJ>07432517001251</CNPJ>';
							$IE = '492558345111';
							$xNome = 'SIMPRESS COMERCIO LOCACAO E SERVICOS ltda osasco';
							$xLgr = 'avenida doutor mauro lindemberg monteiro';
							$nro = '628';
							$xCpl = '-';
							$xBairro = 'parque industrial anhanguera';
							$cMun = '3534401';
							$xMun = 'osasco';
							$CEP = '06278010';
							$UF = 'SP';
						} else if ($IdCliente == 'cd244e9bf90338f2da40b86edb579700') {
							$tagRemCNPJ = '<CNPJ>07432517001170</CNPJ>';
							$IE = '255517076';
							$xNome = 'Simpress comercio locacao e servicos ltda itajai II';
							$xLgr = 'RUA JOSE PEREIRA LIBERATO';
							$nro = '525';
							$xCpl = 'SALA 32';
							$xBairro = 'SAO JOAO';
							$cMun = '4208203';
							$xMun = 'ITAJAI';
							$CEP = '88304401';
							$UF = 'SC';
						}
						$cPais = '1058';
						$xPais = 'BRASIL';

						$XmlDestSimpress = '<dest>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';
					}

					/*else{
		
		$XmlDestSimpress = '<dest>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';

	}*/
				}
				$UFIni = $UF;
				$cliUf = $UF;
				$xMunIni = $xMun;
				$cMunIni = $cMun;

				if ($Row['idcliente'] == '0e315ef5f424167a734414e4f30a9005') {
					$UFIni = "MG";
					$cliUf = $UFIni;
					$xMunIni = $xMunEnv;
					$cMunIni = $cMunEnv;
					$destUf = "SP";
				}

				if ($cliUf == $destUf) {
					$CFOP = '5';
				} else {
					$CFOP = '6';
				}

				if ($emisUf == $cliUf) {
					$CFOP .= '3';
				} else {
					$CFOP .= '932';
					$natOp = 'PREST. SERV. INI. EM UF DIF. UF PRESTADOR';
				}

				if (strlen($CFOP) == 2) {
					if ($TipoServico == 'Industria') {
						$CFOP .= '52';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. INDUSTRIAL';
					} else if ($TipoServico == 'Comercio') {
						$CFOP .= '53';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. DO COMERCIO';
					} else if ($TipoServico == 'Servico') {
						$CFOP .= '54';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. PREST. SERVICO';
					} else if ($TipoServico == 'Nao Contribuinte') {
						$CFOP .= '57';
						$natOp = 'PREST. SERV. TRANSPORTE NAO CONTRIBUINTE';
					}
				}

				// if(strlen($ChaveNF) < 44){
				// 	$CFOP = '5359';
				// 	$natOp = 'PREST. SERV. TRANSPORTE CONTR. NAO CONTR. MERC. DISP. NF.';
				// }

				// $aGemini = array('30e80d2bbd90dfe34fd38818d39a796c', '5193c058b32a6d61b244f959505b2a5d', '40edbc3761e054f4771e68bc0c6d9e09');

				// // REGRA PARA GEMINI ICMS ST (Suelen 14/05/2019)
				// if($RowEmissor['estado'] == 'SP' && $UFIni == 'GO' && in_array($Row['idcliente'], $aGemini)) {

				// 	$CFOP = '6360';
				// 	$natOp = 'PREST. SERV. TRANSP.A CONTRI. SUBSTITUT. AO SERV. DE TRANSP.';

				// }

				//SE CLIENTE FOR PROFILE
				// if ($idCliente == 'b11dad215b67039b93a3f03b745fd9c9' || $idCliente == 'e68f702d0c2ec72cd8731abcc2457b29' || $idCliente == 'a1727513de95981a346c7b20ee875497') {
				if ($Row['cli_id'] == '19fc460ed4f1c9d764cdece52cee1328' || $Row['cli_id'] == 'a29eac1b104704902420a24fe42ffdb3'){
					echo "<br>Atribuiu valor 1 para tpServ;<br>";
					$tpServ = "1"; //subcontratação
				}

				$XmlIDE = '<ide><cUF>' . trim($cUFEmis) . '</cUF><cCT>' . trim($cCT) . '</cCT><CFOP>' . trim($CFOP) . '</CFOP><natOp>' . trim($natOp) . '</natOp><mod>' . trim($mod) . '</mod><serie>' . trim($serie) . '</serie><nCT>' . trim($nCT) . '</nCT><dhEmi>' . trim($dhEmi) . '</dhEmi><tpImp>' . trim($tpImp) . '</tpImp><tpEmis>' . trim($tpEmis) . '</tpEmis><cDV>' . trim($cDV) . '</cDV><tpAmb>' . trim($tpAmb) . '</tpAmb><tpCTe>' . trim($tpCTe) . '</tpCTe><procEmi>' . trim($procEmi) . '</procEmi><verProc>' . trim($verProc) . '</verProc><cMunEnv>' . trim($cMunEnv) . '</cMunEnv><xMunEnv>' . trim(limpaChars($xMunEnv)) . '</xMunEnv><UFEnv>' . trim($UFEnv) . '</UFEnv><modal>' . trim($modal) . '</modal><tpServ>' . trim($tpServ) . '</tpServ><cMunIni>' . trim($cMunIni) . '</cMunIni><xMunIni>' . trim(limpaChars($xMunIni)) . '</xMunIni><UFIni>' . trim($UFIni) . '</UFIni><cMunFim>' . trim($cMunFim) . '</cMunFim><xMunFim>' . trim(limpaChars($xMunFim)) . '</xMunFim><UFFim>' . trim($UFFim) . '</UFFim><retira>' . trim($retira) . '</retira><indIEToma>' . $indIEToma . '</indIEToma>' . $tomador . '</ide>';

				# exped

				$CNPJ = trim($Row['cli_cnpj']);
				$IE = limpaChars(trim($Row['cli_ie']));
				$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));

				if ($tpAmb == '2') {

					//$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

				}

				$xFant = abrev(strtoupper(trim($xNome)));

				# # enderExped

				$xLgr = strtoupper(trim($Row['cli_endereco']));
				$nro = trim(limpaChars($Row['cli_numero']));

				$xCpl = limpaChars($Row['cli_complemento']);
				if ($xCpl == '') {
					$xCpl = 'x';
				} else {
					$xCpl = $xCpl;
				}
				$xBairro = strtoupper(trim($Row['cli_bairro']));
				$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
				$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
				$CEP = trim($Row['cli_cep']);
				$UF = strtoupper(trim($Row['cli_estado']));
				if ($Row['emit_enderEmit_cPais'] == '') {
					$cPais = '1058';
				} else {
					$cPais = trim($Row['emit_enderEmit_cPais']);
				}
				if ($Row['emit_enderEmit_xPais'] == '') {
					$xPais = 'BRASIL';
				} else {
					$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
				}

				# # fim enderExped

				if ($Row['redespacho'] != 'N') {

					if ($Row['redespacho'] == 'XML') {

						$QueryRedespacho = "SELECT
													emit_CNPJ AS cnpj,
													emit_IE AS ie,
													emit_xNome AS nome,
													emit_enderEmit_xLgr AS lgr,
													emit_enderEmit_nro AS nro,
													emit_enderEmit_xBairro AS bairro,
													emit_enderEmit_xMun AS cidade,
													emit_enderEmit_CEP AS cep,
													emit_enderEmit_UF AS uf,
													'a@a.com.br' AS email
												FROM
													tb_nfe
												WHERE
													chave_nf = '" . $ChaveNF . "';";
						$ResultRedespacho = mysql_query($QueryRedespacho);
						if (mysql_num_rows($ResultRedespacho) > 0) {

							$RowRedespacho = mysql_fetch_assoc($ResultRedespacho);

							$CNPJ = trim($RowRedespacho['cnpj']);
							$IE = limpaChars(trim($RowRedespacho['ie']));
							$xNome = abrev(strtoupper(limpaChars($RowRedespacho['nome'])));
							$xFant = $xNome;
							$xLgr = strtoupper(trim($RowRedespacho['lgr']));
							$nro = trim(limpaChars($RowRedespacho['nro']));

							$xCpl = 'x';

							$xBairro = strtoupper(trim($RowRedespacho['bairro']));
							$cMun = trim(getMunIBGE(limpaChars($RowRedespacho['cidade']), $RowRedespacho['uf']));
							$xMun = strtoupper(trim(limpaChars($RowRedespacho['cidade'])));
							$CEP = trim($RowRedespacho['cep']);
							$UF = strtoupper(trim($RowRedespacho['uf']));

							$email = $RowRedespacho['email'];

							$cPais = '1058';
							$xPais = 'BRASIL';
						}
					} else if ($Row['redespacho'] == 'EDI') {

						if($Row['cli_id'] == '19fc460ed4f1c9d764cdece52cee1328' || $Row['cli_id'] == 'a29eac1b104704902420a24fe42ffdb3'){

							$CNPJ = trim($Row['cli_cnpj']);
							$IE = limpaChars(trim($Row['cli_ie']));
							$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));
							$xFant = $xNome;
							$xLgr = strtoupper(trim($Row['cli_endereco']));
							$nro = trim(limpaChars($Row['cli_numero']));
							$xCpl = 'x';
							$xBairro = strtoupper(trim($Row['cli_bairro']));
							$cMun = '3509205'; // IBGE
							$xMun = 'CAJAMAR'; // CIDADE
							$CEP = trim($Row['cli_cep']);
							$UF = strtoupper(trim($Row['cli_estado']));
							$email = $Row['cli_email'];

							$cPais = '1058';
							$xPais = 'BRASIL';

						} else {
							$QueryRedespacho = "SELECT
													CgcCpfConsig AS cnpj,
													IEConsig AS ie,
													rSocialConsig AS nome,
													EndConsig AS lgr,
													'x' AS nro,
													BairroConsig AS bairro,
													CidadeConsig AS cidade,
													CEPConsig AS cep,
													EstadoConsig AS uf,
													'a@a.com.br' AS email
												FROM
													tb_edi
												WHERE
													chave_nf = '" . $ChaveNF . "'
												LIMIT 1;";

							$ResultRedespacho = mysql_query($QueryRedespacho);
							if (mysql_num_rows($ResultRedespacho) > 0) {

								$RowRedespacho = mysql_fetch_assoc($ResultRedespacho);

								$CNPJ = trim($RowRedespacho['cnpj']);
								$IE = limpaChars(trim($RowRedespacho['ie']));
								$xNome = abrev(strtoupper(limpaChars($RowRedespacho['nome'])));
								$xFant = $xNome;
								$xLgr = strtoupper(trim($RowRedespacho['lgr']));
								$nro = trim(limpaChars($RowRedespacho['nro']));

								$xCpl = 'x';

								$xBairro = strtoupper(trim($RowRedespacho['bairro']));
								$cMun = trim(getMunIBGE(limpaChars($RowRedespacho['cidade']), $RowRedespacho['uf']));
								$xMun = strtoupper(trim(limpaChars($RowRedespacho['cidade'])));
								$CEP = trim($RowRedespacho['cep']);
								$UF = strtoupper(trim($RowRedespacho['uf']));

								$email = $RowRedespacho['email'];

								$cPais = '1058';
								$xPais = 'BRASIL';
							}
						}
					}
				}

				// if($RowEmissor['id'] == '21'){
				// 	$UFEnv = "MG";
				// }

				$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

				$XmlExped = '<exped><CNPJ>' . trim($CNPJ) . '</CNPJ><IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderExped><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderExped></exped>';

				# receb destinatario da nota

				if ($Row['redespacho'] == 'N' && ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa')) {
					if ($Row['cli_cnpj'] == '') {
						$Row['cli_cnpj'] = $_POST['cpfcnpj'];
					}
					if (($Row['cli_ie'] == '') or ($Row['cli_ie'] == 'Array')) {
						$Row['cli_ie'] = $_POST['ie'];
					}

					$CNPJ = trim($Row['cli_cnpj']);

					if (strlen($Row['cli_cnpj']) > 11) {

						$tagDestCNPJ = '<CNPJ>' . trim($Row['cli_cnpj']) . '</CNPJ>';
					} else {

						$tagDestCNPJ = '<CPF>' . trim($Row['cli_cnpj']) . '</CPF>';
					}

					if (($Row['cli_ie'] == 'Array') or ($Row['cli_ie'] == '')) {

						$IE = 'ISENTO';
					} else {

						$IE = limpaChars(trim($Row['cli_ie']));
					}

					$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));
				} else {
					if ($Row['dest_cpfcnpj'] == '') {
						$Row['dest_cpfcnpj'] = $_POST['cpfcnpj'];
					}
					if (($Row['dest_ie'] == '') or ($Row['dest_ie'] == 'Array')) {
						$Row['dest_ie'] = $_POST['ie'];
					}

					$CNPJ = trim($Row['dest_cpfcnpj']);

					if (strlen($CNPJ) == 13) {
						$CNPJ = str_pad($CNPJ, 14, "0", STR_PAD_LEFT);
					}

					# ALTEREI AQUI!!!!!! 24-mar-2017

					if (strlen(trim($Row['dest_cpfcnpj'])) == 13) {
						$Row['dest_cpfcnpj'] = str_pad($Row['dest_cpfcnpj'], 14, "0", STR_PAD_LEFT);
					}

					if (strlen($Row['dest_cpfcnpj']) > 11) {

						$tagDestCNPJ = '<CNPJ>' . trim($Row['dest_cpfcnpj']) . '</CNPJ>';
					} else {

						$tagDestCNPJ = '<CPF>' . trim($Row['dest_cpfcnpj']) . '</CPF>';
					}

					if (($Row['dest_ie'] == 'Array') or ($Row['dest_ie'] == '')) {

						$IE = 'ISENTO';
					} else {

						$IE = limpaChars(trim($Row['dest_ie']));
					}

					$xNome = abrev(strtoupper(limpaChars($Row['destinatario'])));
				}

				if ($tpAmb == '2') {

					//$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

				}

				# # enderReceb

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$xLgr = strtoupper(trim($Row['cli_endereco']));
					$nro = trim(limpaChars($Row['cli_numero']));
					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['cli_bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				} else {
					$xLgr = strtoupper(trim($Row['logradouro']));
					$nro = trim(limpaChars($Row['entreganumero']));
					$xCpl = limpaChars($Row['entregacompl']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['bairro']));
					if (strlen($xBairro) < 1) {
						$xBairro = 'NAO DECLARADO';
					}
					$cMun = trim(getMunIBGE(limpaChars($Row['cidade']), $Row['uf']));
					$xMun = strtoupper(trim(limpaChars($Row['cidade'])));
					$CEP = trim($Row['cep']);
					$UF = strtoupper(trim($Row['uf']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				}

				# # fim enderReceb

				$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

				$XmlReceb = '<receb>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderReceb><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderReceb></receb>';

$XmlDestSimpress = '<dest>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';

				# dest destinatario da nota
				if ($Row['redespacho'] == 'N' && ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa')) {
					if ($Row['cli_cnpj'] == '') {
						$Row['cli_cnpj'] = $_POST['cpfcnpj'];
					}
					if (($Row['cli_ie'] == '') or ($Row['cli_ie'] == 'Array')) {
						$Row['cli_ie'] = $_POST['ie'];
					}

					$CNPJ = trim($Row['cli_cnpj']);

					if (strlen($Row['cli_cnpj']) > 11) {
						$tagDestCNPJ = '<CNPJ>' . trim($Row['cli_cnpj']) . '</CNPJ>';
					} else {
						$tagDestCNPJ = '<CPF>' . trim($Row['cli_cnpj']) . '</CPF>';
					}

					if (($Row['cli_ie'] == 'Array') or ($Row['cli_ie'] == '')) {
						$IE = 'ISENTO';
					} else {
						$IE = limpaChars(trim($Row['cli_ie']));
					}

					$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));
				} else {
					if ($Row['dest_cpfcnpj'] == '') {
						$Row['dest_cpfcnpj'] = $_POST['cpfcnpj'];
					}
					if (($Row['dest_ie'] == '') or ($Row['dest_ie'] == 'Array')) {
						$Row['dest_ie'] = $_POST['ie'];
					}
					$CNPJ = trim($Row['dest_cpfcnpj']);

					if (strlen($Row['dest_cpfcnpj']) > 11) {
						$tagDestCNPJ = '<CNPJ>' . trim($Row['dest_cpfcnpj']) . '</CNPJ>';
					} else {

						$tagDestCNPJ = '<CPF>' . trim($Row['dest_cpfcnpj']) . '</CPF>';
					}

					if (($Row['dest_ie'] == 'Array') or ($Row['dest_ie'] == '')) {
						$IE = 'ISENTO';
					} else {
						$IE = limpaChars(trim($Row['dest_ie']));
					}

					$xNome = abrev(strtoupper(limpaChars($Row['destinatario'])));
				}

				if ($tpAmb == '2') {

					//$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

				}
				# # enderDest

				if ($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') {
					$xLgr = strtoupper(trim($Row['cli_endereco']));
					$nro = trim(limpaChars($Row['cli_numero']));
					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['cli_bairro']));

					if (strlen($xBairro) < 1) {

						$xBairro = 'NAO DECLARADO';
						$update = "UPDATE pedidos SET bairro = '" . $xBairro . "' WHERE id = '" . $Nc . "';";
						$result = mysql_query($update);
					}

					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				} else {
					$xLgr = strtoupper(trim($Row['logradouro']));
					$nro = trim(limpaChars($Row['entreganumero']));
					$xCpl = limpaChars($Row['entregacompl']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['bairro']));
					$cMun = trim(getMunIBGE(limpaChars($Row['cidade']), $Row['uf']));
					$xMun = strtoupper(trim(limpaChars($Row['cidade'])));
					$CEP = trim($Row['cep']);
					$UF = strtoupper(trim($Row['uf']));
					if ($Row['emit_enderEmit_cPais'] == '') {
						$cPais = '1058';
					} else {
						$cPais = trim($Row['emit_enderEmit_cPais']);
					}
					if ($Row['emit_enderEmit_xPais'] == '') {
						$xPais = 'BRASIL';
					} else {
						$xPais = strtoupper(trim($Row['emit_enderEmit_xPais']));
					}
				}


				# # fim enderDest

				$nro = str_pad($nro, 4, "0", STR_PAD_LEFT);

				$XmlDest = '<dest>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';

				# vPrest

				if ($tpCTe == '0') {

					$vTPrest = trim($Row['fretevalor']);
					$vRec = trim($Row['fretevalor']);
				}

				if ($tpCTe == '1') {

					$vTPrest = trim($_POST['valorcomplementar']);
					$vRec = trim($_POST['valorcomplementar']);
				}

				# # Comp

				$xNome = 'FRETE';
				$vComp = trim($Row['fretevalor']);

				# # fim Comp


				# imp
				# # ICMS

				$CST = '00';
				$vBC = number_format(trim($vTPrest), 2, '.', '');
				# Verificar regi? do destino para definir aliquota.

				if ($RowEmissor['simples'] == 'N') {

					$pICMS = getAliquota($cMunIni, $cMunFim, $IE, strtoupper($Row['cli_estado']), trim($Row['dest_cpfcnpj'])); ## ALIQUOTA!

				} else {

					$pICMS = '0';
				}

				// Solicitado pelo Eloi em 28/08/2015.
				if ($Row['cli_id'] == '05c13a187d72e5b3bed7eef10f42f004') {


					// UFs dos destinatarios localizados nas regioes Norte, Nordeste, Centro-Oeste, e Espirito Santo
					$UFs = array('RO', 'AC', 'AM', 'RR', 'AP', 'PA', 'TO', 'MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA', 'MS', 'MT', 'GO', 'DF', 'ES');
					if (in_array(strtoupper($destUf), $UFs)) {
						$pICMS = '7';
					}
				}

				$idPhilip = array(
					'50bda9788f2ebe5eee853f314d9e7f9b',
					'35831b686bf4042cbfb9920cbf10b85b',
					'617fc65d4be11249f7ed967b2416a17b',
					'53940a1dc4ce73d68877143854a9a559',
					'0f7be9cae0c132f799b321af2f9555d7',
					'f87cf8e7cdcc49978d078959d62aa7f5',
					'35831b686bf4042cbfb9920cbf10b85b'
				);
				if ($CFOP == '5932' && $destUf == 'RJ' && in_array($Row['idcliente'], $idPhilip)) {

					$pICMS = '20';
					$vICMS = number_format(($vBC / 100) * $pICMS, 2, '.', '');
					$vCred = number_format(($vICMS / 100) * 20, 2, '.', '');
					$vTPrest = $vTPrest + $vICMS;
				} else {

					$vICMS = number_format(($vBC / 100) * $pICMS, 2, '.', '');
				}

				$vICMS = number_format(($vBC / 100) * $pICMS, 2, '.', '');

				// Solicitação Jefferson (09/12/2016) Retirado a condição de zerar ICMS GO
				// Solicitação Paulo (09/12/2016) Origem e destino GO não cobrar ICMS
				if (/*($UFIni == 'GO' && $Row['cli_id'] != '05c13a187d72e5b3bed7eef10f42f004') || */($UFIni == 'GO' && $UFFim == 'GO') || ($RowEmissor['id'] == '2' && $UFIni == 'GO')) {

					$pICMS = '0';
					$vICMS = '0.00';
				}

				if ($outro_tomador == 'S') {

					$cnpj_rem = (isset($_POST['cnpj_rem'])) ? $_POST['cnpj_rem'] : $_GET['cnpj_rem'];
					$ie_rem = (isset($_POST['ie_rem'])) ? $_POST['ie_rem'] : $_GET['ie_rem'];
					$nome_rem = (isset($_POST['nome_rem'])) ? $_POST['nome_rem'] : $_GET['nome_rem'];
					$logradouro_rem = (isset($_POST['logradouro_rem'])) ? $_POST['logradouro_rem'] : $_GET['logradouro_rem'];
					$numero_rem = (isset($_POST['numero_rem'])) ? $_POST['numero_rem'] : $_GET['numero_rem'];
					$bairro_rem = (isset($_POST['bairro_rem'])) ? $_POST['bairro_rem'] : $_GET['bairro_rem'];
					$cidade_rem = (isset($_POST['cidade_rem'])) ? $_POST['cidade_rem'] : $_GET['cidade_rem'];
					$codmun_rem = (isset($_POST['codmun_rem'])) ? $_POST['codmun_rem'] : $_GET['codmun_rem'];
					$cep_rem = (isset($_POST['cep_rem'])) ? $_POST['cep_rem'] : $_GET['cep_rem'];
					$uf_rem = (isset($_POST['uf_rem'])) ? $_POST['uf_rem'] : $_GET['uf_rem'];

					if (strlen($cnpj_rem) == 14) {

						$cpfcnpj_rem = '<CNPJ>' . $cnpj_rem . '</CNPJ>';
					} else {

						$cpfcnpj_rem = '<CPF>' . $cnpj_rem . '</CPF>';
					}

					if ($tpAmb == '2') {

						//$nome_rem = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

					}

					$cnpj_exped = (isset($_POST['cnpj_exped'])) ? $_POST['cnpj_exped'] : $_GET['cnpj_exped'];
					$ie_exped = (isset($_POST['ie_exped'])) ? $_POST['ie_exped'] : $_GET['ie_exped'];
					$nome_exped = (isset($_POST['nome_exped'])) ? $_POST['nome_exped'] : $_GET['nome_exped'];
					$logradouro_exped = (isset($_POST['logradouro_exped'])) ? $_POST['logradouro_exped'] : $_GET['logradouro_exped'];
					$numero_exped = (isset($_POST['numero_exped'])) ? $_POST['numero_exped'] : $_GET['numero_exped'];
					$bairro_exped = (isset($_POST['bairro_exped'])) ? $_POST['bairro_exped'] : $_GET['bairro_exped'];
					$cidade_exped = (isset($_POST['cidade_exped'])) ? $_POST['cidade_exped'] : $_GET['cidade_exped'];
					$codmun_exped = (isset($_POST['codmun_exped'])) ? $_POST['codmun_exped'] : $_GET['codmun_exped'];
					$cep_exped = (isset($_POST['cep_exped'])) ? $_POST['cep_exped'] : $_GET['cep_exped'];
					$uf_exped = (isset($_POST['uf_exped'])) ? $_POST['uf_exped'] : $_GET['uf_exped'];

					if (strlen($cnpj_exped) == 14) {

						$cpfcnpj_exped = '<CNPJ>' . $cnpj_exped . '</CNPJ>';
					} else {

						$cpfcnpj_exped = '<CPF>' . $cnpj_exped . '</CPF>';
					}

					if ($tpAmb == '2') {

						//$nome_exped = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

					}

					$XmlRem = '<rem>' . $cpfcnpj_rem . '<IE>' . strtoupper(limpaChars($ie_rem)) . '</IE><xNome>' . strtoupper(limpaChars($nome_rem)) . '</xNome><xFant>' . strtoupper(limpaChars($nome_rem)) . '</xFant><enderReme><xLgr>' . strtoupper(limpaChars($logradouro_rem)) . '</xLgr><nro>' . strtoupper(limpaChars($numero_rem)) . '</nro><xCpl>x</xCpl><xBairro>' . strtoupper(limpaChars($bairro_rem)) . '</xBairro><cMun>' . $codmun_rem . '</cMun><xMun>' . strtoupper(limpaChars($cidade_rem)) . '</xMun><CEP>' . $cep_rem . '</CEP><UF>' . strtoupper(limpaChars($uf_rem)) . '</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderReme><email>a@a.com.br</email></rem>';

					$XmlExped = '<exped>' . $cpfcnpj_exped . '<IE>' . strtoupper(limpaChars($ie_exped)) . '</IE><xNome>' . strtoupper(limpaChars($nome_exped)) . '</xNome><enderExped><xLgr>' . strtoupper(limpaChars($logradouro_exped)) . '</xLgr><nro>' . strtoupper(limpaChars($numero_exped)) . '</nro><xCpl>x</xCpl><xBairro>' . strtoupper(limpaChars($bairro_exped)) . '</xBairro><cMun>' . $codmun_exped . '</cMun><xMun>' . strtoupper(limpaChars($cidade_exped)) . '</xMun><CEP>' . $cep_exped . '</CEP><UF>' . strtoupper(limpaChars($uf_exped)) . '</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderExped></exped>';
				}

				if ($Row['idcliente'] == '0e315ef5f424167a734414e4f30a9005') {
					$XmlRem = '<rem><CNPJ>10422722000198</CNPJ><IE>ISENTO</IE><xNome>LABORATORIO MEDICO MENDES E SILVEIRA LTDA</xNome><xFant>LABORATORIO MEDICO MENDES E SILVEIRA LTDA</xFant><enderReme><xLgr>AVENIDA CORONEL PRATES</xLgr><nro>0315</nro><xCpl>x</xCpl><xBairro>CENTRO</xBairro><cMun>3143302</cMun><xMun>MONTES CLAROS</xMun><CEP>39400104</CEP><UF>MG</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderReme><email>CESTEVES@WORLDCOURRIER.COM.BR</email></rem>';

					$XmlExped = '<exped><CNPJ>10422722000198</CNPJ><IE>ISENTO</IE><xNome>LABORATORIO MEDICO MENDES E SILVEIRA LTDA</xNome><enderExped><xLgr>AVENIDA CORONEL PRATES</xLgr><nro>0315</nro><xCpl>x</xCpl><xBairro>CENTRO</xBairro><cMun>3143302</cMun><xMun>MONTES CLAROS</xMun><CEP>39400104</CEP><UF>MG</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderExped></exped>';

					$XmlReceb = '<receb><CNPJ>86368172000138</CNPJ><IE>ISENTO</IE><xNome>CIAP CITOLOGIA E ANATOMIA PATOLOGICA LTDA</xNome><enderReceb><xLgr>AVENIDA GETULIO VARGAS</xLgr><nro>0840</nro><xCpl>x</xCpl><xBairro>CENTRO</xBairro><cMun>3122306</cMun><xMun>DIVINOPOLIS</xMun><CEP>35500024</CEP><UF>MG</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderReceb></receb>';

					$XmlDest = '<dest><CNPJ>44064665000134</CNPJ><IE>115336924112</IE><xNome>WORLD COUR.DO BRAS.TRAN.INTE.LTDA.</xNome><enderDest><xLgr>AV ANHANGUERA</xLgr><nro>00SN</nro><xCpl>KM 15 GALPAO DE N.7 ANDAR 1 E G</xCpl><xBairro>PIRITUBA</xBairro><cMun>3550308</cMun><xMun>SAO PAULO</xMun><CEP>05112000</CEP><UF>SP</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderDest></dest>';
				}

				//SIMPRESS (REF. AO PEDIDO PARA ADEQUAR A MUDANÇA PARA O REGISTRO 315)
				if (in_array($IdCliente, $Simpress)) {

					/*$XmlRem = '<rem>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><xFant>' . trim(limpaChars($xFant)) . '</xFant><enderReme><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderReme><email>' . $email . '</email></rem>';

					$XmlRem = '<rem>' . $cpfcnpj_rem . '<IE>' . strtoupper(limpaChars($ie_rem)) . '</IE><xNome>' . strtoupper(limpaChars($nome_rem)) . '</xNome><xFant>' . strtoupper(limpaChars($nome_rem)) . '</xFant><enderReme><xLgr>' . strtoupper(limpaChars($logradouro_rem)) . '</xLgr><nro>' . strtoupper(limpaChars($numero_rem)) . '</nro><xCpl>x</xCpl><xBairro>' . strtoupper(limpaChars($bairro_rem)) . '</xBairro><cMun>' . $codmun_rem . '</cMun><xMun>' . strtoupper(limpaChars($cidade_rem)) . '</xMun><CEP>' . $cep_rem . '</CEP><UF>' . strtoupper(limpaChars($uf_rem)) . '</UF><cPais>1058</cPais><xPais>BRASIL</xPais></enderReme><email>a@a.com.br</email></rem>';

					$tagDestCNPJ = $tagRemCNPJ;*/

					//if ($RowEmissao['tipo'] == 'devolucao') {
					$XmlDest = $XmlDestSimpress;
					//}

					//'<dest>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';

					//$xNome = abrev(strtoupper(limpaChars($Row['destinatario'])));

					//ORIGINAL
					//$XmlDest = '<dest>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';
				}

				$XmlBody .= $XmlIDE . $XmlEmit . $XmlRem . $XmlExped . $XmlReceb . $XmlDest;

				if ($tpCTe == '1' && $_POST['icms'] == 'S') {

					$XmlBody .= '<vPrest><vTPrest>0</vTPrest><vRec>0</vRec><Comp><xNome>' . trim($xNome) . '</xNome><vComp>0</vComp></Comp></vPrest>';
				} else {

					$XmlBody .= '<vPrest><vTPrest>' . trim(number_format($vTPrest, 2, '.', '')) . '</vTPrest><vRec>' . trim(number_format($vRec, 2, '.', '')) . '</vRec><Comp><xNome>' . trim($xNome) . '</xNome><vComp>' . trim(number_format($vComp, 2, '.', '')) . '</vComp></Comp></vPrest>';
				}

				// // REGRA PARA GEMINI ICMS ST (Suelen 14/05/2019)
				// if($RowEmissor['estado'] == 'SP' && $UFIni == 'GO' && in_array($Row['idcliente'], $aGemini)) {

				// 	$pICMS = '0';
				// 	$vICMS = '0.00';

				// 	$CST = '90';
				// 	$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';

				// } else 


				// ICMS90

				if ($RowEmissor['id'] == '17' || $RowEmissor['id'] == '5' && $Row['tipo_cte'] == 'subcontratacao') {

					if ($RowEmissor['id'] == '17' || $RowEmissor['id'] == '21' || $RowEmissor['id'] == '5') {

						$CST = '90';
						$pICMS = '0';
						$vICMS = '0.00';

						$XmlBody .= '<imp><ICMS><ICMSSN><CST>' . trim($CST) . '</CST><indSN>1</indSN></ICMSSN></ICMS></imp>';
					} else {

						$CST = '90';
						$pICMS = '0';
						$vICMS = '0.00';

						$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';
					}
				} else if ($Row['tipo_cte'] == 'subcontratacao' || (($RowEmissor['id'] == '2' || $RowEmissor['id'] == '7' || $RowEmissor['id'] == '12') && ($CFOP == '6932' || $CFOP == '5932'))) {

					if ($RowEmissor['id'] == '17' || $RowEmissor['id'] == '21' || $RowEmissor['id'] == '5') {

						$CST = '90';
						$pICMS = '0';
						$vICMS = '0.00';

						// <vBC>' . trim($vBC) . '</vBC>
						// <pICMS>' . trim($pICMS) . '</pICMS>
						// <vICMS>' . trim($vICMS) . '</vICMS>
						// Solicitado por suelen 2019-10-28

						$XmlBody .= '<imp><ICMS><ICMSSN><CST>' . trim($CST) . '</CST><indSN>1</indSN></ICMSSN></ICMS></imp>';
					} else {

						$CST = '90';
						$pICMS = '0';
						$vICMS = '0.00';

						$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';
					}

					// CST 90 - Solicitado por Suelen Dantas (16/07/2018) - Desenvolvido por André (17/07/2018)

					// ICMS45
					//impedido para transfarma RJ e Medpress - solicitado por suelen (24/05/2019)
				} else if ($pICMS == '0' && ($RowEmissor['id'] != '5' && $RowEmissor['id'] != '17' && $RowEmissor['id'] != '21')) {

					// if($RowEmissor['id'] == '21'){
					// 	$CST = '90';
					// 	$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';
					// }else{
					$CST = '41';
					$XmlBody .= '<imp><ICMS><ICMS45><CST>' . trim($CST) . '</CST></ICMS45></ICMS></imp>';
					// }

					//  Solicitado por Suelen 2019-08-21
					// ICMS60
				} else if ($pICMS == '19') {

					$CST = '60';
					$XmlBody .= '<imp><ICMS><ICMS60><CST>' . trim($CST) . '</CST><vBCSTRet>' . trim($vBC) . '</vBCSTRet><vICMSSTRet>' . trim($vICMS) . '</vICMSSTRet ><pICMSSTRet >' . trim($pICMS) . '</pICMSSTRet><vCred>' . trim($vCred) . '</vCred></ICMS60></ICMS></imp>';

					// ICMS00
				} else {

					$XmlBody .= '<imp><ICMS><ICMS00><CST>' . trim($CST) . '</CST><vBC>' . trim($vBC) . '</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS></ICMS00></ICMS></imp>';
				}

				// Solicitado por Suelen 2019-06-27

				# # # fim ICMS

				if ($tpCTe == '0') {

					#### INICIO DO CTE NORMAL
					# infCTeNorm
					# # infCarga

					$vCarga = trim($Row['valor']);
					$proPred = strtoupper($RowEmissor['prodPredominante']);
					$xOutCat = 'PERECIVEL';

					# # # infQ

					$cUnid[0] = '01';
					$cUnid[1] = '1';
					$cUnid[2] = '00';

					$tpMed[0] = 'PESO BRUTO';
					$qCarga[0] = number_format(calculaPeso($Row['id'], 'real'), 4, '.', '');

					$tpMed[1] = 'PESO TAXADO';
					$qCarga[1] = number_format(calculaPeso($Row['id'], 'cobrado'), 4, '.', '');
					//---------------------------------------------------------------------------------------------------------------------------------------//
					//------------------------------------------------ VALIDAÇÕES DENTAL SPEED CUBAGEM ------------------------------------------------------//
					//---------------------------------------------------------------------------------------------------------------------------------------//
					//valida se é NFSE da dental speed (como é lançamento manual, não tem notifis para puxar cubagem)
					$chaveDental = $Row['chaveacesso'];
					$chaveDental = substr($chaveDental, 0, 3);

					//se for dental speed, utiliza cubagem do próprio notifis
					if ($Row['idcliente'] == '5bc4acbeb5f048f022acf6146998a860' and $chaveDental != 'OUT') {
						$buscaDental = "SELECT cubagem FROM tb_edi WHERE CnpjOrigem='13612214000160' AND numNF = '" . $Row['numerocontrole'] . "' limit 1";
						$resultDental = mysql_query($buscaDental);
						$rowsDental = mysql_fetch_assoc($resultDental);
						$qCarga[1] = number_format($rowsDental['cubagem'], 4, '.', '');
					}
					//---------------------------------------------------------------------------------------------------------------------------------------//
					$tpMed[2] = 'PESO AFERIDO';
					$qCarga[2] = number_format(calculaPeso($Row['id'], 'cobrado'), 4, '.', '');
					# # # fim infQ
					# # fim infCarga
					# seg
					if ($Row['segcliente'] == 'embarcador') {

						$queryBuscaSeguradora = "SELECT  nomeseguradora, numeroapolice, limitesemrastreador, limitecomrastreador, vctoseguro
						FROM clientes
						WHERE id = '" . $Row['idcliente'] . "';";
						$resultSeguradora = mysql_query($queryBuscaSeguradora);

						if (!$resultSeguradora) {

							$respSeg = '4';
							$xSeg = 'YASUDA MARITIMA SEGUROS';
							$nApol = '55000008797';
							$SegvCarga = trim($Row['valor']);
						} else {

							$dadosSeg = mysql_fetch_assoc($resultSeguradora);

							$respSeg = '1';
							$xSeg = $dadosSeg['nomeseguradora'];
							$nApol = $dadosSeg['numeroapolice'];
							$SegvCarga = trim($Row['valor']);
						}
					} else if ($Row['segcliente'] == 'transportador') {

						$queryBuscaSeguradora = "SELECT nomeseguradora, numeroapolice, limitesemrastreador, limitecomrastreador, vctoseguro
						FROM empresas
						WHERE id = '" . $RowEmissor['id'] . "';";
						$resultSeguradora = mysql_query($queryBuscaSeguradora);

						if (!$resultSeguradora) {

							$respSeg = '4';
							$xSeg = 'YASUDA MARITIMA SEGUROS';
							$nApol = '55000008797';
							$SegvCarga = trim($Row['valor']);
						} else {

							$dadosSeg = mysql_fetch_assoc($resultSeguradora);

							$respSeg = '4';
							$xSeg = $dadosSeg['nomeseguradora'];
							$nApol = $dadosSeg['numeroapolice'];
							$SegvCarga = trim($Row['valor']);
						}
					} else {

						$respSeg = '4';
						$xSeg = 'YASUDA MARITIMA SEGUROS';
						$nApol = '55000008797';
						$SegvCarga = trim($Row['valor']);
					}

					# fim seg
					# infModal

					$versaoModal = '3.00';
					$RNTRC = '09513732';
					$dPrev = trim($Row['dataentrega']);

					if ($dPrev == '0000-00-00') {

						$dPrev = date('Y-m-d', strtotime('NOW + 7 DAY'));
					}

					if ($modal == '01') {

						# rodo
						$xmlModal = '<infModal versaoModal="' . trim($versaoModal) . '"><rodo><RNTRC>' . trim($RNTRC) . '</RNTRC></rodo></infModal>';
					} else if ($modal == '02') {

						# aereo
						$xmlModal = '<infModal versaoModal="' . trim($versaoModal) . '"><aereo><dPrevAereo>' . $dPrev . '</dPrevAereo><natCarga><cInfManu>99</cInfManu></natCarga><tarifa><CL>G</CL><vTar>' . $vTPrest . '</vTar></tarifa></aereo></infModal>';;
					}

					# fim infModal

					if ($Row['idcliente'] == 'bad334a477a92df8cf80627786639ddf') {

						$GetPicking = "SELECT chave_nf FROM tb_aux_emefarma WHERE id_pedido = '" . $Nc . "';";
						$ResPicking = mysql_query($GetPicking);

						$aChaves = array();
						$xmlChaves = '';

						if (mysql_num_rows($ResPicking) > 0) {

							while ($RowPicking = mysql_fetch_assoc($ResPicking)) {

								if (!in_array($RowPicking['chave_nf'], $aChaves)) {

									$xmlChaves .= '<infNFe><chave>' . trim($RowPicking['chave_nf']) . '</chave></infNFe>';
									array_push($aChaves, $RowPicking['chave_nf']);
								}
							}

							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc>' . $xmlChaves . '</infDoc>' . $xmlModal . '</infCTeNorm>';
						} else {

							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc><infNFe><chave>' . trim($ChaveNF) . '</chave></infNFe></infDoc>' . $xmlModal . '</infCTeNorm>';
						}
					} else if ($Row['redespacho'] == 'CTE' || $Row['tipo_cte'] == 'subcontratacao') {
						if($Row['cli_id']=='19fc460ed4f1c9d764cdece52cee1328' || $Row['cli_id'] == 'a29eac1b104704902420a24fe42ffdb3'){
							//SE FOR AGV 3PL, EXECUTA ESSA PARTE
							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc><infOutros><tpDoc>99</tpDoc><descOutros>DIVERSOS</descOutros><nDoc>' . trim($nCT) . '</nDoc><dEmi>' . date('Y-m-d') . '</dEmi><vDocFisc>' . $Row['valor'] . '</vDocFisc></infOutros></infDoc><docAnt><emiDocAnt><CNPJ>' . $Row['cli_cnpj'] . '</CNPJ><IE>' . $Row['cli_ie'] . '</IE><UF>' . $Row['cli_estado'] . '</UF><xNome>' . $Row['cli_cliente'] . '</xNome><idDocAnt><idDocAntEle><chCTe>' . $ChaveCteEdi . '</chCTe></idDocAntEle></idDocAnt></emiDocAnt></docAnt>' . $xmlModal . '</infCTeNorm>';
						} else {
							//SE FOR PROFILE, VAI EXECUTAR ESSA PARTE
							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc><infOutros><tpDoc>99</tpDoc><descOutros>DIVERSOS</descOutros><nDoc>' . $NumeroCTE . '</nDoc><dEmi>' . date('Y-m-d') . '</dEmi><vDocFisc>' . $Row['valor'] . '</vDocFisc></infOutros></infDoc><docAnt><emiDocAnt><CNPJ>' . $CNPJ_REDESPACHO . '</CNPJ><IE>' . $IE_REDESPACHO . '</IE><UF>' . $UF_REDESPACHO . '</UF><xNome>' . $xNomeFant . '</xNome><idDocAnt><idDocAntEle><chCTe>' . $ChaveCteXml . '</chCTe></idDocAntEle></idDocAnt></emiDocAnt></docAnt>' . $xmlModal . '</infCTeNorm>';
						}
					} else if (strlen($ChaveNF) < 44) {

						$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc><infOutros><tpDoc>99</tpDoc><descOutros>DIVERSOS</descOutros><nDoc>' . $ChaveNF . '</nDoc><dEmi>' . $Row['dataentrega'] . '</dEmi><vDocFisc>' . $Row['valor'] . '</vDocFisc></infOutros></infDoc>' . $xmlModal . '</infCTeNorm>';
					} else {

						$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc><infNFe><chave>' . trim($ChaveNF) . '</chave></infNFe></infDoc>' . $xmlModal . '</infCTeNorm>';
					}

					$peso = sprintf("%2.3f", $qCarga[2]);
					$valornf = $vCarga;
				}

				##### FIM CTE NORMAL.
				##### INICIO CTE COMPLEMENTAR.

				if ($tpCTe == '1') {

					$BuscaComp = "SELECT id_cte FROM tb_emicte WHERE id_pedido = '" . $Nc . "' AND tipocte = 1 AND status = 'autorizado';";
					$ResComp = mysql_query($BuscaComp);

					if (mysql_num_rows($ResComp) > 1) {

						$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
						$ResultInutilizado = mysql_query($QueryInutilizado);

						$cont++;
						continue;
					}

					// ALERTA!!! VERIFICANDO SE O CTE COMPLEMENTAR FOI EMITIDO A MENOS DE CINCO MINUTOS!!! COMENTAR BLOCO EM CASO DE ERROS!

					$buscaComplementar = "SELECT
					COUNT(*) as complementares
					FROM tb_emicte
					WHERE tipocte = 1 AND
					idusuario = '" . $id_usuario . "' AND
					valor = " . $vTPrest . " AND data_emis
					BETWEEN DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND
					NOW()
					AND id_pedido = '" . $Nc . "';";
					$resultCteComplementar = mysql_query($buscaComplementar);
					if (!$resultCteComplementar) {
						echo mysql_error();
					} else {
						$rowresultCteComplementar = mysql_fetch_assoc($resultCteComplementar);
						if ($rowresultCteComplementar['complementares'] > 0) {

							$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
							$ResultInutilizado = mysql_query($QueryInutilizado);

							$cont++;
							continue;
						}
					}

					// FIM DO ALERTA!!!

					$ChaveCteComp = (isset($_POST['chavectecomp'])) ? $_POST['chavectecomp'] : $RowEmissao['ChaveCte'];
					$XmlBody .= '<infCteComp><chCTe>' . $ChaveCteComp . '</chCTe></infCteComp>';
				}

				##### FIM CTE COMPLEMENTAR
				# fim infCte

				$XmlBody .= '</infCte>';

				//INCLUI QR CODE NO XML PARA EMISSÃO SEGUNDO ALTERAÇÕES DA SEFAZ QUE ENTRARAM EM VIGOR NA DATA DE 07/10/2019
				if ($RowEmissor['id'] == '12' or $RowEmissor['id'] == '2') {
				 //DEFINE URL DA EMISSÃO
					$XmlBody .= '<infCTeSupl><qrCodCTe><![CDATA[https://nfe.fazenda.sp.gov.br/CTeConsulta/qrCode?chCTe=' . $Chave . '&tpAmb=1]]></qrCodCTe></infCTeSupl>';

				} else if($RowEmissor['id'] == '21' || $RowEmissor['id'] == '17'){

					$XmlBody .= '<infCTeSupl><qrCodCTe>https://cte.fazenda.mg.gov.br/portalcte/sistema/qrcode.xhtml?chCTe='. $Chave .'&amp;tpAmb=1</qrCodCTe></infCTeSupl>';

				} else {
					$XmlBody .= '<infCTeSupl><qrCodCTe><![CDATA[https://dfe-portal.svrs.rs.gov.br/cte/qrCode?chCTe=' .  $Chave . '&tpAmb=1]]></qrCodCTe></infCTeSupl>';
				}

				# Assinatura
				$DigestValue = '';
				$SignatureValue = '';
				$X509Certificate = '';

				$XmlBody .= '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><Reference URI="#' . trim($Id) . '"><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><DigestValue>' . trim($DigestValue) . '</DigestValue></Reference></SignedInfo><SignatureValue>' . trim($SignatureValue) . '</SignatureValue><KeyInfo><X509Data><X509Certificate>' . trim($X509Certificate) . '</X509Certificate></X509Data></KeyInfo></Signature>';

				# fim CTe

				$XmlBody .= '</CTe>';

				# fim cteProc
				#$XmlBody.= '</cteProc>';

				$Emis = date("Y-m-d H:i:s");
				$Valor = $vBC;
				$Aliquota = $pICMS;
				$VlIcms = $vICMS;
				$cfop = $CFOP;
				$Uf = $UFFim;
				$idUsuario = (isset($id_usuario)) ? $id_usuario : 'Servidor';

				$QueryVerificaChave = "SELECT chavecte FROM tb_emicte WHERE chavecte = '" . $Chave . "' AND data_emis >= '" . $dataCorteCte . "';";
				$ResultVerificaChave = mysql_query($QueryVerificaChave);
				if (mysql_num_rows($ResultVerificaChave) == 0) {
					#### ASSINA O XML ANTES DE GRAVAR NO BANCO DE DADOS ####

					$XmlTempFile = '/tmp/Cte' . $Chave . 'Temp.xml';
					file_put_contents($XmlTempFile, $XmlBody);

					if ($RowEmissor['id'] == '2' || $RowEmissor['id'] == '9' || $RowEmissor['id'] == '20') {
						$Certificado = '/var/certificado/certificado_quality.pfx';
						// $Senha = 'atl669191';
						$Senha = 'qual1234';
						$ACcertificado = '/var/certificado/certificado_quality.pem';
					}

					if ($RowEmissor['id'] == '4') {

						$Certificado = '/var/certificado/certificado_loglife.pfx';
						$Senha = '123456';
						$ACcertificado = '/var/certificado/certificado_loglife.pem';
					}

					if ($RowEmissor['id'] == '7') {

						$Certificado = '/var/certificado/certificado_transfarma_go.pfx';
						$Senha = 'qual1234';
						$ACcertificado = '/var/certificado/certificado_transfarma_go_cte.pem';
					}

					if ($RowEmissor['id'] == '6') {

						$Certificado = '/var/certificado/certificado_transfarma_sp.pfx';
						$Senha = '1234';
						$ACcertificado = '/var/certificado/certificado_transfarma_sp.pem';
					}

					if ($RowEmissor['id'] == '5') {

						$Certificado = '/var/certificado/certificado_transfarma_rj.pfx';
						$Senha = 'transfrj2016';
						$ACcertificado = '/var/certificado/certificado_transfarma_rj.pem';
					}

					if ($RowEmissor['id'] == '17') {

						$Certificado = '/var/certificado/medpress.pfx';
						$Senha = '15968585';
						$ACcertificado = '/var/certificado/medpress.pem';
					}

					if ($RowEmissor['id'] == '18') {

						$Certificado = '/var/certificado/aln_blandino.pfx';
						$Senha = '12345678';
						$ACcertificado = '/var/certificado/aln_blandino.pem';
					}

					if ($RowEmissor['id'] == '20') {

						$Certificado = '/var/certificado/certificado_quality_cbu.pfx';
						$Senha = 'Qual1234';
						$ACcertificado = '/var/certificado/certificado_quality_cbu.pem';
					}

					if ($RowEmissor['id'] == '12') {

						$Certificado = '/var/certificado/quality-cps.pfx';
						$Senha = 'qu@litycps919';
						$ACcertificado = '/var/certificado/quality-cps.pem';
					}

					if ($RowEmissor['id'] == '21') {

						$Certificado = '/var/certificado/Transfarma-UDI.pfx';
						$Senha = 'transf2019udi';
						$ACcertificado = '/var/certificado/Transfarma-UDI.pem';
					}


					$Command = "xmlsec1 sign --id-attr:Id infCte --output $XmlTempFile --pkcs12 $Certificado --pwd $Senha --trusted-pem $ACcertificado $XmlTempFile 2>&1";

					exec($Command);
					$ArquivoXMLremLFCR = file_get_contents($XmlTempFile);
					$ArquivoXMLremLFCR = str_replace('<?xml version="1.0"?>', '', $ArquivoXMLremLFCR);
					$ArquivoXMLremLFCR = str_replace(chr(13), "", $ArquivoXMLremLFCR);
					$ArquivoXMLremLFCR = str_replace(chr(10), "", $ArquivoXMLremLFCR);

					preg_match_all('/\<X509Certificate\>(.+)\<\/X509Certificate\>/sU', $ArquivoXMLremLFCR, $aXMLx509);

					$DropAssinatura = $aXMLx509[0][0] . $aXMLx509[0][1] . $aXMLx509[0][2];

					if ($RowEmissor['id'] != '7' && $RowEmissor['id'] != '12' && $RowEmissor['id'] != '2' && $RowEmissor['id'] != '5') {

						$ArquivoXMLremLFCR = str_replace($DropAssinatura, '', $ArquivoXMLremLFCR);
					}

					$XmlBody = $ArquivoXMLremLFCR;
					file_put_contents($XmlTempFile, $XmlBody);

					#### ####

					if ($tpCTe == '1') {

						$Validacao = '1;XML obedece as regras.';
					} else {

						$Validacao = validaCte($XmlTempFile);
						//comentei a linha de cima pois devido a alteração referente ao padrão 3.00a, o link acima
						//ainda não consegue validar os layout do XML.
						//  $Validacao = '1;XML obedece as regras.';

					}

					$Validacao = explode(';', $Validacao);

					// print_r($Validacao);

					if ($Validacao[0] == 1) {

						if ($_POST['icms'] == 'S') {

							$Valor = 0;
						}

						$corte_transfarma = date('2017-12-31 23:59:59');

						$Cristalia = array('05c13a187d72e5b3bed7eef10f42f004', '957dc134d42c079033a7222843918aee', '1e7813025ab02904671753c62b3ed4b7', 'e7e56ee2df1d701c1516710cf461e2b4', '063ea90137cb949b64366e086d0646df');

						if (!in_array($IdCliente, $Simpress)) {

							if (strtotime($Emis) > strtotime($corte_transfarma) && $RowEmissor['id'] == '6') {

								$status = 'presoretorno';
							} else {

								$status = 'assinado';

								if (in_array($IdCliente, $Cristalia) && ($cfop == '5932' || $cfop == '6932')) {

									$From = 'sistema@qualityentregas.com.br';
									$RealName = 'Sistema - Quality Entregas';
									$To = 'suelen.dantas@qualityentregas.com.br';
									$Copy = '-c eloi@qualityentregas.com.br -c aline.fernanda@qualityentregas.com.br';
									$Subject = 'CTe Cristalia - ' . $Seq . ' - ' . date('d/m/Y');

									$mensagem = '<em>Olá!</em><p><em>Segue o CTE enviado para emissão N. ' . $Seq . ' Cristalia .</em></p><br>';
									// $mensagem .= $retornoQuery;
									$mensagem .= '<p><em>Att.</p><p>Quality Entregas - T.I.</em></p>';
									file_put_contents($SsPathToHtml . '/sistema/ferramentas/contentEmailPorcentagem.html', $mensagem);

									$CmdMail = 'mutt -e "set content_type=text/html charset=iso-8859-1 from=' . $From . ' realname=\'' . $RealName . '\'" ' . $Copy . ' -s "' . $Subject . '" -- "' . $To . '" < /var/www/sistemas/quality/secure/sistema/ferramentas/contentEmailPorcentagem.html > /dev/null';
									shell_exec($CmdMail);
								}
							}
						} else {

							$status = 'assinado';
						}

						$QueryInsert = "UPDATE tb_emicte SET id_pedido = '" . $Nc . "', status = '" . $status . "', data_emis = '" . $Emis . "', chavecte = '" . $Chave . "', cfop = '" . $cfop . "', uf = '" . $Uf . "', valor = '" . $Valor . "', txicms = '" . $Aliquota . "', vlicms = '" . $VlIcms . "', idemissor = '" . $RowEmissor['id'] . "', tipocte = '" . $tpCTe . "', idusuario = '" . $idUsuario . "', prioridade = '" . $prioridade . "', xml = '" . $XmlBody . "', tpamb = '" . $tpAmb . "', peso = '" . $peso . "', valornf = '" . $valornf . "' WHERE id_cte = '" . $id_cte . "';";
						$ResultInsert = mysql_query($QueryInsert) or die(mysql_error());

						if ($ResultInsert) {

							if ($tpCTe != '1') {

								$QueryEnvioCte = mysql_query("SELECT enviocte FROM pedidos WHERE id = '" . $Nc . "'");
								$RowEnvioCte = mysql_fetch_assoc($QueryEnvioCte);

								if ($RowEnvioCte['enviocte'] >= 0 && $RowEnvioCte['enviocte'] <= 3) {
									$AtualizaEnvio = " , enviocte = '" . ($RowEnvioCte['enviocte'] + 1) . "' ";
								}

								$QueryUpdatePedido = "UPDATE pedidos SET NumCte = '" . $Seq . "', EmisCte = '" . $Emis . "', ChaveCte = '" . $Chave . "' $AtualizaEnvio WHERE id = '" . $Nc . "';";
								$ResultUpdatePedido = mysql_query($QueryUpdatePedido) or die(mysql_error());
							}

							$CtesEnviados++;
							$inChaves .= "'" . $Chave . "',";
						}

						// unlink($XmlTempFile);

					} else {

						if ($_POST['icms'] == 'S') {

							$Valor = 0;
						}

						$QueryInsert = "UPDATE tb_emicte SET id_pedido = '" . $Nc . "', observacao = \"" . $Validacao[1] . "\", status = 'rejeitado', data_emis = '" . $Emis . "', chavecte = '" . $Chave . "', cfop = '" . $cfop . "', uf = '" . $Uf . "', valor = '" . $Valor . "', txicms = '" . $Aliquota . "', vlicms = '" . $VlIcms . "', idemissor = '" . $RowEmissor['id'] . "', tipocte = '" . $tpCTe . "', idusuario = '" . $idUsuario . "', prioridade = '" . $prioridade . "', xml = '" . $XmlBody . "', tpamb = '" . $tpAmb . "', peso = '" . $peso . "', valornf = '" . $valornf . "' WHERE id_cte = '" . $id_cte . "';";
						mysql_query($QueryInsert);
					}
				}

				######## FIM - GERA O CTE.
			} else {

				$naoEmitidos .= "NF " . $Nc . " - CTE nao emitido! Frete esta zerado!\n";
			}
		} else {

			$naoEmitidos .= "NF " . $Nc . " - CTE nao emitido! Nao ha XML de entrada para esta nota.\n";
			$QueryEmissao . mysql_error();
		}

		$cont++;
	}
	// mysql_query("UPDATE tb_emicte SET status = 'assinado' WHERE chavecte IN (" . substr($inChaves, 0, -1) . ")");

	echo "\n\n" . $CtesEnviados . " CTe's enviados para a fila de emissao!\n";
	echo "\n\n" . $naoEmitidos;
} catch (Exception $e) {
	echo "Não foi possível gerar o CTE. Tente novamente. Erro: ".$e->getMessage();
}
