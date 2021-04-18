<?php
include('../../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader3.inc.php';

//Dohvaćam liječnički servis
$servis = new CijeneHandler();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);
        
        //Dohvaćam ID obrade
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;
        //Dohvaćam cijenu doplate na lijek/mag.pripravak
        $doplata = mysqli_real_escape_string($conn, trim($request->doplata));
        $doplata = (float)$doplata;

        $response = $servis->spremiDoplatu($idObrada, $doplata);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>