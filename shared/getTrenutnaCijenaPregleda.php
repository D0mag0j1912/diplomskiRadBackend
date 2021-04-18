<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
include('../getMBO.php');
//Dohvaćam liječnički servis
$servis = new CijeneHandler();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    if(isset($_GET['idObrada']) && isset($_GET['tipKorisnik'])){
        //Dohvaćam ID obrade
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$idObrada;
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiTrenutnaCijenaPregleda($idObrada, $tipKorisnik);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>