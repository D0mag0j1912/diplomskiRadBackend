<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
include('../getMBO.php');
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
    if(isset($_GET['datum']) 
        && isset($_GET['vrijeme']) 
        && isset($_GET['tipSlucaj']) 
        && isset($_GET['primarnaDijagnoza']) 
        && isset($_GET['idPacijent'])){
        
        //Uzmi vrijednost datuma 
        $datum = date('Y-m-d', strtotime($_GET["datum"]));
        //Uzmi vrijednost vremena
        $vrijeme = mysqli_real_escape_string($conn, trim($_GET['vrijeme']));
        //Uzmi vrijednost tipa slučaja
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        //Uzmi vrijednost primarne dijagnoze
        $primarnaDijagnoza = mysqli_real_escape_string($conn, trim($_GET['primarnaDijagnoza']));
        //Dohvaćam poziciju zadnjeg space-a
        $lastSpace = strrpos($primarnaDijagnoza," ");
        //Uzmi vrijednost šifre primarne dijagnoze
        $mkbSifraPrimarna = trim(substr($primarnaDijagnoza,$lastSpace,strlen($primarnaDijagnoza)));
        //Uzmi vrijednost ID-a pacijenta
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiSekundarneDijagnoze($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,getMBO($idPacijent));
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>