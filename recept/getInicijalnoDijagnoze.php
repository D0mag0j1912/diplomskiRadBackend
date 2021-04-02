<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
include('../getMBO.php');
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
    if(isset($_GET['idPacijent']) && isset($_GET['idObrada'])){
        //Dohvaćam ID pacijenta
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        //Dohvaćam ID idObrade
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$idObrada;
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiInicijalneDijagnoze(getMBO($idPacijent),$idObrada);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>