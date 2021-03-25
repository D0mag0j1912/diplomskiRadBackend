<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new MedSestraService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je zahtjev s frontenda uključivao metodu "PUT:
if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        //Dohvaćam pojedine atribute prijave iz polja
        $id = mysqli_real_escape_string($conn, trim($request->id));
        $email = mysqli_real_escape_string($conn, trim($request->email));
        $ime = mysqli_real_escape_string($conn, trim($request->ime));
        $prezime = mysqli_real_escape_string($conn, trim($request->prezime));
        $adresa = mysqli_real_escape_string($conn, trim($request->adresa));
        $specijalizacija = mysqli_real_escape_string($conn, trim($request->specijalizacija));

        //Punim polje sa odgovorom funkcije 
        $response = $servis->azurirajOsobnePodatke($id,$email,$ime,$prezime,$adresa,$specijalizacija);

        //Vraćam odgovor frontendu
        echo json_encode($response);
    }   
}
?>