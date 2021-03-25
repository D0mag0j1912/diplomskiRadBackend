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
        $idPacijent = (int)$idPacijent;
        $mbo = mysqli_real_escape_string($conn, trim($request->mbo));
        $nositeljOsiguranja = mysqli_real_escape_string($conn, trim($request->nositeljOsiguranja));
        $drzavaOsiguranja = mysqli_real_escape_string($conn, trim($request->drzavaOsiguranja));
        $kategorijaOsiguranja = mysqli_real_escape_string($conn, trim($request->kategorijaOsiguranja));
        $trajnoOsnovno = mysqli_real_escape_string($conn, trim($request->trajnoOsnovno));
        $osnovnoOsiguranjeOd = mysqli_real_escape_string($conn, trim($request->osnovnoOsiguranjeOd));
        $osnovnoOsiguranjeDo = mysqli_real_escape_string($conn, trim($request->osnovnoOsiguranjeDo));
        $brIskDopunsko = mysqli_real_escape_string($conn, trim($request->brIskDopunsko));
        $dopunskoOsiguranjeOd = mysqli_real_escape_string($conn, trim($request->dopunskoOsiguranjeOd));
        $dopunskoOsiguranjeDo = mysqli_real_escape_string($conn, trim($request->dopunskoOsiguranjeDo));
        $oslobodenParticipacije = mysqli_real_escape_string($conn, trim($request->oslobodenParticipacije));
        $clanakParticipacija = mysqli_real_escape_string($conn, trim($request->clanakParticipacija));
        $trajnoParticipacija = mysqli_real_escape_string($conn, trim($request->trajnoParticipacija));
        $participacijaDo = mysqli_real_escape_string($conn, trim($request->participacijaDo));
        $sifUred = mysqli_real_escape_string($conn, trim($request->sifUred));
        $sifUred = (int)$sifUred;

        $response = $servis->potvrdiZdravstvenePodatke($idPacijent,$mbo,$nositeljOsiguranja,$drzavaOsiguranja,$kategorijaOsiguranja,
                                                $trajnoOsnovno,$osnovnoOsiguranjeOd,$osnovnoOsiguranjeDo,$brIskDopunsko,
                                                $dopunskoOsiguranjeOd,$dopunskoOsiguranjeDo,$oslobodenParticipacije,
                                                $clanakParticipacija,$trajnoParticipacija,$participacijaDo,$sifUred);

        echo json_encode($response);
    }
}
?>