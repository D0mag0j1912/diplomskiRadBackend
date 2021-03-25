<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new PreglediDetailService();
$servisPrethodni = new PreglediService();
//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslani parametri
    if(isset($_GET['datum']) && isset($_GET['vrijeme']) && isset($_GET['mkbSifraPrimarna']) 
    && isset($_GET['tipSlucaj']) && isset($_GET['idPacijent'])){
        $datum = mysqli_real_escape_string($conn, trim($_GET['datum']));
        $vrijeme = mysqli_real_escape_string($conn, trim($_GET['vrijeme']));
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($_GET['mkbSifraPrimarna']));
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiSekundarneDijagnoze($datum,$vrijeme,$mkbSifraPrimarna,$tipSlucaj,$servisPrethodni->getMBO($idPacijent),$tipKorisnik);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>