<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new PovijestBolestiService();
//Dohvaćam servis prethodnih pregleda radi funkcije za ID => MBO
$servisPrethodniPregled = new PreglediService();
//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];
    if(isset($_GET['idPacijent']) && isset($_GET['idObrada']) && isset($_GET['mkbSifraPrimarna'])){
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$idObrada;
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($_GET['mkbSifraPrimarna']));
        
        $response = $servis->getIDPovijestBolesti($servisPrethodniPregled->getMBO($idPacijent),$idObrada,$mkbSifraPrimarna);

        echo json_encode($response);
    }
}
?>