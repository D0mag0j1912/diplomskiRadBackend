<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

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
    //Ako je sa frontenda poslan izabrani MAGISTRALNI PRIPRAVAK
    if(isset($_GET['magPripravak'])){
        $magPripravak = urldecode($_GET['magPripravak']);
        //Dohvaćam vrijednost izabranog magistralnog pripravka
        $magPripravak = mysqli_real_escape_string($conn, trim($magPripravak));
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiOznakaMagPripravak($magPripravak);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>