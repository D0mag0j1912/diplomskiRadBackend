<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader3.inc.php';

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
    //Ako je sa frontenda poslan parametar pretrage
    if(isset($_GET['lijek'])){
        $lijek = urldecode($_GET['lijek']);
        //Dohvaćam vrijednost izabranog lijeka
        $lijek = mysqli_real_escape_string($conn, trim($lijek));
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiCijenaLijekDL($lijek);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>