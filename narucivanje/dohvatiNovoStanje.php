<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new NarucivanjeService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar vremena
    if(isset($_GET['datum'])){

        $datum = date('Y-m-d', strtotime($_GET["datum"]));
        //Popuni polje sa podatcima
        $response = $servis->dohvatiNovoStanje($datum);
        //Vrati odgovor
        echo json_encode($response);   
    }
}
?>