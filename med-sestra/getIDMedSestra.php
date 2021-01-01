<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new MedSestraService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    $response = $servis->getIDMedSestra();

    echo json_encode($response);
}
?>