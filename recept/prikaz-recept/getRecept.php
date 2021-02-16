<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptHandlerService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Deklariram prazno polje
    $response = [];

    //Ako je frontend poslao tip prijavljenog korisnika i statuse 
    if(isset($_GET['ids'])){
        

        $response = $servis->dohvatiPacijentPoIDu($ids);

        echo json_encode($response);
    }
}
?>