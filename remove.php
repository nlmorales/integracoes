<?php
include "connect.php";

$arr = array('13704993','13704994','13704996','13704995','13704997','13704998','13704999','13705000','13705001','13705002','13705003','13705004','13705005','13705006','13705007','13705008','13705009','13705010','13705011','13705012','13705013','13705014','13705015','13705016','13705017','13705018','13705019','13705020','13705021','13705022','13705023','13705024','13705025','13705026','13705027','13705028','13705029','13705030','13705031','13705032','13705033','13705034','13705035','13705036','13705037','13705038','13705039','13705040','13705041','13705042','13705043','13705044','13705045','13705046','13705047','13705048','13705049','13705050','13705051','13705052','13705053','13705054','13705055','13705056','13705057','13705058','13705059','13705060','13705061','13705062','13705063','13705064','13705065','13705066','13705067','13705068','13705069','13705070','13705071','13705072','13705073','13705074','13705075','13705076','13705077','13705078','13705079','13705080','13705081','13705082','13705083','13705084','13705085','13705086','13705087','13705088','13705089','13705090','13705091','13705092','13705093','13705094','13705095','13705096','13705097','13705098','13705099','13705100','13705101','13705102','13705103','13705104','13705105','13705106','13705577');

$qnt = 0;
foreach ($arr as $idcarga) {
    
    $remove ='{
        "content": {
          "idCargaPk": '.$idcarga.'
        },
        "token": "ycMxoayDaMmA4Ii7hSDxD7U1G9sIeuiXq8MdN6igS%2FGP%2BrzC760bqDVHT5CMZxUt1dRTLrkDWuNUOvfTAe0rTb3sVK64%2BBQncBgHqQ1lv0rxxEwwQuEVOhJNlHCpRyRT"
      }';

    $ch = curl_init('https://www.itrackbrasil.com.br/ws/User/Carga/Remover'); 
    // $ch = curl_init('https://beta.martinlabs.com.br/iTrackServer-1.0-SNAPSHOT/ws/User/Carga/Remover');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $remove);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $runRemove = curl_exec($ch);
    if (curl_error($runRemove)) {
        echo curl_error($runRemove);
    }
    $debug = json_decode($runRemove, true);
    $retornoEnvio = $debug["success"];
    //echo $remove.'<br>';
    if($retornoEnvio==true){
        $qnt++;
    }
    
}
echo 'removidos: '.$qnt;
?>