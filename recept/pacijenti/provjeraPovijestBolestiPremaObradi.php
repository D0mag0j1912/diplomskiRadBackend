<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

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

    //Ako je frontend poslao vrijednost ID-a obrade i ID-a pacijenta
    if(isset($_GET['idObrada']) && isset($_GET['idPacijent'])){
        //Dohvaćam vrijednost ID-a obrade
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$idObrada;
        //Dohvaćam vrijednost ID-a pacijenta
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        //Punim polje sa odgovorom funkcije
        $response = $servis->provjeraPovijestBolestiPremaObradi($idObrada,$idPacijent);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>