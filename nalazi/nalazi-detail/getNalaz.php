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
    if(isset($_GET['idNalaz'])){
        $idNalaz = mysqli_real_escape_string($conn, trim($_GET['idNalaz']));
        $idNalaz = (int)$idNalaz;
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiNalaz($idNalaz);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>