<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader3.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new PreglediDetailService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslani parametri
    if(isset($_GET['id']) && isset($_GET['tipKorisnik'])){
        $id = mysqli_real_escape_string($conn, trim($_GET['id']));
        $id = (int)$id;
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiCijeliPregled($tipKorisnik,$id);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>