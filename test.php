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


$primarnaDijagnoza = "Enteritis uzrokovan adenovirusima | A08.2";

$lastSpace = strrpos($primarnaDijagnoza," ");
echo trim(substr($primarnaDijagnoza,$lastSpace,strlen($primarnaDijagnoza)));
?>