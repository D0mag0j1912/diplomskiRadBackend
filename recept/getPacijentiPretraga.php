<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je frontend poslao vrijednost pretrage
    if(isset($_GET['pretraga'])){
        //Dohvaćam tip prijavljenog korisnika
        $pretraga = mysqli_real_escape_string($conn, trim($_GET['pretraga']));
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiPacijentiPretraga($pretraga);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>