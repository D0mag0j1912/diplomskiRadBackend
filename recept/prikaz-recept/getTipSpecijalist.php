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

    //Ako je frontend poslao šifru specijalista 
    if(isset($_GET['sifraSpecijalist'])){
        //Uzmi vrijednost šifre specijalista 
        $sifraSpecijalist = mysqli_real_escape_string($conn, trim($_GET['sifraSpecijalist']));
        //Punim polje odgovorom funkcije
        $response = $servis->dohvatiTipSpecijalist($sifraSpecijalist);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>