<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new CekaonicaService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Deklariram prazno polje
    $response = [];

    //Ako je frontend poslao tip prijavljenog korisnika i statuse 
    if(isset($_GET['tip']) && isset($_GET['statusi'])){
        //Dohvaćam tip prijavljenog korisnika
        $tip = mysqli_real_escape_string($conn, trim($_GET['tip']));
        //Dohvaćam polje statusa
        $statusi = json_decode($_GET['statusi']);

        $response = $servis->dohvatiPacijentaPoStatusu($tip,$statusi);

        echo json_encode($response);
    }
}
?>