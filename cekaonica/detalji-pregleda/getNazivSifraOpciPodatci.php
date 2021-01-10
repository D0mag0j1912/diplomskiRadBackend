<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

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

    //Ako je frontend poslao ID čekaonice
    if(isset($_GET['mkbSifraSekundarna'])){
        //Dohvati spojene šifre sekundarnih dijagnoza
        $mkbSifraSekundarna = $_GET['mkbSifraSekundarna'];
        //Svaku pojedinu sekundarnu dijagnozu iz stringa odvoji kao jedan element polja
        $polje = explode(" ",$mkbSifraSekundarna);
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiNazivSifraOpciPodatci($polje);
        //Vrati odgovor frontendu
        echo json_encode($response);
    }
}
?>