<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    //Ako su s frontenda poslani parametri
    if(isset($_GET['datum'])){
        //Uzmi vrijednost datuma pregleda 
        $datumPregled = date('Y-m-d', strtotime($_GET["datum"]));
        //Vraćam frontendu rezultat
        echo json_encode($datumPregled);
    }
}
?>