<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new CijeneHandler();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je request metoda koja je pozvala ovu skriptu "GET":
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);
        
        //Dohvaćam ID obrade
        $idObrada = mysqli_real_escape_string($conn, trim($request->idObrada));
        $idObrada = (int)$idObrada;
        //Ako nova cijena nije null 
        if($request->novaCijena != null){
            //Dohvaćam novu cijenu
            $novaCijena = mysqli_real_escape_string($conn, trim($request->novaCijena));
            $novaCijena = (float)$novaCijena;
        }
        //Dohvaćam tipa korisnika
        $tipKorisnik = mysqli_real_escape_string($conn, trim($request->tipKorisnik));
        //Primam sve usluge sa frontenda
        $usluge = $request->usluge;

        $response = $servis->azurirajUkupnuCijenuPregleda(
                $idObrada, 
                $request->novaCijena != null ? $novaCijena : $request->novaCijena, 
                $tipKorisnik,
                $usluge->idRecept,
                $usluge->idUputnica,
                $usluge->idBMI
        ); 
        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>