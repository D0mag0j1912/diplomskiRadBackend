<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new ObradaService();

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

        $visina = mysqli_real_escape_string($conn, trim($request->visina));
        $tezina = mysqli_real_escape_string($conn, trim($request->tezina));
        $bmi = mysqli_real_escape_string($conn, trim($request->bmi));
        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $idPacijent = (int)$idPacijent;
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;

        $response = $servis->spremiBMI($visina,$tezina,$bmi,$idPacijent,$idObrada);

        echo json_encode($response);
    }
} 
?>