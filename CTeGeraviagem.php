<?php
// exit();

ini_set("memory_limit", "1G");

try {
	set_time_limit(600);
	$bHome = true;

	include("../../../include/init.include");
	include($SsPathToInclude."/sistema/sistema.init.include");
	include $SsPathToHtml."/sistema/financeiro/recalculoFreteFiat.inc";
	
	$dataCorteCte = date("Y-m-d H:i:s", strtotime("NOW - 4 MONTH"));
	$dataEntregaCorte = date('Y-m-d', strtotime('NOW - 4 MONTH'));
	$idsFCA = array('7c7ac30350fa0c4931a02d00c5eebc77', 'b5b13e9d68b1a525e33de5906a25c202', '46b7d07d876ed6aa66975668c493b8d4', '3059f2a75bf53a2fe698d51c0478b21f', '298bde74da3b8fe77967f61a7b1283cc');

	function validaCte($CteFile){

		$Xml = file_get_contents($CteFile);
		$postvars = array('txtCTe' => $Xml,
	                  'submit1' => 'Validar');

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

		if($falhaSchema[1] == 'OK') {

			return '1;XML obedece as regras definidas no arquivo XSD!';

		} else {

			$linhas = explode('<BR>', $falhaSchema[1]);

			$msg = '';
			for($i = 0; $i < sizeof($linhas); $i++) {

				$strlinha = strtolower($linhas[$i]);
				preg_match("#the 'http://www.portalfiscal.inf.br/cte:(.*?)' element is invalid#", $strlinha, $tag);
				preg_match("#- the value '(.*?)' is invalid#", $strlinha, $value);

				if(strlen($tag[1]) > 1) {

					switch($tag[1]) {

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

			// $msg = $falhaSchema[1];

			return '0;' . $msg;

		}

	}

	function getUfIBGE($Uf) {
		$QueryIBGE = "SELECT codigo FROM municipios_ibge WHERE uf = '" . $Uf . "' LIMIT 1";
		$ResultIBGE = mysql_query($QueryIBGE);
		$RowIBGE = mysql_fetch_assoc($ResultIBGE);
		$RetUf = substr($RowIBGE['codigo'], 0, 2);
		return $RetUf;
	}

	function getMunIBGE($Municipio, $Uf) {
		$Municipio = str_replace('.', '%', $Municipio);
		$Municipio = str_replace(' ', '%', $Municipio);
		$QueryIBGE = "SELECT codigo FROM municipios_ibge WHERE nome LIKE '" . $Municipio . "' AND uf = '" . $Uf . "' LIMIT 1";
		$ResultIBGE = mysql_query($QueryIBGE);
		$RowIBGE = mysql_fetch_assoc($ResultIBGE);
		$RetMun = $RowIBGE['codigo'];
		return $RetMun;
	}

	// function getAliquota($cMunIni, $cMunFim, $IE) {
	// 	if (substr($cMunIni, 0, 2) != substr($cMunFim, 0, 2)) {
	// 		if ($IE == 'ISENTO') {
	// 			$Aliquota = '12';
	// 		} else {
	// 			if ((substr($cMunFim, 0, 1) != '3') && (substr($cMunFim, 0, 1) != '4')) {
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
	// } desativado 05/07/2018

	// Solicitado por Suelen 05/07/2018
	function getAliquota($cMunIni, $cMunFim, $IE, $UFCliente) {
		
		$Aliquota = '0';
		$aSulSudesteExcetoES = array('31', '33', '35', '41', '42', '43');
		$aNorteNordesteCentroOesteES = array('11', '12', '13', '14', '15', '16', '17', '21', '22', '23', '24', '25', '26', '27', '28', '29', '50', '51', '52', '53', '32');

		// REGRAS PARA INICIO DE PRESTACAO EM SP
		if(substr($cMunIni, 0, 2) == '35') {

			// SE DESTINATARIO NAO-CONTRIBUINTE
			if($IE == 'ISENTO') {

				$Aliquota = '12';

			// SE O DESTINO FOR SP
			} else if(substr($cMunFim, 0, 2) == '35') {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';

			}

		// REGRAS PARA INICIO DE PRESTACAO EM GO
		} else if(substr($cMunIni, 0, 2) == '52') {

			// SE DESTINATARIO NAO-CONTRIBUINTE EM GO
			if($IE == 'ISENTO' && substr($cMunFim, 0, 2) == '52') {

				$Aliquota = '17';

			// SE DESTINATARIO CONTRIBUINTE EM GO
			} else if($IE != 'ISENTO' && substr($cMunFim, 0, 2) == '52') {

				$Aliquota = '0';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';

			}

		// REGRAS PARA INICIO DE PRESTACAO EM DF
		} else if(substr($cMunIni, 0, 2) == '53') {

			// SE DESTINO FOR DF
			if(substr($cMunFim, 0, 2) == '53') {

				$Aliquota = '0';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';

			}

		// REGRAS PARA INICIO DE PRESTACAO EM ES
		} else if(substr($cMunIni, 0, 2) == '32') {


			// SE DESTINO FOR ES
			if(substr($cMunFim, 0, 2) == '32') {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '12';

			}
		
		// REGRAS PARA INICIO DE PRESTACAO EM MG
		} else if(substr($cMunIni, 0, 2) == '31') {

			// SE DESTINO FOR MG
			if(substr($cMunFim, 0, 2) == '31') {

				// SE O TOMADOR FOR DE MG
				if($UFCliente == 'MG') { 
				
					$Aliquota = '0';

				// CASO CONTRARIO
				} else {
					
					$Aliquota = '12';
						
				}

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';

			}

		// REGRAS PARA INICIO DE PRESTACAO EM RJ
		} else if(substr($cMunIni, 0, 2) == '33') {

			// SE DESTINO FOR RJ
			if(substr($cMunFim, 0, 2) == '33') {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';

			}

		// REGRAS PARA INICIO DE PRESTACAO EM PR
		} else if(substr($cMunIni, 0, 2) == '41') {

			// SE DESTINO FOR PR
			if(substr($cMunFim, 0, 2) == '41') {

				// SE O TOMADOR FOR DE PR
				if($UFCliente == 'PR') { 
				
					$Aliquota = '0';

				// CASO CONTRARIO
				} else {
					
					$Aliquota = '12';
						
				}

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO SUL E SUDESTE (EXCETO ES)
			} else if(in_array(substr($cMunFim, 0, 2), $aSulSudesteExcetoES)) {

				$Aliquota = '12';

			// SE OS DESTINATARIOS FOR CONTRIBUINTE DA REGIAO NORTE, NORDESTE, CENTRO-OESTE E ES
			} else if(in_array(substr($cMunFim, 0, 2), $aNorteNordesteCentroOesteES)) {

				$Aliquota = '7';

			}

		}
		
		return $Aliquota;

	}

	function limpaChars($string) {
		$newstring = preg_replace("/[^a-zA-Z0-9_.]/", " ", strtr($string, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC "));
		$newstring = str_replace('  ', '', $newstring);
		return trim(strtoupper($newstring));
	}

	function abrev($string) {
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

	$ncs = $_REQUEST['ncs'];
	$aNcs = explode(',', $ncs);
	$aNcs = array_unique($aNcs); // Remove possiveis valores duplicados do array
	$NumNcs = count($aNcs);
	$prioridade = (isset($_REQUEST['prioridade']) && $_REQUEST['prioridade'] == '1') ? '1' : '0';
	$id_usuario = $_REQUEST['idusuario'];

	$CtesEnviados = 0;
	$cont = 0;

	while ($cont < $NumNcs) {
		$Nc = $aNcs[$cont];

		if(strlen($Nc) < 1) { $cont++; continue; }

		$QueryVerificaCte = "SELECT chavecte FROM tb_emicte WHERE idgrupo = '" . $Nc . "' AND status NOT IN ('cancelado','nuvem','substituido') AND data_emis >= '".$dataCorteCte."';";
		$ResultVerificaCte = mysql_query($QueryVerificaCte);
		$NumRowsVerificaCte = mysql_num_rows($ResultVerificaCte);

		if ($NumRowsVerificaCte > 0 && (!isset($_REQUEST['tpcte']) || $_REQUEST['tpcte'] != '1')) {

			$cont++;
			continue;

		}

		$QueryEmissao = "SELECT x1.tipo, x1.idcliente, x1.numerocontrole, SUM(x1.fretevalor) AS fretevalor, x1.ChaveCte, x1.cidade FROM pedidos x1 JOIN tb_emicte_grupos x2 ON x1.id = x2.idpedido WHERE x1.fretevalor != 0.00 AND x2.id = '" . $Nc . "' AND x1.dataentrega >= '".$dataEntregaCorte."';";
		$ResultEmissao = mysql_query($QueryEmissao);
		$RowEmissao = mysql_fetch_assoc($ResultEmissao);

		if($RowEmissao['tipo'] == "retirada"){

			$cont++;
			continue;

		}

		$IdCliente = $RowEmissao['idcliente'];

		if ($RowEmissao['numerocontrole'] != '') {

			if ($RowEmissao['fretevalor'] > 0.00) {

	####### PEGA DADOS DA EMPRESA EMISSORA

				$QueryEmissor = "SELECT
				x2.*,
				x1.prodPredominante,
				x1.bloqsp
				FROM clientes x1
				JOIN empresas x2 ON x1.EmissoraCte = x2.id
				WHERE x1.id = '" . $IdCliente . "';";
				$ResultEmissor = mysql_query($QueryEmissor);
				$RowEmissor = mysql_fetch_assoc($ResultEmissor);

				if ($RowEmissor['id'] == '' || $RowEmissor['id'] == '0') {
					echo 'Cliente sem empresa emissora!';
					exit();
				}

	######## GERA E RESERVA O NUMERO DO CT-E POR EMPRESA EMISSORA

				$QueryDuplicidadeReserva = "SELECT numCte FROM tb_emicte WHERE status = 'gerado' AND idgrupo = '" . $Nc . "';";
				$ResultDuplicidadeReserva = mysql_query($QueryDuplicidadeReserva);

				if(mysql_num_rows($ResultDuplicidadeReserva) > 0) {

					exit();
					
				}

				// mysql_query("LOCK TABLES tb_emicte WRITE") or die(mysql_error());

				// $QueryNext = "SELECT
				// 					MAX(numcte)+1 AS proximo_cte
				// 				FROM
				// 					tb_emicte
				// 				WHERE
				// 					idemissor = '" . $RowEmissor['id'] . "'";
				// $ResultNext = mysql_query($QueryNext) or die(mysql_error());
				// $RowNext = mysql_fetch_assoc($ResultNext);

				// $nextId = $RowNext['proximo_cte'];
				// $QueryReserva = "INSERT INTO tb_emicte (numCte, idemissor, idgrupo, observacao, idusuarioemitiu, data_emis) 
				//                  VALUES ('" . $nextId . "', '" . $RowEmissor['id'] . "', '" . $Nc . "', 'CTeGeraviagem.php', '" . $id_usuario . "', '" . date('Y-m-d H:i:s') . "');";
				// $ResultReserva = mysql_query($QueryReserva);

				// if(!$ResultReserva) {
				// 	exit();
				// }

				// $id_cte = mysql_insert_id();

				// mysql_query("UNLOCK TABLES");				

		    	try {
					if (!$pdo->beginTransaction()) {
						echo "Não foi possível obter o número do CTE!";
						exit();
					}

					$aRow = $Utils->query("INSERT INTO tb_emicte (numCte, idemissor, observacao, idgrupo, idusuarioemitiu, data_emis) 
										   SELECT MAX(numCte)+1 AS nextCte, '" . $RowEmissor['id'] . "' AS idemissor, 'CTeGeraviagem.php' AS observacao, 
										   '" . $Nc . "' AS idgrupo, '" . $id_usuario . "' AS idusuarioemitiu, '" . date('Y-m-d H:i:s') . "' AS data_emis
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

				if($RowEmissao['tipo'] == 'devolucao'){
				
					$Query = "
						SELECT
							COUNT(x1.id) AS qtd,
							GROUP_CONCAT(x1.id) AS id,
							SUM(x1.valor) AS valor,
							x1.chaveacesso,
							SUM(x1.fretevalor) AS fretevalor,
							x1.uf,
							x1.cidade,
							x1.dest_cpfcnpj,
							x1.dest_ie,
							x1.destinatario,
							x1.entreganumero,
							x1.entregacompl,
							x1.bairro,
							x1.cep,
							x1.logradouro,
							x1.dataentrega,
							x2.id AS cli_id,
							x2.cliente AS cli_cliente,
							x2.endereco AS cli_endereco,
							x2.numero AS cli_numero,
							x2.complemento AS cli_complemento,
							x2.bairro AS cli_bairro,
							x2.cidade AS cli_cidade,
							x2.estado AS cli_estado,
							x2.cep AS cli_cep,
							x2.cpfcnpj AS cli_cnpj,
							x2.rgie AS cli_ie,
							x2.email1 AS cli_email,
							x2.fator as fator_cli,
							x2.seguro as segcliente
						FROM pedidos x1
						JOIN clientes x2 ON x1.idcliente = x2.id
						JOIN tb_emicte_grupos x3 ON x1.id = x3.idpedido
						WHERE x3.id = '" . $Nc . "' AND
						x1.fretevalor != '0.00' AND
						x1.dataentrega >= '" . $dataEntregaCorte . "'";
						$Result = mysql_query($Query);
						$Row = mysql_fetch_assoc($Result);

				} else {
					$Query = "
					SELECT
						COUNT(x1.id) AS qtd,
						GROUP_CONCAT(x1.id) AS id,
						SUM(x1.valor) AS valor,
						x3.id AS grupo,
						x1.chaveacesso,
						SUM(x1.fretevalor) AS fretevalor,
						x1.uf,
						x1.cidade,
						x1.dest_cpfcnpj,
						x1.dest_ie,
						x1.destinatario,
						x1.entreganumero,
						x1.entregacompl,
						x1.bairro,
						x1.cep,
						x1.logradouro,
						x1.dataentrega,
						x2.id AS cli_id,
						x2.cliente AS cli_cliente,
						x2.endereco AS cli_endereco,
						x2.numero AS cli_numero,
						x2.complemento AS cli_complemento,
						x2.bairro AS cli_bairro,
						x2.cidade AS cli_cidade,
						x2.estado AS cli_estado,
						x2.cep AS cli_cep,
						x2.cpfcnpj AS cli_cnpj,
						x2.rgie AS cli_ie,
						x2.email1 AS cli_email,
						x2.fator as fator_cli,
						x2.seguro as segcliente
					FROM pedidos x1
					JOIN clientes x2 ON x1.idcliente = x2.id
					JOIN tb_emicte_grupos x3 ON x1.id = x3.idpedido
					WHERE x3.id = '" . $Nc . "' AND
					x1.fretevalor != '0.00' AND
					x1.dataentrega >= '" . $dataEntregaCorte . "'";
					$Result = mysql_query($Query);
					$Row = mysql_fetch_assoc($Result);

				}

				// ADICIONADO CALCULO DO GRUPO
				if(in_array($RowEmissao['idcliente'], $idsFCA)) {
				
					// SE O GRUPO É DE PEDIDOS DE 1 CENTAVO, ENTAO NAO RECALCULA O FRETE/VALOR/PESO
					if($Row['fretevalor'] == (0.01 * $Row['qtd'])) {

						$aIDs = explode(',', $Row['id']);
						$peso_total = 0;

						for($i = 0; $i < sizeof($aIDs); $i++) {
							
							$peso_total += str_replace(',', '.', calculaPeso($aIDs[$i]));

						}

						$pesoGrupo = $peso_total;
						$freteGrupo = $Row['fretevalor'];
						$valorGrupo = $Row['valor'];

					} else {

						$aFreteGrupo = intaFreteFiat(0, $Nc);

						echo "Obtive os dados da Frete Fiat para o grupo ".$Nc."<br />";
						print_r($aFreteGrupo);

						$pesoGrupo = $aFreteGrupo['peso'];
						$freteGrupo = $aFreteGrupo['frete'];
						$valorGrupo = $aFreteGrupo['valornf'];

						// Se for Fiat Dedicados, recalcular o valor do frete que vai para a tabela pedidos dividindo proporcionalmente o valor do CTE pelo número de pedidos
						if ($RowEmissao["idcliente"]=="b5b13e9d68b1a525e33de5906a25c202") {
							echo "CTE de pedido de FCA Dedicado<br />\n";
							$aPedidos=$Utils->query("SELECT pedidos.id, pedidos.fretevalor FROM pedidos INNER JOIN tb_emicte_grupos ON idpedido=pedidos.id WHERE tb_emicte_grupos.id='".(int)$Nc."'");
							$iNumPedidos=sizeOf($aPedidos);

							echo "NumPedidos do grupo ".$Nc.": ".$iNumPedidos."<br />\n";
							$dFreteValor=$aFreteGrupo["frete"]/$iNumPedidos;
							echo "Frete médio: ".$dFreteValor."<br />\n";

							for ($i=0;$i<sizeOf($aPedidos);$i++) { 
								$Utils->query("UPDATE pedidos SET fretevalor='".$dFreteValor."' WHERE id='".$aPedidos[$i]["id"]."'");
							}
						}
						else {
							echo "CTE de outro FCA!<br />\n";
						}

					}

				} else {

					$aIDs = explode(',', $Row['id']);
					$peso_total = 0;

					for($i = 0; $i < sizeof($aIDs); $i++) {
						
						$peso_total += str_replace(',', '.', calculaPeso($aIDs[$i]));

					}

					$pesoGrupo = $peso_total;
					$freteGrupo = $Row['fretevalor'];
					$valorGrupo = $Row['valor'];
					
				}

				// ADICIONADO CALCULO PELO GRUPO
				$qCarga[0] = $pesoGrupo;
				echo "<BR />Até aqui: ".$qCarga[0]."<br />\n";
				
				######## ADICIONADO BLOQUEIO DE SP

				if($RowEmissor['bloqsp'] == 'S' && (strtolower($Row['cidade'])=="sao paulo" || strtolower($Row['cidade'])=="s?o paulo" || strtolower($Row['cidade'])=="são paulo" || strtolower(($Row['cidade']))=="são paulo" || strtolower(($Row['cidade']))=="sao paulo" || strtolower(($Row['cidade']))=="s?o paulo")) {

					$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
					$ResultInutilizado = mysql_query($QueryInutilizado);
					
					$cont++;
					continue;

				}
				
				######## ADICIONADO BLOQUEIO DE RJ PARA HERBALIFE

				if($Row['cli_cnpj'] == '00292858001653' && (strtolower($Row['cidade'])=="rio de janeiro" || strtolower($Row['cidade'])=="rio janeiro" || strtolower($Row['cidade'])=="rj" || strtolower(($Row['cidade']))=="rio de janeiro" || strtolower(($Row['cidade']))=="rio janeiro" || strtolower(($Row['cidade']))=="rj")) {

					$QueryInutilizado = "UPDATE tb_emicte SET status = 'inutilizado', idgrupo = 0, id_pedido = '' WHERE id_cte = '" . $id_cte . "';";
					$ResultInutilizado = mysql_query($QueryInutilizado);

					$cont++;
					continue;

				}

				if ($freteGrupo>30000) {
					glbvErro("Tentando gerar CTE acima de R$ 30.000,00");
					$naoEmitidos .= "Viagem/LR " . $Nc . " - CTE nao emitido! Frete esta acima de 30.000,00!\n";
					$cont++;
					continue;
				}

				$ChaveNF = trim($Row['chaveacesso']);
				####### GERA O CTE.

				// echo $Query;

				if (isset($_REQUEST['tpcte']) && $_REQUEST['tpcte'] != '0') {
				
					$tpCTe = $_REQUEST['tpcte'];
				
				} else {
				
					$tpCTe = '0';
				
				}

				$FatorCubagem = ($Row['fator_cli'] > 0) ? $Row['fator_cli'] : 3000;

				// ADICIONADO CALCULO PELO GRUPO
				$Row['fretevalor'] = $freteGrupo;

				if ($tpCTe == '1') {

					$Row['fretevalor'] = trim($_REQUEST['valorcomplementar']);

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

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					$cliUf = strtoupper($Row['uf']);
					$destUf = strtoupper($Row['cli_estado']);
				}else{
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

				if(explode('.', $_SERVER['SERVER_NAME'])[0] == 'sistema'){
					$tpAmb = '1';
				}else{ 
					$tpAmb = '2'; // Homologacao
				}

				$procEmi = '0';
				$verProc = '0.01';
				$modal = '01';
				$tpServ = '0';
				$retira = '1';
				$refCTE = trim($Chave);

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					$xMunEnv = trim(strtoupper(limpaChars($RowEmissor['cidade'])));
					$UFEnv = trim(strtoupper(limpaChars($RowEmissor['estado'])));
					$cMunEnv = trim($RowEmissor['ibge_municipio']);
					$cMunIni = trim(getMunIBGE(limpaChars($Row['cidade']), limpaChars($Row['uf'])));
					$xMunIni = trim(strtoupper(limpaChars($Row['cidade'])));
					$UFIni = trim(strtoupper($Row['uf']));
					$cMunFim = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMunFim = limpaChars(trim(strtoupper($Row['cli_cidade'])));
					$UFFim = limpaChars(trim(strtoupper($Row['cli_estado'])));
					$CEPFim = trim($Row['cli_cep']);
				}else{
					$xMunEnv = trim(strtoupper(limpaChars($RowEmissor['cidade'])));
					$UFEnv = trim(strtoupper(limpaChars($RowEmissor['estado'])));
					$cMunEnv = trim($RowEmissor['ibge_municipio']);
					$cMunIni = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMunIni = limpaChars(trim(strtoupper($Row['cli_cidade'])));
					$UFIni = limpaChars(trim(strtoupper($Row['cli_estado'])));
					$cMunFim = trim(getMunIBGE(limpaChars($Row['cidade']), limpaChars($Row['uf'])));
					$xMunFim = trim(strtoupper(limpaChars($Row['cidade'])));
					$UFFim = trim(strtoupper($Row['uf']));
					$CEPFim = trim($Row['cep']);
				}

				$indIEToma = '1';

				if(strtoupper($Row['cli_ie']) == 'ISENTO') {

					$indIEToma = '2';

				}

	# #  toma3
				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					$toma = '3';
					$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
				}else if($Row['notadevdest'] == 'S'){
					$toma = '0';
					$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
				} else {
					$toma = '0';
					$tomador = '<toma3><toma>' . trim($toma) . '</toma></toma3>';
				}

				if($cliUf == $destUf){
					$CFOP = '5';
				}else{
					$CFOP = '6';
				}

				if($emisUf == $cliUf){
					$CFOP .= '3';
				}else{
					$CFOP .= '932';
					$natOp = 'Prest. serv. ini. em UF dif. UF prestador';
				}

				if(strlen($CFOP) == 2){
					if ($TipoServico == 'Industria') {
						$CFOP .= '52';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. INDUSTRIAL';
					}else if ($TipoServico == 'Comercio') {
						$CFOP .= '53';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. DO COMERCIO';
					}else if ($TipoServico == 'Servico') {
						$CFOP .= '54';
						$natOp = 'PREST. SERV. TRANSPORTE ESTAB. PREST. SERVICO';
					}else if ($TipoServico == 'Nao Contribuinte') {
						$CFOP .= '57';
						$natOp = 'PREST. SERV. TRANSPORTE NAO CONTRIBUINTE';
					}
				}

				$XmlBody .= '<ide><cUF>' . trim($cUF) . '</cUF><cCT>' . trim($cCT) . '</cCT><CFOP>' . trim($CFOP) . '</CFOP><natOp>' . trim($natOp) . '</natOp><mod>' . trim($mod) . '</mod><serie>' . trim($serie) . '</serie><nCT>' . trim($nCT) . '</nCT><dhEmi>' . trim($dhEmi) . '</dhEmi><tpImp>' . trim($tpImp) . '</tpImp><tpEmis>' . trim($tpEmis) . '</tpEmis><cDV>' . trim($cDV) . '</cDV><tpAmb>' . trim($tpAmb) . '</tpAmb><tpCTe>' . trim($tpCTe) . '</tpCTe><procEmi>' . trim($procEmi) . '</procEmi><verProc>' . trim($verProc) . '</verProc><cMunEnv>' . trim($cMunEnv) . '</cMunEnv><xMunEnv>' . trim(limpaChars($xMunEnv)) . '</xMunEnv><UFEnv>' . trim($UFEnv) . '</UFEnv><modal>' . trim($modal) . '</modal><tpServ>' . trim($tpServ) . '</tpServ><cMunIni>' . trim($cMunIni) . '</cMunIni><xMunIni>' . trim(limpaChars($xMunIni)) . '</xMunIni><UFIni>' . trim($UFIni) . '</UFIni><cMunFim>' . trim($cMunFim) . '</cMunFim><xMunFim>' . trim(limpaChars($xMunFim)) . '</xMunFim><UFFim>' . trim($UFFim) . '</UFFim><retira>' . trim($retira) . '</retira><indIEToma>' . $indIEToma . '</indIEToma>' . $tomador . '</ide>';
	# emit

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					
				}else{
					
					$xMun = strtoupper(trim(limpaChars($Row['cidade'])));
					
				}

				if(in_array($RowEmissao['idcliente'], $idsFCA)) {

					$TipoTransporte = '002';

					if($xMun == 'COTIA' || $xMun == 'MAUA' || $xMun == 'OSASCO' || $xMun == 'POA' || $xMun == 'SANTO ANDRE' || $xMun == 'SAO BERNARDO DO CAMPO' || $xMun == 'SAO PAULO') {

						$TipoTransporte = '001';

					}

		        	$CodTrans = 'B90';
		        	$AliquotaPIS = '0,65';
		        	$ValorPIS = number_format((trim($Row['fretevalor']) * 0.0065), 2, ',', '');
		        	$AliquotaCOFINS = '3,00';
		        	$ValorCOFINS = number_format((trim($Row['fretevalor']) * 0.03), 2, ',', '');

		        	// SE O CLIENTE FOR FCA FRETES DEDICADOS
					if($RowEmissao['idcliente'] == 'b5b13e9d68b1a525e33de5906a25c202') {

						$tipo_frete = 'FTL';

					} else {

						$tipo_frete = 'LTL';

					}

					$QueryIdPedido = "SELECT idpedido FROM tb_emicte_grupos WHERE id = '" . $Nc . "';";
					$ResultIdPedido = mysql_query($QueryIdPedido);
					$RowIdPedido = mysql_fetch_assoc($ResultIdPedido);

					$id_pedido = $RowIdPedido['idpedido'];

					$QueryVeiculo = "SELECT x3.veiculo FROM entregadorpedidos x1 JOIN funcionarios x2 ON x1.identregador = x2.id JOIN veiculos x3 ON x2.idveiculo = x3.id WHERE x1.idpedido = '" . $id_pedido . "';";
					$ResultVeiculo = mysql_query($QueryVeiculo);
					$RowVeiculo = mysql_fetch_assoc($ResultVeiculo);

					$QueryViagem = "SELECT cont, numero FROM entregadorpedidos WHERE idpedido = '" . $id_pedido . "' ORDER BY cont ASC LIMIT 1";
					$ResultViagem = mysql_query($QueryViagem);
					$RowViagem = mysql_fetch_assoc($ResultViagem);

					$QueryQuantidade = "SELECT COUNT(DISTINCT x2.id) AS qtd FROM entregadorpedidos x1 JOIN tb_emicte_grupos x2 ON x1.idpedido = x2.idpedido WHERE x1.numero = '" . $RowViagem['numero'] . "';";
					$ResultQuantidade = mysql_query($QueryQuantidade);
					$RowQuantidade = mysql_fetch_assoc($ResultQuantidade);

					$tipo_veiculo = (strlen($RowVeiculo['veiculo']) > 0) ? trim($RowVeiculo['veiculo']) : 'X';
					$numero_viagem = (strlen($RowViagem['cont']) > 0) ? $RowViagem['cont'] : '0';
					$qtd_cte = (strlen($RowQuantidade['qtd']) > 0) ? $RowQuantidade['qtd'] : '0';

					$XmlBody .= '<compl><xCaracAd>TRANSPORTE</xCaracAd><xCaracSer>CONVENCIONAL</xCaracSer><xEmi>' . strtoupper($RowEmissor['fantasia']) . '</xEmi><origCalc>' . strtoupper($xMunIni) . '</origCalc><destCalc>' . strtoupper($xMunFim) . '</destCalc><ObsCont xCampo="PERC_PIS"><xTexto>' . $AliquotaPIS . '</xTexto></ObsCont><ObsCont xCampo="VALOR_PIS"><xTexto>' . $ValorPIS . '</xTexto></ObsCont><ObsCont xCampo="PERC_COFI"><xTexto>' . $AliquotaCOFINS . '</xTexto></ObsCont><ObsCont xCampo="VALOR_COFI"><xTexto>' . $ValorCOFINS . '</xTexto></ObsCont><ObsCont xCampo="CODTRANS"><xTexto>' . $CodTrans . '</xTexto></ObsCont><ObsCont xCampo="TIPO_TRANSPORTE"><xTexto>' . $TipoTransporte . '</xTexto></ObsCont><ObsCont xCampo="TIPO_FRETE"><xTexto>' . $tipo_frete . '</xTexto></ObsCont><ObsCont xCampo="TIPO_VEICULO"><xTexto>' . $tipo_veiculo . '</xTexto></ObsCont><ObsCont xCampo="PLANO_CARGA"><xTexto>' . $numero_viagem . '</xTexto></ObsCont><ObsCont xCampo="QTE_CTE"><xTexto>' . $qtd_cte . '</xTexto></ObsCont></compl>';

				}

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

				$XmlBody .= '<emit><CNPJ>' . trim($CNPJ) . '</CNPJ><IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><xFant>' . trim(limpaChars($xFant)) . '</xFant><enderEmit><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . trim($xCpl) . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><fone>' . trim($fone) . '</fone></enderEmit></emit>';

				# rem

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					$CNPJ = trim($Row['dest_cpfcnpj']);
					$IE = limpaChars(trim($Row['dest_ie']));
					$xNome = abrev(strtoupper(limpaChars($Row['destinatario'])));
					$xFant = abrev(strtoupper(trim($xNome)));
				}else{
					$CNPJ = trim($Row['cli_cnpj']);
					$IE = limpaChars(trim($Row['cli_ie']));
					$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));
					$xFant = abrev(strtoupper(trim($xNome)));
				}

				if(strlen($CNPJ) > 11) {
					$tagRemCNPJ = '<CNPJ>'.trim($CNPJ).'</CNPJ>';
				} else {
					$tagRemCNPJ = '<CPF>'.trim($CNPJ).'</CPF>';
				}

				if($tpAmb == '2') {

					$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';

				}

				# # enderReme

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
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
					$email = mb_strtoupper(trim($Row['cli_email']));
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}else{
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
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
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
					$email = mb_strtoupper(trim($Row['cli_email']));
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}else{
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
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}

	# # fim enderReme
				#infNFe

	# fim rem

				$XmlBody .= '<rem>' . $tagRemCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><xFant>' . trim(limpaChars($xFant)) . '</xFant><enderReme><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderReme><email>' . $email . '</email></rem>';

	# exped

				$CNPJ = trim($Row['cli_cnpj']);
				$IE = limpaChars(trim($Row['cli_ie']));
				$xNome = abrev(strtoupper(limpaChars($Row['cli_cliente'])));

				if($tpAmb == '2') {
		
					$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
		
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
				$cPais = '1058';
				$xPais = 'BRASIL';
				
	# # fim enderExped

				$XmlBody .= '<exped><CNPJ>' . trim($CNPJ) . '</CNPJ><IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderExped><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderExped></exped>';

	# receb destinatario da nota

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					if ($Row['cli_cnpj'] == '') {
						$Row['cli_cnpj'] = $_REQUEST['cpfcnpj'];
					}
					if (($Row['cli_ie'] == '') or ($Row['cli_ie'] == 'Array')) {
						$Row['cli_ie'] = $_REQUEST['ie'];
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

				}else{
					if ($Row['dest_cpfcnpj'] == '') {
						$Row['dest_cpfcnpj'] = $_REQUEST['cpfcnpj'];
					}
					if (($Row['dest_ie'] == '') or ($Row['dest_ie'] == 'Array')) {
						$Row['dest_ie'] = $_REQUEST['ie'];
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

				if($tpAmb == '2') {
					
					$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
				
				}

				# # enderReceb

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
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
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}else{
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
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}

				# # fim enderReceb

				$XmlBody .= '<receb>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderReceb><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderReceb></receb>';

				# dest destinatario da nota
				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					if ($Row['cli_cnpj'] == '') {
						$Row['cli_cnpj'] = $_REQUEST['cpfcnpj'];
					}
					if (($Row['cli_ie'] == '') or ($Row['cli_ie'] == 'Array')) {
						$Row['cli_ie'] = $_REQUEST['ie'];
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
				}else{
					if ($Row['dest_cpfcnpj'] == '') {
						$Row['dest_cpfcnpj'] = $_REQUEST['cpfcnpj'];
					}
					if (($Row['dest_ie'] == '') or ($Row['dest_ie'] == 'Array')) {
						$Row['dest_ie'] = $_REQUEST['ie'];
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

				if($tpAmb == '2') {
		
					$xNome = 'CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL';
		
				}

				# # enderDest

				if($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa'){
					$xLgr = strtoupper(trim($Row['cli_endereco']));
					$nro = trim(limpaChars($Row['cli_numero']));
					$xCpl = limpaChars($Row['cli_complemento']);
					if ($xCpl == '') {
						$xCpl = 'x';
					} else {
						$xCpl = $xCpl;
					}
					$xBairro = strtoupper(trim($Row['cli_bairro']));

					if(strlen($xBairro) < 1){

						$xBairro = 'NAO DECLARADO';

					}

					$cMun = trim(getMunIBGE(limpaChars($Row['cli_cidade']), $Row['cli_estado']));
					$xMun = strtoupper(trim(limpaChars($Row['cli_cidade'])));
					$CEP = trim($Row['cli_cep']);
					$UF = strtoupper(trim($Row['cli_estado']));
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}else{
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
					$cPais = '1058';
					$xPais = 'BRASIL';
					
				}

	# # fim enderDest

				$XmlBody .= '<dest>' . $tagDestCNPJ . '<IE>' . str_replace('.', '', trim($IE)) . '</IE><xNome>' . trim($xNome) . '</xNome><enderDest><xLgr>' . trim(limpaChars($xLgr)) . '</xLgr><nro>' . trim(limpaChars($nro)) . '</nro><xCpl>' . $xCpl . '</xCpl><xBairro>' . trim(limpaChars($xBairro)) . '</xBairro><cMun>' . trim($cMun) . '</cMun><xMun>' . trim(limpaChars($xMun)) . '</xMun><CEP>' . trim($CEP) . '</CEP><UF>' . trim($UF) . '</UF><cPais>' . trim($cPais) . '</cPais><xPais>' . trim(limpaChars($xPais)) . '</xPais></enderDest></dest>';

	# vPrest

				$vTPrest = trim($Row['fretevalor']);
				$vRec = trim($Row['fretevalor']);

	# # Comp

				$xNome = 'FRETE';
				$vComp = trim($Row['fretevalor']);

	# # fim Comp

	# imp
				# # ICMS
				# # # ICMS45

				$CST = '00';
				$vBC = number_format(trim($vTPrest), 2, '.', '');
	# Verificar regi? do destino para definir aliquota.

				if ($RowEmissor['simples'] == 'N') {

					$pICMS = getAliquota($cMunIni, $cMunFim, $IE, strtoupper($Row['cli_estado'])); ## ALIQUOTA!
				
				} else {

					$pICMS = '0';

				}

				// ADICIONADO CALCULO PELO GRUPO
				$vCarga = $valorGrupo;

				$vICMS = number_format(($vBC / 100) * $pICMS, 2, '.', '');

				if (($UFIni == 'GO' && $UFFim == 'GO') || ($RowEmissor['id'] == '2' && $UFIni == 'GO')) {

					$pICMS = '0';
					$vICMS = '0.00';

				}

				$freteValor = 0;
				$fretePeso = 0;

				if($TipoTransporte = '002') {
					
					$vPeso = 0.35;

				} else if($TipoTransporte == '001') {

					$vPeso = 0.29;

				}

				$freteValor = $vCarga * 0.0033;

				if(in_array($RowEmissao['idcliente'], $idsFCA)) {

					if($tpCTe == '1' && $_REQUEST['icms'] == 'S') {
					
						$XmlBody .= '<vPrest><vTPrest>0</vTPrest><vRec>0</vRec><Comp><xNome>' . trim($xNome) . '</xNome><vComp>0</vComp></Comp><Comp><xNome>Frete Peso</xNome><vComp>********************</vComp></Comp><Comp><xNome>Frete Valor</xNome><vComp>' . number_format($freteValor, 2, '.', '') . '</vComp></Comp></vPrest>';

					} else {

						$XmlBody .= '<vPrest><vTPrest>' . trim(number_format($vTPrest, 2, '.', '')) . '</vTPrest><vRec>' . trim(number_format($vRec, 2, '.', '')) . '</vRec><Comp><xNome>' . trim($xNome) . '</xNome><vComp>' . trim(number_format($vComp, 2, '.', '')) . '</vComp></Comp><Comp><xNome>Frete Peso</xNome><vComp>********************</vComp></Comp><Comp><xNome>Frete Valor</xNome><vComp>' . number_format($freteValor, 2, '.', '') . '</vComp></Comp></vPrest>';

					}

				} else {

					$XmlBody .= '<vPrest><vTPrest>' . trim(number_format($vTPrest, 2, '.', '')) . '</vTPrest><vRec>' . trim(number_format($vRec, 2, '.', '')) . '</vRec><Comp><xNome>' . trim($xNome) . '</xNome><vComp>' . trim(number_format($vComp, 2, '.', '')) . '</vComp></Comp></vPrest>';

				}

				// Regra solicitada pelo Tiago Pizani (03/01/2018) - Autorizado por Fabiano Pilipavicius
				if(($RowEmissor['id'] == '2' || $RowEmissor['id'] == '7') && ($CFOP == '6932' || $CFOP == '5932')) {
					
					$pICMS = '0';
					$vICMS = '0.00';

					// CST 90 - Solicitado por Suelen Dantas (16/07/2018) - Desenvolvido por André (17/07/2018)
					$CST = '90';
					$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';

				} else if ($pICMS == '0') {

					if($RowEmissor['id'] == '17'){

						$pICMS = '0';
						$vICMS = '0.00';

						$CST = '90';
						$XmlBody .= '<imp><ICMS><ICMS90><CST>' . trim($CST) . '</CST><vBC>0.00</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS><vCred>0.00</vCred></ICMS90></ICMS></imp>';

					}else{

						$XmlBody .= '<imp><ICMS><ICMS45><CST>41</CST></ICMS45></ICMS></imp>';
					}

				} else {

					$XmlBody .= '<imp><ICMS><ICMS00><CST>' . trim($CST) . '</CST><vBC>' . trim($vBC) . '</vBC><pICMS>' . trim($pICMS) . '</pICMS><vICMS>' . trim($vICMS) . '</vICMS></ICMS00></ICMS></imp>';

				}

	# # # fim ICMS45

				if ($tpCTe == '0') {

	#### INICIO DO CTE NORMAL
					# infCTeNorm
					# # infCarga

					$proPred = strtoupper($RowEmissor['prodPredominante']);
					$xOutCat = strtoupper($RowEmissor['prodPredominante']);

	# # # infQ

					$cUnid[0] = '01';
					$cUnid[1] = '1';
					$cUnid[2] = '01';

					$tpMed[0] = 'PESO BRUTO';
					$tpMed[1] = 'PESO LIQUIDO';
					$tpMed[2] = 'PESO CUBADO';

	# # # fim infQ
					# # fim infCarga
					# seg
					if($Row['segcliente'] == 'embarcador') {
						
						$queryBuscaSeguradora = "SELECT  nomeseguradora, numeroapolice, limitesemrastreador, limitecomrastreador, vctoseguro
						FROM clientes
						WHERE id = '".$Row['idcliente']."';";
						$resultSeguradora = mysql_query($queryBuscaSeguradora);

						if(!$resultSeguradora) {

							$respSeg = '4';
							$xSeg = 'YASUDA MARITIMA SEGUROS';
							$nApol = '55000008797';
							$SegvCarga = trim($vCarga);

						} else {

							$dadosSeg = mysql_fetch_assoc($resultSeguradora);

							$respSeg = '1';
							$xSeg = $dadosSeg['nomeseguradora'];
							$nApol = $dadosSeg['numeroapolice'];
							$SegvCarga = trim($vCarga);

						}
						

					} else if($Row['segcliente'] == 'transportador') {
						
						$queryBuscaSeguradora = "SELECT nomeseguradora, numeroapolice, limitesemrastreador, limitecomrastreador, vctoseguro
						FROM empresas
						WHERE id = '".$RowEmissor['id']."';";
						$resultSeguradora = mysql_query($queryBuscaSeguradora);

						if(!$resultSeguradora) {

							$respSeg = '4';
							$xSeg = 'YASUDA MARITIMA SEGUROS';
							$nApol = '55000008797';
							$SegvCarga = trim($vCarga);

						} else {

							$dadosSeg = mysql_fetch_assoc($resultSeguradora);

							$respSeg = '4';
							$xSeg = $dadosSeg['nomeseguradora'];
							$nApol = $dadosSeg['numeroapolice'];
							$SegvCarga = trim($vCarga);

						}

					} else {

						$respSeg = '4';
						$xSeg = 'YASUDA MARITIMA SEGUROS';
						$nApol = '55000008797';
						$SegvCarga = trim($vCarga);

					}
					
	# fim seg
					# infModal

					$versaoModal = '3.00';

	# rodo

					$RNTRC = '09513732';
					$dPrev = trim($Row['dataentrega']);
					$lota = '0';

	# fim infModal
					
					if(in_array($RowEmissao['idcliente'], $idsFCA)) {

						if(($RowEmissao['tipo'] == 'devolucao' || $RowEmissao['tipo'] == 'reversa') && strlen($ChaveNF) == 44) {

							$xmlChaves = '';
							$qCarga[0] = 0;
							echo "Linha ".__LINE__.": zerou peso!<br />\n";

							$QueryChaves = "SELECT x1.chaveacesso AS chave_nf, x1.id AS id_pedido, x1.fretevalor, x1.fretepeso FROM pedidos x1 JOIN tb_emicte_grupos x2 ON x1.id = x2.idpedido WHERE x2.id = '" . $Nc . "' AND LENGTH(chaveacesso)=44;";
							$ResultChaves = mysql_query($QueryChaves);

							$aPedidos = array();
							$aChaves = array();

							while($RowChaves = mysql_fetch_assoc($ResultChaves)) {

								if(!in_array($RowChaves['id_pedido'], $aPedidos) && $RowChaves['fretevalor'] > 0) {

									if($RowChaves['fretepeso'] > 0) {

										$qCarga[0] += $RowChaves['fretepeso'];
             							echo "Linha ".__LINE__.": frete passou para ".$qCarga[0]."<br />\n";

									} else {

										$qCarga[0] += str_replace(',', '.', calculaPeso($RowChaves['id_pedido'], 'cobrado'));
             							echo "Linha ".__LINE__.": frete passou para ".$qCarga[0]."<br />\n";
									}

									array_push($aPedidos, $RowChaves['id_pedido']);

								}
								
								if(!in_array($RowChaves['chave_nf'], $aChaves)) {

									$xmlChaves .= '<infNFe><chave>' . trim($RowChaves['chave_nf']) . '</chave></infNFe>';
									array_push($aChaves, $RowChaves['chave_nf']);

								}
								
							}

							$qCarga[0] = sprintf("%2.4f", $qCarga[0]);
							$qCarga[1] = $qCarga[0];
							$qCarga[2] = $qCarga[0];
								
							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc>';
								
							$XmlBody .= $xmlChaves;

							$XmlBody .= '</infDoc><infModal versaoModal="' . trim($versaoModal) . '"><rodo><RNTRC>' . trim($RNTRC) . '</RNTRC></rodo></infModal></infCTeNorm>';

						} else {

							$xmlChaves = '';
							// $qCarga[0] = 0;

							$QueryChaves = "SELECT x1.chave_nf, x1.id_pedido, x3.fretevalor, x3.fretepeso FROM tb_aux_fiat x1 JOIN tb_emicte_grupos x2 ON x1.id_pedido = x2.idpedido JOIN pedidos x3 ON x2.idpedido = x3.id WHERE x2.id = '" . $Nc . "';";
							$ResultChaves = mysql_query($QueryChaves);
								
							$aPedidos = array();
							$aChaves = array();

							while($RowChaves = mysql_fetch_assoc($ResultChaves)) {

								if(!in_array($RowChaves['id_pedido'], $aPedidos) && $RowChaves['fretevalor'] > 0) {

									// if($RowChaves['fretepeso'] > 0) {

									// 	$qCarga[0] += $RowChaves['fretepeso'];

									// } else {

									// 	$qCarga[0] += str_replace(',', '.', calculaPeso($RowChaves['id_pedido'], 'cobrado'));

									// }
									
									array_push($aPedidos, $RowChaves['id_pedido']);

								}
								
								if(!in_array($RowChaves['chave_nf'], $aChaves)) {

									$xmlChaves .= '<infNFe><chave>' . trim($RowChaves['chave_nf']) . '</chave></infNFe>';
									array_push($aChaves, $RowChaves['chave_nf']);

								}
								
							}

							$qCarga[0] = sprintf("%2.4f", $qCarga[0]);
							$qCarga[1] = $qCarga[0];
							$qCarga[2] = $qCarga[0];			
								
							$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc>';
								
							$XmlBody .= $xmlChaves;

							$XmlBody .= '</infDoc><infModal versaoModal="' . trim($versaoModal) . '"><rodo><RNTRC>' . trim($RNTRC) . '</RNTRC></rodo></infModal></infCTeNorm>';

						}

					} else {

						$xmlChaves = '';

						$QueryChaves = "SELECT x1.chaveacesso AS chave_nf FROM pedidos x1 JOIN tb_emicte_grupos x2 ON x1.id = x2.idpedido WHERE x2.id = '" . $Nc . "';";
						$ResultChaves = mysql_query($QueryChaves);

						$aChaves = array();

						while($RowChaves = mysql_fetch_assoc($ResultChaves)) {
							
							if(!in_array($RowChaves['chave_nf'], $aChaves)) {

								$xmlChaves .= '<infNFe><chave>' . trim($RowChaves['chave_nf']) . '</chave></infNFe>';
								array_push($aChaves, $RowChaves['chave_nf']);

							}
							
						}

						$qCarga[0] = sprintf("%2.4f", $qCarga[0]);
						$qCarga[1] = $qCarga[0];
						$qCarga[2] = $qCarga[0];
							
						$XmlBody .= '<infCTeNorm><infCarga><vCarga>' . trim($vCarga) . '</vCarga><proPred>' . trim($proPred) . '</proPred><xOutCat>' . trim(limpaChars($xOutCat)) . '</xOutCat><infQ><cUnid>' . $cUnid[0] . '</cUnid><tpMed>' . $tpMed[0] . '</tpMed><qCarga>' . $qCarga[0] . '</qCarga></infQ><infQ><cUnid>0' . $cUnid[1] . '</cUnid><tpMed>' . $tpMed[1] . '</tpMed><qCarga>' . $qCarga[1] . '</qCarga></infQ><infQ><cUnid>' . $cUnid[2] . '</cUnid><tpMed>' . $tpMed[2] . '</tpMed><qCarga>' . $qCarga[2] . '</qCarga></infQ></infCarga><infDoc>';
							
						$XmlBody .= $xmlChaves;

						$XmlBody .= '</infDoc><infModal versaoModal="' . trim($versaoModal) . '"><rodo><RNTRC>' . trim($RNTRC) . '</RNTRC></rodo></infModal></infCTeNorm>';

					}

				}

				$fretePeso = $vPeso * $qCarga[0];
				$XmlBody = str_replace('********************', number_format($fretePeso, 2, '.', ''), $XmlBody);

				$peso = sprintf("%2.3f", $qCarga[0]);
				$valornf = $vCarga;
				
	##### FIM CTE NORMAL.
				##### INICIO CTE COMPLEMENTAR.

				if ($tpCTe == '1') {

					$BuscaComp = "SELECT * FROM tb_emicte WHERE id_pedido = '". $Nc ."' AND tipocte = 1 AND status = 'autorizado';";
					$ResComp = mysql_query($BuscaComp);
					$RowComp = mysql_fetch_assoc($ResComp);

					if(mysql_num_rows($ResComp) > 1){

						$cont++;
						continue;						
					}

	// ALERTA!!! VERIFICANDO SE O CTE COMPLEMENTAR FOI EMITIDO A MENOS DE CINCO MINUTOS!!! COMENTAR BLOCO EM CASO DE ERROS!

					$buscaComplementar = "SELECT
					COUNT(*) as complementares
					FROM tb_emicte
					WHERE tipocte = 1 AND
					idusuario = '".$id_usuario."' AND
					valor = ".$vTPrest." AND data_emis
					BETWEEN DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND
					NOW()
					AND idgrupo = '".$Nc."';";
					$resultCteComplementar = mysql_query($buscaComplementar);
					if(!$resultCteComplementar){

						echo mysql_error();

					} else {
						
						$rowresultCteComplementar = mysql_fetch_assoc($resultCteComplementar);
						if($rowresultCteComplementar['complementares'] > 0){
							$cont++;
							continue;
						}

					}

	// FIM DO ALERTA!!!

					$ChaveCteComp = (isset($_REQUEST['chavectecomp'])) ? $_REQUEST['chavectecomp'] : $RowEmissao['ChaveCte'];
					$XmlBody .= '<infCteComp><chCTe>' . $ChaveCteComp . '</chCTe></infCteComp>';

				}

	##### FIM CTE COMPLEMENTAR
				# fim infCte

				$XmlBody .= '</infCte>';

				if ($RowEmissor['id']=='12' or $RowEmissor['id']=='2'){ //DEFINE URL DA EMISSÃO

					$XmlBody .= '<infCTeSupl><qrCodCTe><![CDATA[https://nfe.fazenda.sp.gov.br/CTeConsulta/qrCode?chCTe=' .  $Chave . '&tpAmb=1]]></qrCodCTe></infCTeSupl>';

				} else {
					
					$XmlBody .= '<infCTeSupl><qrCodCTe><![CDATA[http://dfe-portal.svrs.rs.gov.br/cte/QRCode?chCTe=' .  $Chave . '&tpAmb=1]]></qrCodCTe></infCTeSupl>';

				}

	# Assinatura

				$DigestValue = '';
				$SignatureValue = '';
				$X509Certificate = '';

				$XmlBody .= '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><Reference URI="#' . trim($Id) . '"><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><DigestValue>' . trim($DigestValue) . '</DigestValue></Reference></SignedInfo><SignatureValue>' . trim($SignatureValue) . '</SignatureValue><KeyInfo><X509Data><X509Certificate>' . trim($X509Certificate) . '</X509Certificate></X509Data></KeyInfo></Signature>';

	# fim CTe

				$XmlBody .= '</CTe>';

	# fim cteProc
				// $XmlBody.= '</cteProc>';

				$Emis = date("Y-m-d H:i:s");
				$Valor = $vBC;
				$Aliquota = $pICMS;
				$VlIcms = $vICMS;
				$cfop = $CFOP;
				$Uf = $UFFim;
				$idUsuario = (isset($id_usuario)) ? $id_usuario : 'Servidor';

				$QueryVerificaChave = "SELECT chavecte FROM tb_emicte WHERE chavecte = '" . $Chave . "' AND data_emis >= '".$dataCorteCte."';";
				$ResultVerificaChave = mysql_query($QueryVerificaChave);
				if (mysql_num_rows($ResultVerificaChave) == 0) {
	#### ASSINA O XML ANTES DE GRAVAR NO BANCO DE DADOS ####

					$XmlTempFile = '/tmp/Cte' . $Chave . 'Temp.xml';
					file_put_contents($XmlTempFile, $XmlBody);

					if ($RowEmissor['id'] == '2' || $RowEmissor['id'] == '9') {

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

					if($RowEmissor['id'] != '7' && $RowEmissor['id'] != '12' && $RowEmissor['id'] != '2') {

						$ArquivoXMLremLFCR = str_replace($DropAssinatura, '', $ArquivoXMLremLFCR);

					}
					
					$XmlBody = $ArquivoXMLremLFCR;

					file_put_contents($XmlTempFile, $XmlBody);

		#### ####
					if($tpCTe == '1') {

						$Validacao = '1;XML obedece as regras.';

					} else {

						// $Validacao = validaCte($XmlTempFile);
						$Validacao = '1;XML obedece as regras.';

					}

					$Validacao = explode(';', $Validacao);

					if ($Validacao[0] == 1) {

						if($_REQUEST['icms'] == 'S') {
						
							$Valor = 0;

						}

						$corte_transfarma = date('2017-12-31 23:59:59');

						$Simpress = array('07432517001766','07432517000360','07432517001251','07432517001847','07432517001170','07432517000794');

						if(!in_array($CNPJ, $Simpress)){

							if(strtotime($Emis) > strtotime($corte_transfarma) && $RowEmissor['id'] == '6'){

								$status = 'presoretorno';
							}else{
								$status = 'assinado';
							}
							
						}else{

							$status = 'assinado';
						}
					
						$QueryInsert = "UPDATE tb_emicte SET idgrupo = '" . $Nc . "', status = '". $status ."', data_emis = '" . $Emis . "', chavecte = '" . $Chave . "', cfop = '" . $cfop . "', uf = '" . $Uf . "', valor = '" . $Valor . "', txicms = '" . $Aliquota . "', vlicms = '" . $VlIcms . "', idemissor = '" . $RowEmissor['id'] . "', tipocte = '" . $tpCTe . "', idusuario = '" . $idUsuario . "', prioridade = '" . $prioridade . "', xml = '" . $XmlBody . "', tpamb = '" . $tpAmb . "', peso = '" . $peso . "', valornf = '" . $valornf . "' WHERE id_cte = '" . $id_cte . "';";
						$ResultInsert = mysql_query($QueryInsert);
						if ($ResultInsert) {

							if ($tpCTe != '1') {

								$QueryPedidos = "SELECT idpedido FROM tb_emicte_grupos WHERE id = '" . $Nc . "';";
								$ResultPedidos = mysql_query($QueryPedidos);

								while($RowPedidos = mysql_fetch_assoc($ResultPedidos)) {

									$QueryUpdatePedido = "UPDATE pedidos SET NumCte = '$Seq', EmisCte = '$Emis', ChaveCte = '$Chave' WHERE id = '" . $RowPedidos['idpedido'] . "' AND dataentrega >= '" . $dataEntregaCorte . "'";
									$ResultUpdatePedido = mysql_query($QueryUpdatePedido);

								}

							}

							$CtesEnviados++;
							$inChaves .= "'" . $Chave . "',";
						
						}

					} else {

						if($_REQUEST['icms'] == 'S') {
						
							$Valor = 0;

						}

						$QueryInsert = "UPDATE tb_emicte SET idgrupo = '" . $Nc . "', observacao = \"" . $Validacao[1] . "\", status = 'rejeitado', data_emis = '" . $Emis . "', chavecte = '" . $Chave . "', cfop = '" . $cfop . "', uf = '" . $Uf . "', valor = '" . $Valor . "', txicms = '" . $Aliquota . "', vlicms = '" . $VlIcms . "', idemissor = '" . $RowEmissor['id'] . "', tipocte = '" . $tpCTe . "', idusuario = '" . $idUsuario . "', prioridade = '" . $prioridade . "', xml = '" . $XmlBody . "', tpamb = '" . $tpAmb . "', peso = '" . $peso . "', valornf = '" . $valornf . "' WHERE id_cte = '" . $id_cte . "';";
						mysql_query($QueryInsert);

					}
				
				}

	####### FIM - GERA O CTE.
			} else {

				$naoEmitidos .= "Viagem " . $Nc . " - CTE nao emitido! Frete esta zerado!\n";
			
			}

		} else {

			$naoEmitidos .= "Viagem ". $Nc . " - CTE nao emitido! Nao ha XML de entrada para esta nota.\n";
			echo $QueryEmissao . mysql_error();
		}
		$cont++;

	}

	// mysql_query("UPDATE tb_emicte SET status = 'assinado' WHERE chavecte IN (" . substr($inChaves, 0, -1) . ")");

	echo "\n\n" . $CtesEnviados . " CTe's enviados para a fila de emissao!\n";
	echo "\n\n" . $naoEmitidos;

} catch (Exception $e) {

    mysql_query("UNLOCK TABLES");
    mysql_query("DELETE FROM tb_emicte WHERE id_cte = '" . $id_cte . "';");
    echo 'Exceção capturada: ',  $e->getMessage(), "\n";

}
