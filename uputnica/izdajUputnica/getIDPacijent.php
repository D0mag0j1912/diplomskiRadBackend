<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';
include('../../getIDPacijent.php');
//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Ako su sa frontenda poslani nužni parametri
    if(isset($_GET['mboPacijent'])){
        //Dohvaćam ID pacijenta
        $mboPacijent = mysqli_real_escape_string($conn, trim($_GET['mboPacijent']));
        //Punim polje sa odgovorom funkcije
        $response = getIDPacijent($mboPacijent);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>