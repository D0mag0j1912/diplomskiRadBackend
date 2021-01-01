<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader.inc.php';

//Dohvaćam liječnički servis
$servis = new PosjetService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();


//Ako zahtjev frontenda uključuje metodu POST:
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam pojedine atribute koje je frontend poslao
        $datumPosjet = mysqli_real_escape_string($conn, trim($request->datumPosjet));
        //$dijagnoza = mysqli_real_escape_string($conn, trim($request->dijagnoza));
        $razlog = mysqli_real_escape_string($conn, trim($request->razlog));
        $anamneza = mysqli_real_escape_string($conn, trim($request->anamneza));
        $status = mysqli_real_escape_string($conn, trim($request->status));
        $preporuka = mysqli_real_escape_string($conn, trim($request->preporuka));

        $response = $servis->dodajPosjet($datumPosjet,$dijagnoza,$razlog,$anamneza,$status,$preporuka);

        echo json_encode($response);
    }
}
?>