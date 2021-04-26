<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Kreiram objekt tipa "Baza"
$baza = new Baza();
//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();
$servis = new Uzorci();

//Ako je frontend poslao GET zahtjev
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Ako je sa frontenda poslan ID uputnice
    if(isset($_GET['idUputnica'])){
        //Kreiram prazno polje
        $response = [];
        $idUputnica = mysqli_real_escape_string($conn, trim($_GET['idUputnica']));
        $idUputnica = (int)$idUputnica;

        $response = $servis->dohvatiPodatciUputnica($idUputnica);
        
        echo json_encode($response);
    }
}
?>