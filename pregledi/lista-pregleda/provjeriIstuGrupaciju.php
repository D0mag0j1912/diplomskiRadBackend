<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new PreglediListService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako su s frontenda poslani parametri
    if(isset($_GET['ids']) && isset($_GET['tipKorisnik'])){
        //Dohvaćam ID-ove koje sam poslao sa frontenda
        $ids = json_decode($_GET['ids']);
        $tipKorisnik = mysqli_real_escape_string($conn, trim($_GET['tipKorisnik']));
        //Prolazim kroz te ID-ove
        foreach($ids as $idPregled){
            $idPregled = mysqli_real_escape_string($conn, $idPregled);
            $idPregled = (int)$idPregled;
        }
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->provjeriIstuGrupaciju($tipKorisnik,$ids);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>