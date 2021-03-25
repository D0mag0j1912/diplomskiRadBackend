<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

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
        $mkbPrimarnaDijagnoza = mysqli_real_escape_string($conn, trim($request->mkbPrimarnaDijagnoza));
        $mkbSifre = $request->mkbSifre;
        foreach($mkbSifre as $mkb){
            $mkb = mysqli_real_escape_string($conn, trim($mkb));
        }
        $tipSlucaj = mysqli_real_escape_string($conn, trim($request->tipSlucaj));
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;
        $prosliPregled = mysqli_real_escape_string($conn, trim($request->prosliPregled));
        $prosliPregled = (int)$prosliPregled;
        $proslaBoja = mysqli_real_escape_string($conn, trim($request->proslaBoja));
        $response = $servis->dodajOpcePodatkePregleda($idMedSestra,$idPacijent,$nacinPlacanja, $podrucniUredHZZO, $podrucniUredOzljeda, $nazivPoduzeca,
                                                    $oznakaOsiguranika, $nazivDrzave, $mbo, $brIskDopunsko, $mkbPrimarnaDijagnoza,
                                                    $mkbSifre, $tipSlucaj,$idObrada,$prosliPregled,$proslaBoja);

        echo json_encode($response);
    }
} 
?>