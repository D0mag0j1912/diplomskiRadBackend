<?php
include('../backend-path.php');
//Importam potrebne klase pomoću autoloadera
require_once BASE_PATH.'\includes\autoloader2.inc.php';

//Dohvaćam liječnički servis
$servis = new LijecnikService();

//Kreiram objekt tipa "Baza"
$baza = new Baza();

//Konekciju na bazu spremam u varijablu
$conn = $baza->spojiSBazom();

//Ako je zahtjev sa frontenda uključivao metodu "PUT":
if($_SERVER['REQUEST_METHOD'] === 'PUT'){
    //Dohvaćam podatke koje je poslao frontend
	$putdata = file_get_contents("php://input");
	//Ako je frontend vratio nešto
	if(isset($putdata) && !empty($putdata)){
		//Pretvaram podatke iz JSON formata u format polja
		$request = json_decode($putdata);

		//Deklariram prazno polje
        $response = [];

        $id = mysqli_real_escape_string($conn, trim($request->id));
        $trenutna = mysqli_real_escape_string($conn, trim($request->trenutna));
        $nova = mysqli_real_escape_string($conn, trim($request->nova));
        $potvrdaNova = mysqli_real_escape_string($conn, trim($request->potvrdaNova));

        //Ako ova metoda ne vraća null, odgovor metode spremam u polje odgovora (ako vraća null to znači da nema errora)
        if($servis->provjeraLozinka($id,$trenutna) != null){
            $response = $servis->provjeraLozinka($id,$trenutna);
        }
        //Ako metoda ne vraća null, odgovor metode spremam u polje odgovora (ako vraća null to znači da nema errora)
        else if($servis->jednakeLozinke($nova,$potvrdaNova) != null){
            $response = $servis->jednakeLozinke($nova,$potvrdaNova);
        }
        else{
            $response = $servis->azurirajLozinka($id,$nova);
        }

        //Vraćam odgovor frontendu
        echo json_encode($response);
    }    
}
?>