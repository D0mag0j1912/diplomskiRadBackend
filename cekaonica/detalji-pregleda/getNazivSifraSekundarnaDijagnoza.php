<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

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
    if(isset($_GET['datum']) && 
        isset($_GET['vrijeme']) && 
        isset($_GET['tipSlucaj']) && 
        isset($_GET['primarnaDijagnoza']) && 
        isset($_GET['idObrada']) && 
        isset($_GET['tip'])){
        //Uzmi vrijednost datuma pregleda 
        $datum = date('Y-m-d', strtotime($_GET["datum"]));
        //Dohvaćam vrijeme
        $vrijeme = mysqli_real_escape_string($conn, trim($_GET['vrijeme']));
        //Dohvaćam tip slučaja
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        //Dohvaćam MKB šifru primarne dijagnoze
        $primarnaDijagnoza = mysqli_real_escape_string($conn, trim($_GET['primarnaDijagnoza']));
        //Dohvaćam poziciju zadnjeg space-a
        $firstSpace = strpos($primarnaDijagnoza," ");
        //Uzmi vrijednost šifre primarne dijagnoze
        $mkbSifraPrimarna = trim(substr($primarnaDijagnoza,0,$firstSpace));
        //Dohvati ID obrade
        $idObrada = $_GET['idObrada'];
        $idObrada = (int)$idObrada;
        //Dohvaćam tip prijavljenog korisnika
        $tip = mysqli_real_escape_string($conn, trim($_GET['tip']));
        //Ako je tip korisnika "lijecnik":
        if($tip == "lijecnik"){
            //Punim polje sa odgovorom funkcije
            $response = $servis->dohvatiNazivSifraPovijestBolesti($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,$idObrada);
        }
        //Ako je tip korisnika "medicinska sestra":
        else if($tip == "sestra"){
            //Punim polje sa odgovorom funkcije
            $response = $servis->dohvatiNazivSifraOpciPodatci($datum,$vrijeme,$tipSlucaj,$mkbSifraPrimarna,$idObrada);
        }
        //Vrati odgovor frontendu
        echo json_encode($response);
    }
}
?>