<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je frontend poslao GET zahtjev
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];
    $eritrociti = mt_rand (4.34*10, 5.72*10) / 10;
    $response['eritrociti'] = $eritrociti;
    $hemoglobin = mt_rand (138, 175);
    $response['hemoglobin'] = $hemoglobin;
    $hematokrit = mt_rand (0.415*1000, 0.530*1000) / 1000;
    $response['hematokrit'] = $hematokrit;
    $mcv = mt_rand (83.0*10, 97.2*10) / 10;
    $response['mcv'] = $mcv;
    $mch = mt_rand (27.4*10, 33.9*10) / 10;
    $response['mch'] = $mch;
    $mchc = mt_rand (320, 345);
    $response['mchc'] = $mchc;
    $rdw = mt_rand (9.0, 15.0);
    $response['rdw'] = $rdw;
    $leukociti = mt_rand (3.4*10, 9.7*10) / 10;
    $response['leukociti'] = $leukociti;
    $trombociti = mt_rand (158, 424);
    $response['trombociti'] = $trombociti;
    $mpv = mt_rand (6.8*10, 10.4*10) / 10;
    $response['mpv'] = $mpv;  
    $trombokrit = mt_rand (0.150*1000, 0.320*1000) / 1000;
    $response['trombokrit'] = $trombokrit;  
    $pdw = mt_rand (16, 25);
    $response['pdw'] = $pdw;  
    $neutrofilniGranulociti = mt_rand (2.06*100, 6.49*100) / 100;
    $response['neutrofilniGranulociti'] = $neutrofilniGranulociti;  
    $monociti = mt_rand (0.12*100, 0.84*100) / 100;
    $response['monociti'] = $monociti;  
    $limfociti = mt_rand (1.19*100, 3.35*100) / 100;
    $response['limfociti'] = $limfociti;  
    $eozinofilniGranulociti = mt_rand (0.00*100, 0.43*100) / 100;
    $response['eozinofilniGranulociti'] = $eozinofilniGranulociti;  
    $bazofilniGranulociti = mt_rand (0.00*100, 0.06*100) / 100;
    $response['bazofilniGranulociti'] = $bazofilniGranulociti;  
    $retikulociti = mt_rand (22, 97);
    $response['retikulociti'] = $retikulociti;
    echo json_encode($response);
}
?>