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

$primarnaDijagnoza = 'A00 | Kolera';
//Dohvaćam poziciju zadnjeg space-a
$firstSpace = strpos($primarnaDijagnoza," ");
//Uzmi vrijednost šifre primarne dijagnoze
$mkbSifraPrimarna = trim(substr($primarnaDijagnoza,0,$firstSpace));
echo $mkbSifraPrimarna;
?>