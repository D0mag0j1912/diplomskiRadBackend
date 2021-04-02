<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';
//Importam getMBO()
include('../../getMBO.php');
//Dohvaćam liječnički servis
$servis = new IzdajUputnica();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako su sa frontenda poslani nužni parametri
    if(isset($_GET['idObrada']) && isset($_GET['mboPacijent'])){
        //Dohvaćam ID obrade
        $idObrada = mysqli_real_escape_string($conn, trim($_GET['idObrada']));
        $idObrada = (int)$idObrada;
        //Dohvaćam ID pacijenta
        $mboPacijent = mysqli_real_escape_string($conn, trim($_GET['mboPacijent']));
        //Punim polje sa odgovorom funkcije
        $response = $servis->isUnesenaPovijestBolesti($idObrada,$mboPacijent);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>