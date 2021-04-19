<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new CekaonicaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je frontend poslao ID obrade
    if(isset($_GET['tipKorisnik']) && isset($_GET['idObrada'])){
        //Dohvaćam tip korisnika koji je obradio pacijenta čije usluge pokušavam dohvatiti
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Dohvati ID obrade
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$_GET['idObrada'];
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiNaplaceneUsluge($tipKorisnik,$idObrada);
        echo json_encode($response);
    }
}
?>