<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader2.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new OtvoreniSlucajService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta te tip prijavljenog korisnika
    if(isset($_GET['id'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $id = (int)$_GET['id'];
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiSveOtvoreneSlucajeve($id);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>