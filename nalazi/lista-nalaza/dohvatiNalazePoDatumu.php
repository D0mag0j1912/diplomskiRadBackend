<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam servis liste prethodnih pregleda
$servis = new NalaziListService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako su s frontenda poslani parametri
    if(isset($_GET['datum']) && isset($_GET['idPacijent'])){
        $datum = date('Y-m-d', strtotime($_GET["datum"]));
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiNalazePoDatumu($datum,$idPacijent);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>