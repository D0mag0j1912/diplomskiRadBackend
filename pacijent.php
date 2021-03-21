<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\diplomskiBackend\includes\autoloader.inc.php';

//Dohvaćam liječnički servis
$servis = new LijecnikService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

if($_SERVER['REQUEST_METHOD'] === "DELETE"){
    //Dohvaćam podatke koje je poslao frontend
	$deletedata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($deletedata) && !empty($deletedata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($deletedata);
        //Kreiram prazno polje
        $response = [];

        //Uzmi tu vrijednost ID-a i pretvori je u INTEGER
        $id = mysqli_real_escape_string($conn, trim($request->id));
        //Punim polje sa vrijednostima polja iz funkcije
        $response = $servis->obrisiPacijenta($id);
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>