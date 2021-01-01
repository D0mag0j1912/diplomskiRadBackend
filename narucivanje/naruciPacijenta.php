<?php
//Importam potrebne klase pomoću autoloadera
require_once 'C:\wamp64\www\angularPHP\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new NarucivanjeService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je metoda koja je poslala zahtjev backendu PUT:
if ($_SERVER["REQUEST_METHOD"] === "POST"){
    //Dohvaćam podatke koje je poslao frontend
	$postdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($postdata) && !empty($postdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($postdata);

		//Deklariram prazno polje
        $response = [];

        $vrijeme = mysqli_real_escape_string($conn, trim($request->vrijeme));
        $vrstaPregleda = mysqli_real_escape_string($conn, trim($request->vrstaPregleda));
        $datum = mysqli_real_escape_string($conn, trim($request->datum));
        $pacijent = mysqli_real_escape_string($conn, trim($request->pacijent));
        $napomena = mysqli_real_escape_string($conn, trim($request->napomena));

        //Razdvajanje stringa pacijenta na posebne varijable
        $polje = explode(" ",$pacijent);
        [$ime,$prezime,$mbo] = $polje;

        $response = $servis->naruciPacijenta($vrijeme,$vrstaPregleda,$datum,$ime,$prezime,$mbo,$napomena);

        //Vraćam odgovor frontendu
        echo json_encode($response);
    }
}
?>