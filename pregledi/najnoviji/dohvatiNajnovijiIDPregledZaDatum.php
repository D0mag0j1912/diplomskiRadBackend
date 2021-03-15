<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

//Dohvaćam servis liste prethodnih pregleda
$servis = new PreglediListService();
//Dohvaćam servis prethodnih pregleda
$servisPrethodni = new PreglediService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako su s frontenda poslani parametri
    if(isset($_GET['tipKorisnik']) && isset($_GET['idPacijent']) && isset($_GET['datum'])){
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        $datum = mysqli_real_escape_string($conn, trim($_GET['datum']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiNajnovijiIDPregledPoDatumu($tipKorisnik,$servisPrethodni->getMBO($idPacijent),$datum);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>