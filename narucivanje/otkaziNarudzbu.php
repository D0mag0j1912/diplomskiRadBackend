<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new NarucivanjeService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar vremena
    if(isset($_GET['idNarudzba'])){

        $idNarudzba = (int)$_GET['idNarudzba'];
        //Popuni polje sa podatcima
        $response = $servis->otkaziNarudzbu($idNarudzba);
        //Vrati odgovor
        echo json_encode($response);   
    }
}
?>