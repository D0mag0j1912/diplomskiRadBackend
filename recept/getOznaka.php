<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //Kreiram prazno polje
    $response = [];
    //Ako je sa frontenda poslan parametar pretrage
    if(isset($_GET['lijek'])){
        $lijek = urldecode($_GET['lijek']);
        //Dohvaćam vrijednost izabranog lijeka
        $lijek = mysqli_real_escape_string($conn, trim($lijek));
        //Splitam string da mu uzmem ime i oblik-jačinu-pakiranje
        $polje = explode(" ",$lijek,2);
        //Dohvaćam ime lijeka
        $imeLijek = $polje[0];
        //Dohvaćam oblik,jačinu i pakiranje lijeka
        $ojpLijek = $polje[1];
        //Punim polje sa odgovorom funkcije
        $response = $servis->dohvatiOznakaLijek($imeLijek,$ojpLijek,$lijek);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>