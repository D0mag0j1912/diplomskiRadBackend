<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new PovijestBolestiService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je metoda koja je poslala zahtjev backendu POST:
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        $idLijecnik = mysqli_real_escape_string($conn, trim($request->idLijecnik));
        $idLijecnik = (int)$idLijecnik;
        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $idPacijent = (int)$idPacijent;
        $razlogDolaska = mysqli_real_escape_string($conn, trim($request->razlogDolaska));
        $anamneza = mysqli_real_escape_string($conn, trim($request->anamneza));
        $status = mysqli_real_escape_string($conn, trim($request->status));
        $nalaz = mysqli_real_escape_string($conn, trim($request->nalaz));
        $primarnaDijagnoza = mysqli_real_escape_string($conn, trim($request->primarnaDijagnoza));
        $sekundarneDijagnoze = $request->sekundarnaDijagnoza;
        $tipSlucaj = mysqli_real_escape_string($conn, trim($request->tipSlucaj));
        $terapija = mysqli_real_escape_string($conn, trim($request->terapija));
        $preporukaLijecnik = mysqli_real_escape_string($conn, trim($request->preporukaLijecnik));
        $napomena = mysqli_real_escape_string($conn, trim($request->napomena));
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;

        $response = $servis->potvrdiPovijestBolesti($idLijecnik,$idPacijent,$razlogDolaska,$anamneza,$status,
                                                    $nalaz,$primarnaDijagnoza,$sekundarneDijagnoze,$tipSlucaj,
                                                    $terapija,$preporukaLijecnik,$napomena,$idObrada);
        //Vrati odgovor frontendu
        echo json_encode($response);
    }
}
?>