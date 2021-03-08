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
    if(isset($_GET['datum']) && isset($_GET['razlogDolaska']) 
        && isset($_GET['mkbSifraPrimarna']) && isset($_GET['id']) && isset($_GET['vrijeme']) 
        && isset($_GET['tipSlucaj'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $id = (int)$_GET['id'];
        //Uzmi vrijednost datuma i pretvori u format datuma iz baze
        $datum = date("Y-m-d",strtotime($_GET['datum']));
        //Dekodiram razlog dolaska
        $razlogDolaska = urldecode($_GET['razlogDolaska']);
        //Uzmi vrijednost razloga dolaska
        $razlogDolaska = mysqli_real_escape_string($conn, trim($razlogDolaska));
        //Uzmi vrijednost šifre primarne dijagnoze
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($_GET['mkbSifraPrimarna']));
        $vrijeme = mysqli_real_escape_string($conn, trim($_GET['vrijeme']));
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiPovijestBolestiPovezanSlucaj($datum,$razlogDolaska, 
                                                                $mkbSifraPrimarna,$vrijeme,$tipSlucaj,$id);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>