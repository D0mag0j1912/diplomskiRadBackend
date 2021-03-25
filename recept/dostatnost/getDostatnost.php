<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

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
    if(isset($_GET['lijek']) && isset($_GET['kolicina']) 
        && isset($_GET['doza']) && isset($_GET['brojPonavljanja'])){
        //Dekodiram lijek, količinu, dozu i broj ponavljanja
        $lijek = urldecode($_GET['lijek']);
        $kolicina = urldecode($_GET['kolicina']);
        $doza = urldecode($_GET['doza']);
        $brojPonavljanja = urldecode($_GET['brojPonavljanja']);
        //Dohvaćam uneseni lijek
        $lijek = mysqli_real_escape_string($conn, trim($lijek));
        //Dohvaćam unesenu količinu
        $kolicina = mysqli_real_escape_string($conn, trim($kolicina));
        $kolicina = (int)$kolicina;
        //Dohvaćam unesenu dozu
        $doza = mysqli_real_escape_string($conn, trim($doza));
        //Dohvaćam uneseni broj ponavljanja
        $brojPonavljanja = mysqli_real_escape_string($conn, trim($brojPonavljanja));
        $brojPonavljanja = (int)$brojPonavljanja;
        //Punim polje sa odgovorom funkcije
        $response = $servis->izracunajDostatnost($lijek,$kolicina,$doza,$brojPonavljanja);
        //Vraćam response frontendu
        echo json_encode($response);
    }
}
?>