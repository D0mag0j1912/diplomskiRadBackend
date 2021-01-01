<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam servis povezane povijesti bolesti
$servis = new PovezanaPovijestBolestiService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta
    if(isset($_GET['mkbSifra'])){
        //Uzmi vrijednost mkb šifre (ovo je string sa spojenim sekundarnim dijagnozama)
        $mkbSifra = $_GET['mkbSifra'];
        //Svaku pojedinu sekundarnu dijagnozu iz stringa odvoji kao jedan element polja
        $polje = explode(" ",$mkbSifra);
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiNazivSekundarna($polje);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>