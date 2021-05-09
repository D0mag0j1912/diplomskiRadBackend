<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Kreiram objekt tipa "Baza"
$baza = new Baza();
//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();
$servis = new Uzorci();

//Ako je frontend poslao GET zahtjev
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Ako je sa frontenda poslan ID aktivnog pacijenta
    if(isset($_GET['idPacijent'])){
        //Kreiram prazno polje
        $response = [];
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;

        $response = $servis->dohvatiUstanovaUzorci($idPacijent);
        
        echo json_encode($response);
    }
}
?>