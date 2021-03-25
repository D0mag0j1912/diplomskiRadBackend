<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new PreglediService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslani parametri
    if(isset($_GET['idPacijent']) && isset($_GET['tipKorisnik'])){
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiNajnovijiDatum($tipKorisnik,$servis->getMBO($idPacijent));
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>