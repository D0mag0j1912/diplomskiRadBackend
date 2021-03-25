<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

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
        && isset($_GET['mkbSifraPrimarna']) && isset($_GET['tipSlucaj']) && isset($_GET['vrijeme']) 
        && isset($_GET['idPacijent'])){
        //Uzmi vrijednost datuma i pretvori u format datuma iz baze
        $datum = date("Y-m-d",strtotime($_GET['datum']));
        $razlogDolaska = urldecode($_GET['razlogDolaska']);
        $razlogDolaska = mysqli_real_escape_string($conn, trim($razlogDolaska));
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($_GET['mkbSifraPrimarna']));
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        $vrijeme = mysqli_real_escape_string($conn, trim($_GET['vrijeme']));
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;

        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiSekundarneDijagnoze($datum,$razlogDolaska,$mkbSifraPrimarna, 
                                                        $tipSlucaj,$vrijeme,$idPacijent);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>