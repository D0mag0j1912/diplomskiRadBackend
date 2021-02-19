<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new ReceptHandlerService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako zahtjev frontenda uključuje metodu GET:
if($_SERVER["REQUEST_METHOD"] === "GET"){
    //Deklariram prazno polje
    $response = [];

    //Ako je frontend poslao podatke kliknutog recepta 
    if(isset($_GET['dostatnost']) && isset($_GET['datumRecept']) 
        && isset($_GET['idPacijent']) && isset($_GET['mkbSifraPrimarna']) 
        && isset($_GET['proizvod']) && isset($_GET['vrijemeRecept'])){
        //Dohvaćam dostatnost 
        $dostatnost = mysqli_real_escape_string($conn, trim($_GET['dostatnost']));
        $dostatnost = (int)$dostatnost;
        //Dohvaćam datum recepta
        $datumRecept = mysqli_real_escape_string($conn, trim($_GET['datumRecept'])); 
        //Dohvaćam ID pacijenta 
        $idPacijent = mysqli_real_escape_string($conn, trim($_GET['idPacijent']));
        $idPacijent = (int)$idPacijent;
        //Dohvaćam šifru primarne dijagnoze
        $mkbSifraPrimarna = mysqli_real_escape_string($conn, trim($_GET['mkbSifraPrimarna'])); 
        //Dohvaćam proizvod
        $proizvod = urldecode($_GET['proizvod']);
        $proizvod = mysqli_real_escape_string($conn, trim($proizvod)); 
        //Dohvaćam vrijeme recepta
        $vrijemeRecept = mysqli_real_escape_string($conn, trim($_GET['vrijemeRecept']));
        //Punim polje odgovorom funkcije
        $response = $servis->dohvatiRecept($dostatnost,$datumRecept, 
                                        $idPacijent,$mkbSifraPrimarna, 
                                        $proizvod,$vrijemeRecept);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>