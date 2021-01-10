<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new CekaonicaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je frontend poslao ID obrade
    if(isset($_GET['idObrada'])){
        //Dohvati ID obrade
        $idObrada = (int)$_GET['idObrada'];
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiPovijestBolesti($idObrada);
        echo json_encode($response);
    }
}
?>