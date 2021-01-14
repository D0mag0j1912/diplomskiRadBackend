<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new ObradaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Kreiram prazno polje
    $response = [];

    //Ako je frontend poslao ID trenutno aktivnog pacijenta
    if(isset($_GET['idPacijent'])){
        //Dohvaćam ID trenutno aktivnog pacijenta
        $idPacijent = (int)$_GET['idPacijent'];
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiObradenPovijestBolesti($idPacijent);

        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>