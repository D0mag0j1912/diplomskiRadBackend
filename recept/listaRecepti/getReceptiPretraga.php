<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptHandlerService();

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
        //Dekodiram parametar
        $pretraga = urldecode($_GET['pretraga']);
        //Trimam ga
        $pretraga = mysqli_real_escape_string($conn, trim($pretraga));
        //Punim polje odgovorom funkcije
        $response = $servis->dohvatiReceptePretraga($pretraga);
        //Vrati odgovor frontendu
        echo json_encode($response);
    }
}
?>