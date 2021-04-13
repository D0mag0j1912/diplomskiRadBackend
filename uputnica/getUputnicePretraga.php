<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new UputnicaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];
    //Ako je frontend poslao parametar pretrage
    if(isset($_GET['pretraga'])){
        //Dekodiram ga
        $pretraga = urldecode($_GET['pretraga']);
        //Dohvaćam pretragu
        $pretraga = mysqli_real_escape_string($conn, trim($pretraga));
        $response = $servis->dohvatiSveUputnicePretraga($pretraga);
        echo json_encode($response);
    }
}
?>