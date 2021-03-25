<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new PrikazReceptService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Deklariram prazno polje
    $response = [];

    //Ako je frontend poslao podatke šifara sekundarnih dijagnoza 
    if(isset($_GET['mkbSifre'])){
        //Uzmi vrijednost mkb šifre (ovo je string sa spojenim sekundarnim dijagnozama)
        $mkbSifra = mysqli_real_escape_string($conn, trim($_GET['mkbSifre']));
        //Svaku pojedinu sekundarnu dijagnozu iz stringa odvoji kao jedan element polja
        $polje = explode(" ",$mkbSifra);
        //Punim polje odgovorom funkcije
        $response = $servis->dohvatiSekundarneDijagnoza($polje);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>