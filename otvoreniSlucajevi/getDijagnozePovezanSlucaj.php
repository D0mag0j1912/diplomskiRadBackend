<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam servis otvorenog slučaja
$servis = new OtvoreniSlucajService();
$servisPrethodniPregled = new PreglediService();
//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];

    //Ako je s frontenda poslan parametar ID pacijenta i šifra primarne dijagnoze
    if(isset($_GET['idPacijent']) && isset($_GET['mkbSifra']) && 
    isset($_GET['datumPregled']) && isset($_GET['vrijemePregled']) && 
    isset($_GET['tipSlucaj'])){
        //Uzmi tu vrijednosti ID-a i pretvori je u INTEGER
        $idPacijent = (int)$_GET['idPacijent'];
        //Uzmi vrijednost šifre primarne dijagnoze
        $mkbSifra = $_GET['mkbSifra'];
        //Uzmi vrijednost datuma pregleda 
        $datumPregled = date('Y-m-d', strtotime($_GET["datumPregled"]));
        //Uzmi vrijednost vremena
        $vrijemePregled = mysqli_real_escape_string($conn, trim($_GET['vrijemePregled']));
        //Uzmi vrijednost tipa slučaja
        $tipSlucaj = mysqli_real_escape_string($conn, trim($_GET['tipSlucaj']));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->dohvatiDijagnozePovezanSlucaj($mkbSifra, $servisPrethodniPregled->getMBO($idPacijent), 
                                                        $datumPregled,$vrijemePregled,$tipSlucaj);
        //Vraćam frontendu rezultat
        echo json_encode($response);
    }
}
?>