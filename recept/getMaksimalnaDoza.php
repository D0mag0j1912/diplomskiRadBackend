<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

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
    if(isset($_GET['lijek']) && isset($_GET['doza'])){
        //Dekodiram lijek, količinu, dozu i broj ponavljanja
        $lijek = urldecode($_GET['lijek']);
        $doza = urldecode($_GET['doza']);
        //Dohvaćam uneseni lijek
        $lijek = mysqli_real_escape_string($conn, trim($lijek));
        //Dohvaćam unesenu dozu
        $doza = mysqli_real_escape_string($conn, trim($doza));
        //Punim polje sa odgovorom funkcije
        $response = $servis->izracunajMaksimalnuDozu($lijek,$doza);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>