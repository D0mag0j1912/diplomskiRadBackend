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

$primarnaDijagnoza = 'Paratifus A [A01.1]';
preg_match("/\[([^\]]*)\]/", $primarnaDijagnoza, $matches);
$mkbSifraPrimarna = $matches[1];
echo $mkbSifraPrimarna;
?>