<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

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

    //Ako je frontend poslao tip prijavljenog korisnika
    if(isset($_GET['tip'])){
        //Dohvaćam tip prijavljenog korisnika
        $tip = mysqli_real_escape_string($conn, trim($_GET['tip']));
        $response = $servis->dohvati10zadnjih($tip);

        echo json_encode($response);
    }
}
?>