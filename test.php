<?php
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

$servis = new OpciPodatciService();
//Kreiram prazno polje
$response = [];
//Kreiram objekt tipa "Baza"
$baza = new Baza();
$conn = $baza->spojiSBazom();
//Postavljam vremensku zonu
date_default_timezone_set('Europe/Zagreb');

$polje = ['#FAEBD7','#7FFFD4','#F0FFFF','#F5F5DC','#FFE4C4','#5F9EA0','#DEB887','#D2691E','#008B8B'];
echo $polje[array_rand($polje)];
?>