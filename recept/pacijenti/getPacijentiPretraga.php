<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptHandlerService();

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
        //Dohvaćam vrijednost pretrage
        $pretraga = mysqli_real_escape_string($conn, trim($_GET['pretraga']));
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiPacijentiPretraga($pretraga);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>