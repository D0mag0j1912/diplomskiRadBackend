<?php
include('./backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader.inc.php';
include('./getMBO.php');
//Kreiram objekt tipa "Baza"
$baza = new Baza();
//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

$servis = new SharedService();

//Ako je frontend poslao zahtjev
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    //Ako je frontend poslao ID pacijenta
    if(isset($_GET['idPacijent'])){
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        $response = $servis->getDopunsko(getMBO($idPacijent));
        echo json_encode($response);
    }
}

?>