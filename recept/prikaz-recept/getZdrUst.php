<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new PrikazReceptService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Deklariram prazno polje
    $response = [];

    //Punim polje odgovorom funkcije
    $response = $servis->dohvatiZdrUst();
    //Vraćam odgovor frontendu
    echo json_encode($response);
}
?>