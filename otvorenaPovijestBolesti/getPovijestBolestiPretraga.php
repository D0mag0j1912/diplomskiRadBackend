<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';
include('../getMBO.php');
//Dohvaćam servis povezane povijesti bolesti
$servis = new PovezanaPovijestBolestiService();
//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta
    if(isset($_GET['id']) && isset($_GET['pretraga'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $id = (int)$_GET['id'];
        //Dekodiram parametar pretrage
        $pretraga = urldecode($_GET['pretraga']);
        //Uzmi vrijednost pretrage
        $pretraga = mysqli_real_escape_string($conn, trim($pretraga));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiPovijestBolestiPretraga(getMBO($id),$pretraga);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>