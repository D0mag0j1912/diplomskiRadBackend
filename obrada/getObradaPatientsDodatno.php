<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new MedSestraService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();
//Ako je zahtjev frontenda uključivao metodu POST
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        $ime = mysqli_real_escape_string($conn, trim($request->ime));
        $prezime = mysqli_real_escape_string($conn, trim($request->prezime));

        $response = $servis->pacijentiDodatno($ime,$prezime);

        echo json_encode($response);
    }
} 
?>