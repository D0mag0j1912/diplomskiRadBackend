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

//Ako je metoda koja je poslala zahtjev PUT:
if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $ime = mysqli_real_escape_string($conn, trim($request->ime));
        $prezime = mysqli_real_escape_string($conn, trim($request->prezime));
        $datRod = mysqli_real_escape_string($conn, trim($request->datRod));
        $adresa = mysqli_real_escape_string($conn, trim($request->adresa));
        $oib = mysqli_real_escape_string($conn, trim($request->oib));
        $email = mysqli_real_escape_string($conn, trim($request->email));
        $spol = mysqli_real_escape_string($conn, trim($request->spol));
        $pbr = mysqli_real_escape_string($conn, trim($request->pbr));
        $pbr = (int)$pbr;
        $mobitel = mysqli_real_escape_string($conn, trim($request->mobitel));
        $bracnoStanje = mysqli_real_escape_string($conn, trim($request->bracnoStanje));
        $radniStatus = mysqli_real_escape_string($conn, trim($request->radniStatus));
        $status = mysqli_real_escape_string($conn, trim($request->status));

        $response = $servis->potvrdiOsnovnePodatke($idPacijent, $ime, $prezime,$datRod,$adresa,$oib,$email,$spol,
                                                    $pbr,$mobitel,$bracnoStanje,$radniStatus,$status);

        echo json_encode($response);
    }
}
?>