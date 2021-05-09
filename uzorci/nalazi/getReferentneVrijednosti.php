<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Kreiram objekt tipa "Baza"
$baza = new Baza();
//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();
$servis = new ImportService();

//Ako je frontend poslao GET zahtjev
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    $response = $servis->dohvatiReferentneVrijednosti();
    
    echo json_encode($response);
}
?>