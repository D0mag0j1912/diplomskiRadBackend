<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
//Dohvaćam liječnički servis
$servis = new SharedService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Punim polje sa odgovorom funkcije
    $response = $servis->dohvatiSlucajniIDObrada();
    //Vraćam response frontendu
    echo json_encode($response);
}
?>