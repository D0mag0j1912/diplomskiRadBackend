<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new CekaonicaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je frontend poslao ID obrade
    if(isset($_GET['idObrada']) && isset($_GET['tip'])){
        //Dohvati ID obrade
        $idObrada = (int)$_GET['idObrada'];
        //Dohvaćam tip prijavljenog korisnika
        $tip = mysqli_real_escape_string($conn, trim($_GET['tip']));
        //Ako je tip korisnika "lijecnik":
        if($tip == "lijecnik"){
            //Punim polje sa odgovorom funkcije
            $response = $servis->dohvatiPovijestBolesti($idObrada);
        }
        //Ako je tip korisnika medicinska sestra:
        else if($tip == "sestra"){
            //Punim polje sa odgovorom funkcije
            $response = $servis->dohvatiOpcePodatke($idObrada);
        }
        echo json_encode($response);
    }
}
?>