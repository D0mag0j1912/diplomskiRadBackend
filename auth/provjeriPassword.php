<?php

//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Importam login servis da mogu pristupiti metodama servisa
$servis = new LoginService();

//Importam bazu da mogu pristupiti konekciji
$baza = new Baza();

//Spremam konekciju u $conn
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "POST":
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Kreiram prazno polje
    $response = [];
    //Ako je frontend poslao email logina
    if(isset($_GET['email']) && isset($_GET['lozinka'])){
        $email = mysqli_real_escape_string($conn, trim($_GET['email']));
        $lozinka = mysqli_real_escape_string($conn, trim($_GET['lozinka']));
        //U polje dohvaćam povratnu vrijednost funkcije prijavaKorisnik()
        $response = $servis->dohvatiLozinku($email,$lozinka);

        //Vrati nazad frontendu
        echo json_encode($response);
    }
}
?>