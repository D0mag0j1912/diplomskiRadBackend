<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new OtvoreniSlucajService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta i šifra primarne dijagnoze
    if(isset($_GET['id']) && isset($_GET['mkbSifra']) && isset($_GET['datumPregled']) && isset($_GET['odgovornaOsoba'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $id = (int)$_GET['id'];
        //Uzmi vrijednost šifre primarne dijagnoze
        $mkbSifra = $_GET['mkbSifra'];
        //Uzmi vrijednost datuma pregleda 
        $datumPregled = date('Y-m-d', strtotime($_GET["datumPregled"]));
        //Uzmi vrijednost odgovorne osobe
        $odgovornaOsoba = $_GET['odgovornaOsoba'];
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiDijagnozePovezanSlucaj($mkbSifra, $id,$datumPregled,$odgovornaOsoba);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>