<?php
include('./backend-path.php');
require_once BASE_PATH.'\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

$response = [];
$eritrociti = mt_rand (4.34*10, 5.72*10) / 10;
$response['eritrociti'] = $eritrociti." *10^12/L";
$hemoglobin = mt_rand (138*10, 175*10) / 10;
$response['hemoglobin'] = $hemoglobin." g/L";
$hematokrit = mt_rand (0.415*10, 0.530*10) / 10;
$response['hematokrit'] = $hematokrit." L/L";
$mcv = mt_rand (83.0*10, 97.2*10) / 10;
$response['mcv'] = $mcv." fL";
$mch = mt_rand (27.4*10, 33.9*10) / 10;
$response['mch'] = $mch." pg";
$mchc = mt_rand (320*10, 345*10) / 10;
$response['mchc'] = $mchc." g/L";
$rdw = mt_rand (9.0*10, 15.0*10) / 10;
$response['rdw'] = $rdw." %";
$leukociti = mt_rand (3.4*10, 9.7*10) / 10;
$response['leukociti'] = $leukociti." *10^9/L";
$trombociti = mt_rand (158*10, 424*10) / 10;
$response['trombociti'] = $trombociti." *10^9/L";
$mpv = mt_rand (6.8*10, 10.4*10) / 10;
$response['mpv'] = $mpv." fL";  
$trombokrit = mt_rand (0.150*10, 0.320*10) / 10;
$response['trombokrit'] = $trombokrit." %";  
$pdw = mt_rand (16*10, 25*10) / 10;
$response['pdw'] = $pdw;  
$neutrofilniGranulociti = mt_rand (6.8*10, 10.4*10) / 10;
$response['neutrofilniGranulociti'] = $neutrofilniGranulociti." *10^9/L";  
$monociti = mt_rand (0.12*10, 0.84*10) / 10;
$response['monociti'] = $monociti." *10^9/L";  
$limfociti = mt_rand (1.19*10, 3.35*10) / 10;
$response['limfociti'] = $limfociti." *10^9/L";  
$eozinofilniGranulociti = mt_rand (0.00*10, 0.43*10) / 100;
$response['eozinofilniGranulociti'] = $eozinofilniGranulociti." *10^9/L";  
$bazofilniGranulociti = mt_rand (0.00*100, 0.06*100) / 100;
$response['bazofilniGranulociti'] = $bazofilniGranulociti." *10^9/L";  
$retikulociti = mt_rand (22*10, 97*10) / 10;
$response['retikulociti'] = $retikulociti." *10^9/L";
foreach($response as $key=>$value){
    echo($key.":".$value."\n");
}
?>