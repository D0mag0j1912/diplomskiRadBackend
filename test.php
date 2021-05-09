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

$zdrUstanove = [19601964,25002503,21902194,47104716,19701977,47204729,49604961,356235629,359135919,999000420,358435846,389738972,258825880];
echo $zdrUstanove[array_rand($zdrUstanove,1)];
?>