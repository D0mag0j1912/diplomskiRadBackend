<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new OpciPodatciService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();
//Ako je zahtjev frontenda uključivao metodu POST:
if($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = []; 

        $idMedSestra = mysqli_real_escape_string($conn, trim($request->idMedSestra));
        $idMedSestra = (int)$idMedSestra;
        $idPacijent = mysqli_real_escape_string($conn, trim($request->idPacijent));
        $idPacijent = (int)$idPacijent;
        $nacinPlacanja = mysqli_real_escape_string($conn, trim($request->nacinPlacanja));
        $podrucniUredHZZO = mysqli_real_escape_string($conn, trim($request->podrucniUredHZZO));
        $podrucniUredOzljeda = mysqli_real_escape_string($conn, trim($request->podrucniUredOzljeda));
        $nazivPoduzeca = mysqli_real_escape_string($conn, trim($request->nazivPoduzeca));
        $oznakaOsiguranika = mysqli_real_escape_string($conn, trim($request->oznakaOsiguranika));
        $nazivDrzave = mysqli_real_escape_string($conn, trim($request->nazivDrzave));
        $mbo = mysqli_real_escape_string($conn, trim($request->mbo));
        $brIskDopunsko = mysqli_real_escape_string($conn, trim($request->brIskDopunsko));
        $primarnaDijagnoza = mysqli_real_escape_string($conn, trim($request->primarnaDijagnoza));
        $sekundarneDijagnoze = $request->sekundarnaDijagnoza;
        $tipSlucaj = mysqli_real_escape_string($conn, trim($request->tipSlucaj));
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;

        $response = $servis->dodajOpcePodatkePregleda($idMedSestra,$idPacijent,$nacinPlacanja, $podrucniUredHZZO, $podrucniUredOzljeda, $nazivPoduzeca,
                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $primarnaDijagnoza,
                                                    $sekundarneDijagnoze, $tipSlucaj,$idObrada);

        echo json_encode($response);
    }
} 
?>